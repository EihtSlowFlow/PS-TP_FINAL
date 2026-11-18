<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * The tenant reaches the catalog from the "Buscar inmuebles" entry of their panel (HU-30), so the catalog and the
 * property sheet must stay inside that panel. Visitors and the other roles keep the public layout, and the public
 * footer offers "Publicar mi inmueble" only to whoever can use it (visitors and owners).
 *
 * @internal
 */
final class TenantCatalogPanelTest extends CIUnitTestCase
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

        parent::tearDown();
    }

    public function testTenantSeesTheCatalogInsideTheirPanelWithBuscarInmueblesActive(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER), ['address' => 'Belgrano 450']);
        $tenantId   = $this->createUser(UserModel::ROLE_TENANT);

        $response = $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))->get('catalogo-inmuebles');

        $response->assertStatus(200);
        $response->assertSeeElement('aside#panel-sidebar');
        $response->assertDontSee('public-catalog-header');
        $response->assertDontSee('id="publish-property-link"');
        $this->assertActiveMenu((string) $response->getBody(), 'catalogo-inmuebles', 'Buscar inmuebles');
        $response->assertSee('Buscar inmuebles</span>');

        // The catalog itself is unchanged: cards, detail link and the tenant's request button.
        $response->assertSee('Belgrano 450');
        $response->assertSee(site_url('catalogo-inmuebles/' . $propertyId));
        $response->assertSee(site_url('inquilino/inmuebles/' . $propertyId . '/solicitar'));
    }

    public function testTenantSeesThePropertySheetInsideTheirPanel(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER), ['address' => 'Belgrano 450']);
        $tenantId   = $this->createUser(UserModel::ROLE_TENANT);

        $response = $this->withSession($this->sessionFor($tenantId, UserModel::ROLE_TENANT))
            ->get('catalogo-inmuebles/' . $propertyId);

        $response->assertStatus(200);
        $response->assertSeeElement('aside#panel-sidebar');
        $response->assertDontSee('public-catalog-header');
        $this->assertActiveMenu((string) $response->getBody(), 'catalogo-inmuebles', 'Buscar inmuebles');
        $response->assertSee('Buscar inmuebles / Ficha');
        $response->assertSee('Belgrano 450');
        $response->assertSeeElement('#btn-action-request');
    }

    public function testVisitorKeepsThePublicLayoutWithThePublishLink(): void
    {
        $propertyId = $this->createProperty($this->createUser(UserModel::ROLE_OWNER));

        foreach (['catalogo-inmuebles', 'catalogo-inmuebles/' . $propertyId] as $path) {
            $_SESSION = [];
            $response = $this->withSession([])->get($path);

            $response->assertStatus(200);
            $response->assertSee('public-catalog-header');
            $response->assertDontSee('id="panel-sidebar"');
            $response->assertSee('id="publish-property-link"');
        }
    }

    public function testOtherRolesKeepThePublicLayoutAndOnlyTheOwnerIsOfferedToPublish(): void
    {
        $ownerId    = $this->createUser(UserModel::ROLE_OWNER);
        $propertyId = $this->createProperty($ownerId);
        $users      = [
            UserModel::ROLE_OWNER    => $ownerId,
            UserModel::ROLE_ADMIN    => $this->createUser(UserModel::ROLE_ADMIN),
            UserModel::ROLE_EMPLOYEE => $this->createUser(UserModel::ROLE_EMPLOYEE),
        ];

        foreach ($users as $role => $userId) {
            foreach (['catalogo-inmuebles', 'catalogo-inmuebles/' . $propertyId] as $path) {
                $_SESSION = [];
                $response = $this->withSession($this->sessionFor($userId, $role))->get($path);

                $response->assertStatus(200);
                $response->assertSee('public-catalog-header');
                $response->assertDontSee('id="panel-sidebar"');

                if ($role === UserModel::ROLE_OWNER) {
                    $response->assertSee('id="publish-property-link"');
                } else {
                    $response->assertDontSee('id="publish-property-link"');
                }
            }
        }
    }

    public function testCardAddressLinkKeepsTheHeadingStyle(): void
    {
        $css = (string) file_get_contents(FCPATH . 'css/panel.css');

        $this->assertMatchesRegularExpression(
            '/\.public-property-card h2 a\s*\{[^}]*color:\s*inherit;[^}]*text-decoration:\s*none;/',
            $css
        );
    }

    /**
     * The property sheet once used class names that no rule styled (no columns, a default blue back link). Every class
     * of the sheet must have at least one rule in panel.css, except the plain wrappers listed here, which only group
     * blocks and inherit everything they need.
     */
    public function testEveryClassOfThePropertySheetHasAStyle(): void
    {
        $plainWrappers = [
            'property-nav-separator',
            'property-detail-header-info',
            'property-condition-item',
            'property-action-price-box',
            'property-role-action',
            'property-contact-box',
        ];

        $view = (string) file_get_contents(APPPATH . 'Views/properties/public_show.php');
        $css  = (string) file_get_contents(FCPATH . 'css/panel.css');

        preg_match_all('/class="([^"<]+)"/', $view, $matches);
        $classes = array_diff(
            array_unique(preg_split('/\s+/', trim(implode(' ', $matches[1]))) ?: []),
            $plainWrappers
        );
        $this->assertNotEmpty($classes);

        $unstyled = array_values(array_filter(
            $classes,
            static fn (string $class): bool => preg_match('/\.' . preg_quote($class, '/') . '(?![\w-])/', $css) !== 1
        ));

        $this->assertSame([], $unstyled, 'Classes of public_show.php without a CSS rule: ' . implode(', ', $unstyled));
    }

    /**
     * Asserts that the sidebar entry pointing at {path} is the highlighted one and carries {label}.
     */
    private function assertActiveMenu(string $body, string $path, string $label): void
    {
        $pattern = '#<a href="' . preg_quote(site_url($path), '#') . '" class="sidebar-link is-active">\s*<span>'
            . preg_quote($label, '#') . '</span>#';

        $this->assertMatchesRegularExpression($pattern, $body, "The \"{$label}\" sidebar entry must be active");
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

    private function createUser(string $roleName): int
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->sequence++;
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => 'Usuario',
            'last_name'       => 'Catálogo',
            'document_number' => (string) (39000000 + $this->sequence),
            'email'           => "{$roleName}{$this->sequence}.catalogo@example.test",
            'phone'           => '2994000000',
            'address'         => 'Dirección 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
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
            'owner_id'        => $ownerId,
            'address'         => 'Calle ' . (++$this->sequence),
            'city'            => 'Cipolletti',
            'type'            => PropertyModel::TYPE_HOUSE,
            'rooms'           => 3,
            'surface'         => 90.00,
            'base_rent'       => '420000.00',
            'description'     => 'Descripción de prueba',
            'status'          => PropertyModel::STATUS_AVAILABLE,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
            'created_at'      => '2026-01-01 10:00:00',
            'updated_at'      => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }
}
