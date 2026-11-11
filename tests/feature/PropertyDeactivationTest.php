<?php

use App\Controllers\ContractController;
use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\PropertyModel;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance and security tests for HU-09: logical property deactivation (soft delete).
 *
 * @internal
 */
final class PropertyDeactivationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION           = [];
        $this->userSequence = 0;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenarios 1 & 3: Requesting deactivation and canceling / unconfirmed requests
    // ---------------------------------------------------------------------------------------------

    public function testScenario1And3RequestingDeactivationRequiresConfirmationAndLeavesPropertyActiveUntilConfirmed(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');
        $propertyId = $this->insertProperty($ownerId, [
            'address' => 'Roca 1020, Piso 2',
            'city'    => 'Cipolletti',
            'type'    => 'office',
        ]);

        // Both the listing and the edit form expose the deactivation trigger and modal for an active property without a contract in force.
        $listResponse = $this->withSession($admin)->get('admin/inmuebles');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Roca 1020, Piso 2');
        $listResponse->assertSee('admin/inmuebles/baja/' . $propertyId);
        $listResponse->assertSee('¿Confirmás la baja lógica del inmueble?');

        $editResponse = $this->withSession($admin)->get("admin/inmuebles/editar/{$propertyId}");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Dar de baja inmueble');
        $editResponse->assertSee('admin/inmuebles/baja/' . $propertyId);
        $editResponse->assertSee('¿Confirmás la baja lógica del inmueble?');

        // Any POST without confirmed=1 renders the explicit confirmation page and does not modify the property.
        foreach ([[], ['confirmed' => '0'], ['confirmed' => 'cancel']] as $unconfirmedPayload) {
            $confirmPage = $this->postWithSession($admin, "admin/inmuebles/baja/{$propertyId}", $unconfirmedPayload);

            $confirmPage->assertStatus(200);
            $confirmPage->assertSee('¿Confirmás la baja lógica del inmueble?');
            $confirmPage->assertSee('Roca 1020, Piso 2, Cipolletti');
            $confirmPage->assertSee('Oficina');
            $confirmPage->assertSee('Marta Quiroga');
            $confirmPage->assertSee('pasará a estado inactivo');
            $confirmPage->assertSee('admin/inmuebles/baja/' . $propertyId);
            $confirmPage->assertSeeElement('input[name=confirmed]');
            $this->assertStringContainsString('name="confirmed" value="1"', (string) $confirmPage->getBody());
            $confirmPage->assertSee('Cancelar');

            $row = $this->db->table('properties')->where('id', $propertyId)->get()->getRow();
            $this->assertNotNull($row);
            $this->assertNull($row->deleted_at, 'The property must remain active while confirmation is not given.');
            $this->assertNull($row->deleted_by, 'deleted_by must remain null while confirmation is not given.');
            $this->assertSame('available', $row->status);
        }

        // Scenario 3: Following the Cancel link back to the listing leaves the property active and untouched.
        $afterCancel = $this->withSession($admin)->get('admin/inmuebles');
        $afterCancel->assertStatus(200);
        $afterCancel->assertSee('Roca 1020, Piso 2');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: Confirming logical deactivation
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function operatorRoleProvider(): array
    {
        return [
            'admin'    => ['admin'],
            'employee' => ['employee'],
        ];
    }

    #[DataProvider('operatorRoleProvider')]
    public function testScenario2ConfirmingDeactivationPersistsDeletedAtAndDeletedByAndPreservesPropertyData(string $roleName): void
    {
        $operator   = $this->sessionFor($roleName);
        $ownerId    = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu09@example.com');
        $propertyId = $this->insertProperty($ownerId, [
            'address'           => 'Sarmiento 77, 1°A',
            'city'              => 'Neuquén',
            'type'              => 'apartment',
            'rooms'             => 2,
            'surface'           => 58.75,
            'included_services' => 'Agua, expensas',
            'base_rent'         => 350000.50,
            'description'       => 'Departamento frente a plaza',
            'status'            => 'maintenance',
        ]);

        $response = $this->postWithSession($operator, "admin/inmuebles/baja/{$propertyId}", ['confirmed' => '1']);

        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('properties')->where('id', $propertyId)->get()->getRow();
        $this->assertNotNull($row, 'Physical property record must be preserved.');
        $this->assertNotNull($row->deleted_at, 'Deactivation timestamp must be recorded.');
        $this->assertLessThanOrEqual(120, abs(time() - strtotime((string) $row->deleted_at)));
        $this->assertSame($operator['user_id'], (int) $row->deleted_by, 'The responsible user must be recorded in deleted_by.');

        // All property attributes remain intact.
        $this->assertSame($ownerId, (int) $row->owner_id);
        $this->assertSame('Sarmiento 77, 1°A', $row->address);
        $this->assertSame('Neuquén', $row->city);
        $this->assertSame('apartment', $row->type);
        $this->assertSame(2, (int) $row->rooms);
        $this->assertSame('58.75', $row->surface);
        $this->assertSame('Agua, expensas', $row->included_services);
        $this->assertSame('350000.50', $row->base_rent);
        $this->assertSame('Departamento frente a plaza', $row->description);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenarios 4 & 5: Default active listing and filtering by active / inactive status
    // ---------------------------------------------------------------------------------------------

    public function testScenario4And5ActiveListingExcludesInactivePropertiesAndStatusFilterShowsInactiveWithDeactivationDate(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');

        $activeAvailableId = $this->insertProperty($ownerId, [
            'address' => 'Belgrano 1250, 4°B',
            'status'  => 'available',
        ]);
        $activeMaintenanceId = $this->insertProperty($ownerId, [
            'address' => 'San Martín 450',
            'status'  => 'maintenance',
        ]);
        $toDeactivateId = $this->insertProperty($ownerId, [
            'address' => 'Roca 77',
            'status'  => 'available',
        ]);

        $this->postWithSession($admin, "admin/inmuebles/baja/{$toDeactivateId}", ['confirmed' => '1'])
            ->assertRedirectTo('/admin/inmuebles');

        $deactivatedRow      = $this->db->table('properties')->where('id', $toDeactivateId)->get()->getRow();
        $expectedDeletedDate = date('d/m/Y', strtotime((string) $deactivatedRow->deleted_at));

        // Scenario 4: Default listing (no filter) shows only active properties.
        $defaultList = $this->withSession($admin)->get('admin/inmuebles');
        $defaultList->assertStatus(200);
        $defaultList->assertSee('Belgrano 1250, 4°B');
        $defaultList->assertSee('San Martín 450');
        $defaultList->assertDontSee('Roca 77');
        $defaultList->assertSee('2 registrados');

        // Scenario 5: Filtering by "active" ("active" and "activo") shows only active properties.
        foreach (['active', 'activo'] as $activeFilterValue) {
            $activeList = $this->withSession($admin)->get("admin/inmuebles?status={$activeFilterValue}");
            $activeList->assertStatus(200);
            $activeList->assertSee('Belgrano 1250, 4°B');
            $activeList->assertSee('San Martín 450');
            $activeList->assertDontSee('Roca 77');
        }

        // Scenario 5: Filtering by "inactive" ("inactive" and "inactivo") shows only inactive properties and their deactivation date.
        foreach (['inactive', 'inactivo'] as $inactiveFilterValue) {
            $inactiveList = $this->withSession($admin)->get("admin/inmuebles?status={$inactiveFilterValue}");
            $inactiveList->assertStatus(200);
            $inactiveList->assertSee('Roca 77');
            $inactiveList->assertSee('Inactivo');
            $inactiveList->assertSee('Baja: ' . $expectedDeletedDate);
            $inactiveList->assertSee($admin['user_name']);
            $inactiveList->assertDontSee('Belgrano 1250, 4°B');
            $inactiveList->assertDontSee('San Martín 450');
            $inactiveList->assertDontSee("admin/inmuebles/baja/{$toDeactivateId}");
            $inactiveList->assertDontSee("admin/inmuebles/editar/{$toDeactivateId}");
        }

        // Combining status=inactive with type, price range and owner filters
        $combinedInactive = $this->withSession($admin)->get(
            "admin/inmuebles?status=inactive&type=house&min_price=600000&max_price=700000&owner_id={$ownerId}"
        );
        $combinedInactive->assertStatus(200);
        $combinedInactive->assertSee('Roca 77');

        $nonMatchingInactive = $this->withSession($admin)->get('admin/inmuebles?status=inactive&type=office');
        $nonMatchingInactive->assertStatus(200);
        $nonMatchingInactive->assertDontSee('Roca 77');
        $nonMatchingInactive->assertSee('No se encontraron inmuebles con los filtros seleccionados.');

        // Owner and public catalogs never expose deactivated properties even if status=inactive is passed.
        $ownerSession = $this->sessionForUser($ownerId, 'owner');
        $ownerCatalog = $this->withSession($ownerSession)->get('propietario/inmuebles?status=inactive');
        $ownerCatalog->assertStatus(200);
        $ownerCatalog->assertDontSee('Roca 77');
        $ownerCatalog->assertSee('Seleccioná un estado válido.');

        $publicCatalog = $this->get('catalogo-inmuebles?status=inactive');
        $publicCatalog->assertStatus(200);
        $publicCatalog->assertSee('Belgrano 1250, 4°B');
        $publicCatalog->assertDontSee('Roca 77');
        $this->assertGreaterThan(0, $activeAvailableId);
        $this->assertGreaterThan(0, $activeMaintenanceId);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 6: Deactivated property cannot be linked to a new contract
    // ---------------------------------------------------------------------------------------------

    public function testScenario6DeactivatedPropertyIsExcludedFromContractSelectorAndRejectedOnDirectServerPost(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');
        $tenantId   = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu09@example.com');
        $activeId   = $this->insertProperty($ownerId, ['address' => 'Disponible Activo 100']);
        $inactiveId = $this->insertProperty($ownerId, ['address' => 'Inmueble Dado De Baja 200']);

        // Deactivate the property through the HU-09 endpoint
        $this->postWithSession($admin, "admin/inmuebles/baja/{$inactiveId}", ['confirmed' => '1'])
            ->assertRedirectTo('/admin/inmuebles');

        // Not offered as an option when creating a contract
        $contractForm = $this->withSession($admin)->get('admin/contratos/nuevo');
        $contractForm->assertStatus(200);
        $contractForm->assertSee('Disponible Activo 100');
        $contractForm->assertDontSee('Inmueble Dado De Baja 200');

        // Direct POST to create a contract with the deactivated property is rejected by the server
        $contractPost = $this->postWithSession($admin, 'admin/contratos', [
            'property_id'               => (string) $inactiveId,
            'tenant_id'                 => (string) $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '500000',
            'update_percentage'         => '10',
            'commission_percentage'     => '8',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ]);

        $contractPost->assertRedirectTo('/admin/contratos/nuevo');
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('errors')['property_id'] ?? null);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults(), 'No contract may be created for an inactive property.');
        $this->assertGreaterThan(0, $activeId);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 7: Historical records are preserved and consultable after logical deactivation
    // ---------------------------------------------------------------------------------------------

    public function testScenario7DeactivatingPropertyWithHistoricalContractsPreservesHistoryAndReferences(): void
    {
        $admin    = $this->sessionFor('admin');
        $ownerId  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');
        $tenantId = $this->createUser('tenant', 'Joaquín', 'Medina', '32148902', 'joaquin.medina.hu09@example.com');

        $propertyId = $this->insertProperty($ownerId, [
            'address' => 'Alberdi 305',
            'city'    => 'Cipolletti',
            'status'  => 'available',
        ]);

        // Historical (finished) contract with installment and payment
        $contractId = $this->insertContract($propertyId, $tenantId, $ownerId, [
            'start_date'     => '2024-01-01',
            'end_date'       => '2025-12-31',
            'status'         => 'finished',
            'closed_at'      => '2025-12-31',
            'closing_reason' => 'Finalización normal del plazo',
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => '2024-01-01',
            'due_date'    => '2024-01-10',
            'base_amount' => 650000.00,
            'late_fee'    => 0.00,
            'status'      => 'paid',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $installmentId = (int) $this->db->insertID();

        $this->db->table('payments')->insert([
            'installment_id'   => $installmentId,
            'amount'           => 650000.00,
            'paid_on'          => '2024-01-08',
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-HU09-001',
            'status'           => 'approved',
            'resolved_by'      => $admin['user_id'],
            'resolved_at'      => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $paymentId = (int) $this->db->insertID();

        // Deactivate the property (allowed because its contract is finished, not in force)
        $response = $this->postWithSession($admin, "admin/inmuebles/baja/{$propertyId}", ['confirmed' => '1']);
        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));

        // Verify contract, installment, and payment rows and foreign keys remain intact
        $contractRow = $this->db->table('contracts')->where('id', $contractId)->get()->getRow();
        $this->assertNotNull($contractRow);
        $this->assertSame($propertyId, (int) $contractRow->property_id);

        $installmentRow = $this->db->table('installments')->where('id', $installmentId)->get()->getRow();
        $this->assertNotNull($installmentRow);
        $this->assertSame($contractId, (int) $installmentRow->contract_id);

        $paymentRow = $this->db->table('payments')->where('id', $paymentId)->get()->getRow();
        $this->assertNotNull($paymentRow);
        $this->assertSame($installmentId, (int) $paymentRow->installment_id);

        // Historical contract file remains consultable and shows the deactivated property
        $contractShow = $this->withSession($admin)->get("admin/contratos/{$contractId}");
        $contractShow->assertStatus(200);
        $contractShow->assertSee('Alberdi 305');
        $contractShow->assertSee('Finalizado');
        $contractShow->assertSee('Dado de baja');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 8: Property with a contract in force cannot be deactivated
    // ---------------------------------------------------------------------------------------------

    public function testScenario8PropertyWithContractInForceCannotBeDeactivated(): void
    {
        $admin    = $this->sessionFor('admin');
        $ownerId  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');
        $tenantId = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu09@example.com');

        // Case A: Rented property with a contract in force
        $rentedPropertyId = $this->insertProperty($ownerId, [
            'address' => 'Mitre 845',
            'status'  => 'rented',
        ]);
        $this->insertContract($rentedPropertyId, $tenantId, $ownerId, ['status' => 'in_force']);

        // The listing renders the disabled button and the edit page hides the deactivation button
        $listResponse = $this->withSession($admin)->get('admin/inmuebles');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('No se puede dar de baja (Contrato vigente)');
        $listResponse->assertDontSee("admin/inmuebles/baja/{$rentedPropertyId}");

        $editResponse = $this->withSession($admin)->get("admin/inmuebles/editar/{$rentedPropertyId}");
        $editResponse->assertStatus(200);
        $editResponse->assertDontSee('Dar de baja inmueble');

        // Both unconfirmed and confirmed POST attempts are rejected with the active contract message
        foreach ([[], ['confirmed' => '1']] as $payload) {
            $attempt = $this->postWithSession($admin, "admin/inmuebles/baja/{$rentedPropertyId}", $payload);
            $attempt->assertRedirectTo('/admin/inmuebles');
            $this->assertSame(PropertyController::ACTIVE_CONTRACT_DEACTIVATION_MESSAGE, session()->getFlashdata('error'));
        }

        $rentedRow = $this->db->table('properties')->where('id', $rentedPropertyId)->get()->getRow();
        $this->assertNull($rentedRow->deleted_at);
        $this->assertNull($rentedRow->deleted_by);
        $this->assertSame('rented', $rentedRow->status);

        // Case B: Even if the property column says "available", an existing contract in_force still blocks deactivation
        $inconsistentPropertyId = $this->insertProperty($ownerId, [
            'address' => 'Moreno 560',
            'status'  => 'available',
        ]);
        $this->insertContract($inconsistentPropertyId, $tenantId, $ownerId, ['status' => 'in_force']);

        $attemptInconsistent = $this->postWithSession($admin, "admin/inmuebles/baja/{$inconsistentPropertyId}", ['confirmed' => '1']);
        $attemptInconsistent->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::ACTIVE_CONTRACT_DEACTIVATION_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull($this->db->table('properties')->where('id', $inconsistentPropertyId)->get()->getRow()->deleted_at);
    }

    // ---------------------------------------------------------------------------------------------
    // Error handling & Access control
    // ---------------------------------------------------------------------------------------------

    public function testRejectsAlreadyDeactivatedNonExistentMissingCsrfAndUnauthorizedRequests(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu09@example.com');

        // Case A: Already deactivated property is rejected without altering original deleted_at / deleted_by
        $alreadyDeletedId = $this->insertProperty($ownerId, [
            'address'    => 'Propiedad Ya Baja',
            'deleted_at' => '2026-10-01 10:00:00',
            'deleted_by' => $admin['user_id'],
        ]);

        $alreadyDeletedResponse = $this->postWithSession($admin, "admin/inmuebles/baja/{$alreadyDeletedId}", ['confirmed' => '1']);
        $alreadyDeletedResponse->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_ALREADY_DEACTIVATED_MESSAGE, session()->getFlashdata('error'));

        $alreadyRow = $this->db->table('properties')->where('id', $alreadyDeletedId)->get()->getRow();
        $this->assertSame('2026-10-01 10:00:00', $alreadyRow->deleted_at);
        $this->assertSame($admin['user_id'], (int) $alreadyRow->deleted_by);

        // Case B: Non-existent property ID
        $notFoundResponse = $this->postWithSession($admin, 'admin/inmuebles/baja/999999', ['confirmed' => '1']);
        $notFoundResponse->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));

        // Case C: External roles (owner, tenant) cannot deactivate properties
        $activePropertyId = $this->insertProperty($ownerId, ['address' => 'Propiedad Protegida']);

        foreach (['owner' => '/propietario/dashboard', 'tenant' => '/inquilino/estado-cuenta'] as $role => $redirectUrl) {
            $externalSession = $this->sessionFor($role);
            $forbidden       = $this->postWithSession($externalSession, "admin/inmuebles/baja/{$activePropertyId}", ['confirmed' => '1']);
            $forbidden->assertRedirectTo($redirectUrl);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        }

        // Case D: Unauthenticated visitor is redirected to login
        $_SESSION        = [];
        $visitorResponse = $this->postWithSession([], "admin/inmuebles/baja/{$activePropertyId}", ['confirmed' => '1']);
        $visitorResponse->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));

        $protectedRow = $this->db->table('properties')->where('id', $activePropertyId)->get()->getRow();
        $this->assertNull($protectedRow->deleted_at);
        $this->assertNull($protectedRow->deleted_by);

        // Case E: Database error / non-existent actor rolls back atomically leaving deleted_at and deleted_by null
        $modelResult = model(PropertyModel::class)->deactivate($activePropertyId, 999999);
        $this->assertFalse($modelResult['ok']);
        $this->assertSame(PropertyModel::ERROR_PERSISTENCE, $modelResult['error']);
        $afterFailedTx = $this->db->table('properties')->where('id', $activePropertyId)->get()->getRow();
        $this->assertNull($afterFailedTx->deleted_at);
        $this->assertNull($afterFailedTx->deleted_by);

        // Case F: POST without CSRF token is rejected
        $this->expectException(SecurityException::class);
        $this->withSession($admin)->post("admin/inmuebles/baja/{$activePropertyId}", ['confirmed' => '1']);
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionFor(string $roleName): array
    {
        $this->userSequence++;
        $email  = "{$roleName}.session.hu09.{$this->userSequence}@example.com";
        $userId = $this->createUser(
            $roleName,
            'Operador',
            ucfirst($roleName),
            (string) (30000000 + $this->userSequence),
            $email
        );

        return $this->sessionForUser($userId, $roleName);
    }

    /**
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionForUser(int $userId, string $roleName): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertIsArray($user);

        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => (string) $user['email'],
            'user_name'    => trim($user['first_name'] . ' ' . $user['last_name']),
            'user_role'    => $roleName,
        ];
    }

    private function createUser(
        string $roleName,
        string $firstName,
        string $lastName,
        string $documentNumber,
        string $email,
        ?string $deletedAt = null
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => $documentNumber,
            'email'           => $email,
            'phone'           => '2994000000',
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert(array_merge([
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => 4,
            'surface'           => 120.50,
            'included_services' => 'Agua',
            'base_rent'         => 650000.00,
            'description'       => 'Casa con jardín',
            'status'            => 'available',
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertContract(int $propertyId, int $tenantId, int $ownerId, array $overrides = []): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('contracts')->insert(array_merge([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => 650000.00,
            'update_percentage'         => 10.00,
            'due_day'                   => 10,
            'commission_percentage'     => 8.00,
            'late_fee_daily_percentage' => 0.50,
            'status'                    => 'in_force',
            'created_at'                => $now,
            'updated_at'                => $now,
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed>  $sessionData
     * @param array<string, string> $postData
     */
    private function postWithSession(array $sessionData, string $uri, array $postData)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, array_merge($postData, [$tokenName => $tokenHash]));
    }
}
