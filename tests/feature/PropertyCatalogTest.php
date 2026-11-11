<?php

use App\Database\Seeds\RoleSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance and authorization tests for HU-08 property catalogs.
 *
 * @internal
 */
final class PropertyCatalogTest extends CIUnitTestCase
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

        parent::tearDown();
    }

    public function testAdministratorCanCombineStatusTypePriceAndOwnerFilters(): void
    {
        $admin       = $this->sessionFor($this->createUser('admin'), 'admin');
        $firstOwner  = $this->createUser('owner', 'Ana', 'López', 'ana.hu08@example.test');
        $secondOwner = $this->createUser('owner', 'Luis', 'Paz', 'luis.hu08@example.test');
        $this->createProperty($firstOwner, [
            'address'   => 'Casa coincidente',
            'type'      => 'house',
            'status'    => 'available',
            'base_rent' => '350000.00',
        ]);
        $this->createProperty($firstOwner, [
            'address'   => 'Casa alquilada',
            'type'      => 'house',
            'status'    => 'rented',
            'base_rent' => '350000.00',
        ]);
        $this->createProperty($secondOwner, [
            'address'   => 'Departamento ajeno',
            'type'      => 'apartment',
            'status'    => 'available',
            'base_rent' => '350000.00',
        ]);

        $response = $this->withSession($admin)->get(
            "admin/inmuebles?status=available&type=house&min_price=300000&max_price=400000&owner_id={$firstOwner}"
        );

        $response->assertOK();
        $response->assertSee('Casa coincidente');
        $response->assertDontSee('Casa alquilada');
        $response->assertDontSee('Departamento ajeno');
        $response->assertSee('Ana López');
    }

    public function testInvalidFiltersAreReportedAndNoResultsHaveAnExplicitMessage(): void
    {
        $admin = $this->sessionFor($this->createUser('admin'), 'admin');
        $this->createProperty($this->createUser('owner'));

        $invalid = $this->withSession($admin)->get('admin/inmuebles?min_price=900000&max_price=100000');
        $invalid->assertOK();
        $invalid->assertSee('El precio máximo debe ser igual o mayor al precio mínimo.');

        $empty = $this->withSession($admin)->get('admin/inmuebles?status=maintenance');
        $empty->assertOK();
        $empty->assertSee('No se encontraron inmuebles con los filtros seleccionados.');
    }

    public function testOwnerCanSeeAndFilterOnlyTheirOwnProperties(): void
    {
        $owner      = $this->createUser('owner', 'María', 'Gómez', 'maria.hu08@example.test');
        $otherOwner = $this->createUser('owner', 'Otro', 'Propietario', 'otro.hu08@example.test');
        $this->createProperty($owner, [
            'address'   => 'Mi casa disponible',
            'type'      => 'house',
            'status'    => 'available',
            'base_rent' => '500000.00',
        ]);
        $this->createProperty($owner, [
            'address'   => 'Mi departamento alquilado',
            'type'      => 'apartment',
            'status'    => 'rented',
            'base_rent' => '400000.00',
        ]);
        $this->createProperty($otherOwner, ['address' => 'Propiedad de otra cuenta']);

        $response = $this->withSession($this->sessionFor($owner, 'owner'))->get('propietario/inmuebles');

        $response->assertOK();
        $response->assertSee('Mi casa disponible');
        $response->assertSee('Mi departamento alquilado');
        $response->assertSee('Alquilado');
        $response->assertDontSee('Propiedad de otra cuenta');
        $response->assertDontSee('otro.hu08@example.test');

        $filtered = $this->withSession($this->sessionFor($owner, 'owner'))->get(
            'propietario/inmuebles?status=available&type=house&min_price=450000&max_price=550000'
        );
        $filtered->assertOK();
        $filtered->assertSee('Mi casa disponible');
        $filtered->assertDontSee('Mi departamento alquilado');
        $filtered->assertDontSee('Propiedad de otra cuenta');
    }

    public function testNonOwnersCannotAccessTheOwnerCatalog(): void
    {
        $tenant  = $this->createUser('tenant');
        $session = $this->sessionFor($tenant, 'tenant');

        $this->withSession($session)
            ->get('propietario/inmuebles')
            ->assertRedirectTo('/inquilino/estado-cuenta');
    }

    public function testPublicCatalogContainsOnlyAvailablePropertiesAndNoOwnerData(): void
    {
        $owner = $this->createUser('owner', 'Dato', 'Interno', 'interno.hu08@example.test');
        $this->createProperty($owner, ['address' => 'Propiedad pública disponible']);
        $this->createProperty($owner, [
            'address' => 'Propiedad alquilada',
            'status'  => 'rented',
        ]);
        $this->createProperty($owner, [
            'address' => 'Propiedad en mantenimiento',
            'status'  => 'maintenance',
        ]);
        $this->createProperty($owner, [
            'address'    => 'Propiedad dada de baja',
            'deleted_at' => '2026-01-02 10:00:00',
        ]);

        $response = $this->get('catalogo-inmuebles');

        $response->assertOK();
        $response->assertSee('Propiedad pública disponible');
        $response->assertSee('Disponible');
        $response->assertDontSee('Propiedad alquilada');
        $response->assertDontSee('Propiedad en mantenimiento');
        $response->assertDontSee('Propiedad dada de baja');
        $response->assertDontSee('interno.hu08@example.test');
        $response->assertDontSee('Dato Interno');
    }

    public function testPublicCatalogFiltersAvailablePropertiesAndIgnoresRequestedStatus(): void
    {
        $owner = $this->createUser('owner');
        $this->createProperty($owner, [
            'address'   => 'Departamento en rango',
            'type'      => 'apartment',
            'status'    => 'available',
            'base_rent' => '420000.00',
        ]);
        $this->createProperty($owner, [
            'address'   => 'Casa fuera del tipo',
            'type'      => 'house',
            'status'    => 'available',
            'base_rent' => '420000.00',
        ]);
        $this->createProperty($owner, [
            'address'   => 'Departamento alquilado',
            'type'      => 'apartment',
            'status'    => 'rented',
            'base_rent' => '420000.00',
        ]);

        $response = $this->get('catalogo-inmuebles?type=apartment&min_price=400000&max_price=450000&status=rented');

        $response->assertOK();
        $response->assertSee('Departamento en rango');
        $response->assertDontSee('Casa fuera del tipo');
        $response->assertDontSee('Departamento alquilado');
    }

    public function testPublicCatalogReportsWhenNoPropertyMatches(): void
    {
        $this->createProperty($this->createUser('owner'), [
            'type'      => 'house',
            'base_rent' => '420000.00',
        ]);

        $response = $this->get('catalogo-inmuebles?type=office');

        $response->assertOK();
        $response->assertSee('No se encontraron propiedades disponibles con los filtros seleccionados.');
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
        ?string $email = null
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->userSequence++;
        $email ??= "{$roleName}.hu08.{$this->userSequence}@example.test";
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (30000000 + $this->userSequence),
            'email'           => $email,
            'phone'           => '2994000000',
            'address'         => 'Dirección interna 123',
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
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => 3,
            'surface'           => 90.00,
            'included_services' => 'Agua, luz',
            'base_rent'         => '420000.00',
            'description'       => 'Descripción de prueba',
            'status'            => 'available',
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }
}
