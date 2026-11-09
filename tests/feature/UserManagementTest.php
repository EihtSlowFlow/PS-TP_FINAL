<?php

use App\Controllers\UserController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance and security tests for HU-04: System user management.
 *
 * @internal
 */
final class UserManagementTest extends CIUnitTestCase
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function allSystemRolesProvider(): array
    {
        return [
            'create administrator' => ['admin', 'Administrador'],
            'create employee'      => ['employee', 'Empleado'],
            'create owner'         => ['owner', 'Propietario'],
            'create tenant'        => ['tenant', 'Inquilino'],
        ];
    }

    #[DataProvider('allSystemRolesProvider')]
    public function testScenario1AdminCreatesUserWithAnyRoleAndEncryptedPassword(
        string $targetRole,
        string $expectedLabel
    ): void {
        $adminSession = $this->createAdminSession();
        $email        = "nuevo.{$targetRole}.hu04@example.com";
        $dni          = (string) (38100000 + crc32($targetRole) % 899999);
        $plainPass    = 'ClaveInicial2026!';

        $createResponse = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios', [
            'first_name'      => 'Valeria',
            'last_name'       => 'Méndez',
            'document_number' => $dni,
            'email'           => $email,
            'phone'           => '2994123456',
            'address'         => 'San Martín 450, Cipolletti',
            'role'            => $targetRole,
            'password'        => $plainPass,
        ]);

        $createResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_CREATED_MESSAGE, session()->getFlashdata('success'));

        $createdUser = $this->db->table('users')
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $email)
            ->get()
            ->getRow();

        $this->assertNotNull($createdUser);
        $this->assertSame('Valeria', $createdUser->first_name);
        $this->assertSame('Méndez', $createdUser->last_name);
        $this->assertSame($dni, $createdUser->document_number);
        $this->assertSame($targetRole, $createdUser->role_name);
        $this->assertNotSame($plainPass, $createdUser->password_hash);
        $this->assertTrue(password_verify($plainPass, $createdUser->password_hash));

        $listResponse = $this->withSession($adminSession)->get('admin/usuarios');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Valeria Méndez');
        $listResponse->assertSee($email);
        $listResponse->assertSee($expectedLabel);
    }

    public function testScenario2RejectsDuplicateEmailOrDniAmongActiveUsersAndAllowsReuseFromDeactivatedUser(): void
    {
        $adminSession = $this->createAdminSession();

        $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Ricardo',
            lastName: 'Sosa',
            documentNumber: '24118905',
            email: 'ricardo.sosa.hu04@example.com',
            plainPassword: 'Password123!'
        );

        // Case A: Duplicate active email
        $dupEmailResponse = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios', [
            'first_name'      => 'Otro',
            'last_name'       => 'Usuario',
            'document_number' => '39999111',
            'email'           => 'ricardo.sosa.hu04@example.com',
            'phone'           => '2995000111',
            'address'         => 'Calle 1',
            'role'            => 'tenant',
            'password'        => 'Password123!',
        ]);

        $dupEmailResponse->assertRedirectTo('/admin/usuarios/nuevo');
        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertSame(UserController::DUPLICATE_EMAIL_MESSAGE, $errors['email'] ?? null);
        $this->assertNull(old('password'));

        // Case B: Duplicate active DNI (submitted with dots)
        $dupDniResponse = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios', [
            'first_name'      => 'Otro',
            'last_name'       => 'Documento',
            'document_number' => '24.118.905',
            'email'           => 'unico.hu04@example.com',
            'phone'           => '2995000222',
            'address'         => 'Calle 2',
            'role'            => 'tenant',
            'password'        => 'Password123!',
        ]);

        $dupDniResponse->assertRedirectTo('/admin/usuarios/nuevo');
        $errorsDni = session()->getFlashdata('errors');
        $this->assertIsArray($errorsDni);
        $this->assertSame(UserController::DUPLICATE_DNI_MESSAGE, $errorsDni['document_number'] ?? null);

        // Case C: Email and DNI belonging only to a soft-deleted user can be registered again
        $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Baja',
            lastName: 'Previa',
            documentNumber: '29777888',
            email: 'baja.previa.hu04@example.com',
            plainPassword: 'Password123!',
            deletedAt: '2026-09-15 10:00:00'
        );

        $recreateResponse = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios', [
            'first_name'      => 'Baja',
            'last_name'       => 'Reactivada',
            'document_number' => '29777888',
            'email'           => 'baja.previa.hu04@example.com',
            'phone'           => '2995000333',
            'address'         => 'Calle 3',
            'role'            => 'tenant',
            'password'        => 'NuevaClave123!',
        ]);

        $recreateResponse->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_CREATED_MESSAGE, session()->getFlashdata('success'));
    }

    public function testScenario3ListsSearchesFiltersByRoleAndPaginatesUsers(): void
    {
        $adminSession = $this->createAdminSession();

        $this->insertUserRecord('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu04@example.com', 'Clave1234!');
        $this->insertUserRecord('tenant', 'Joaquín', 'Medina', '32148902', 'joaquin.medina.hu04@example.com', 'Clave1234!');
        $this->insertUserRecord('employee', 'Esteban', 'Paz', '28349880', 'esteban.paz.hu04@example.com', 'Clave1234!');

        // Search by name
        $searchByName = $this->withSession($adminSession)->get('admin/usuarios?q=Marta+Quiroga');
        $searchByName->assertStatus(200);
        $searchByName->assertSee('marta.quiroga.hu04@example.com');
        $searchByName->assertDontSee('joaquin.medina.hu04@example.com');

        // Search by formatted DNI
        $searchByDni = $this->withSession($adminSession)->get('admin/usuarios?q=32.148.902');
        $searchByDni->assertStatus(200);
        $searchByDni->assertSee('Joaquín Medina');
        $searchByDni->assertDontSee('Marta Quiroga');

        // Filter by role
        $filterByRole = $this->withSession($adminSession)->get('admin/usuarios?role=employee');
        $filterByRole->assertStatus(200);
        $filterByRole->assertSee('Esteban Paz');
        $filterByRole->assertDontSee('Marta Quiroga');
        $filterByRole->assertDontSee('Joaquín Medina');

        // Pagination (per_page=2 across 4 active users: 1 admin + 3 inserted)
        $page1 = $this->withSession($adminSession)->get('admin/usuarios?per_page=2&page=1');
        $page1->assertStatus(200);
        $page1->assertSee('Mostrando');
        $page1->assertSee('1–2');

        $page2 = $this->withSession($adminSession)->get('admin/usuarios?per_page=2&page=2');
        $page2->assertStatus(200);
        $page2->assertSee('3–4');
    }

    public function testScenario4UpdatesUserContactAndRolePreservingPasswordWhenBlankAndUpdatingWhenProvided(): void
    {
        $adminSession = $this->createAdminSession();
        $userId       = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Camila',
            lastName: 'Rojas',
            documentNumber: '39421084',
            email: 'camila.rojas.hu04@example.com',
            plainPassword: 'ClaveOriginal2026!'
        );

        $beforeUpdate = $this->db->table('users')->where('id', $userId)->get()->getRow();
        $this->assertNotNull($beforeUpdate);
        $originalHash = (string) $beforeUpdate->password_hash;

        // Case A: Leave password blank -> updates profile & role without altering password_hash
        $updateBlankPass = $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/editar/{$userId}", [
            'first_name'      => 'Camila Andrea',
            'last_name'       => 'Rojas',
            'document_number' => '39421084',
            'email'           => 'camila.rojas.hu04@example.com',
            'phone'           => '2996112233',
            'address'         => 'Belgrano 1250 4B, Neuquén',
            'role'            => 'owner',
            'password'        => '',
        ]);

        $updateBlankPass->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_UPDATED_MESSAGE, session()->getFlashdata('success'));

        $afterBlankUpdate = $this->db->table('users')
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->get()
            ->getRow();

        $this->assertNotNull($afterBlankUpdate);
        $this->assertSame('Camila Andrea', $afterBlankUpdate->first_name);
        $this->assertSame('2996112233', $afterBlankUpdate->phone);
        $this->assertSame('Belgrano 1250 4B, Neuquén', $afterBlankUpdate->address);
        $this->assertSame('owner', $afterBlankUpdate->role_name);
        $this->assertSame($originalHash, $afterBlankUpdate->password_hash);

        // Case B: Provide a new password -> updates password_hash encrypted
        $updateNewPass = $this->postAsSessionWithCsrf($adminSession, "admin/usuarios/editar/{$userId}", [
            'first_name'      => 'Camila Andrea',
            'last_name'       => 'Rojas',
            'document_number' => '39421084',
            'email'           => 'camila.rojas.hu04@example.com',
            'phone'           => '2996112233',
            'address'         => 'Belgrano 1250 4B, Neuquén',
            'role'            => 'owner',
            'password'        => 'NuevaClaveSegura99!',
        ]);

        $updateNewPass->assertRedirectTo('/admin/usuarios');
        $afterNewPass = $this->db->table('users')->where('id', $userId)->get()->getRow();
        $this->assertNotNull($afterNewPass);
        $this->assertNotSame($originalHash, $afterNewPass->password_hash);
        $this->assertTrue(password_verify('NuevaClaveSegura99!', $afterNewPass->password_hash));
    }

    public function testScenario5IncludesSoftDeletedUsersOnlyWhenRequestedAndPreservesConsultableHistory(): void
    {
        $adminSession = $this->createAdminSession();

        $ownerId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Jorge',
            lastName: 'Dueño',
            documentNumber: '20555111',
            email: 'jorge.dueno.hu04@example.com',
            plainPassword: 'Password123!'
        );

        $deletedTenantId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Ana',
            lastName: 'Vega',
            documentNumber: '29888999',
            email: 'ana.vega.baja.hu04@example.com',
            plainPassword: 'Password123!',
            deletedAt: '2026-10-05 14:30:00'
        );

        // Create associated historical records (property -> contract -> installment -> payment)
        $now = date('Y-m-d H:i:s');
        $this->db->table('properties')->insert([
            'owner_id'   => $ownerId,
            'address'    => 'Alberdi 305, Cipolletti',
            'city'       => 'Cipolletti',
            'type'       => 'apartment',
            'rooms'      => 2,
            'base_rent'  => 350000.00,
            'status'     => 'available',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $deletedTenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2025-01-01',
            'end_date'                  => '2025-12-31',
            'monthly_rent'              => 350000.00,
            'update_percentage'         => 10.00,
            'due_day'                   => 10,
            'commission_percentage'     => 8.00,
            'late_fee_daily_percentage' => 0.50,
            'status'                    => 'finished',
            'closed_at'                 => '2025-12-31',
            'closing_reason'            => 'Vencimiento normal del plazo contractual',
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);
        $contractId = (int) $this->db->insertID();

        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => '2025-01-01',
            'due_date'    => '2025-01-10',
            'base_amount' => 350000.00,
            'late_fee'    => 0.00,
            'status'      => 'paid',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $installmentId = (int) $this->db->insertID();

        $this->db->table('payments')->insert([
            'installment_id'   => $installmentId,
            'amount'           => 350000.00,
            'paid_on'          => '2025-01-08',
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-00091',
            'status'           => 'approved',
            'resolved_by'      => $adminSession['user_id'],
            'resolved_at'      => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // Without include_deleted: soft-deleted user is hidden
        $defaultList = $this->withSession($adminSession)->get('admin/usuarios');
        $defaultList->assertStatus(200);
        $defaultList->assertDontSee('ana.vega.baja.hu04@example.com');
        $defaultList->assertSee('jorge.dueno.hu04@example.com');

        // With include_deleted=1: soft-deleted user is shown and marked as "Dado de baja"
        $withDeletedList = $this->withSession($adminSession)->get('admin/usuarios?include_deleted=1');
        $withDeletedList->assertStatus(200);
        $withDeletedList->assertSee('ana.vega.baja.hu04@example.com');
        $withDeletedList->assertSee('Dado de baja');

        // Visiting edit/detail screen of soft-deleted user shows their data and associated records
        $detailResponse = $this->withSession($adminSession)->get("admin/usuarios/editar/{$deletedTenantId}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Ana Vega');
        $detailResponse->assertSee('Dado de baja');
        $detailResponse->assertSee('#CTO-' . $contractId);
        $detailResponse->assertSee('Alberdi 305, Cipolletti');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nonAdminRolesProvider(): array
    {
        return [
            'employee is blocked from user management' => ['employee', '/admin/dashboard'],
            'owner is blocked from user management'    => ['owner', '/propietario/dashboard'],
            'tenant is blocked from user management'   => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('nonAdminRolesProvider')]
    public function testNonAdminRolesCannotAccessAnyUserManagementRoute(
        string $roleName,
        string $expectedRedirect
    ): void {
        $userId = $this->insertUserRecord(
            roleName: $roleName,
            firstName: 'Usuario',
            lastName: ucfirst($roleName),
            documentNumber: (string) (40100000 + crc32($roleName) % 99999),
            email: "blocked.{$roleName}.hu04@example.com",
            plainPassword: 'Password123!'
        );

        $session = [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => "blocked.{$roleName}.hu04@example.com",
            'user_name'    => 'Usuario ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ];

        $getList = $this->withSession($session)->get('admin/usuarios');
        $getList->assertRedirectTo($expectedRedirect);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $getCreate = $this->withSession($session)->get('admin/usuarios/nuevo');
        $getCreate->assertRedirectTo($expectedRedirect);

        $postCreate = $this->postAsSessionWithCsrf($session, 'admin/usuarios', [
            'first_name' => 'Intento',
        ]);
        $postCreate->assertRedirectTo($expectedRedirect);
    }

    public function testUnauthenticatedVisitorIsRedirectedToLoginOnUserRoutes(): void
    {
        $getCreate = $this->get('admin/usuarios/nuevo');
        $getCreate->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));

        $getEdit = $this->get('admin/usuarios/editar/1');
        $getEdit->assertRedirectTo('/auth/login');
    }

    public function testCreateRejectsInvalidFieldsOnTheServerAndKeepsTheInput(): void
    {
        $adminSession = $this->createAdminSession();
        $usersBefore  = $this->db->table('users')->countAllResults();

        $response = $this->postAsSessionWithCsrf($adminSession, 'admin/usuarios', [
            'first_name'      => '',
            'last_name'       => 'X',
            'document_number' => '12',
            'email'           => 'no-es-un-email',
            'phone'           => '123',
            'address'         => '',
            'role'            => 'superuser',
            'password'        => 'abc',
        ]);

        $response->assertRedirectTo('/admin/usuarios/nuevo');
        $this->assertSame(UserController::VALIDATION_ERROR_MESSAGE, session()->getFlashdata('error'));

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        foreach (['first_name', 'last_name', 'document_number', 'email', 'phone', 'role', 'password'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Missing validation error for {$field}");
        }

        $this->assertSame('no-es-un-email', old('email'));
        $this->assertNull(old('password'));
        $this->assertSame($usersBefore, $this->db->table('users')->countAllResults());
    }

    public function testUserFormKeepsNativeBrowserValidationEnabled(): void
    {
        $adminSession = $this->createAdminSession();

        $response = $this->withSession($adminSession)->get('admin/usuarios/nuevo');

        $response->assertStatus(200);
        $response->assertDontSee('novalidate');
        $response->assertSee('minlength="8"');
    }

    public function testSoleActiveAdministratorCannotLoseTheAdministratorRoleOnEdit(): void
    {
        $adminSession = $this->createAdminSession();
        $adminId      = $adminSession['user_id'];

        // A deactivated administrator must not count as an available administrator.
        $this->insertUserRecord(
            roleName: 'admin',
            firstName: 'Admin',
            lastName: 'Retirado',
            documentNumber: '26111222',
            email: 'admin.retirado.hu04@example.com',
            plainPassword: 'Password123!',
            deletedAt: '2026-09-01 09:00:00'
        );

        // Case A: self-demotion of the only active administrator is rejected on the server.
        $demotion = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/editar/{$adminId}",
            $this->adminFormPayload('Laura', 'Benítez', '27000999', 'admin.test.hu04@example.com', 'employee')
        );

        $demotion->assertRedirectTo("/admin/usuarios/editar/{$adminId}");
        $this->assertSame(UserController::LAST_ADMIN_ROLE_CHANGE_MESSAGE, session()->getFlashdata('error'));
        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertSame(UserController::LAST_ADMIN_ROLE_CHANGE_MESSAGE, $errors['role'] ?? null);
        $this->assertSame('admin', $this->roleNameOf($adminId));

        // Case B: the same administrator can still edit other data while keeping the role.
        $contactUpdate = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/editar/{$adminId}",
            $this->adminFormPayload('Laura', 'Benítez', '27000999', 'admin.test.hu04@example.com', 'admin', '2995999888')
        );

        $contactUpdate->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_UPDATED_MESSAGE, session()->getFlashdata('success'));
        $this->assertSame('admin', $this->roleNameOf($adminId));
        $this->assertSame(
            '2995999888',
            $this->db->table('users')->where('id', $adminId)->get()->getRow()->phone
        );
    }

    public function testAdministratorRoleCanBeRemovedWhileAnotherActiveAdministratorRemains(): void
    {
        $adminSession = $this->createAdminSession();
        $adminId      = $adminSession['user_id'];

        $secondAdminId = $this->insertUserRecord(
            roleName: 'admin',
            firstName: 'Segundo',
            lastName: 'Administrador',
            documentNumber: '26333444',
            email: 'segundo.admin.hu04@example.com',
            plainPassword: 'Password123!'
        );

        $thirdAdminId = $this->insertUserRecord(
            roleName: 'admin',
            firstName: 'Tercer',
            lastName: 'Administrador',
            documentNumber: '26555666',
            email: 'tercer.admin.hu04@example.com',
            plainPassword: 'Password123!'
        );

        // Demoting a different administrator is allowed while others remain active.
        $demoteSecond = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/editar/{$secondAdminId}",
            $this->adminFormPayload('Segundo', 'Administrador', '26333444', 'segundo.admin.hu04@example.com', 'employee')
        );
        $demoteSecond->assertRedirectTo('/admin/usuarios');
        $this->assertSame('employee', $this->roleNameOf($secondAdminId));

        // Self-demotion is allowed (behind the client-side confirmation) when another administrator remains.
        $demoteSelf = $this->postAsSessionWithCsrf(
            $adminSession,
            "admin/usuarios/editar/{$adminId}",
            $this->adminFormPayload('Laura', 'Benítez', '27000999', 'admin.test.hu04@example.com', 'employee')
        );
        $demoteSelf->assertRedirectTo('/admin/usuarios');
        $this->assertSame(UserController::USER_UPDATED_MESSAGE, session()->getFlashdata('success'));
        $this->assertSame('employee', $this->roleNameOf($adminId));
        $this->assertSame('admin', $this->roleNameOf($thirdAdminId));
    }

    public function testRoleSelectorMarkupKeepsLabelContentValidForHtml5(): void
    {
        $adminSession = $this->createAdminSession();
        $userId       = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Valeria',
            lastName: 'Ortiz',
            documentNumber: '40222333',
            email: 'valeria.ortiz.hu04@example.com',
            plainPassword: 'Password123!'
        );

        foreach (['admin/usuarios/nuevo', "admin/usuarios/editar/{$userId}"] as $uri) {
            $response = $this->withSession($adminSession)->get($uri);
            $response->assertStatus(200);
            // Sanity check: the role cards are rendered as labels.
            $response->assertSeeElement('label.role-option-card');
            // HTML5: <label> only accepts phrasing content, so no block-level descendants.
            $this->assertSame([], $this->flowElementsInsideLabels((string) $response->getBody()), $uri);
        }
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
            email: 'admin.test.hu04@example.com',
            plainPassword: 'AdminPassword2026!'
        );

        return [
            'is_logged_in' => true,
            'user_id'      => $adminId,
            'user_email'   => 'admin.test.hu04@example.com',
            'user_name'    => 'Laura Benítez',
            'user_role'    => 'admin',
        ];
    }

    /**
     * Builds the full POST payload of the user form (password left blank, as when editing).
     *
     * @return array<string, string>
     */
    private function adminFormPayload(
        string $firstName,
        string $lastName,
        string $documentNumber,
        string $email,
        string $role,
        string $phone = '2994000000'
    ): array {
        return [
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => $documentNumber,
            'email'           => $email,
            'phone'           => $phone,
            'address'         => 'Dirección de prueba 123',
            'role'            => $role,
            'password'        => '',
        ];
    }

    /**
     * Lists flow (non-phrasing) elements nested inside <label>, which HTML5 does not allow.
     * CI4's DOMParser has no descendant selectors, so this queries the DOM with XPath.
     *
     * @return list<string>
     */
    private function flowElementsInsideLabels(string $html): array
    {
        $flowTags = [
            'div', 'p', 'ul', 'ol', 'li', 'dl', 'table', 'form', 'fieldset', 'section', 'article',
            'aside', 'header', 'footer', 'nav', 'main', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        ];

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $conditions = implode(' or ', array_map(static fn (string $tag): string => "self::{$tag}", $flowTags));
        $found      = [];

        foreach ((new DOMXPath($document))->query("//label//*[{$conditions}]") ?: [] as $node) {
            $found[] = $node->nodeName;
        }

        return $found;
    }

    private function roleNameOf(int $userId): string
    {
        $row = $this->db->table('users')
            ->select('roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->get()
            ->getRow();

        $this->assertNotNull($row);

        return (string) $row->role_name;
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

