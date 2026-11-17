<?php

use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\PropertyModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Libraries\PropertyReviewFixtures;

/**
 * Tests for the owner's own deactivation (HU-32): cancelling the request of a pending or rejected property, or taking
 * an approved one down. It is the logical deactivation of HU-09, so a property with a contract in force is refused.
 *
 * @internal
 */
final class PropertyOwnerDeactivationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PropertyReviewFixtures;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION              = [];
        $this->fixtureSequence = 0;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Cancelling the request of a property that was never approved
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function notApprovedProvider(): array
    {
        return ['pending' => ['pending'], 'rejected' => ['rejected']];
    }

    #[DataProvider('notApprovedProvider')]
    public function testTheOwnerCancelsTheRequestOfAPendingOrRejectedProperty(string $approval): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makePropertyIn($approval, $ownerId, $adminId, ['address' => 'Casa A Cancelar 1']);

        // First the server asks for confirmation and deactivates nothing.
        $confirm = $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '0']);
        $confirm->assertOK();
        $confirm->assertSee('¿Cancelás la solicitud de alta?');
        $confirm->assertSee('Casa A Cancelar 1');
        $confirm->assertSee('Sí, cancelar solicitud');
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);

        // Then, once confirmed, it is deactivated with the owner as the one responsible.
        $response = $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1']);

        $response->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_REQUEST_CANCELED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertNotNull($row['deleted_at']);
        $this->assertSame($ownerId, (int) $row['deleted_by']);
        $this->assertSame($approval, $row['approval_status'], 'The review state is kept for the record.');

        // It is gone from the owner's list and from the administration's queue and counter.
        $this->withSession($session)->get('propietario/inmuebles')->assertDontSee('Casa A Cancelar 1');
        $this->assertSame(0, model(PropertyModel::class)->countPendingApproval());
        $queue = $this->withSession($this->sessionOf($adminId, 'admin'))->get('admin/inmuebles?approval=' . $approval);
        $queue->assertOK();
        $queue->assertDontSee('Casa A Cancelar 1');
    }

    public function testAnAdministratorCannotResolveACancelledRequest(): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $propertyId = $this->makePendingProperty($ownerId);

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1']);

        $this->postWithSession($this->sessionOf($adminId, 'admin'), "admin/inmuebles/{$propertyId}/aprobacion")
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_DEACTIVATED_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Taking an approved property down
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function approvedStatusProvider(): array
    {
        return ['available' => ['available'], 'maintenance' => ['maintenance']];
    }

    #[DataProvider('approvedStatusProvider')]
    public function testTheOwnerTakesAnApprovedPropertyDownAndItStopsBeingPublished(string $status): void
    {
        $ownerId    = $this->makeUser('owner');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makeProperty($ownerId, ['address' => 'Casa Aprobada 3', 'status' => $status]);
        $this->makeProperty($ownerId, ['address' => 'Casa Que Sigue 4']);

        if ($status === 'available') {
            $this->get('catalogo-inmuebles')->assertSee('Casa Aprobada 3');
        }

        $confirm = $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '0']);
        $confirm->assertOK();
        $confirm->assertSee('¿Das de baja este inmueble?');
        $confirm->assertSee('Confirmar baja');
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);

        $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1'])
            ->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_PROPERTY_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertNotNull($row['deleted_at']);
        $this->assertSame($ownerId, (int) $row['deleted_by']);
        $this->assertSame('approved', $row['approval_status']);

        $this->get('catalogo-inmuebles')->assertDontSee('Casa Aprobada 3');
        $this->assertSame(['Casa Que Sigue 4'], array_column(model(PropertyModel::class)->findAvailableForContract(), 'address'));
        $this->assertSame(1, model(PropertyModel::class)->getStatusCounts()['total']);
        $this->assertSame(1, model(PropertyModel::class)->getStatusCounts()['inactive']);

        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertDontSee('Casa Aprobada 3');
        $list->assertSee('Casa Que Sigue 4');

        // The administration still finds it among the inactive ones, with who took it down.
        $inactive = $this->withSession($this->sessionOf($this->makeUser('admin'), 'admin'))->get('admin/inmuebles?status=inactive');
        $inactive->assertOK();
        $inactive->assertSee('Casa Aprobada 3');
    }

    // ---------------------------------------------------------------------------------------------
    // The HU-09 rule: not with a contract in force
    // ---------------------------------------------------------------------------------------------

    public function testAPropertyWithAContractInForceCannotBeTakenDown(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makeProperty($ownerId, ['address' => 'Casa Alquilada 5', 'status' => 'rented']);
        $this->insertContractInForce($propertyId, $tenantId, $ownerId);

        foreach (['0', '1'] as $confirmed) {
            $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => $confirmed])
                ->assertRedirectTo('/propietario/inmuebles');
            $this->assertSame(PropertyController::OWNER_CONTRACT_IN_FORCE_MESSAGE, session()->getFlashdata('error'));
        }

        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);

        // The list does not even offer the button.
        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertSee('Con contrato vigente: no se puede dar de baja');
        $list->assertDontSee("propietario/inmuebles/baja/{$propertyId}");
    }

    public function testAContractInForceBlocksEvenIfTheStatusSaysAvailable(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId); // inconsistent on purpose: available but with a contract in force
        $this->insertContractInForce($propertyId, $tenantId, $ownerId);

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1'])
            ->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_CONTRACT_IN_FORCE_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);

        // The atomic operation checks it again under the lock.
        $result = model(PropertyModel::class)->deactivate($propertyId, $ownerId, $ownerId);
        $this->assertSame(PropertyModel::ERROR_CONTRACT_IN_FORCE, $result['error']);
    }

    // ---------------------------------------------------------------------------------------------
    // The owner's list offers the right action for each state
    // ---------------------------------------------------------------------------------------------

    public function testTheListOffersCancelForPendingAndRejectedAndBajaForApproved(): void
    {
        $ownerId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');
        $pending  = $this->makePendingProperty($ownerId, ['address' => 'Casa En Revision 1']);
        $rejected = $this->makeRejectedProperty($ownerId, $adminId, 'Faltan datos.', ['address' => 'Casa Rechazada 2']);
        $approved = $this->makeProperty($ownerId, ['address' => 'Casa Aprobada 3']);

        $list = $this->withSession($this->sessionOf($ownerId, 'owner'))->get('propietario/inmuebles');

        $list->assertOK();
        $list->assertSee('Cancelar solicitud');
        $list->assertSee('Dar de baja');
        foreach ([$pending, $rejected, $approved] as $propertyId) {
            $list->assertSee("propietario/inmuebles/baja/{$propertyId}");
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Somebody else's property, missing, already deactivated, roles
    // ---------------------------------------------------------------------------------------------

    public function testAnotherOwnersPropertyAnswers404AndIsNotTouched(): void
    {
        $ownerId    = $this->makeUser('owner');
        $other      = $this->makeUser('owner');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makePendingProperty($other, ['address' => 'Casa Ajena 5']);

        foreach (['0', '1'] as $confirmed) {
            $this->assertAnswers404(
                fn () => $this->postWithSession($session, "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => $confirmed]),
                "confirmed={$confirmed}"
            );
        }

        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);
        $this->assertNull($this->propertyRow($propertyId)['deleted_by']);
    }

    public function testTheModelReportsAnotherOwnersPropertyAsNotFoundUnderTheLock(): void
    {
        $ownerId    = $this->makeUser('owner');
        $other      = $this->makeUser('owner');
        $propertyId = $this->makeProperty($other);

        $result = model(PropertyModel::class)->deactivate($propertyId, $ownerId, $ownerId);

        $this->assertSame(PropertyModel::ERROR_NOT_FOUND, $result['error']);
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);

        // Without the owner restriction it is the administration's HU-09 deactivation, as before.
        $this->assertTrue(model(PropertyModel::class)->deactivate($propertyId, $this->makeUser('admin'))['ok']);
    }

    public function testAMissingOrAlreadyDeactivatedPropertyAnswers404(): void
    {
        $ownerId     = $this->makeUser('owner');
        $session     = $this->sessionOf($ownerId, 'owner');
        $deactivated = $this->makePendingProperty($ownerId, ['deleted_at' => '2026-10-01 10:00:00']);

        $this->assertAnswers404(fn () => $this->postWithSession($session, 'propietario/inmuebles/baja/999999', ['confirmed' => '1']), 'missing');
        $this->assertAnswers404(
            fn () => $this->postWithSession($session, "propietario/inmuebles/baja/{$deactivated}", ['confirmed' => '1']),
            'already deactivated'
        );
    }

    public function testTheDeactivationRouteAcceptsPostOnly(): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makeProperty($ownerId);

        $this->assertAnswers404(
            fn () => $this->withSession($this->sessionOf($ownerId, 'owner'))->get("propietario/inmuebles/baja/{$propertyId}")
        );
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nonOwnerRoleProvider(): array
    {
        return [
            'admin'    => ['admin', '/admin/dashboard'],
            'employee' => ['employee', '/admin/dashboard'],
            'tenant'   => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('nonOwnerRoleProvider')]
    public function testOnlyOwnersCanUseTheOwnerDeactivationRoute(string $roleName, string $ownPanel): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makeProperty($ownerId);

        $this->postWithSession($this->sessionOf($this->makeUser($roleName), $roleName), "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1'])
            ->assertRedirectTo($ownPanel);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $propertyId = $this->makeProperty($this->makeUser('owner'));

        $this->postWithSession([], "propietario/inmuebles/baja/{$propertyId}", ['confirmed' => '1'])->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull($this->propertyRow($propertyId)['deleted_at']);
    }

    /**
     * Inserts a contract in force straight into the table, bypassing the application.
     */
    private function insertContractInForce(int $propertyId, int $tenantId, int $ownerId): void
    {
        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => 500000.00,
            'update_percentage'         => 10,
            'due_day'                   => 10,
            'commission_percentage'     => 8,
            'late_fee_daily_percentage' => 0.5,
            'status'                    => 'in_force',
            'created_at'                => '2026-01-01 10:00:00',
            'updated_at'                => '2026-01-01 10:00:00',
        ]);
    }
}
