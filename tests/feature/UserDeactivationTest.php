<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Automated acceptance and security tests for HU-05: Logical user deactivation (soft delete).
 *
 * @internal
 */
final class UserDeactivationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testScenario1AdminDeactivatesActiveUserPersistsSoftDeleteAndBlocksLoginAndSession(): void
    {
        $adminSession  = $this->createAdminSession();
        $plainPassword = 'ClaveInquilino2026!';
        $tenantEmail   = 'inquilino.activo.hu05@example.com';

        $tenantId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Lucía',
            lastName: 'Paredes',
            documentNumber: '38712945',
            email: $tenantEmail,
            plainPassword: $plainPassword
        );

        // Verify directory and edit views expose the deactivation trigger and modal
        $listBefore = $this->withSession($adminSession)->get('admin/usuarios');
        $listBefore->assertStatus(200);
        $listBefore->assertSee('Lucía Paredes');
        $listBefore->assertSee('admin/usuarios/baja/' . $tenantId);
        $listBefore->assertSee('No se puede dar de baja (Usuario propio)');

        $editBefore = $this->withSession($adminSession)->get("admin/usuarios/editar/{$tenantId}");
        $editBefore->assertStatus(200);
        $editBefore->assertSee('Dar de baja usuario');
        $editBefore->assertSee('admin/usuarios/baja/' . $tenantId);

        // Execute logical deactivation
        $deactivateResponse = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/baja/{$tenantId}",
            ['confirmed' => '1']
        );

        $deactivateResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));

        // Physical record is preserved in database with non-null deleted_at
        $persistedRow = $this->db->table('users')->where('id', $tenantId)->get()->getRow();
        $this->assertNotNull($persistedRow);
        $this->assertSame('Lucía', $persistedRow->first_name);
        $this->assertSame($tenantEmail, $persistedRow->email);
        $this->assertNotNull($persistedRow->deleted_at);

        // Hidden by default in active listing, visible as "Dado de baja" when include_deleted=1
        $listAfter = $this->withSession($adminSession)->get('admin/usuarios');
        $listAfter->assertStatus(200);
        $listAfter->assertDontSee($tenantEmail);

        $listWithDeleted = $this->withSession($adminSession)->get('admin/usuarios?include_deleted=1');
        $listWithDeleted->assertStatus(200);
        $listWithDeleted->assertSee($tenantEmail);
        $listWithDeleted->assertSee('Dado de baja');

        // Deactivated user can no longer log in with valid credentials
        $_SESSION      = [];
        $loginResponse = $this->postWithCsrf('auth/login', [
            'email'    => $tenantEmail,
            'password' => $plainPassword,
        ]);

        $loginResponse->assertRedirectTo('/auth/login');
        $this->assertSame(AuthController::ACCOUNT_DISABLED_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull(session()->get('is_logged_in'));

        // Any pre-existing session of the deactivated user is immediately revoked on the next request
        $openTenantSession = [
            'is_logged_in' => true,
            'user_id'      => $tenantId,
            'user_email'   => $tenantEmail,
            'user_name'    => 'Lucía Paredes',
            'user_role'    => 'tenant',
        ];

        $protectedResponse = $this->withSession($openTenantSession)->get('inquilino/estado-cuenta');
        $protectedResponse->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::ACCOUNT_DISABLED_MESSAGE, session()->getFlashdata('error'));
    }

    public function testScenario1DeactivationNeedsServerSideConfirmationEvenWithoutJavaScript(): void
    {
        $adminSession = $this->createAdminSession();
        $tenantId     = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Nora',
            lastName: 'Ibáñez',
            documentNumber: '37123456',
            email: 'nora.ibanez.hu05@example.com',
            plainPassword: 'Password123!'
        );

        // The list/edit forms post confirmed=0 until the in-page modal is accepted (and always without JavaScript).
        foreach ([[], ['confirmed' => '0'], ['confirmed' => 'yes']] as $payload) {
            $response = $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/baja/{$tenantId}", $payload);

            // A confirmation page is rendered instead of deactivating the user.
            $response->assertStatus(200);
            $response->assertSee('¿Confirmás la baja lógica del usuario?');
            $response->assertSee('Nora Ibáñez');
            $response->assertSee('admin/usuarios/baja/' . $tenantId);
            $response->assertSeeElement('input[name=confirmed]');
            $this->assertNull(
                $this->db->table('users')->where('id', $tenantId)->get()->getRow()->deleted_at,
                'The user must stay active until the confirmation is sent.'
            );
        }

        // Sending the confirmation performs the logical deactivation.
        $confirmed = $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/baja/{$tenantId}", ['confirmed' => '1']);
        $confirmed->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));
        $this->assertNotNull($this->db->table('users')->where('id', $tenantId)->get()->getRow()->deleted_at);
    }

    public function testScenario2DeactivatedUserIsExcludedFromSelectableOptionsInNewOperations(): void
    {
        $adminSession = $this->createAdminSession();

        $activeTenantId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Camila',
            lastName: 'Rojas',
            documentNumber: '39421084',
            email: 'camila.activa.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $deactivatedTenantId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Tomás',
            lastName: 'Aguirre',
            documentNumber: '29834110',
            email: 'tomas.baja.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $activeOwnerId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Ricardo',
            lastName: 'Sosa',
            documentNumber: '24118905',
            email: 'ricardo.activo.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $deactivatedOwnerId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Hernán',
            lastName: 'Paz',
            documentNumber: '28349880',
            email: 'hernan.baja.hu05@example.com',
            plainPassword: 'Password123!'
        );

        // Logically deactivate one tenant and one owner through the controller endpoint
        $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/baja/{$deactivatedTenantId}", ['confirmed' => '1'])
            ->assertRedirectTo('/admin/usuarios');

        $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/baja/{$deactivatedOwnerId}", ['confirmed' => '1'])
            ->assertRedirectTo('/admin/usuarios');

        $userModel = model(UserModel::class);

        $selectableTenants   = $userModel->findActiveTenantsForContract();
        $selectableTenantIds = array_map(static fn (array $row): int => (int) $row['id'], $selectableTenants);

        $this->assertContains($activeTenantId, $selectableTenantIds);
        $this->assertNotContains($deactivatedTenantId, $selectableTenantIds);

        $selectableOwners   = $userModel->findActiveOwnersForProperty();
        $selectableOwnerIds = array_map(static fn (array $row): int => (int) $row['id'], $selectableOwners);

        $this->assertContains($activeOwnerId, $selectableOwnerIds);
        $this->assertNotContains($deactivatedOwnerId, $selectableOwnerIds);
    }

    public function testScenario3DeactivationPreservesAssociatedContractsInstallmentsAndPaymentsForAudit(): void
    {
        $adminSession = $this->createAdminSession();

        $ownerId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Marta',
            lastName: 'Quiroga',
            documentNumber: '21650312',
            email: 'marta.quiroga.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $tenantId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Joaquín',
            lastName: 'Medina',
            documentNumber: '32148902',
            email: 'joaquin.medina.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $now = date('Y-m-d H:i:s');
        $this->db->table('properties')->insert([
            'owner_id'   => $ownerId,
            'address'    => 'Mitre 845, Cipolletti',
            'city'       => 'Cipolletti',
            'type'       => 'house',
            'rooms'      => 4,
            'base_rent'  => 650000.00,
            'status'     => 'rented',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2025-03-01',
            'end_date'                  => '2027-02-28',
            'monthly_rent'              => 650000.00,
            'update_percentage'         => 12.00,
            'due_day'                   => 10,
            'commission_percentage'     => 8.00,
            'late_fee_daily_percentage' => 0.50,
            'status'                    => 'in_force',
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);
        $contractId = (int) $this->db->insertID();

        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => '2025-03-01',
            'due_date'    => '2025-03-10',
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
            'paid_on'          => '2025-03-09',
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-HU05-001',
            'status'           => 'approved',
            'resolved_by'      => $adminSession['user_id'],
            'resolved_at'      => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $paymentId = (int) $this->db->insertID();

        // Confirm logical deactivation of the tenant with active historical records
        $deactivateResponse = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/baja/{$tenantId}",
            ['confirmed' => '1']
        );

        $deactivateResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_DEACTIVATED_MESSAGE, session()->getFlashdata('success'));

        // Verify contract, installment, and payment rows and foreign keys remain intact
        $contractRow = $this->db->table('contracts')->where('id', $contractId)->get()->getRow();
        $this->assertNotNull($contractRow);
        $this->assertSame($tenantId, (int) $contractRow->tenant_id);
        $this->assertSame($ownerId, (int) $contractRow->owner_id);

        $installmentRow = $this->db->table('installments')->where('id', $installmentId)->get()->getRow();
        $this->assertNotNull($installmentRow);
        $this->assertSame($contractId, (int) $installmentRow->contract_id);

        $paymentRow = $this->db->table('payments')->where('id', $paymentId)->get()->getRow();
        $this->assertNotNull($paymentRow);
        $this->assertSame($installmentId, (int) $paymentRow->installment_id);

        // Verify audit detail view still displays the deactivated user and their operational history
        $auditResponse = $this->withSession($adminSession)->get("admin/usuarios/editar/{$tenantId}");
        $auditResponse->assertStatus(200);
        $auditResponse->assertSee('Joaquín Medina');
        $auditResponse->assertSee('Dado de baja');
        $auditResponse->assertSee('Fecha de baja lógica');
        $auditResponse->assertSee('#CTO-' . $contractId);
        $auditResponse->assertSee('Mitre 845, Cipolletti');
        $auditResponse->assertDontSee('Dar de baja usuario');
    }

    public function testRejectsSelfDeactivationAlreadyDeactivatedNonExistentAndUnauthorizedRequests(): void
    {
        $adminSession = $this->createAdminSession();
        $adminId      = $adminSession['user_id'];

        // Case A: Administrator cannot deactivate their own account
        $selfResponse = $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/baja/{$adminId}", []);
        $selfResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::SELF_DEACTIVATION_FORBIDDEN_MESSAGE, session()->getFlashdata('error'));

        $adminRow = $this->db->table('users')->where('id', $adminId)->get()->getRow();
        $this->assertNotNull($adminRow);
        $this->assertNull($adminRow->deleted_at);

        // Case B: Deactivating an already deactivated user is rejected with informative message
        $alreadyDeletedId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Elena',
            lastName: 'Arias',
            documentNumber: '36550819',
            email: 'elena.baja.hu05@example.com',
            plainPassword: 'Password123!',
            deletedAt: '2026-10-20 11:00:00'
        );

        $alreadyDeletedResponse = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/baja/{$alreadyDeletedId}",
            []
        );
        $alreadyDeletedResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_ALREADY_DEACTIVATED_MESSAGE, session()->getFlashdata('error'));

        // Case C: Non-existent user ID returns not-found message
        $notFoundResponse = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios/baja/999999', []);
        $notFoundResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));

        // Case D: Non-administrator role (employee) cannot deactivate users
        $targetUserId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Propietario',
            lastName: 'Protegido',
            documentNumber: '25111222',
            email: 'propietario.protegido.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $employeeId = $this->insertUserRecord(
            roleName: 'employee',
            firstName: 'Empleado',
            lastName: 'Operador',
            documentNumber: '31222333',
            email: 'empleado.hu05@example.com',
            plainPassword: 'Password123!'
        );

        $employeeSession = [
            'is_logged_in' => true,
            'user_id'      => $employeeId,
            'user_email'   => 'empleado.hu05@example.com',
            'user_name'    => 'Empleado Operador',
            'user_role'    => 'employee',
        ];

        $forbiddenResponse = $this->postAsSessionWithCsrf(
            $employeeSession,
            "admin/usuarios/baja/{$targetUserId}",
            []
        );
        $forbiddenResponse->assertRedirectTo('/admin/dashboard');
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $protectedRow = $this->db->table('users')->where('id', $targetUserId)->get()->getRow();
        $this->assertNotNull($protectedRow);
        $this->assertNull($protectedRow->deleted_at);

        // Case E: Unauthenticated visitor is redirected to login
        $_SESSION        = [];
        $visitorResponse = $this->postWithCsrf("admin/usuarios/baja/{$targetUserId}", []);
        $visitorResponse->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
    }

    /**
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function createAdminSession(): array
    {
        $adminId = $this->insertUserRecord(
            roleName: 'admin',
            firstName: 'Laura',
            lastName: 'Benítez',
            documentNumber: '27000999',
            email: 'admin.test.hu05@example.com',
            plainPassword: 'AdminPassword2026!'
        );

        return [
            'is_logged_in' => true,
            'user_id'      => $adminId,
            'user_email'   => 'admin.test.hu05@example.com',
            'user_name'    => 'Laura Benítez',
            'user_role'    => 'admin',
        ];
    }

    /**
     * @param array<string, mixed>  $sessionData
     * @param array<string, string> $postData
     */
    private function postAsSessionWithCsrf(array $sessionData, string $uri, array $postData)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [
            $tokenName => $tokenHash,
        ]))->post($uri, array_merge($postData, [
            $tokenName => $tokenHash,
        ]));
    }

    /**
     * @param array<string, string> $postData
     */
    private function postWithCsrf(string $uri, array $postData)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession([
            $tokenName => $tokenHash,
        ])->post($uri, array_merge($postData, [
            $tokenName => $tokenHash,
        ]));
    }

    private function insertUserRecord(
        string $roleName,
        string $firstName,
        string $lastName,
        string $documentNumber,
        string $email,
        string $plainPassword,
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
            'password_hash'   => password_hash($plainPassword, PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }
}

