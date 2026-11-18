<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Agency;

/**
 * Acceptance tests for HU-29: public detail of a property (`/catalogo-inmuebles/{id}`).
 *
 * @internal
 */
final class PublicPropertyDetailTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Factories::reset('config');

        parent::tearDown();
    }

    // ---- Scenario 1: public sheet ---------------------------------------------------------------------------------

    public function testVisitorSeesTheFullSheetWithoutOwnerContractsOrAuditFields(): void
    {
        $ownerId    = $this->createUser(UserModel::ROLE_OWNER, 'Eulalia', 'Reservada');
        $propertyId = $this->createProperty($ownerId, [
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => PropertyModel::TYPE_APARTMENT,
            'rooms'             => 3,
            'surface'           => 68.50,
            'included_services' => 'Agua, expensas',
            'base_rent'         => '650000.00',
            'description'       => "Luminoso, con balcón.\nAcepta mascotas.",
        ]);

        $response = $this->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        foreach (['Mitre 845', 'Cipolletti', 'Departamento', '3 ambientes', '68.5', 'Agua, expensas', '$ 650.000', 'Luminoso, con balcón.', 'Acepta mascotas.', 'Disponible'] as $visible) {
            $response->assertSee($visible);
        }

        // Neither the owner, nor contracts, nor audit fields reach the page.
        $body = html_entity_decode((string) $response->getBody(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        foreach (['owner_id', 'Eulalia', 'Reservada', 'hu29@example.test', 'document_number', 'created_at', 'updated_at', 'deleted_at', 'approval_status', 'rejection_reason', 'Contrato #'] as $internal) {
            $this->assertStringNotContainsString($internal, $body, "Internal data leaked: {$internal}");
        }
    }

    public function testThePublicQueryDoesNotSelectTheOwnerNorAuditColumns(): void
    {
        $ownerId    = $this->createUser(UserModel::ROLE_OWNER);
        $propertyId = $this->createProperty($ownerId);

        $row = model(PropertyModel::class)->findPublicDetail($propertyId);

        $this->assertIsArray($row);

        foreach (['owner_id', 'created_at', 'updated_at', 'deleted_at', 'approval_status', 'rejection_reason', 'approved_by'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $row);
        }
    }

    // ---- Scenario 2: call to action by role ------------------------------------------------------------------------

    public function testVisitorIsOfferedToSignInOrRegisterAsTenant(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));

        $response = $this->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        $response->assertSee('btn-action-login');
        $response->assertSee(site_url('auth/login'));
        $response->assertSee('btn-action-register');
        $response->assertSee(site_url('auth/register?role=tenant'));
        $response->assertSee('Registrate como inquilino para solicitar este alquiler');
        $response->assertDontSee('Solicitar alquiler');
    }

    public function testTenantSeesTheRequestButton(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));
        $tenantId   = $this->createUser(UserModel::ROLE_TENANT);

        $response = $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        $response->assertSee('btn-action-request');
        $response->assertSee(site_url('inquilino/inmuebles/' . $propertyId . '/solicitar'));
        $response->assertDontSee('btn-action-register');

        // The link of the button leads to the form of HU-30.
        $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))
            ->get('inquilino/inmuebles/' . $propertyId . '/solicitar')
            ->assertStatus(200);
    }

    public function testOwnersAdministratorsAndEmployeesSeeNoRequestButtonButAPublicViewNotice(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));

        foreach ([UserModel::ROLE_OWNER, UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE] as $role) {
            $userId   = $this->createUser($role);
            $response = $this->withSession($this->sessionFor($userId, $role))->get('catalogo-inmuebles/' . $propertyId);

            $response->assertStatus(200);
            $response->assertDontSee('btn-action-request');
            $response->assertDontSee('Solicitar alquiler');
            $response->assertDontSee('btn-action-register');
            $response->assertSee('ficha pública');
            $response->assertSee(UserModel::labelForRole($role));
        }
    }

    // ---- Scenario 3: property that is not publicly available -------------------------------------------------------

    public function testPropertiesThatAreNotPubliclyAvailableAnswer404(): void
    {
        $ownerId         = $this->createUser(UserModel::ROLE_OWNER);
        $deactivatedUser = $this->createUser(UserModel::ROLE_OWNER, 'Dado', 'DeBaja', true);

        $visible = $this->createProperty($ownerId, ['address' => 'Calle Visible 1']);
        $hidden  = [
            'rented'          => $this->createProperty($ownerId, ['status' => PropertyModel::STATUS_RENTED]),
            'maintenance'     => $this->createProperty($ownerId, ['status' => PropertyModel::STATUS_MAINTENANCE]),
            'deactivated'     => $this->createProperty($ownerId, ['deleted_at' => '2026-11-01 10:00:00']),
            'pending'         => $this->createProperty($ownerId, ['approval_status' => PropertyModel::APPROVAL_PENDING]),
            'rejected'        => $this->createProperty($ownerId, [
                'approval_status'  => PropertyModel::APPROVAL_REJECTED,
                'rejection_reason' => 'Faltan datos',
            ]),
            'inactive owner'  => $this->createProperty($deactivatedUser),
        ];

        $this->get('catalogo-inmuebles/' . $visible)->assertStatus(200);

        foreach ($hidden as $label => $propertyId) {
            $this->assertNotFound('catalogo-inmuebles/' . $propertyId, $label);
        }
    }

    public function testUnknownAndMalformedIdentifiersAnswer404(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));

        $this->assertNotFound('catalogo-inmuebles/' . ($propertyId + 1000), 'unknown');
        $this->assertNotFound('catalogo-inmuebles/0', 'zero');
        $this->assertNotFound('catalogo-inmuebles/abc', 'text');
        $this->assertNotFound('catalogo-inmuebles/' . $propertyId . 'abc', 'digits and text');
        $this->assertNotFound('catalogo-inmuebles/-1', 'negative');
        // Too long for an integer: a plain 404, not a type error (HTTP 500).
        $this->assertNotFound('catalogo-inmuebles/99999999999999999999', 'too long');
    }

    public function testTheDetailIsReadOnly(): void
    {
        foreach (array_keys(service('routes')->getRoutes('POST')) as $route) {
            $this->assertStringStartsNotWith('catalogo-inmuebles', (string) $route, 'The public catalog only exposes GET routes.');
        }
    }

    // ---- Scenario 4: contact with the agency -----------------------------------------------------------------------

    public function testContactCardShowsPhoneEmailAndWhatsAppLinkFromTheConfiguration(): void
    {
        $agency           = new Agency();
        $agency->phone    = '+54 (11) 5555-0000';
        $agency->email    = 'ventas@agencia.example.test';
        $agency->hours    = 'Martes a Sábados de 10 a 16 hs';
        $agency->whatsapp = '+54 9 11 5555-0000';
        Factories::injectMock('config', 'Agency', $agency);

        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER), ['address' => 'Mitre 845', 'city' => 'Cipolletti']);

        $response = $this->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        $response->assertSee('+54 (11) 5555-0000');
        $response->assertSee('ventas@agencia.example.test');
        $response->assertSee('Martes a Sábados de 10 a 16 hs');
        $response->assertSee('https://wa.me/5491155550000?text=');
        // The prefilled message carries the address; the attribute escaping writes `%` as an entity, so it is decoded.
        $this->assertStringContainsString(
            rawurlencode('Mitre 845'),
            html_entity_decode((string) $response->getBody(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
        $response->assertDontSee('contacto@inmogestion2026.com.ar');
    }

    public function testContactCardShowsTheDefaultAgencyData(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));
        $agency     = new Agency();

        $response = $this->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        $response->assertSee($agency->phone);
        $response->assertSee($agency->email);
        $response->assertSee('https://wa.me/' . $agency->whatsapp . '?text=');
    }

    // ---- Links from the catalog and back to it ---------------------------------------------------------------------

    public function testCatalogCardsLinkToTheDetail(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER), ['address' => 'Calle Catalogo 9']);

        $response = $this->get('catalogo-inmuebles');

        $response->assertStatus(200);
        $response->assertSee('Ver detalle');
        $response->assertSee(site_url('catalogo-inmuebles/' . $propertyId));
    }

    public function testBackToTheCatalogKeepsTheFiltersWhenComingFromTheCatalog(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));
        $catalogUrl = site_url('catalogo-inmuebles');

        // The in-app catalog URL carries `index.php`; unknown parameters are dropped.
        $back = $this->backUrlFor($propertyId, $catalogUrl . '?type=house&min_price=100000&max_price=500000&page=2&evil=1');

        $this->assertStringStartsWith($catalogUrl . '?', $back);
        parse_str((string) parse_url($back, PHP_URL_QUERY), $query);
        $this->assertSame(['type' => 'house', 'min_price' => '100000', 'max_price' => '500000', 'page' => '2'], $query);

        // Empty filters are not copied.
        $this->assertSame($catalogUrl . '?' . http_build_query(['type' => 'house']), $this->backUrlFor($propertyId, $catalogUrl . '?type=house&min_price=&max_price='));
    }

    public function testBackToTheCatalogIgnoresAnyOtherReferer(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));
        $catalogUrl = site_url('catalogo-inmuebles');

        // No referer, the landing, another page of the site and a page of another site.
        $this->assertSame($catalogUrl, $this->backUrlFor($propertyId, null));
        $this->assertSame($catalogUrl, $this->backUrlFor($propertyId, site_url('/') . '?type=house'));
        $this->assertSame($catalogUrl, $this->backUrlFor($propertyId, site_url('catalogo-inmuebles/' . $propertyId) . '?type=house'));
        $this->assertSame($catalogUrl, $this->backUrlFor($propertyId, 'https://otro-sitio.example.test/catalogo-inmuebles?type=house'));
        $this->assertSame($catalogUrl, $this->backUrlFor($propertyId, 'esto no es una url'));
    }

    // ---- Helpers ---------------------------------------------------------------------------------------------------

    /**
     * Returns the address of the «Volver al catálogo» button of the detail page for a given Referer.
     */
    private function backUrlFor(int $propertyId, ?string $referer): string
    {
        $_SESSION = [];
        $response = $this->withSession([])->withHeaders(['Referer' => $referer ?? ''])->get('catalogo-inmuebles/' . $propertyId);
        $body     = html_entity_decode((string) $response->getBody(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertSame(1, preg_match('/<a href="([^"]+)" class="btn btn-secondary w-full" id="btn-back-catalog">/', $body, $matches), 'The page has the back button.');

        return $matches[1];
    }

    /**
     * Asserts that GET {path} is a 404 (CodeIgniter throws PageNotFoundException in the test environment).
     */
    private function assertNotFound(string $path, string $label): void
    {
        $_SESSION = [];

        try {
            $response = $this->withSession([])->get($path);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertSame(404, $response->response()->getStatusCode(), "Case {$label}: {$path} must answer 404");
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

    private function createUser(string $roleName, string $firstName = 'Usuario', string $lastName = 'Prueba', bool $deactivated = false): int
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->sequence++;
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (38000000 + $this->sequence),
            'email'           => "{$roleName}{$this->sequence}.hu29@example.test",
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
            'address'           => 'Calle ' . (++$this->sequence),
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
