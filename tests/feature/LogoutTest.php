<?php

use App\Controllers\AuthController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance tests for HU-03: secure logout.
 *
 * @internal
 */
final class LogoutTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const SESSION_KEYS = ['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role'];

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
    public static function rolePanelProvider(): array
    {
        return [
            'admin'    => ['admin', 'admin/dashboard'],
            'employee' => ['employee', 'admin/dashboard'],
            'owner'    => ['owner', 'propietario/dashboard'],
            'tenant'   => ['tenant', 'inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('rolePanelProvider')]
    public function testScenario1LogoutDestroysTheSessionRedirectsToLoginAndInformsSuccess(string $roleName, string $panel): void
    {
        $session = $this->authenticatedSession($roleName);

        $result = $this->postWithCsrf($session, 'auth/logout');

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(AuthController::LOGOUT_SUCCESS_MESSAGE, session()->getFlashdata('success'));

        foreach (self::SESSION_KEYS as $key) {
            $this->assertNull(session()->get($key), "The session still holds {$key} after logging out.");
        }

        // The panel the user came from is no longer reachable with what is left of the session.
        $this->withSession()->get($panel)->assertRedirectTo('/auth/login');
    }

    public function testScenario1LoginScreenShowsTheLogoutConfirmation(): void
    {
        $result = $this->withSession([
            'success'  => AuthController::LOGOUT_SUCCESS_MESSAGE,
            '__ci_vars' => ['success' => 'old'],
        ])->get('auth/login');

        $result->assertStatus(200);
        $result->assertSee(AuthController::LOGOUT_SUCCESS_MESSAGE);
    }

    public function testScenario2ProtectedPagesRequireLoginAgainAfterLoggingOut(): void
    {
        $session = $this->authenticatedSession('admin');

        $this->postWithCsrf($session, 'auth/logout')->assertRedirectTo('/auth/login');
        $leftOver = $_SESSION;

        foreach (['admin/dashboard', 'admin/usuarios', 'admin/usuarios/nuevo', 'propietario/dashboard', 'inquilino/estado-cuenta'] as $uri) {
            $result = $this->withSession($leftOver)->get($uri);

            $result->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'), $uri);
        }
    }

    public function testScenario2ProtectedPagesAreNotCacheableSoTheBackButtonCannotRestoreThem(): void
    {
        $session = $this->authenticatedSession('admin');

        $page = $this->withSession($session)->get('admin/dashboard');
        $page->assertStatus(200);
        $this->assertStringContainsString('no-store', $page->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('no-cache', $page->response()->getHeaderLine('Pragma'));

        $logout = $this->postWithCsrf($session, 'auth/logout');
        $this->assertStringContainsString('no-store', $logout->response()->getHeaderLine('Cache-Control'));
    }

    #[DataProvider('rolePanelProvider')]
    public function testEveryRolePanelOffersTheLogoutAction(string $roleName, string $panel): void
    {
        $session = $this->authenticatedSession($roleName);

        $result = $this->withSession($session)->get($panel);

        $result->assertStatus(200);
        $result->assertSeeElement('form.logout-form');
        $result->assertSee('auth/logout');
        $result->assertSee('Cerrar sesión');
        $result->assertSee(csrf_token());
    }

    public function testUserManagementScreensOfTheAdministratorOfferTheLogoutActionToo(): void
    {
        $session = $this->authenticatedSession('admin');

        foreach (['admin/usuarios', 'admin/usuarios/nuevo'] as $uri) {
            $result = $this->withSession($session)->get($uri);

            $result->assertStatus(200);
            $result->assertSeeElement('form.logout-form');
        }
    }

    public function testLogoutIsNotAvailableThroughGet(): void
    {
        $session = $this->authenticatedSession('admin');

        try {
            $result = $this->withSession($session)->get('auth/logout');
            $this->assertSame(404, $result->response()->getStatusCode());
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $this->assertTrue(session()->get('is_logged_in'), 'A GET request must not close the session.');
    }

    public function testLogoutWithoutCsrfTokenIsRejectedAndKeepsTheSession(): void
    {
        $session = $this->authenticatedSession('admin');

        try {
            $this->withSession($session)->post('auth/logout');
            $this->fail('A logout request without CSRF token must be rejected.');
        } catch (SecurityException) {
            $this->addToAssertionCount(1);
        }

        $this->assertTrue(session()->get('is_logged_in'));
    }

    public function testLogoutWithoutAnActiveSessionJustShowsTheLoginScreen(): void
    {
        $result = $this->postWithCsrf([], 'auth/logout');

        $result->assertRedirectTo('/auth/login');
        $this->assertNull(session()->getFlashdata('success'), 'There was no session to close.');
        $this->assertNull(session()->get('is_logged_in'));
    }

    public function testLoggingOutTwiceIsHarmless(): void
    {
        $session = $this->authenticatedSession('tenant');

        $this->postWithCsrf($session, 'auth/logout')->assertRedirectTo('/auth/login');

        // What is left of the session once the login screen has shown (and consumed) the confirmation message.
        $leftOver = $_SESSION;
        unset($leftOver['success'], $leftOver['__ci_vars']);

        $second = $this->postWithCsrf($leftOver, 'auth/logout');

        $second->assertRedirectTo('/auth/login');
        $this->assertNull(session()->getFlashdata('success'));
        $this->assertNull(session()->get('is_logged_in'));
    }

    public function testAUserDeactivatedWhileLoggedInCanStillLogOut(): void
    {
        $session = $this->authenticatedSession('owner', '2026-11-09 10:00:00');

        $result = $this->postWithCsrf($session, 'auth/logout');

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(AuthController::LOGOUT_SUCCESS_MESSAGE, session()->getFlashdata('success'));
        foreach (self::SESSION_KEYS as $key) {
            $this->assertNull(session()->get($key));
        }
    }

    /**
     * Creates a user with the given role and returns the session data its login would store.
     *
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function authenticatedSession(string $roleName, ?string $deletedAt = null): array
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $email = "{$roleName}.logout.hu03@example.com";
        $now   = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => 'Test',
            'last_name'       => ucfirst($roleName),
            'document_number' => '38100200',
            'email'           => $email,
            'phone'           => '2994000000',
            'password_hash'   => password_hash('ClaveSegura2026!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return [
            'is_logged_in' => true,
            'user_id'      => (int) $this->db->insertID(),
            'user_email'   => $email,
            'user_name'    => 'Test ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ];
    }

    /**
     * Sends a POST with the session data plus a valid session CSRF token.
     *
     * @param array<string, mixed> $sessionData
     */
    private function postWithCsrf(array $sessionData, string $uri)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, [$tokenName => $tokenHash]);
    }
}
