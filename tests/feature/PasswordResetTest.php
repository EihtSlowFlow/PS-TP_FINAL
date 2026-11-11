<?php

use App\Controllers\PasswordResetController;
use App\Database\Seeds\RoleSeeder;
use App\Libraries\ResetAttemptLimiter;
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
        cache()->clean();
        // Reset the shared route collection in case an earlier test called withRoutes().
        service('routes')->resetRoutes();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        cache()->clean();

        parent::tearDown();
    }

    public function testRequestFormIsPublicAndLoginLinksToIt(): void
    {
        $recovery = $this->get('auth/recuperar-password');
        $recovery->assertStatus(200);
        $recovery->assertSee('Restablecer contraseña');
        $recovery->assertSeeElement('span.input-icon');
        $recovery->assertSeeElement('input[name=email]');
        $recovery->assertSeeElement('input[name=document_number]');

        $login = $this->get('auth/login');
        $login->assertStatus(200);
        $login->assertSee('¿Olvidaste tu contraseña?');
        $login->assertSee('auth/recuperar-password');
    }

    // Scenario 1 --------------------------------------------------------------------------------------------

    public function testScenario1ActiveUserGetsAnExpiringSingleUseTokenStoredOnlyAsHash(): void
    {
        $userId = $this->createUser('tenant', 'reset.s1@example.com');

        $result = $this->requestReset('Reset.S1@Example.com', $this->dniOf($userId));

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

        $result = $this->requestReset('reset.twice@example.com', $this->dniOf($userId));
        $this->assertMatchesRegularExpression(
            '#/auth/restablecer-password/[a-f0-9]{64}$#',
            $result->response()->getHeaderLine('Location')
        );

        $this->assertNull($resets->findUsableByToken($previous));
        $this->assertSame(1, $this->db->table('password_resets')->where('user_id', $userId)->where('used_at', null)->countAllResults());
    }

    public function testDniIsAcceptedWithSeparatorsLikeInTheRegistration(): void
    {
        $userId = $this->createUser('tenant', 'reset.dni.format@example.com');
        $dni    = $this->dniOf($userId);
        $dotted = substr($dni, 0, 2) . '.' . substr($dni, 2, 3) . '.' . substr($dni, 5);

        $this->requestReset('reset.dni.format@example.com', $dotted);

        $this->assertSame(1, $this->db->table('password_resets')->where('user_id', $userId)->countAllResults());
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

    public function testRequestWithInvalidEmailOrDniFormatIsRejectedWithoutCreatingTokens(): void
    {
        $userId = $this->createUser('tenant', 'reset.format@example.com');

        foreach ([
            ['no-es-un-correo', $this->dniOf($userId)],
            ['reset.format@example.com', ''],
            ['reset.format@example.com', '12$34*56'],
        ] as [$email, $dni]) {
            $result = $this->requestReset($email, $dni);

            $result->assertRedirectTo('/auth/recuperar-password');
            $this->assertSame(PasswordResetController::REQUEST_INVALID_DATA_MESSAGE, session()->getFlashdata('error'));
        }

        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    // Scenario 4 --------------------------------------------------------------------------------------------
    // Any non-matching combination (unknown email, wrong DNI, or deactivated user) stays on the request screen
    // with the same generic message ("Las credenciales ingresadas son incorrectas.") and creates no token.

    public function testScenario4UnknownEmailIsRejectedWithGenericErrorAndNoToken(): void
    {
        $result = $this->requestReset('nadie@example.com', '30111222');

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    public function testScenario4RealEmailWithWrongDniGetsTheSameGenericErrorAndNoToken(): void
    {
        $userId = $this->createUser('tenant', 'reset.wrongdni@example.com');

        $result = $this->requestReset('reset.wrongdni@example.com', '99999999');

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->where('user_id', $userId)->countAllResults());
    }

    public function testDeactivatedUserGetsTheDisabledMessageAndNoToken(): void
    {
        $userId = $this->createUser('tenant', 'reset.baja@example.com', '2026-10-01 12:00:00');

        $result = $this->requestReset('reset.baja@example.com', $this->dniOf($userId));

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::ACCOUNT_DISABLED_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    public function testDeactivatedUserWithWrongDniGetsGenericErrorWithoutRevealingDisabledState(): void
    {
        $this->createUser('tenant', 'reset.baja.wrongdni@example.com', '2026-10-01 12:00:00');

        $result = $this->requestReset('reset.baja.wrongdni@example.com', '99999999');

        $result->assertRedirectTo('/auth/recuperar-password');
        $this->assertSame(PasswordResetController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    // Cooldown ----------------------------------------------------------------------------------------------
    // Three non-matching attempts start a 2-minute cooldown immediately on the third failure.

    public function testThirdNonMatchingAttemptStartsTheTwoMinuteCooldownImmediately(): void
    {
        $userId = $this->createUser('tenant', 'reset.cooldown@example.com');

        for ($attempt = 1; $attempt < ResetAttemptLimiter::MAX_ATTEMPTS; $attempt++) {
            $result = $this->requestReset('reset.cooldown@example.com', '99999999');

            $result->assertRedirectTo('/auth/recuperar-password');
            $this->assertSame(PasswordResetController::INVALID_CREDENTIALS_MESSAGE, session()->getFlashdata('error'));
            $this->assertNull(session()->getFlashdata('retry_after'));
        }

        // The 3rd failed attempt triggers the 2-minute cooldown right away.
        $third = $this->requestReset('reset.cooldown@example.com', '99999999');
        $third->assertRedirectTo('/auth/recuperar-password');
        $this->assertStringContainsString('segundos', (string) session()->getFlashdata('error'));
        $retryAfter = (int) session()->getFlashdata('retry_after');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(ResetAttemptLimiter::COOLDOWN_SECONDS, $retryAfter);

        // Locked: even the correct DNI gets nothing while the cooldown lasts.
        $locked = $this->requestReset('reset.cooldown@example.com', $this->dniOf($userId));

        $locked->assertRedirectTo('/auth/recuperar-password');
        $this->assertStringContainsString('segundos', (string) session()->getFlashdata('error'));
        $this->assertGreaterThan(0, (int) session()->getFlashdata('retry_after'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    public function testAttemptsWithAnUnknownEmailAreLockedToo(): void
    {
        for ($attempt = 1; $attempt <= ResetAttemptLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->requestReset('nadie.cooldown@example.com', '30111222');
        }

        $locked = $this->requestReset('nadie.cooldown@example.com', '30111222');

        $locked->assertRedirectTo('/auth/recuperar-password');
        $this->assertGreaterThan(0, (int) session()->getFlashdata('retry_after'));
    }

    public function testTheCooldownIsPerIpSoAnotherEmailFromTheSameAddressIsAlsoLocked(): void
    {
        $userId = $this->createUser('tenant', 'reset.otheremail@example.com');

        for ($attempt = 1; $attempt <= ResetAttemptLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->requestReset('nadie.ip@example.com', '30111222');
        }

        $this->requestReset('reset.otheremail@example.com', $this->dniOf($userId))->assertRedirectTo('/auth/recuperar-password');
        $this->assertGreaterThan(0, (int) session()->getFlashdata('retry_after'));
        $this->assertSame(0, $this->db->table('password_resets')->countAllResults());
    }

    public function testRequestsWorkAgainOnceTheCooldownHasEnded(): void
    {
        $userId = $this->createUser('tenant', 'reset.after@example.com');

        for ($attempt = 1; $attempt <= ResetAttemptLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->requestReset('reset.after@example.com', '99999999');
        }

        // Simulates the 2 minutes going by.
        (new ResetAttemptLimiter())->clear(['ip:' . $this->limiterIp(), 'email:reset.after@example.com']);

        $result = $this->requestReset('reset.after@example.com', $this->dniOf($userId));

        $this->assertMatchesRegularExpression(
            '#/auth/restablecer-password/[a-f0-9]{64}$#',
            $result->response()->getHeaderLine('Location')
        );
        $this->assertSame(1, $this->db->table('password_resets')->where('user_id', $userId)->countAllResults());
    }

    public function testTwoFailedAttemptsDoNotLockAndASuccessfulOneClearsTheEmailCounter(): void
    {
        $userId = $this->createUser('tenant', 'reset.twofails@example.com');

        $this->requestReset('reset.twofails@example.com', '99999999');
        $this->requestReset('reset.twofails@example.com', '88888888');
        $this->assertSame(0, (new ResetAttemptLimiter())->secondsLocked(['email:reset.twofails@example.com']));

        $this->requestReset('reset.twofails@example.com', $this->dniOf($userId));
        $this->assertSame(1, $this->db->table('password_resets')->where('user_id', $userId)->countAllResults());

        // The email counter restarted, so two more failures still do not lock that email.
        $this->requestReset('reset.twofails@example.com', '77777777');
        $this->requestReset('reset.twofails@example.com', '66666666');
        $this->assertSame(0, (new ResetAttemptLimiter())->secondsLocked(['email:reset.twofails@example.com']));
    }

    public function testRequestsWithInvalidFormatAreNotCounted(): void
    {
        $userId = $this->createUser('tenant', 'reset.nocount@example.com');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->requestReset('reset.nocount@example.com', '12$34*56');
        }

        $result = $this->requestReset('reset.nocount@example.com', $this->dniOf($userId));

        $this->assertMatchesRegularExpression(
            '#/auth/restablecer-password/[a-f0-9]{64}$#',
            $result->response()->getHeaderLine('Location')
        );
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

    public function testScenario2PasswordWithoutComplexityIsRejectedAndTheTokenStaysUsable(): void
    {
        $userId = $this->createUser('tenant', 'reset.weak@example.com');
        $resets = new PasswordResetModel();
        $token  = $resets->issueFor($userId, 60);

        foreach (['sinmayuscula1!', 'SinNumeroAqui!', 'SinSimbolo2026'] as $weak) {
            $result = $this->postWithCsrf('auth/restablecer-password/' . $token, [
                'password'         => $weak,
                'password_confirm' => $weak,
            ]);

            $result->assertRedirectTo('/auth/restablecer-password/' . $token);
            $this->assertArrayHasKey('password', session()->getFlashdata('errors'));
            $this->assertTrue($this->passwordIs($userId, self::OLD_PASSWORD));
        }

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

    private function limiterIp(): string
    {
        return service('request')->getIPAddress();
    }

    private function dniOf(int $userId): string
    {
        return (string) $this->db->table('users')->where('id', $userId)->get()->getRow()->document_number;
    }

    private function requestReset(string $email, string $dni)
    {
        return $this->postWithCsrf('auth/recuperar-password', ['email' => $email, 'document_number' => $dni]);
    }

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
