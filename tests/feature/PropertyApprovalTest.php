<?php

use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\PropertyModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Libraries\PropertyReviewFixtures;

/**
 * Acceptance and security tests for HU-33: the administration approves or rejects the properties owners publish.
 *
 * @internal
 */
final class PropertyApprovalTest extends CIUnitTestCase
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
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: the queue of pending properties
    // ---------------------------------------------------------------------------------------------

    public function testScenario1TheListingFiltersThePendingPropertiesAndIdentifiesTheOwnerWhoPublishedThem(): void
    {
        $adminId = $this->makeUser('admin');
        $ownerId = $this->makeUser('owner', null, 'Marta', 'Quiroga');
        $this->makeProperty($ownerId, ['address' => 'Casa De La Agencia 1']);
        $this->makePendingProperty($ownerId, ['address' => 'Casa Pendiente 2']);
        $this->makeRejectedProperty($ownerId, $adminId, 'Faltan datos.', ['address' => 'Casa Rechazada 3']);

        $session = $this->sessionOf($adminId, 'admin');

        $pending = $this->withSession($session)->get('admin/inmuebles?approval=pending');
        $pending->assertOK();
        $pending->assertSee('Casa Pendiente 2');
        $pending->assertDontSee('Casa De La Agencia 1');
        $pending->assertDontSee('Casa Rechazada 3');
        $pending->assertSee('Pendiente de aprobación');
        $pending->assertSee('Quiroga');
        $pending->assertSee('Lo publicó el propietario');

        $rejected = $this->withSession($session)->get('admin/inmuebles?approval=rejected');
        $rejected->assertSee('Casa Rechazada 3');
        $rejected->assertSee('Faltan datos.');
        $rejected->assertDontSee('Casa Pendiente 2');

        $approved = $this->withSession($session)->get('admin/inmuebles?approval=approved');
        $approved->assertSee('Casa De La Agencia 1');
        $approved->assertDontSee('Casa Pendiente 2');
        $approved->assertDontSee('Casa Rechazada 3');

        // Without the filter the administration sees all of them.
        $all = $this->withSession($session)->get('admin/inmuebles');
        foreach (['Casa De La Agencia 1', 'Casa Pendiente 2', 'Casa Rechazada 3'] as $address) {
            $all->assertSee($address);
        }
        $all->assertSee('1 pendiente de aprobación');
    }

    public function testScenario1TheFilterOffersTheReviewOptionsAndRejectsInvalidValues(): void
    {
        $session = $this->sessionOf($this->makeUser('admin'), 'admin');

        $form = $this->withSession($session)->get('admin/inmuebles');
        $form->assertOK();
        $form->assertSee('Pendientes de aprobación');
        $form->assertSee('Aprobados');
        $form->assertSee('Rechazados');

        $invalid = $this->withSession($session)->get('admin/inmuebles?approval=cualquiera');
        $invalid->assertOK();
        $invalid->assertSee('Seleccioná una revisión válida.');
    }

    public function testScenario1TheMenuShowsHowManyPropertiesArePending(): void
    {
        $adminId = $this->makeUser('admin');
        $ownerId = $this->makeUser('owner');
        $session = $this->sessionOf($adminId, 'admin');

        // Nothing pending: no badge.
        $this->withSession($session)->get('admin/dashboard')->assertDontSee('pending-properties-badge');

        $this->makePendingProperty($ownerId);
        $this->makePendingProperty($ownerId);
        $this->makePendingProperty($ownerId, ['deleted_at' => '2026-10-01 10:00:00']); // deactivated: not in the queue
        $this->makeRejectedProperty($ownerId, $adminId);                                // already resolved
        $this->makeProperty($ownerId);                                                  // loaded by the administration

        $dashboard = $this->withSession($session)->get('admin/dashboard');
        $dashboard->assertSee('pending-properties-badge');
        $dashboard->assertSee('pendientes de aprobación');
        $this->assertSame(2, model(PropertyModel::class)->countPendingApproval());

        // The employee sees the same queue; owners and tenants never get the badge.
        $employee = $this->sessionOf($this->makeUser('employee'), 'employee');
        $this->withSession($employee)->get('admin/inmuebles')->assertSee('pending-properties-badge');
        $this->withSession($this->sessionOf($ownerId, 'owner'))->get('propietario/inmuebles')->assertDontSee('pending-properties-badge');
    }

    public function testThePaginationLinksKeepTheReviewFilter(): void
    {
        $ownerId = $this->makeUser('owner');
        for ($i = 0; $i < 3; $i++) {
            $this->makePendingProperty($ownerId);
        }

        $response = $this->withSession($this->sessionOf($this->makeUser('admin'), 'admin'))
            ->get('admin/inmuebles?approval=pending&per_page=2');

        $response->assertOK();
        $response->assertSee('approval=pending');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: approve
    // ---------------------------------------------------------------------------------------------

    public function testScenario2ApprovingPublishesThePropertyAndKeepsItAvailable(): void
    {
        $adminId    = $this->makeUser('admin');
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId, ['address' => 'Casa Pendiente 2']);
        Time::setTestNow('2026-11-17 10:30:00');

        // Hidden before the approval.
        $this->get('catalogo-inmuebles')->assertDontSee('Casa Pendiente 2');
        $this->assertSame([], model(PropertyModel::class)->findAvailableForContract());

        $response = $this->postWithSession($this->sessionOf($adminId, 'admin'), "admin/inmuebles/{$propertyId}/aprobacion");

        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_APPROVED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('approved', $row['approval_status']);
        $this->assertSame('available', $row['status'], 'Approving does not change the commercial status.');
        $this->assertSame($adminId, (int) $row['reviewed_by']);
        $this->assertSame('2026-11-17 10:30:00', $row['reviewed_at']);
        $this->assertNull($row['rejection_reason']);
        $this->assertSame($ownerId, (int) $row['submitted_by']);

        // Published in the catalog and selectable in a contract.
        $this->get('catalogo-inmuebles')->assertSee('Casa Pendiente 2');
        $this->assertSame([$propertyId], array_map(
            static fn (array $property): int => (int) $property['id'],
            model(PropertyModel::class)->findAvailableForContract()
        ));

        // The owner sees it approved, with its commercial status again.
        $list = $this->withSession($this->sessionOf($ownerId, 'owner'))->get('propietario/inmuebles');
        $list->assertSee('Casa Pendiente 2');
        $list->assertSee('Disponible');
        $list->assertDontSee('En revisión');

        // It counts in the occupancy indicators now.
        $counts = model(PropertyModel::class)->getStatusCounts();
        $this->assertSame(1, $counts['available']);
        $this->assertSame(0, $counts['pending_approval']);
    }

    public function testScenario2AfterApprovingTheReviewerKeepsWorkingThroughTheQueue(): void
    {
        $adminId = $this->makeUser('admin');
        $ownerId = $this->makeUser('owner');
        $first   = $this->makePendingProperty($ownerId);
        $second  = $this->makePendingProperty($ownerId);
        $session = $this->sessionOf($adminId, 'admin');

        $this->postWithSession($session, "admin/inmuebles/{$first}/aprobacion")
            ->assertRedirectTo('/admin/inmuebles?approval=pending');

        $this->postWithSession($session, "admin/inmuebles/{$second}/aprobacion")
            ->assertRedirectTo('/admin/inmuebles');
    }

    public function testTheEmployeeCanApproveToo(): void
    {
        $employeeId = $this->makeUser('employee');
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));

        $this->postWithSession($this->sessionOf($employeeId, 'employee'), "admin/inmuebles/{$propertyId}/aprobacion")
            ->assertRedirectTo('/admin/inmuebles');

        $row = $this->propertyRow($propertyId);
        $this->assertSame('approved', $row['approval_status']);
        $this->assertSame($employeeId, (int) $row['reviewed_by']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: reject with a reason
    // ---------------------------------------------------------------------------------------------

    public function testScenario3RejectingStoresTheReasonAndTheOwnerSeesItAndCanCorrectIt(): void
    {
        $adminId    = $this->makeUser('admin');
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId, ['address' => 'Casa Pendiente 2']);
        $session    = $this->sessionOf($adminId, 'admin');
        Time::setTestNow('2026-11-17 11:00:00');

        $response = $this->postWithSession($session, "admin/inmuebles/{$propertyId}/rechazo", [
            'rejection_reason' => '  Falta la dirección exacta del local.  ',
        ]);

        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_REJECTED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('rejected', $row['approval_status']);
        $this->assertSame('Falta la dirección exacta del local.', $row['rejection_reason'], 'The reason is stored trimmed.');
        $this->assertSame($adminId, (int) $row['reviewed_by']);
        $this->assertSame('2026-11-17 11:00:00', $row['reviewed_at']);
        $this->assertSame('available', $row['status']);

        // Not published.
        $this->get('catalogo-inmuebles')->assertDontSee('Casa Pendiente 2');
        $this->assertSame([], model(PropertyModel::class)->findAvailableForContract());

        // The owner sees the reason in "Mis inmuebles" and in the correction form, and can resubmit it.
        $ownerSession = $this->sessionOf($ownerId, 'owner');
        $list         = $this->withSession($ownerSession)->get('propietario/inmuebles');
        $list->assertSee('Rechazado');
        $list->assertSee('Falta la dirección exacta del local.');
        $list->assertSee('Corregir');

        $this->withSession($ownerSession)->get("propietario/inmuebles/editar/{$propertyId}")
            ->assertSee('Falta la dirección exacta del local.');

        $this->postWithSession($ownerSession, "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload())
            ->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);
        $this->assertNull($this->propertyRow($propertyId)['rejection_reason']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidReasonProvider(): array
    {
        return [
            'missing'    => ['', PropertyController::REJECTION_REASON_REQUIRED_MESSAGE],
            'blank'      => ['     ', PropertyController::REJECTION_REASON_REQUIRED_MESSAGE],
            'too long'   => [str_repeat('a', 256), PropertyController::REJECTION_REASON_TOO_LONG_MESSAGE],
        ];
    }

    #[DataProvider('invalidReasonProvider')]
    public function testScenario3TheReasonIsMandatoryAndLimitedTo255Characters(string $reason, string $expectedMessage): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));

        $this->postWithSession($this->sessionOf($this->makeUser('admin'), 'admin'), "admin/inmuebles/{$propertyId}/rechazo", [
            'rejection_reason' => $reason,
        ])->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");

        $this->assertSame($expectedMessage, session()->getFlashdata('error'));
        $this->assertSame($expectedMessage, session()->getFlashdata('errors')['rejection_reason'] ?? null);

        $row = $this->propertyRow($propertyId);
        $this->assertSame('pending', $row['approval_status']);
        $this->assertNull($row['rejection_reason']);
        $this->assertNull($row['reviewed_by']);
    }

    public function testScenario3AReasonOfExactly255CharactersIsAccepted(): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));
        $reason     = str_repeat('á', 255);

        $this->postWithSession($this->sessionOf($this->makeUser('admin'), 'admin'), "admin/inmuebles/{$propertyId}/rechazo", [
            'rejection_reason' => $reason,
        ])->assertRedirectTo('/admin/inmuebles');

        $this->assertSame($reason, $this->propertyRow($propertyId)['rejection_reason']);
    }

    public function testScenario3TheRejectionFormKeepsTheTypedReasonAfterAnError(): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));
        $session    = $this->sessionOf($this->makeUser('admin'), 'admin');

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/rechazo", [
            'rejection_reason' => str_repeat('b', 300),
        ])->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");

        $form = $this->withSession()->get("admin/inmuebles/editar/{$propertyId}");
        $form->assertOK();
        $form->assertSee(PropertyController::REJECTION_REASON_TOO_LONG_MESSAGE);
        $form->assertSee(str_repeat('b', 300));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: correct before approving, with the HU-07 form
    // ---------------------------------------------------------------------------------------------

    public function testScenario4EditingAPendingPropertyKeepsItPending(): void
    {
        $adminId    = $this->makeUser('admin');
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId, ['address' => 'Casa Con Error 1']);

        $this->postWithSession($this->sessionOf($adminId, 'admin'), "admin/inmuebles/editar/{$propertyId}", $this->validPropertyPayload([
            'owner_id'  => (string) $ownerId,
            'address'   => 'Casa Corregida 2',
            'base_rent' => '480000',
        ]))->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_UPDATED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Corregida 2', $row['address']);
        $this->assertSame('480000.00', $row['base_rent']);
        $this->assertSame('pending', $row['approval_status'], 'Correcting does not resolve the review.');
        $this->assertSame($ownerId, (int) $row['submitted_by']);
        $this->assertNull($row['reviewed_by']);
    }

    public function testScenario4TheEditFormOffersTheReviewActionsOnlyForAPendingProperty(): void
    {
        $adminId  = $this->makeUser('admin');
        $ownerId  = $this->makeUser('owner', null, 'Marta', 'Quiroga');
        $session  = $this->sessionOf($adminId, 'admin');
        $pending  = $this->makePendingProperty($ownerId, ['address' => 'Casa Pendiente 2']);
        $rejected = $this->makeRejectedProperty($ownerId, $adminId, 'Falta la dirección exacta.', ['address' => 'Casa Rechazada 3']);
        $approved = $this->makeProperty($ownerId, ['address' => 'Casa De La Agencia 1']);

        $pendingForm = $this->withSession($session)->get("admin/inmuebles/editar/{$pending}");
        $pendingForm->assertOK();
        $pendingForm->assertSee('Revisión del inmueble');
        $pendingForm->assertSee('Lo publicó el propietario: Marta Quiroga.');
        $pendingForm->assertSee('btn-approve-property');
        $pendingForm->assertSee('btn-reject-property');
        $pendingForm->assertSee("admin/inmuebles/{$pending}/aprobacion");
        $pendingForm->assertSee("admin/inmuebles/{$pending}/rechazo");
        $pendingForm->assertSee('rejection_reason');
        $pendingForm->assertSee('name="owner_id"'); // the HU-07 form, with its owner selector

        $rejectedForm = $this->withSession($session)->get("admin/inmuebles/editar/{$rejected}");
        $rejectedForm->assertOK();
        $rejectedForm->assertSee('Falta la dirección exacta.');
        $rejectedForm->assertDontSee('btn-approve-property');
        $rejectedForm->assertDontSee('btn-reject-property');

        $approvedForm = $this->withSession($session)->get("admin/inmuebles/editar/{$approved}");
        $approvedForm->assertOK();
        $approvedForm->assertDontSee('Revisión del inmueble');
        $approvedForm->assertDontSee('btn-approve-property');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: already resolved
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: bool, 1: bool}> first resolution (true = approve), second resolution (true = approve)
     */
    public static function secondResolutionProvider(): array
    {
        return [
            'approve then approve' => [true, true],
            'approve then reject'  => [true, false],
            'reject then approve'  => [false, true],
            'reject then reject'   => [false, false],
        ];
    }

    #[DataProvider('secondResolutionProvider')]
    public function testScenario5ASecondResolutionIsReportedAndChangesNothing(bool $firstApproves, bool $secondApproves): void
    {
        $first      = $this->makeUser('admin');
        $second     = $this->makeUser('employee');
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));
        Time::setTestNow('2026-11-17 12:00:00');

        $resolve = function (int $userId, string $role, bool $approve) use ($propertyId) {
            return $this->postWithSession(
                $this->sessionOf($userId, $role),
                "admin/inmuebles/{$propertyId}/" . ($approve ? 'aprobacion' : 'rechazo'),
                $approve ? [] : ['rejection_reason' => 'Motivo del primero.']
            );
        };

        $resolve($first, 'admin', $firstApproves)->assertRedirectTo('/admin/inmuebles');
        $before = $this->propertyRow($propertyId);

        Time::setTestNow('2026-11-17 13:00:00');
        $resolve($second, 'employee', $secondApproves)->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_ALREADY_RESOLVED_MESSAGE, session()->getFlashdata('error'));

        $this->assertSame($before, $this->propertyRow($propertyId), 'Nothing may change on the second resolution.');
        $this->assertSame($first, (int) $before['reviewed_by']);
        $this->assertSame('2026-11-17 12:00:00', $before['reviewed_at']);
    }

    public function testScenario5APropertyTheAdministrationLoadedOrAnApprovedOneIsNotResolvable(): void
    {
        $adminId    = $this->makeUser('admin');
        $propertyId = $this->makeProperty($this->makeUser('owner')); // approved by default
        $session    = $this->sessionOf($adminId, 'admin');

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/rechazo", ['rejection_reason' => 'No corresponde.'])
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_ALREADY_RESOLVED_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/aprobacion")->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_ALREADY_RESOLVED_MESSAGE, session()->getFlashdata('error'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('approved', $row['approval_status']);
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['rejection_reason']);
    }

    public function testADeactivatedPropertyCannotBeResolved(): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'), ['deleted_at' => '2026-10-01 10:00:00']);
        $session    = $this->sessionOf($this->makeUser('admin'), 'admin');

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/aprobacion")->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_DEACTIVATED_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/rechazo", ['rejection_reason' => 'Motivo.'])
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::APPROVAL_DEACTIVATED_MESSAGE, session()->getFlashdata('error'));

        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);
    }

    public function testAMissingPropertyIsReported(): void
    {
        $session = $this->sessionOf($this->makeUser('admin'), 'admin');

        $this->postWithSession($session, 'admin/inmuebles/999999/aprobacion')->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, 'admin/inmuebles/999999/rechazo', ['rejection_reason' => 'Motivo.'])
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    public function testTheModelResolvesAPropertyOnlyOnceAndRefusesAnEmptyReasonOrReviewer(): void
    {
        $adminId    = $this->makeUser('admin');
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));
        $model      = model(PropertyModel::class);

        $this->assertFalse($model->resolveApproval($propertyId, $adminId, false, '   ')['ok'], 'A rejection needs a reason.');
        $this->assertFalse($model->resolveApproval($propertyId, 0, true)['ok'], 'A review needs a reviewer.');
        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);

        $this->assertTrue($model->resolveApproval($propertyId, $adminId, true)['ok']);
        $again = $model->resolveApproval($propertyId, $adminId, false, 'Otro motivo.');

        $this->assertFalse($again['ok']);
        $this->assertSame(PropertyModel::ERROR_ALREADY_RESOLVED, $again['error']);
        $this->assertSame('approved', $this->propertyRow($propertyId)['approval_status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 6: access
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function externalRoleProvider(): array
    {
        return [
            'owner'  => ['owner', '/propietario/dashboard'],
            'tenant' => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('externalRoleProvider')]
    public function testScenario6OwnersAndTenantsCannotApproveOrReject(string $roleName, string $ownPanel): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId);
        $session    = $this->sessionOf($roleName === 'owner' ? $ownerId : $this->makeUser($roleName), $roleName);

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/aprobacion")->assertRedirectTo($ownPanel);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, "admin/inmuebles/{$propertyId}/rechazo", ['rejection_reason' => 'Motivo.'])
            ->assertRedirectTo($ownPanel);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('pending', $row['approval_status']);
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['rejection_reason']);
    }

    public function testScenario6VisitorsAreRedirectedToTheLogin(): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));

        $this->postWithSession([], "admin/inmuebles/{$propertyId}/aprobacion")->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession([], "admin/inmuebles/{$propertyId}/rechazo", ['rejection_reason' => 'Motivo.'])
            ->assertRedirectTo('/auth/login');

        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);
    }

    public function testTheReviewRoutesAcceptPostOnly(): void
    {
        $propertyId = $this->makePendingProperty($this->makeUser('owner'));
        $session    = $this->sessionOf($this->makeUser('admin'), 'admin');

        // A link or an image cannot approve or reject: a GET does not even reach the controller.
        foreach (['aprobacion', 'rechazo'] as $action) {
            $this->assertAnswers404(
                fn () => $this->withSession($session)->get("admin/inmuebles/{$propertyId}/{$action}"),
                $action
            );
        }

        $this->assertSame('pending', $this->propertyRow($propertyId)['approval_status']);
    }
}
