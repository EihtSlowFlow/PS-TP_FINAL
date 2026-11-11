<?php

use App\Controllers\AuthController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance tests for HU-01: Login and role-based access control.
 *
 * @internal
 */
final class AuthAccessControlTest extends CIUnitTestCase
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

    public function testLoginScreenRendersFormAndCsrfField(): void
    {
        $result = $this->get('auth/login');

        $result->assertStatus(200);
        $result->assertSee('Ingresar a InmoGestión');
        $result->assertSeeElement('#email');
        $result->assertSeeElement('#password');
        $result->assertSee(csrf_token());
    }

    public function testRootRedirectsGuestToLogin(): void
    {
        $result = $this->get('/');

        $result->assertRedirectTo('/auth/login');
    }

    public function testLoginScreenOnlyLinksToRegisteredAuthRoutes(): void
    {
        $body = (string) $this->get('auth/login')->getBody();

        preg_match_all('#href="[^"]*/(auth/[a-z\-]+)"#', $body, $matches);
        $registered = array_keys(service('routes')->getRoutes('GET'));

        // Control: the collection is readable (otherwise the check below would pass vacuously).
        $this->assertContains('auth/login', $registered);

        $this->assertSame(
            [],
            array_values(array_diff($matches[1], $registered)),
            'The login screen links to auth routes that are not registered yet.'
        );
    }

    public function testLoginScreenShowsRegistrationAndRecoveryLinksOnceTheirRoutesExist(): void
    {
        $result = $this->withRoutes([
            ['get', 'auth/login', 'AuthController::login'],
            ['get', 'auth/register', 'AuthController::login'],
            ['get', 'auth/recuperar-password', 'AuthController::login'],
        ])->get('auth/login');

        $result->assertStatus(200);
        $result->assertSee('auth/register');
        $result->assertSee('auth/recuperar-password');
    }

    public function testLoginFormKeepsNativeBrowserValidationEnabled(): void
    {
        $this->get('auth/login')->assertDontSee('novalidate');
    }

    public function testLoginPostWithoutCsrfTokenIsRejected(): void
    {
        $this->createTestUser('owner', 'csrf.hu01@example.com', 'ClaveSegura2026!', '37100200');

        $this->expectException(SecurityException::class);

        $this->post('auth/login', [
            'email'    => 'csrf.hu01@example.com',
            'password' => 'ClaveSegura2026!',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>, 3: list<string>}>
     */
    public static function panelNavigationProvider(): array
    {
        return [
            'admin sees dashboard, users, properties and contracts' => [
                'admin', 'admin/dashboard', ['admin/dashboard', 'admin/usuarios', 'admin/inmuebles', 'admin/contratos/nuevo'], ['propietario/dashboard', 'inquilino/estado-cuenta'],
            ],
            'employee sees dashboard, properties and contracts but not users' => [
                'employee', 'admin/dashboard', ['admin/dashboard', 'admin/inmuebles', 'admin/contratos/nuevo'], ['admin/usuarios', 'propietario/dashboard'],
            ],
            'owner sees owner dashboard only' => [
                'owner', 'propietario/dashboard', ['propietario/dashboard'], ['admin/dashboard', 'admin/usuarios', 'admin/inmuebles', 'admin/contratos/nuevo'],
            ],
            'tenant sees account state only' => [
                'tenant', 'inquilino/estado-cuenta', ['inquilino/estado-cuenta'], ['admin/dashboard', 'admin/usuarios', 'admin/inmuebles', 'admin/contratos/nuevo'],
            ],
        ];
    }

    /**
     * @param list<string> $visibleLinks
     * @param list<string> $hiddenLinks
     */
    #[DataProvider('panelNavigationProvider')]
    public function testSharedPanelLayoutRendersRoleAwareNavigation(
        string $roleName,
        string $uri,
        array $visibleLinks,
        array $hiddenLinks
    ): void {
        $email  = "{$roleName}.layout.hu01@example.com";
        $userId = $this->createTestUser($roleName, $email, 'ClaveSegura2026!', '36100200');

        $result = $this->withSession([
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => $email,
            'user_name'    => 'Test ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ])->get($uri);

        $result->assertStatus(200);
        $result->assertSeeElement('aside.panel-sidebar');
        $result->assertSeeElement('main.panel-content');

        foreach ($visibleLinks as $link) {
            $result->assertSee($link);
        }

        foreach ($hiddenLinks as $link) {
            $result->assertDontSee($link);
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function roleRedirectProvider(): array
    {
        return [
            'admin redirects to admin dashboard'       => ['admin', '/admin/dashboard'],
            'employee redirects to admin dashboard'    => ['employee', '/admin/dashboard'],
            'owner redirects to owner dashboard'       => ['owner', '/propietario/dashboard'],
            'tenant redirects to tenant account state' => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('roleRedirectProvider')]
    public function testScenario1SuccessfulLoginRedirectsToRolePanelAndStoresMinimalSession(
        string $roleName,
        string $expectedPath
    ): void {
        $email    = "{$roleName}.hu01@example.com";
        $password = 'ClaveSegura2026!';
        $userId   = $this->createTestUser($roleName, $email, $password, '30100200');

        $result = $this->postWithCsrf('auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        $result->assertRedirectTo($expectedPath);

        $session = session();
        $this->assertTrue($session->get('is_logged_in'));
        $this->assertSame($userId, $session->get('user_id'));
        $this->assertSame($email, $session->get('user_email'));
        $this->assertSame($roleName, $session->get('user_role'));
        $this->assertNotEmpty($session->get('__ci_last_regenerate'));
        $this->assertNull($session->get('password_hash'));
    }

    public function testScenario2InvalidCredentialsReturnUnifiedGenericMessageAndPreserveEmail(): void
    {
        $email = 'valid.user@example.com';
        $this->createTestUser('owner', $email, 'CorrectPassword123!', '31222333');

        // Case A: Wrong password for existing email
        $wrongPassResult = $this->postWithCsrf('auth/login', [
            'email'    => $email,
            'password' => 'WrongPassword999!',
        ]);

        $wrongPassResult->assertRedirectTo('/auth/login');
        $this->assertNull(session()->get('is_logged_in'));
        $this->assertSame(AuthController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame($email, old('email'));
        $this->assertNull(old('password'));

        // Case B: Unregistered email returns the exact same generic message
        $_SESSION          = [];
        $unknownMailResult = $this->postWithCsrf('auth/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'AnyPassword123!',
        ]);

        $unknownMailResult->assertRedirectTo('/auth/login');
        $this->assertNull(session()->get('is_logged_in'));
        $this->assertSame(AuthController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame('nonexistent@example.com', old('email'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'admin dashboard'      => ['admin/dashboard'],
            'admin users'          => ['admin/usuarios'],
            'owner dashboard'      => ['propietario/dashboard'],
            'tenant account state' => ['inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('protectedRoutesProvider')]
    public function testScenario3UnauthenticatedAccessToProtectedRouteRedirectsToLogin(string $uri): void
    {
        $result = $this->get($uri);

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
    }

    public function testScenario4RoleWithoutPermissionIsDeniedAndRedirectedToOwnPanel(): void
    {
        $tenantId = $this->createTestUser('tenant', 'tenant.hu01@example.com', 'ClaveSegura2026!', '32444555');

        $result = $this->withSession([
            'is_logged_in' => true,
            'user_id'      => $tenantId,
            'user_email'   => 'tenant.hu01@example.com',
            'user_name'    => 'Test User',
            'user_role'    => 'tenant',
        ])->get('admin/dashboard');

        $result->assertRedirectTo('/inquilino/estado-cuenta');
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
    }

    public function testEmployeeSharesAdminDashboardButIsBlockedFromUserManagement(): void
    {
        $employeeId = $this->createTestUser('employee', 'employee.hu01@example.com', 'ClaveSegura2026!', '33555666');
        $session    = [
            'is_logged_in' => true,
            'user_id'      => $employeeId,
            'user_email'   => 'employee.hu01@example.com',
            'user_name'    => 'Empleado Test',
            'user_role'    => 'employee',
        ];

        $dashboardResult = $this->withSession($session)->get('admin/dashboard');
        $dashboardResult->assertStatus(200);
        $dashboardResult->assertSee('Panel de control');

        $usersResult = $this->withSession($session)->get('admin/usuarios');
        $usersResult->assertRedirectTo('/admin/dashboard');
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
    }

    public function testScenario5SoftDeletedUserCannotLoginAndReceivesDisabledMessage(): void
    {
        $email    = 'deactivated.hu01@example.com';
        $password = 'ClaveSegura2026!';
        $this->createTestUser('tenant', $email, $password, '34666777', '2026-10-01 12:00:00');

        $result = $this->postWithCsrf('auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        $result->assertRedirectTo('/auth/login');
        $this->assertNull(session()->get('is_logged_in'));
        $this->assertSame(AuthController::ACCOUNT_DISABLED_MESSAGE, session()->getFlashdata('error'));
    }

    public function testScenario5DisabledStateIsNotRevealedWithoutTheCorrectPassword(): void
    {
        $email = 'deactivated.wrongpass.hu01@example.com';
        $this->createTestUser('tenant', $email, 'ClaveSegura2026!', '34666778', '2026-10-01 12:00:00');

        $result = $this->postWithCsrf('auth/login', [
            'email'    => $email,
            'password' => 'WrongPassword999!',
        ]);

        // Same generic answer as an unknown email or a wrong password (Scenario 2): no account-state leak.
        $result->assertRedirectTo('/auth/login');
        $this->assertNull(session()->get('is_logged_in'));
        $this->assertSame(AuthController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame($email, old('email'));
        $this->assertNull(old('password'));
    }

    public function testUserDeactivatedWhileLoggedInIsBlockedOnSubsequentProtectedRequest(): void
    {
        $userId = $this->createTestUser('owner', 'owner.active@example.com', 'ClaveSegura2026!', '35777888');

        $this->db->table('users')
            ->where('id', $userId)
            ->update(['deleted_at' => '2026-11-09 10:00:00']);

        $result = $this->withSession([
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => 'owner.active@example.com',
            'user_name'    => 'Owner Test',
            'user_role'    => 'owner',
        ])->get('propietario/dashboard');

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::ACCOUNT_DISABLED_MESSAGE, session()->getFlashdata('error'));
        $this->assertNull(session()->get('is_logged_in'));
    }

    /**
     * Sends a POST request including a valid session CSRF token.
     *
     * @param array<string, string> $data
     */
    private function postWithCsrf(string $uri, array $data)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession([
            $tokenName => $tokenHash,
        ])->post($uri, array_merge($data, [
            $tokenName => $tokenHash,
        ]));
    }

    private function createTestUser(
        string $roleName,
        string $email,
        string $plainPassword,
        string $documentNumber,
        ?string $deletedAt = null
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => 'Test',
            'last_name'       => ucfirst($roleName),
            'document_number' => $documentNumber,
            'email'           => $email,
            'password_hash'   => password_hash($plainPassword, PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }
}
