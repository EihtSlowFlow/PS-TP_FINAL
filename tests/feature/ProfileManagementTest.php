<?php

use App\Controllers\ProfileController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acceptance and authorization tests for HU-06: personal profile management.
 *
 * @internal
 */
final class ProfileManagementTest extends CIUnitTestCase
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
    public static function clientRolesProvider(): array
    {
        return [
            'owner'  => ['owner', 'Propietario'],
            'tenant' => ['tenant', 'Inquilino'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function blockedRolesProvider(): array
    {
        return [
            'administrator' => ['admin', '/admin/dashboard'],
            'employee'      => ['employee', '/admin/dashboard'],
        ];
    }

    #[DataProvider('clientRolesProvider')]
    public function testClientCanSeeOnlyTheirOwnProfileAndImmutableAccountDetails(
        string $roleName,
        string $roleLabel
    ): void {
        $email = "{$roleName}.profile.hu06@example.test";
        $userId = $this->insertUserRecord(
            roleName: $roleName,
            firstName: 'Valeria',
            lastName: 'Méndez',
            documentNumber: '38100203',
            email: $email,
            plainPassword: 'PasswordActual2026!'
        );
        $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Otra',
            lastName: 'Persona',
            documentNumber: '38100204',
            email: 'otra.persona.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );

        $response = $this->withSession($this->userSession($userId, $roleName, $email))
            ->get('perfil');

        $response->assertStatus(200);
        $response->assertSee('Valeria Méndez');
        $response->assertSee('38100203');
        $response->assertSee($email);
        $response->assertSee($roleLabel);
        $response->assertDontSee('otra.persona.hu06@example.test');
        $response->assertDontSeeElement('input[name="document_number"]');
        $response->assertDontSeeElement('input[name="role"]');
        $response->assertSee(csrf_token());
    }

    public function testScenario1ContactUpdateChangesOnlyTheAuthenticatedUsersContactFields(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Valeria',
            lastName: 'Méndez',
            documentNumber: '38100203',
            email: 'valeria.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'owner', 'valeria.profile.hu06@example.test');

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contacto', [
            'phone'           => '+54 9 299 555-0101',
            'address'         => 'San Martín 450, Cipolletti',
            'document_number' => '99999999',
            'email'           => 'otra.cuenta@example.test',
            'role_id'         => '1',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(ProfileController::CONTACT_UPDATED_MESSAGE, session()->getFlashdata('success'));

        $updated = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertSame('+54 9 299 555-0101', $updated['phone']);
        $this->assertSame('San Martín 450, Cipolletti', $updated['address']);
        $this->assertSame('38100203', $updated['document_number']);
        $this->assertSame('valeria.profile.hu06@example.test', $updated['email']);
        $this->assertSame('owner', $this->roleNameForUser($userId));
    }

    public function testInvalidContactDetailsAreRejectedWithoutUpdatingTheProfile(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Tomás',
            lastName: 'Paz',
            documentNumber: '38100205',
            email: 'tomas.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'tenant', 'tomas.profile.hu06@example.test');

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contacto', [
            'phone'           => '12abc456',
            'address'          => str_repeat('a', 256),
            'current_password' => 'No guardar esta clave',
            'new_password'     => 'Tampoco guardar esta clave',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(ProfileController::CONTACT_VALIDATION_MESSAGE, session()->getFlashdata('error'));
        $errors = session()->getFlashdata('errors');
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertNull(old('current_password'));
        $this->assertNull(old('new_password'));

        $stored = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertSame('2994000000', $stored['phone']);
        $this->assertSame('Dirección de prueba 123', $stored['address']);
    }

    public function testScenario2PasswordChangeVerifiesCurrentPasswordAndStoresANewHash(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Valeria',
            lastName: 'Méndez',
            documentNumber: '38100206',
            email: 'password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'owner', 'password.profile.hu06@example.test');
        $newPassword = 'NuevaClaveSegura2026!';

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
            'current_password' => 'PasswordActual2026!',
            'new_password'     => $newPassword,
            'password_confirm' => $newPassword,
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(ProfileController::PASSWORD_UPDATED_MESSAGE, session()->getFlashdata('success'));
        $storedHash = (string) $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash;
        $this->assertNotSame($newPassword, $storedHash);
        $this->assertTrue(password_verify($newPassword, $storedHash));
        $this->assertFalse(password_verify('PasswordActual2026!', $storedHash));
        $this->assertNull(old('current_password'));
        $this->assertNull(old('new_password'));
    }

    public function testScenario3WrongCurrentPasswordDoesNotChangeTheExistingPassword(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Tomás',
            lastName: 'Paz',
            documentNumber: '38100207',
            email: 'wrong-password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'tenant', 'wrong-password.profile.hu06@example.test');
        $storedHash = (string) $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash;

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
            'current_password' => 'NoEsLaClaveActual!',
            'new_password'     => 'NuevaClaveSegura2026!',
            'password_confirm' => 'NuevaClaveSegura2026!',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(ProfileController::CURRENT_PASSWORD_ERROR, session()->getFlashdata('error'));
        $this->assertSame(ProfileController::CURRENT_PASSWORD_ERROR, session()->getFlashdata('errors')['current_password']);
        $this->assertNull(old('current_password'));
        $this->assertNull(old('new_password'));

        $after = (string) $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash;
        $this->assertSame($storedHash, $after);
        $this->assertTrue(password_verify('PasswordActual2026!', $after));
    }

    public function testPasswordShorterThanEightCharactersIsRejected(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Valeria',
            lastName: 'Méndez',
            documentNumber: '38100208',
            email: 'short-password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'owner', 'short-password.profile.hu06@example.test');
        $storedHash = (string) $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash;

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
            'current_password' => 'PasswordActual2026!',
            'new_password'     => 'corta',
            'password_confirm' => 'corta',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(ProfileController::PASSWORD_VALIDATION_MESSAGE, session()->getFlashdata('error'));
        $this->assertArrayHasKey('new_password', session()->getFlashdata('errors'));
        $this->assertSame($storedHash, $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash);
    }

    public function testNewPasswordMustNotMatchCurrentPassword(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'owner',
            firstName: 'Valeria',
            lastName: 'Méndez',
            documentNumber: '38100210',
            email: 'same-password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'owner', 'same-password.profile.hu06@example.test');
        $storedHash = (string) $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash;

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
            'current_password' => 'PasswordActual2026!',
            'new_password'     => 'PasswordActual2026!',
            'password_confirm' => 'PasswordActual2026!',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(
            ProfileController::PASSWORD_REUSE_ERROR,
            session()->getFlashdata('error')
        );
        $this->assertSame($storedHash, $this->db->table('users')
            ->select('password_hash')
            ->where('id', $userId)
            ->get()
            ->getRow()
            ->password_hash);
    }

    public function testNewPasswordMustContainAnUppercaseLetterNumberAndSymbol(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Tomás',
            lastName: 'Paz',
            documentNumber: '38100211',
            email: 'weak-password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'tenant', 'weak-password.profile.hu06@example.test');

        foreach ([
            'without uppercase' => 'clavesegura2026!',
            'without number'    => 'ClaveSegura!',
            'without symbol'    => 'ClaveSegura2026',
        ] as $case => $newPassword) {
            $_SESSION = [];

            $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
                'current_password' => 'PasswordActual2026!',
                'new_password'     => $newPassword,
                'password_confirm' => $newPassword,
            ]);

            $response->assertRedirectTo('/perfil');
            $this->assertSame(
                'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.',
                session()->getFlashdata('errors')['new_password'] ?? null,
                "The policy error should be reported for {$case}."
            );
            $this->assertNull(old('current_password'));
            $this->assertNull(old('new_password'));
            $this->assertNull(old('password_confirm'));
        }
    }

    public function testPasswordConfirmationMustMatchTheNewPassword(): void
    {
        $userId = $this->insertUserRecord(
            roleName: 'tenant',
            firstName: 'Tomás',
            lastName: 'Paz',
            documentNumber: '38100212',
            email: 'confirm-password.profile.hu06@example.test',
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, 'tenant', 'confirm-password.profile.hu06@example.test');

        $response = $this->postAsSessionWithCsrf($session, 'perfil/contrasena', [
            'current_password' => 'PasswordActual2026!',
            'new_password'     => 'NuevaClaveSegura2026!',
            'password_confirm' => 'OtraClaveSegura2026!',
        ]);

        $response->assertRedirectTo('/perfil');
        $this->assertSame(
            'Las contraseñas no coinciden.',
            session()->getFlashdata('errors')['password_confirm'] ?? null
        );
        $this->assertNull(old('current_password'));
        $this->assertNull(old('new_password'));
        $this->assertNull(old('password_confirm'));
    }

    #[DataProvider('blockedRolesProvider')]
    public function testAdministrativeRolesCannotAccessOrUpdateClientProfiles(
        string $roleName,
        string $expectedRedirect
    ): void {
        $userId = $this->insertUserRecord(
            roleName: $roleName,
            firstName: 'Usuario',
            lastName: ucfirst($roleName),
            documentNumber: '38100209',
            email: "{$roleName}.blocked.hu06@example.test",
            plainPassword: 'PasswordActual2026!'
        );
        $session = $this->userSession($userId, $roleName, "{$roleName}.blocked.hu06@example.test");

        $getResponse = $this->withSession($session)->get('perfil');
        $getResponse->assertRedirectTo($expectedRedirect);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $postResponse = $this->postAsSessionWithCsrf($session, 'perfil/contacto', [
            'phone'   => '2995550000',
            'address' => 'No autorizado',
        ]);
        $postResponse->assertRedirectTo($expectedRedirect);

        $stored = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertSame('2994000000', $stored['phone']);
        $this->assertSame('Dirección de prueba 123', $stored['address']);
    }

    public function testUnauthenticatedVisitorsAreRedirectedFromProfileRoutes(): void
    {
        $response = $this->get('perfil');

        $response->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
    }

    /**
     * @return array<string, mixed>
     */
    private function userSession(int $userId, string $roleName, string $email): array
    {
        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => $email,
            'user_name'    => 'Usuario de prueba',
            'user_role'    => $roleName,
        ];
    }

    /**
     * @param array<string, mixed> $sessionData
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
        string $plainPassword
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
            'deleted_at'      => null,
        ]);

        return (int) $this->db->insertID();
    }

    private function roleNameForUser(int $userId): string
    {
        return (string) $this->db->table('users')
            ->select('roles.name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $userId)
            ->get()
            ->getRow()
            ->name;
    }
}
