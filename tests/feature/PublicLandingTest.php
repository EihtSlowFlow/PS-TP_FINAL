<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Agency;

/**
 * Acceptance tests for HU-28: Public landing page.
 *
 * @internal
 */
final class PublicLandingTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private int $userSequence = 0;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $this->userSequence = 0;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Factories::reset('config');

        parent::tearDown();
    }

    public function testGuestVisitingRootSeesPublicLandingWithAllSections(): void
    {
        $ownerId = $this->createUser(UserModel::ROLE_OWNER);
        $this->createProperty($ownerId, [
            'address'         => 'Av. Argentina 100',
            'city'            => 'Neuquén',
            'type'            => PropertyModel::TYPE_APARTMENT,
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Header navigation
        $response->assertSee('Inicio');
        $response->assertSee('Propiedades');
        $response->assertSee('Ingresar');
        $response->assertSee('Registrarse');

        // Value proposition (Hero)
        $response->assertSee('Gestión transparente y moderna de alquileres');

        // Quick search
        $response->assertSee('Buscador rápido');
        $response->assertSee('Tipo de inmueble');
        $response->assertSee('Precio máximo');

        // Latest properties
        $response->assertSee('Últimas propiedades disponibles');
        $response->assertSee('Av. Argentina 100');

        // Explanation for tenants and owners
        $response->assertSee('Para Inquilinos');
        $response->assertSee('Para Propietarios');

        // Contact info
        $response->assertSee('Ponete en contacto');
        $response->assertSee('contacto@inmogestion2026.com.ar');
    }

    public function testCallsToActionLeadToExpectedDestinations(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $body = (string) $response->getBody();

        // Escenario 2: CTAs
        $this->assertStringContainsString('href="' . site_url('catalogo-inmuebles') . '"', $body);
        $this->assertStringContainsString('href="' . site_url('auth/register?role=owner') . '"', $body);
        $this->assertStringContainsString('href="' . site_url('auth/register') . '"', $body);
    }

    public function testRegistrationAcceptsOwnerOrTenantPreselection(): void
    {
        // Brand logo in registration leads to landing page
        $regResponse = $this->get('auth/register');
        $regResponse->assertSee('href="' . site_url('/') . '"');

        // Owner preselection
        $ownerResponse = $this->get('auth/register?role=owner');
        $ownerResponse->assertStatus(200);
        $ownerBody = (string) $ownerResponse->getBody();
        $this->assertMatchesRegularExpression('/<input[^>]+name="role"[^>]+value="owner"[^>]*checked/i', $ownerBody);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="role"[^>]+value="tenant"[^>]*checked/i', $ownerBody);

        // Tenant preselection
        $tenantResponse = $this->get('auth/register?role=tenant');
        $tenantResponse->assertStatus(200);
        $tenantBody = (string) $tenantResponse->getBody();
        $this->assertMatchesRegularExpression('/<input[^>]+name="role"[^>]+value="tenant"[^>]*checked/i', $tenantBody);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="role"[^>]+value="owner"[^>]*checked/i', $tenantBody);

        // Invalid preselection is ignored
        $invalidResponse = $this->get('auth/register?role=admin');
        $invalidResponse->assertStatus(200);
        $invalidBody = (string) $invalidResponse->getBody();
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="role"[^>]+checked/i', $invalidBody);
    }

    public function testAuthenticatedUserVisitingRootIsRedirectedToPanel(): void
    {
        // Admin
        $adminId = $this->createUser(UserModel::ROLE_ADMIN);
        $responseAdmin = $this->withSession($this->sessionFor($adminId, UserModel::ROLE_ADMIN))->get('/');
        $responseAdmin->assertRedirectTo(UserModel::ROLE_HOME_ROUTES[UserModel::ROLE_ADMIN]);

        // Owner
        $ownerId = $this->createUser(UserModel::ROLE_OWNER);
        $responseOwner = $this->withSession($this->sessionFor($ownerId, UserModel::ROLE_OWNER))->get('/');
        $responseOwner->assertRedirectTo(UserModel::ROLE_HOME_ROUTES[UserModel::ROLE_OWNER]);

        // Tenant
        $tenantId = $this->createUser(UserModel::ROLE_TENANT);
        $responseTenant = $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))->get('/');
        $responseTenant->assertRedirectTo(UserModel::ROLE_HOME_ROUTES[UserModel::ROLE_TENANT]);
    }

    public function testEmptyPropertiesShowsInformativeMessage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('No hay propiedades disponibles');
    }

    public function testLandingExplainsTheProcessInStepsForTenantsAndOwners(): void
    {
        $body = (string) $this->get('/')->getBody();

        // Two ordered lists (tenants and owners), each with its steps.
        $this->assertSame(2, preg_match_all('/<ol class="landing-steps">(.*?)<\/ol>/s', $body, $lists));

        foreach ($lists[1] as $list) {
            $this->assertGreaterThanOrEqual(3, substr_count($list, '<li>'));
        }

        $this->assertStringContainsString('Para Inquilinos', $body);
        $this->assertStringContainsString('Para Propietarios', $body);
    }

    public function testLandingContactDataComesFromTheConfiguration(): void
    {
        $agency          = new Agency();
        $agency->address = 'Calle Configurada 123, Neuquén';
        $agency->phone   = '+54 (11) 5555-0000';
        $agency->email   = 'ventas@agencia.example.test';
        $agency->hours   = 'Martes a Sábados de 10 a 16 hs';
        Factories::injectMock('config', 'Agency', $agency);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Calle Configurada 123, Neuquén');
        $response->assertSee('+54 (11) 5555-0000');
        $response->assertSee('ventas@agencia.example.test');
        $response->assertSee('Martes a Sábados de 10 a 16 hs');
        $response->assertDontSee('contacto@inmogestion2026.com.ar');
    }

    public function testPublicFooterKeepsThePublishLinkOnEveryPublicPage(): void
    {
        $ownerId    = $this->createUser(UserModel::ROLE_OWNER);
        $propertyId = $this->createProperty($ownerId);

        foreach (['/', 'catalogo-inmuebles', 'catalogo-inmuebles/' . $propertyId] as $path) {
            $response = $this->get($path);

            $response->assertStatus(200);
            $response->assertSee('id="publish-property-link"');
            $response->assertSee('Publicar mi inmueble');
            $response->assertSee('href="' . site_url('propietario/inmuebles/nuevo') . '"');
        }
    }

    public function testCatalogCardsOfferTheRequestButtonOnlyToTenants(): void
    {
        $ownerId    = $this->createUser(UserModel::ROLE_OWNER);
        $propertyId = $this->createProperty($ownerId);
        $tenantId   = $this->createUser(UserModel::ROLE_TENANT);
        $requestUrl = site_url('inquilino/inmuebles/' . $propertyId . '/solicitar');

        $tenant = $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))->get('catalogo-inmuebles');
        $tenant->assertStatus(200);
        $tenant->assertSee('Ver detalle');
        $tenant->assertSee('Solicitar alquiler');
        $tenant->assertSee($requestUrl);

        $_SESSION = [];
        $visitor  = $this->withSession([])->get('catalogo-inmuebles');
        $visitor->assertStatus(200);
        $visitor->assertSee('Ver detalle');
        $visitor->assertDontSee('Solicitar alquiler');
        $visitor->assertDontSee($requestUrl);
    }

    public function testOnlyActiveApprovedAndAvailablePropertiesOfActiveOwnersAreShown(): void
    {
        $activeOwner   = $this->createUser(UserModel::ROLE_OWNER);
        $inactiveOwner = $this->createUser(UserModel::ROLE_OWNER, 'Inactivo', 'Owner', 'inactive@test.com', true);

        // Visible property
        $this->createProperty($activeOwner, [
            'address'         => 'Calle Visible 123',
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
        ]);

        // Unapproved property (pending)
        $this->createProperty($activeOwner, [
            'address'         => 'Calle Pendiente 456',
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_PENDING,
        ]);

        // Rented property
        $this->createProperty($activeOwner, [
            'address'         => 'Calle Alquilada 789',
            'status'          => PropertyModel::STATUS_RENTED,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
        ]);

        // Deactivated property
        $this->createProperty($activeOwner, [
            'address'         => 'Calle Deactivada 101',
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
            'deleted_at'      => date('Y-m-d H:i:s'),
        ]);

        // Property of inactive owner
        $this->createProperty($inactiveOwner, [
            'address'         => 'Calle Propietario Inactivo 202',
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Calle Visible 123');
        $response->assertDontSee('Calle Pendiente 456');
        $response->assertDontSee('Calle Alquilada 789');
        $response->assertDontSee('Calle Deactivada 101');
        $response->assertDontSee('Calle Propietario Inactivo 202');
    }

    /**
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionFor(int $userId, string $role): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertIsArray($user);

        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => (string) $user['email'],
            'user_name'    => trim($user['first_name'] . ' ' . $user['last_name']),
            'user_role'    => $role,
        ];
    }

    private function createUser(
        string $roleName,
        string $firstName = 'Usuario',
        string $lastName = 'Prueba',
        ?string $email = null,
        bool $deactivated = false
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->userSequence++;
        $email ??= "{$roleName}.hu28.{$this->userSequence}@example.test";
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (35000000 + $this->userSequence),
            'email'           => $email,
            'phone'           => '2994000000',
            'address'         => 'Dirección interna 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'deleted_at'      => $deactivated ? $now : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert(array_merge([
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => PropertyModel::TYPE_HOUSE,
            'rooms'             => 3,
            'surface'           => 90.00,
            'included_services' => 'Agua, luz',
            'base_rent'         => '420000.00',
            'description'       => 'Descripción de prueba',
            'status'            => PropertyModel::STATUS_AVAILABLE,
            'approval_status'   => PropertyModel::APPROVAL_APPROVED,
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }
}
