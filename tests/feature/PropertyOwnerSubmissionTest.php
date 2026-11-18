<?php

use App\Controllers\ContractController;
use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\ContractModel;
use App\Models\PropertyModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Libraries\PropertyReviewFixtures;

/**
 * Acceptance and security tests for HU-32: an owner publishes their own property, which waits for approval.
 *
 * @internal
 */
final class PropertyOwnerSubmissionTest extends CIUnitTestCase
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
    // Scenario 1: the property is registered in the owner's name, pending approval
    // ---------------------------------------------------------------------------------------------

    public function testScenario1OwnerPublishesAPropertyThatWaitsForApproval(): void
    {
        $ownerId = $this->makeUser('owner');
        $session = $this->sessionOf($ownerId, 'owner');

        $response = $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload());

        $response->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_PROPERTY_SUBMITTED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('properties')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame($ownerId, (int) $row['owner_id']);
        $this->assertSame('Belgrano 1250, 4°B', $row['address']);
        $this->assertSame('Cipolletti', $row['city']);
        $this->assertSame('apartment', $row['type']);
        $this->assertSame(3, (int) $row['rooms']);
        $this->assertSame('54.50', $row['surface']);
        $this->assertSame('Agua, Luz', $row['included_services']);
        $this->assertSame('420000.00', $row['base_rent']);
        $this->assertSame('Luminoso, con balcón', $row['description']);
        $this->assertSame('available', $row['status']);
        $this->assertSame('pending', $row['approval_status']);
        $this->assertSame($ownerId, (int) $row['submitted_by']);
        $this->assertNull($row['rejection_reason']);
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['reviewed_at']);
        $this->assertNull($row['deleted_at']);
        $this->assertNotNull($row['created_at']);

        // "Mis inmuebles" shows it as under review.
        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertOK();
        $list->assertSee('Belgrano 1250, 4°B');
        $list->assertSee('En revisión');
    }

    public function testALandNeedsNoRoomsAndOptionalFieldsAreStoredAsNull(): void
    {
        $ownerId = $this->makeUser('owner');

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), 'propietario/inmuebles', $this->validPropertyPayload([
            'type'              => 'land',
            'rooms'             => '',
            'surface'           => '',
            'included_services' => '',
            'description'       => '',
        ]))->assertRedirectTo('/propietario/inmuebles');

        $row = $this->db->table('properties')->get()->getRowArray();
        $this->assertSame('land', $row['type']);
        $this->assertNull($row['rooms']);
        $this->assertNull($row['surface']);
        $this->assertNull($row['included_services']);
        $this->assertNull($row['description']);
    }

    public function testTheOwnerComesFromTheSessionAndNeverFromTheForm(): void
    {
        $ownerId = $this->makeUser('owner');
        $other   = $this->makeUser('owner');
        $admin   = $this->makeUser('admin');

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), 'propietario/inmuebles', $this->validPropertyPayload([
            'owner_id'         => (string) $other,
            'status'           => 'maintenance',
            'approval_status'  => 'approved',
            'submitted_by'     => (string) $other,
            'reviewed_by'      => (string) $admin,
            'rejection_reason' => 'inventado',
        ]))->assertRedirectTo('/propietario/inmuebles');

        $row = $this->db->table('properties')->get()->getRowArray();
        $this->assertSame($ownerId, (int) $row['owner_id'], 'The owner is the one in the session.');
        $this->assertSame('available', $row['status'], 'The owner cannot choose the commercial status.');
        $this->assertSame('pending', $row['approval_status'], 'The owner cannot approve their own property.');
        $this->assertSame($ownerId, (int) $row['submitted_by']);
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['rejection_reason']);
        $this->assertSame(1, $this->db->table('properties')->countAllResults());
    }

    public function testTheOwnerFormHasNoOwnerSelectorAndNoStatusField(): void
    {
        $session = $this->sessionOf($this->makeUser('owner'), 'owner');

        $response = $this->withSession($session)->get('propietario/inmuebles/nuevo');

        $response->assertOK();
        $response->assertSee('Registrar inmueble');
        $response->assertSee('Dirección exacta');
        $response->assertSee('Enviar a revisión');
        foreach (['Departamento', 'Casa', 'Local comercial', 'Terreno', 'Oficina'] as $label) {
            $response->assertSee($label);
        }
        $response->assertDontSee('name="owner_id"');
        $response->assertDontSee('name="status"');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: invalid data, same rules as HU-07
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'price zero'             => [['base_rent' => '0'], 'base_rent'],
            'price negative'         => [['base_rent' => '-100'], 'base_rent'],
            'price empty'            => [['base_rent' => ''], 'base_rent'],
            'price text'             => [['base_rent' => 'barato'], 'base_rent'],
            'rooms zero'             => [['rooms' => '0'], 'rooms'],
            'rooms negative'         => [['rooms' => '-2'], 'rooms'],
            'rooms decimal'          => [['rooms' => '2.5'], 'rooms'],
            'rooms empty (not land)' => [['rooms' => ''], 'rooms'],
            'surface zero'           => [['surface' => '0'], 'surface'],
            'surface negative'       => [['surface' => '-10'], 'surface'],
            'city empty'             => [['city' => ''], 'city'],
            'city blank'             => [['city' => '   '], 'city'],
            'address empty'          => [['address' => ''], 'address'],
            'type invalid'           => [['type' => 'castle'], 'type'],
            'description too long'   => [['description' => str_repeat('a', 1001)], 'description'],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidDataProvider')]
    public function testScenario2InvalidDataIsRejectedAndEveryFieldErrorIsReported(array $overrides, string $errorField): void
    {
        $session = $this->sessionOf($this->makeUser('owner'), 'owner');

        $response = $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload($overrides));

        $response->assertRedirectTo('/propietario/inmuebles/nuevo');
        $this->assertSame(PropertyController::VALIDATION_ERROR_MESSAGE, session()->getFlashdata('error'));
        $this->assertArrayHasKey($errorField, session()->getFlashdata('errors'));
        $this->assertSame(0, $this->db->table('properties')->countAllResults(), 'Nothing may be stored.');
    }

    public function testScenario2SeveralErrorsAreReportedTogetherAndTheTypedValuesAreKept(): void
    {
        $session = $this->sessionOf($this->makeUser('owner'), 'owner');

        $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload([
            'address'   => 'Mitre 845',
            'base_rent' => '0',
            'rooms'     => '0',
            'surface'   => '-1',
            'city'      => '',
        ]))->assertRedirectTo('/propietario/inmuebles/nuevo');

        $this->assertEqualsCanonicalizing(
            ['base_rent', 'rooms', 'surface', 'city'],
            array_keys(session()->getFlashdata('errors'))
        );

        $form = $this->withSession()->get('propietario/inmuebles/nuevo');
        $form->assertOK();
        $form->assertSee('Ingresá la ciudad.');
        $form->assertSee('El precio base debe ser mayor a cero.');
        $form->assertSee('Mitre 845');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: nothing is published or counted until the administration approves it
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function notApprovedProvider(): array
    {
        return ['pending' => ['pending'], 'rejected' => ['rejected']];
    }

    #[DataProvider('notApprovedProvider')]
    public function testScenario3ANotApprovedPropertyIsNotInThePublicCatalog(string $approval): void
    {
        $ownerId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');
        $this->makeProperty($ownerId, ['address' => 'Casa Aprobada 100']);
        $this->makePropertyIn($approval, $ownerId, $adminId, ['address' => 'Casa Oculta 200']);

        $response = $this->get('catalogo-inmuebles');

        $response->assertOK();
        $response->assertSee('Casa Aprobada 100');
        $response->assertDontSee('Casa Oculta 200');
        $this->assertSame(1, model(PropertyModel::class)->paginatePublic()['total']);
    }

    #[DataProvider('notApprovedProvider')]
    public function testScenario3ANotApprovedPropertyIsNotInTheContractSelector(string $approval): void
    {
        $ownerId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');
        $this->makeUser('tenant');
        $approved = $this->makeProperty($ownerId, ['address' => 'Casa Aprobada 100']);
        $hidden   = $this->makePropertyIn($approval, $ownerId, $adminId, ['address' => 'Casa Oculta 200']);

        $ids = array_map(
            static fn (array $property): int => (int) $property['id'],
            model(PropertyModel::class)->findAvailableForContract()
        );
        $this->assertSame([$approved], $ids);
        $this->assertNotContains($hidden, $ids);

        $form = $this->withSession($this->sessionOf($adminId, 'admin'))->get('admin/contratos/nuevo');
        $form->assertOK();
        $form->assertSee('Casa Aprobada 100');
        $form->assertDontSee('Casa Oculta 200');
    }

    #[DataProvider('notApprovedProvider')]
    public function testScenario3ANotApprovedPropertyIsNotCountedInTheOccupancyIndicators(string $approval): void
    {
        $ownerId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');
        $this->makeProperty($ownerId);
        $this->makeProperty($ownerId, ['status' => 'rented']);
        $this->makePropertyIn($approval, $ownerId, $adminId);
        $this->makePropertyIn($approval, $ownerId, $adminId, ['status' => 'maintenance']);

        $counts = model(PropertyModel::class)->getStatusCounts();

        $this->assertSame(1, $counts['available']);
        $this->assertSame(1, $counts['rented']);
        $this->assertSame(0, $counts['maintenance']);
        $this->assertSame(2, $counts['total']);
        $this->assertSame($approval === 'pending' ? 2 : 0, $counts['pending_approval']);
    }

    #[DataProvider('notApprovedProvider')]
    public function testScenario3AContractCannotBeCreatedForANotApprovedProperty(string $approval): void
    {
        $ownerId  = $this->makeUser('owner');
        $adminId  = $this->makeUser('admin');
        $tenantId = $this->makeUser('tenant');
        $hidden   = $this->makePropertyIn($approval, $ownerId, $adminId, ['address' => 'Casa Oculta 200']);

        $this->postWithSession($this->sessionOf($adminId, 'admin'), 'admin/contratos', [
            'property_id'               => (string) $hidden,
            'tenant_id'                 => (string) $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '510000',
            'update_percentage'         => '12.5',
            'commission_percentage'     => '10',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ])->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('errors')['property_id'] ?? null);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
        $this->assertSame('available', $this->propertyRow($hidden)['status']);

        // The atomic operation checks it again under the lock, so it holds even if the controller check is skipped.
        $result = model(ContractModel::class)->createAndRentProperty([
            'property_id'               => $hidden,
            'tenant_id'                 => $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '510000',
            'update_percentage'         => 12.5,
            'commission_percentage'     => 10,
            'due_day'                   => 10,
            'late_fee_daily_percentage' => 0.5,
        ]);
        $this->assertNull($result['id']);
        $this->assertSame(ContractModel::ERROR_PROPERTY_UNAVAILABLE, $result['error']);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
    }

    public function testScenario3TheOwnerSeesPendingAndRejectedPropertiesWithTheirStateAndTheReason(): void
    {
        $ownerId = $this->makeUser('owner');
        $adminId = $this->makeUser('admin');
        $this->makePendingProperty($ownerId, ['address' => 'Casa En Revision 1']);
        $this->makeRejectedProperty($ownerId, $adminId, 'Falta la dirección exacta del local.', ['address' => 'Casa Rechazada 2']);
        $this->makeProperty($ownerId, ['address' => 'Casa Aprobada 3', 'status' => 'rented']);

        $response = $this->withSession($this->sessionOf($ownerId, 'owner'))->get('propietario/inmuebles');

        $response->assertOK();
        $response->assertSee('Casa En Revision 1');
        $response->assertSee('En revisión');
        $response->assertSee('Casa Rechazada 2');
        $response->assertSee('Rechazado');
        $response->assertSee('Motivo del rechazo');
        $response->assertSee('Falta la dirección exacta del local.');
        $response->assertSee('Casa Aprobada 3');
        $response->assertSee('Alquilado');
    }

    public function testTheCatalogsNeverExposeTheReviewColumns(): void
    {
        $ownerId = $this->makeUser('owner');
        $this->makeProperty($ownerId);

        $item = model(PropertyModel::class)->paginatePublic()['items'][0];

        foreach (['approval_status', 'submitted_by', 'rejection_reason', 'reviewed_by', 'reviewed_at'] as $column) {
            $this->assertArrayNotHasKey($column, $item, "The public catalog must not expose {$column}.");
        }
    }

    // ---------------------------------------------------------------------------------------------
    // What the administration loads is approved; what existed before stays visible
    // ---------------------------------------------------------------------------------------------

    public function testAPropertyLoadedByTheAdministrationIsApprovedAutomaticallyAndVisible(): void
    {
        $adminId = $this->makeUser('admin');
        $ownerId = $this->makeUser('owner');

        $this->postWithSession($this->sessionOf($adminId, 'admin'), 'admin/inmuebles', $this->validPropertyPayload([
            'owner_id' => (string) $ownerId,
            'address'  => 'Casa Cargada Por Admin 7',
        ]))->assertRedirectTo('/admin/inmuebles');

        $row = $this->db->table('properties')->get()->getRowArray();
        $this->assertSame('approved', $row['approval_status']);
        $this->assertNull($row['submitted_by']);
        $this->assertSame('available', $row['status']);

        $this->get('catalogo-inmuebles')->assertSee('Casa Cargada Por Admin 7');
        $this->assertCount(1, model(PropertyModel::class)->findAvailableForContract());
    }

    public function testExistingPropertiesStayApprovedByDefaultAndVisible(): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makeProperty($ownerId, ['address' => 'Casa De Siempre 9']);

        $row = $this->propertyRow($propertyId);
        $this->assertSame('approved', $row['approval_status']);
        $this->assertNull($row['submitted_by']);
        $this->assertNull($row['rejection_reason']);
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['reviewed_at']);

        $this->get('catalogo-inmuebles')->assertSee('Casa De Siempre 9');
    }

    public function testTheDatabaseRefusesARejectedPropertyWithoutAReason(): void
    {
        $ownerId = $this->makeUser('owner');

        try {
            $stored = $this->db->table('properties')->insert([
                'owner_id'        => $ownerId,
                'address'         => 'Sin motivo 1',
                'city'            => 'Cipolletti',
                'type'            => 'house',
                'rooms'           => 2,
                'base_rent'       => 100000,
                'approval_status' => 'rejected',
            ]);
        } catch (DatabaseException) {
            $stored = false;
        }

        $this->assertFalse((bool) $stored);
        $this->assertSame(0, $this->db->table('properties')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: correct and resubmit
    // ---------------------------------------------------------------------------------------------

    #[DataProvider('notApprovedProvider')]
    public function testScenario4CorrectingAPendingOrRejectedPropertyLeavesItPendingAndClearsTheReason(string $approval): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makePropertyIn($approval, $ownerId, $adminId, ['address' => 'Casa Vieja 1']);

        $response = $this->postWithSession($session, "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload([
            'address'   => 'Casa Corregida 2',
            'base_rent' => '500000',
        ]));

        $response->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_PROPERTY_RESUBMITTED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Corregida 2', $row['address']);
        $this->assertSame('500000.00', $row['base_rent']);
        $this->assertSame('pending', $row['approval_status']);
        $this->assertNull($row['rejection_reason'], 'The previous rejection reason is cleared.');
        $this->assertNull($row['reviewed_by']);
        $this->assertNull($row['reviewed_at']);
        $this->assertSame($ownerId, (int) $row['owner_id']);
        $this->assertSame($ownerId, (int) $row['submitted_by']);
        $this->assertSame('available', $row['status']);
        $this->assertSame(1, $this->db->table('properties')->countAllResults());

        $this->withSession($session)->get('propietario/inmuebles')->assertSee('En revisión');
    }

    public function testScenario4TheCorrectionFormShowsTheDataAndTheRejectionReason(): void
    {
        $ownerId  = $this->makeUser('owner');
        $adminId  = $this->makeUser('admin');
        $session  = $this->sessionOf($ownerId, 'owner');
        $rejected = $this->makeRejectedProperty($ownerId, $adminId, 'Falta la dirección exacta del local.', ['address' => 'Casa Rechazada 2']);
        $pending  = $this->makePendingProperty($ownerId, ['address' => 'Casa En Revision 1']);

        $rejectedForm = $this->withSession($session)->get("propietario/inmuebles/editar/{$rejected}");
        $rejectedForm->assertOK();
        $rejectedForm->assertSee('Casa Rechazada 2');
        $rejectedForm->assertSee('Falta la dirección exacta del local.');
        $rejectedForm->assertSee('Guardar y reenviar a revisión');
        $rejectedForm->assertDontSee('name="owner_id"');

        $pendingForm = $this->withSession($session)->get("propietario/inmuebles/editar/{$pending}");
        $pendingForm->assertOK();
        $pendingForm->assertSee('Casa En Revision 1');
        $pendingForm->assertDontSee('La administración rechazó este inmueble');
    }

    public function testScenario4InvalidCorrectionsAreRejectedAndNothingChanges(): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $propertyId = $this->makeRejectedProperty($ownerId, $adminId, 'Faltan datos.', ['address' => 'Casa Vieja 1']);

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload([
            'address'   => 'Casa Corregida 2',
            'base_rent' => '-5',
        ]))->assertRedirectTo("/propietario/inmuebles/editar/{$propertyId}");

        $this->assertArrayHasKey('base_rent', session()->getFlashdata('errors'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Vieja 1', $row['address']);
        $this->assertSame('rejected', $row['approval_status']);
        $this->assertSame('Faltan datos.', $row['rejection_reason']);
    }

    public function testScenario4ACorrectionCannotChangeTheOwnerTheStatusOrTheReview(): void
    {
        $ownerId    = $this->makeUser('owner');
        $other      = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId);

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload([
            'owner_id'        => (string) $other,
            'status'          => 'maintenance',
            'approval_status' => 'approved',
            'submitted_by'    => (string) $other,
        ]))->assertRedirectTo('/propietario/inmuebles');

        $row = $this->propertyRow($propertyId);
        $this->assertSame($ownerId, (int) $row['owner_id']);
        $this->assertSame($ownerId, (int) $row['submitted_by']);
        $this->assertSame('available', $row['status']);
        $this->assertSame('pending', $row['approval_status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: an approved property is edited by the administration only
    // ---------------------------------------------------------------------------------------------

    public function testScenario5AnApprovedPropertyCannotBeEditedFromThePortal(): void
    {
        $ownerId    = $this->makeUser('owner');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makePendingProperty($ownerId, [
            'address'         => 'Casa Aprobada 3',
            'approval_status' => 'approved',
            'reviewed_by'     => $this->makeUser('admin'),
            'reviewed_at'     => '2026-02-01 09:30:00',
        ]);

        $this->withSession($session)->get("propietario/inmuebles/editar/{$propertyId}")
            ->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_PROPERTY_APPROVED_LOCKED_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload([
            'address'   => 'Casa Cambiada 4',
            'base_rent' => '1',
        ]))->assertRedirectTo('/propietario/inmuebles');
        $this->assertSame(PropertyController::OWNER_PROPERTY_APPROVED_LOCKED_MESSAGE, session()->getFlashdata('error'));

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Aprobada 3', $row['address']);
        $this->assertSame('approved', $row['approval_status'], 'It must not go back to pending.');
        $this->assertSame('650000.00', $row['base_rent']);

        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertSee('Cambios: pedilos a la administración');
        $list->assertDontSee('Corregir');
    }

    public function testScenario5ApprovalThatLandsWhileTheOwnerIsEditingIsNotUndone(): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $propertyId = $this->makePendingProperty($ownerId, ['address' => 'Casa Vieja 1']);

        // The owner opened the form while it was pending; meanwhile the administration approves it.
        $this->assertTrue(model(PropertyModel::class)->resolveApproval($propertyId, $adminId, true)['ok']);

        $result = model(PropertyModel::class)->resubmitForApproval($propertyId, $ownerId, [
            'address'   => 'Casa Cambiada 4',
            'city'      => 'Cipolletti',
            'type'      => 'house',
            'rooms'     => 3,
            'base_rent' => '1',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertSame(PropertyModel::ERROR_ALREADY_APPROVED, $result['error']);

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Vieja 1', $row['address']);
        $this->assertSame('approved', $row['approval_status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 6: somebody else's property
    // ---------------------------------------------------------------------------------------------

    public function testScenario6AnotherOwnersPropertyAnswers404ForViewingAndEditing(): void
    {
        $ownerId    = $this->makeUser('owner');
        $other      = $this->makeUser('owner');
        $session    = $this->sessionOf($ownerId, 'owner');
        $propertyId = $this->makePendingProperty($other, ['address' => 'Casa Ajena 5']);

        $this->assertAnswers404(fn () => $this->withSession($session)->get("propietario/inmuebles/editar/{$propertyId}"), 'edit form');
        $this->assertAnswers404(
            fn () => $this->postWithSession($session, "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload(['address' => 'Casa Robada 6'])),
            'update'
        );

        $row = $this->propertyRow($propertyId);
        $this->assertSame('Casa Ajena 5', $row['address']);
        $this->assertSame($other, (int) $row['owner_id']);

        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertDontSee('Casa Ajena 5');
    }

    public function testScenario6AMissingOrDeactivatedPropertyAlsoAnswers404(): void
    {
        $ownerId     = $this->makeUser('owner');
        $session     = $this->sessionOf($ownerId, 'owner');
        $deactivated = $this->makePendingProperty($ownerId, ['deleted_at' => '2026-10-01 10:00:00']);

        $this->assertAnswers404(fn () => $this->withSession($session)->get('propietario/inmuebles/editar/999999'), 'missing');
        $this->assertAnswers404(fn () => $this->withSession($session)->get("propietario/inmuebles/editar/{$deactivated}"), 'deactivated');
        $this->assertAnswers404(
            fn () => $this->postWithSession($session, "propietario/inmuebles/editar/{$deactivated}", $this->validPropertyPayload()),
            'deactivated update'
        );
    }

    public function testScenario6TheModelNeverReturnsAPropertyOfAnotherOwnerNorResubmitsIt(): void
    {
        $ownerId    = $this->makeUser('owner');
        $other      = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($other, ['address' => 'Casa Ajena 5']);

        $this->assertNull(model(PropertyModel::class)->findForOwner($propertyId, $ownerId));
        $this->assertNotNull(model(PropertyModel::class)->findForOwner($propertyId, $other));

        $result = model(PropertyModel::class)->resubmitForApproval($propertyId, $ownerId, ['address' => 'Casa Robada 6']);
        $this->assertSame(PropertyModel::ERROR_NOT_FOUND, $result['error']);
        $this->assertSame('Casa Ajena 5', $this->propertyRow($propertyId)['address']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 7: no properties yet
    // ---------------------------------------------------------------------------------------------

    public function testScenario7AnOwnerWithoutPropertiesIsInvitedToRegisterTheFirstOne(): void
    {
        $ownerId = $this->makeUser('owner');
        $other   = $this->makeUser('owner');
        $this->makeProperty($other, ['address' => 'Casa De Otro 8']);

        $response = $this->withSession($this->sessionOf($ownerId, 'owner'))->get('propietario/inmuebles');

        $response->assertOK();
        $response->assertSee('Registrar mi primer inmueble');
        $response->assertSee(site_url('propietario/inmuebles/nuevo'));
        $response->assertDontSee('Casa De Otro 8');
    }

    public function testAnOwnerWithPropertiesSeesTheRegisterButtonButNotTheEmptyState(): void
    {
        $ownerId = $this->makeUser('owner');
        $this->makePendingProperty($ownerId, ['address' => 'Casa En Revision 1', 'type' => 'house']);
        $session = $this->sessionOf($ownerId, 'owner');

        $list = $this->withSession($session)->get('propietario/inmuebles');
        $list->assertOK();
        $list->assertSee('Registrar inmueble');
        $list->assertDontSee('Registrar mi primer inmueble');

        // A filter without matches is not "no properties".
        $filtered = $this->withSession($session)->get('propietario/inmuebles?type=land');
        $filtered->assertOK();
        $filtered->assertSee('No se encontraron inmuebles con los filtros seleccionados.');
        $filtered->assertDontSee('Registrar mi primer inmueble');
    }

    // ---------------------------------------------------------------------------------------------
    // Access
    // ---------------------------------------------------------------------------------------------

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
    public function testOnlyOwnersReachThePublishRoutes(string $roleName, string $ownPanel): void
    {
        $session    = $this->sessionOf($this->makeUser($roleName), $roleName);
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId, ['address' => 'Casa En Revision 1']);

        foreach (['propietario/inmuebles/nuevo', "propietario/inmuebles/editar/{$propertyId}"] as $uri) {
            $this->withSession($session)->get($uri)->assertRedirectTo($ownPanel);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession($session, 'propietario/inmuebles', $this->validPropertyPayload())->assertRedirectTo($ownPanel);
        $this->postWithSession($session, "propietario/inmuebles/editar/{$propertyId}", $this->validPropertyPayload(['address' => 'Casa Tomada 9']))
            ->assertRedirectTo($ownPanel);

        $this->assertSame(1, $this->db->table('properties')->countAllResults());
        $this->assertSame('Casa En Revision 1', $this->propertyRow($propertyId)['address']);
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makePendingProperty($ownerId);

        foreach (['propietario/inmuebles/nuevo', "propietario/inmuebles/editar/{$propertyId}"] as $uri) {
            $this->get($uri)->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession([], 'propietario/inmuebles', $this->validPropertyPayload())->assertRedirectTo('/auth/login');
        $this->assertSame(1, $this->db->table('properties')->countAllResults());
    }

    public function testADeactivatedOwnerCannotPublish(): void
    {
        $ownerId = $this->makeUser('owner', '2026-10-01 10:00:00');

        $this->postWithSession($this->sessionOf($ownerId, 'owner'), 'propietario/inmuebles', $this->validPropertyPayload())
            ->assertRedirectTo('/auth/login');

        $this->assertSame(0, $this->db->table('properties')->countAllResults());
    }

    public function testThePublicCatalogOffersTheLinkToPublishAProperty(): void
    {
        $response = $this->get('catalogo-inmuebles');

        $response->assertOK();
        $response->assertSee('Publicar mi inmueble');
        $response->assertSee(site_url('propietario/inmuebles/nuevo'));
    }
}
