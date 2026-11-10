<?php

use App\Controllers\AuthController;
use App\Database\Seeds\RoleSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acceptance tests for HU-02: public owner and tenant registration.
 *
 * @internal
 */
final class PublicRegistrationTest extends CIUnitTestCase
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

    public function testRegistrationPageRendersOnlyPublicRolesAndCsrfField(): void
    {
        $result = $this->get('auth/register');

        $result->assertStatus(200);
        $result->assertSee('Crear cuenta');
        $result->assertSeeElement('input[name="role"][value="owner"]');
        $result->assertSeeElement('input[name="role"][value="tenant"]');
        $result->assertDontSee('value="admin"', false);
        $result->assertSee(csrf_token());
    }

    #[DataProvider('publicRoleProvider')]
    public function testScenario1CreatesOwnerOrTenantWithHashedPassword(string $role): void
    {
        $password = 'ClaveSegura2026!';
        $data     = $this->validRegistrationData($role);
        $data['password']         = $password;
        $data['password_confirm'] = $password;

        $result = $this->postWithCsrf('auth/register', $data);

        $result->assertRedirectTo('/auth/login');
        $this->assertSame(AuthController::REGISTRATION_SUCCESS_MESSAGE, session()->getFlashdata('success'));
        $this->assertNull(session()->get('is_logged_in'));

        $user = $this->db->table('users')
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $data['email'])
            ->get()
            ->getRow();

        $this->assertNotNull($user);
        $this->assertSame($role, $user->role_name);
        $this->assertSame('Valeria', $user->first_name);
        $this->assertSame('Méndez', $user->last_name);
        $this->assertSame('38100203', $user->document_number);
        $this->assertNotSame($password, $user->password_hash);
        $this->assertTrue(password_verify($password, $user->password_hash));
    }

    public static function publicRoleProvider(): array
    {
        return [
            'owner'  => ['owner'],
            'tenant' => ['tenant'],
        ];
    }

    public function testScenario2RejectsDuplicateEmailAndDocumentNumberAndClearsPasswords(): void
    {
        $this->insertExistingUser('owner', 'duplicate.email@example.test', '24118905');

        $emailData = $this->validRegistrationData('tenant');
        $emailData['email'] = 'duplicate.email@example.test';

        $emailResponse = $this->postWithCsrf('auth/register', $emailData);
        $emailResponse->assertRedirectTo('/auth/register');
        $emailErrors = session()->getFlashdata('errors');
        $this->assertSame(AuthController::DUPLICATE_EMAIL_MESSAGE, $emailErrors['email'] ?? null);
        $this->assertSame('Valeria', old('first_name'));
        $this->assertNull(old('password'));
        $this->assertNull(old('password_confirm'));

        $_SESSION = [];
        $dniData   = $this->validRegistrationData('tenant');
        $dniData['document_number'] = '24.118.905';

        $dniResponse = $this->postWithCsrf('auth/register', $dniData);
        $dniResponse->assertRedirectTo('/auth/register');
        $dniErrors = session()->getFlashdata('errors');
        $this->assertSame(AuthController::DUPLICATE_DNI_MESSAGE, $dniErrors['document_number'] ?? null);
        $this->assertNull(old('password'));
        $this->assertNull(old('password_confirm'));
    }

    public function testScenario3RejectsInvalidEmailShortOrMismatchedPasswordsAndAdminRole(): void
    {
        $invalidCases = [
            [
                'data' => ['email' => 'not-an-email'],
                'field' => 'email',
            ],
            [
                'data' => ['password' => 'short', 'password_confirm' => 'short'],
                'field' => 'password',
            ],
            [
                'data' => ['password' => 'ClaveSegura2026!', 'password_confirm' => 'OtraClave2026!'],
                'field' => 'password_confirm',
            ],
            [
                'data' => ['role' => 'admin'],
                'field' => 'role',
            ],
        ];

        foreach ($invalidCases as $index => $case) {
            $_SESSION = [];
            $data     = array_merge($this->validRegistrationData('tenant'), $case['data']);

            $result = $this->postWithCsrf('auth/register', $data);
            $result->assertRedirectTo('/auth/register');

            $errors = session()->getFlashdata('errors');
            $this->assertArrayHasKey($case['field'], $errors, "Invalid case {$index} should report {$case['field']}.");
            $this->assertNull(old('password'));
            $this->assertNull(old('password_confirm'));
        }

        $this->assertSame(0, $this->db->table('users')->countAllResults());
    }

    /**
     * @return array<string, string>
     */
    private function validRegistrationData(string $role): array
    {
        return [
            'first_name'      => 'Valeria',
            'last_name'       => 'Méndez',
            'document_number' => '38.100.203',
            'email'           => "valeria.{$role}@example.test",
            'phone'           => '2994123456',
            'role'            => $role,
            'password'        => 'ClaveSegura2026!',
            'password_confirm' => 'ClaveSegura2026!',
        ];
    }

    private function insertExistingUser(string $roleName, string $email, string $documentNumber): void
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role);

        $now = date('Y-m-d H:i:s');
        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => 'Usuario',
            'last_name'       => 'Existente',
            'document_number' => $documentNumber,
            'email'           => $email,
            'phone'           => '2994000000',
            'password_hash'   => password_hash('ClaveExistente2026!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    /**
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
}
