<?php

use App\Controllers\PasswordResetController;
use App\Database\Seeds\RoleSeeder;
use App\Models\PasswordResetModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Automated acceptance tests for HU-03b: password recovery by single-use token.
 *
 * @internal
 */
final class PasswordResetTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const OLD_PASSWORD = 'ClaveVieja2026!';
    private const NEW_PASSWORD = 'ClaveNueva2026!';

    private int $dniCounter = 40100000;

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

    public function testRequestFormIsPublicAndLoginLinksToIt(): void
    {
        $recovery = $this->get('auth/recuperar-password');
        $recovery->assertStatus(200);
        $recovery->assertSee('Restablecer contraseña');
        $recovery->assertSeeElement('span.input-icon');

        $login = $this->get('auth/login');
        $login->assertStatus(200);
        $login->assertSee('¿Olvidaste tu contraseña?');
        $login->assertSee('auth/recuperar-password');
    }

    // Scenario 1 --------------------------------------------------------------------------------------------

    public function testScenario1ActiveUserGetsAnExpiringSingleUseTokenStoredOnlyAsHash(): void
    {
        $userId = $this->createUser('tenant', 'reset.s1@example.com');

        $result = $this->postWithCsrf('auth/recuperar-password', ['email' => 'Reset.S1@Example.com']);

        $this->assertMatchesRegularExpression(
            '#/auth/restablecer-password/[a-f0-9]{64}$#',
            $result->response()->getHeaderLine('Location')
        );

        $rows = $this->db->table('password_resets')->where('user_id', $userId)->get()->getResultArray();
        $this->assertCount(1, $rows);
        $this->assertSame(64, strlen((string) $rows[0]['token_hash']));
        $this->assertNull($rows[0]['used_at']);

        $expiresIn = strtotime((string) $rows[0]['expires_at']) - time();
        $this->assertGreaterThan(55 * 60, $expiresIn);
        $this->assertLessThanOrEqual(PasswordResetController::TOKEN_TTL_MINUTES * 60, $expiresIn);
    }

    public function testRequestingAgainInvalidatesThePreviousToken(): void
    {
        $userId   = $this->createUser('owner', 'reset.twice@example.com');
        $resets   = new PasswordResetModel();
        $previous = $resets->issueFor($userId, 60);

        $result = $this->postWithCsrf('auth/recuperar-password', ['email' => 'reset.twice@example.com']);
        $this->assertMatchesRegularExpression(
            '#/auth/restablecer-password/[a-f0-9]{64}$#',
            $result->response()->getHeaderLine('Location')
        );

        $this->assertNull($resets->findUsableByToken($previous));
        $this->assertSame(1, $this->db->table('password_resets')->where('user_id', $userId)->where('used_at', null)->countAllResults());
    }

    public function testTokensAreUnpredictable(): void
    {
        $userId = $this->createUser('owner', 'reset.random@example.com');
        $resets = new PasswordResetModel();

        $first  = $resets->issueFor($userId, 60);
        $second = $resets->issueFor($userId, 60);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        $this->assertNotSame($first, $second);
    }

    public function testRequestWithInvalidEmailFormatIsRejectedWithoutCreatingTokens(): void
    {
        $result = $this->postWithCsrf('auth/recuperar-password', ['email' => 'no-es-un-correo']);

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::REQUEST_INVALID_EMAIL_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    // Scenario 4 --------------------------------------------------------------------------------------------

    public function testScenario4UnknownEmailGetsTheSameNeutralAnswerAndNoToken(): void
    {
        $result = $this->postWithCsrf('auth/recuperar-password', ['email' => 'nadie@example.com']);

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::REQUEST_NEUTRAL_MESSAGE, session()->getFlashdata('success'));
        $this->assertNull(session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    public function testDeactivatedUserGetsTheSameNeutralAnswerAndNoToken(): void
    {
        $this->createUser('tenant', 'reset.baja@example.com', '2026-10-01 12:00:00');

        $result = $this->postWithCsrf('auth/recuperar-password', ['email' => 'reset.baja@example.com']);

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::REQUEST_NEUTRAL_MESSAGE, session()->getFlashdata('success'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    // Scenario 2 --------------------------------------------------------------------------------------------

    public function testScenario2ValidTokenShowsTheNewPasswordForm(): void
    {
        $userId = $this->createUser('tenant', 'reset.form@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $form = $this->get('auth/restablecer-password/' . $token);

        $form->assertStatus(200);
        $form->assertSee('Definir nueva contraseña');
        $form->assertSeeElement('input[name=password]');
        $form->assertSeeElement('input[name=password_confirm]');
        $form->assertSeeElement('span.input-icon');
    }

    public function testScenario2NewPasswordIsStoredHashedTheTokenIsBurnedAndUserGoesToLogin(): void
    {
        $userId = $this->createUser('tenant', 'reset.ok@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(PasswordResetController::RESET_SUCCESS_MESSAGE, session()->getFlashdata('success'));
        $this->assertNull(old('password'));

        $hash = (string) $this->db->table('users')->where('id', $userId)->get()->getRow()->password_hash;
        $this->assertNotSame(self::NEW_PASSWORD, $hash);
        $this->assertTrue(password_verify(self::NEW_PASSWORD, $hash));
        $this->assertFalse(password_verify(self::OLD_PASSWORD, $hash));

        $this->assertSame(0, $this->db->table('password_resets')->where('user_id', $userId)->where('used_at', null)->countAllResults());
    }

    public function testScenario2TheNewPasswordWorksToLogIn(): void
    {
        $userId = $this->createUser('owner', 'reset.login@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ])->assertRedirectTo('/auth/login');

        $login = $this->postWithCsrf('auth/login', ['email' => 'reset.login@example.com', 'password' => self::NEW_PASSWORD]);
        $login->assertRedirectTo('/propietario/dashboard');
    }

    public function testScenario2ShortPasswordIsRejectedAndTheTokenStaysUsable(): void
    {
        $userId = $this->createUser('tenant', 'reset.short@example.com');
        $resets = new PasswordResetModel();
        $token  = $resets->issueFor($userId, 60);

        $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => 'corta',
            'password_confirm' => 'corta',
        ]);

        $result->assertRedirectTo('/auth/restablecer-password/' . $token);
        $this->assertSame(PasswordResetController::RESET_VALIDATION_MESSAGE, session()->getFlashdata('error'));
        $this->assertArrayHasKey('password', session()->getFlashdata('errors'));
        $this->assertTrue($this->passwordIs($userId, self::OLD_PASSWORD));
        $this->assertNotNull($resets->findUsableByToken($token));
    }

    public function testScenario2MismatchedConfirmationIsRejected(): void
    {
        $userId = $this->createUser('tenant', 'reset.mismatch@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => 'OtraClave2026!',
        ]);

        $result->assertRedirectTo('/auth/restablecer-password/' . $token);
        $this->assertArrayHasKey('password_confirm', session()->getFlashdata('errors'));
        $this->assertTrue($this->passwordIs($userId, self::OLD_PASSWORD));
    }

    // Scenario 3 --------------------------------------------------------------------------------------------

    public function testScenario3UnknownOrMalformedTokenIsRejected(): void
    {
        $unknown = bin2hex(random_bytes(32));

        foreach ([$unknown, 'abc', str_repeat('Z', 64)] as $token) {
            $this->get('auth/restablecer-password/' . $token)->assertRedirectTo('/auth/recuperar-password');
            $this->assertSame(PasswordResetController::INVALID_TOKEN_MESSAGE, session()->getFlashdata('error'));

            $this->postWithCsrf('auth/restablecer-password/' . $token, [
                'password'         => self::NEW_PASSWORD,
                'password_confirm' => self::NEW_PASSWORD,
            ])->assertRedirectTo('/auth/recuperar-password');
            $this->assertSame(PasswordResetController::INVALID_TOKEN_MESSAGE, session()->getFlashdata('error'));
        }
    }

    public function testScenario3ExpiredTokenIsRejectedAndPasswordIsUnchanged(): void
    {
        $userId = $this->createUser('tenant', 'reset.expired@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);
        $this->db->table('password_resets')->where('user_id', $userId)->update(['expires_at' => date('Y-m-d H:i:s', time() - 60)]);

        $this->get('auth/restablecer-password/' . $token)->assertRedirectTo('/auth/recuperar-password');

        $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_TOKEN_MESSAGE, session()->getFlashdata('error'));
        $this->assertTrue($this->passwordIs($userId, self::OLD_PASSWORD));
    }

    public function testScenario3AUsedTokenCannotBeReused(): void
    {
        $userId = $this->createUser('tenant', 'reset.reuse@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ])->assertRedirectTo('/auth/login');

        $again = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => 'TercerClave2026!',
            'password_confirm' => 'TercerClave2026!',
        ]);

        $again->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_TOKEN_MESSAGE, session()->getFlashdata('error'));
        $this->assertTrue($this->passwordIs($userId, self::NEW_PASSWORD));
    }

    public function testBusinessRuleUserDeactivatedAfterRequestingCannotResetThePassword(): void
    {
        $userId = $this->createUser('tenant', 'reset.late@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);
        $this->db->table('users')->where('id', $userId)->update(['deleted_at' => '2026-11-09 10:00:00']);

        $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
            'password'         => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_TOKEN_MESSAGE, session()->getFlashdata('error'));
        $this->assertTrue($this->passwordIs($userId, self::OLD_PASSWORD));
    }

    public function testResetFormIsNotCacheableAndDoesNotLeakTheReferrer(): void
    {
        $userId = $this->createUser('tenant', 'reset.headers@example.com');
        $token  = (new PasswordResetModel())->issueFor($userId, 60);

        $form = $this->get('auth/restablecer-password/' . $token);

        $this->assertStringContainsString('no-store', $form->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('no-referrer', $form->response()->getHeaderLine('Referrer-Policy'));
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->post('auth/recuperar-password', ['email' => 'alguien@example.com']);
    }

    // Helpers -----------------------------------------------------------------------------------------------

    private function passwordIs(int $userId, string $plain): bool
    {
        $row = $this->db->table('users')->where('id', $userId)->get()->getRow();

        return password_verify($plain, (string) $row->password_hash);
    }

    private function createUser(string $roleName, string $email, ?string $deletedAt = null): int
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => 'Test',
            'last_name'       => ucfirst($roleName),
            'document_number' => (string) $this->dniCounter++,
            'email'           => $email,
            'phone'           => '2994000000',
            'password_hash'   => password_hash(self::OLD_PASSWORD, PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Sends a POST including a valid session CSRF token.
     *
     * @param array<string, string> $data
     */
    private function postWithCsrf(string $uri, array $data)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession([$tokenName => $tokenHash])
            ->post($uri, array_merge($data, [$tokenName => $tokenHash]));
    }
}
