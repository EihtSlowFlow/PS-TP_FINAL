<?php

use App\Controllers\PropertyController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance and security tests for HU-07: property creation and edition.
 *
 * @internal
 */
final class PropertyManagementTest extends CIUnitTestCase
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

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: creation
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function propertyTypeProvider(): array
    {
        return [
            'apartment' => ['apartment', '3', 'Departamento'],
            'house'     => ['house', '4', 'Casa'],
            'commercial' => ['commercial', '1', 'Local comercial'],
            'land'      => ['land', '', 'Terreno'],
            'office'    => ['office', '2', 'Oficina'],
        ];
    }

    #[DataProvider('propertyTypeProvider')]
    public function testScenario1AdminCreatesAPropertyForAnActiveOwnerWithStatusAvailable(
        string $type,
        string $rooms,
        string $expectedLabel
    ): void {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        $response = $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, [
            'type'  => $type,
            'rooms' => $rooms,
        ]));

        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_CREATED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('properties')->get()->getRow();
        $this->assertNotNull($row);
        $this->assertSame($ownerId, (int) $row->owner_id);
        $this->assertSame('Belgrano 1250, 4°B', $row->address);
        $this->assertSame('Cipolletti', $row->city);
        $this->assertSame($type, $row->type);
        $this->assertSame($rooms === '' ? null : (int) $rooms, $row->rooms === null ? null : (int) $row->rooms);
        $this->assertSame('54.50', $row->surface);
        $this->assertSame('Agua, luz', $row->included_services);
        $this->assertSame('420000.00', $row->base_rent);
        $this->assertSame('Luminoso, con balcón', $row->description);
        $this->assertSame('available', $row->status);
        $this->assertNotNull($row->created_at);
        $this->assertNull($row->deleted_at);

        // It is shown in the listing with its status and type.
        $list = $this->withSession($admin)->get('admin/inmuebles');
        $list->assertStatus(200);
        $list->assertSee('Belgrano 1250, 4°B');
        $list->assertSee($expectedLabel);
        $list->assertSee('Disponible');
        $list->assertSee('Gómez');
    }

    public function testANewPropertyIsAlwaysCreatedAsAvailableWhateverStatusIsPosted(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        foreach (['rented', 'maintenance'] as $postedStatus) {
            $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, ['status' => $postedStatus]))
                ->assertRedirectTo('/admin/inmuebles');
        }

        $statuses = array_column($this->db->table('properties')->select('status')->get()->getResultArray(), 'status');
        $this->assertSame(['available', 'available'], $statuses);
    }

    public function testOptionalFieldsCanBeLeftEmptyAndAreStoredAsNull(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, [
            'surface'           => '',
            'included_services' => '',
            'description'       => '',
        ]))->assertRedirectTo('/admin/inmuebles');

        $row = $this->db->table('properties')->get()->getRow();
        $this->assertNull($row->surface);
        $this->assertNull($row->included_services);
        $this->assertNull($row->description);
    }

    public function testThePriceAcceptsACommaOrADotAsDecimalSeparator(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, ['base_rent' => '$ 420000,50']))
            ->assertRedirectTo('/admin/inmuebles');

        $this->assertSame('420000.50', $this->db->table('properties')->get()->getRow()->base_rent);
    }

    public function testNewPropertyFormOffersTheTypesAndExplainsTheInitialStatus(): void
    {
        $response = $this->withSession($this->sessionFor('admin'))->get('admin/inmuebles/nuevo');

        $response->assertStatus(200);
        $response->assertSee('Nuevo inmueble');
        foreach (['Departamento', 'Casa', 'Local comercial', 'Terreno', 'Oficina'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee('Disponible');
        $response->assertSee('Se asigna automáticamente al guardar');
        $response->assertDontSee('novalidate');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: invalid data
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'price zero'              => [['base_rent' => '0'], 'base_rent'],
            'price negative'          => [['base_rent' => '-100'], 'base_rent'],
            'price empty'             => [['base_rent' => ''], 'base_rent'],
            'price text'              => [['base_rent' => 'barato'], 'base_rent'],
            'price with 3 decimals'   => [['base_rent' => '100.555'], 'base_rent'],
            'rooms zero'              => [['rooms' => '0'], 'rooms'],
            'rooms negative'          => [['rooms' => '-2'], 'rooms'],
            'rooms decimal'           => [['rooms' => '2.5'], 'rooms'],
            'rooms text'              => [['rooms' => 'dos'], 'rooms'],
            'rooms empty (not land)'  => [['rooms' => ''], 'rooms'],
            'rooms zero on land'      => [['type' => 'land', 'rooms' => '0'], 'rooms'],
            'surface zero'            => [['surface' => '0'], 'surface'],
            'surface negative'        => [['surface' => '-10'], 'surface'],
            'surface text'            => [['surface' => 'amplia'], 'surface'],
            'city empty'              => [['city' => ''], 'city'],
            'city blank'              => [['city' => '   '], 'city'],
            'address empty'           => [['address' => ''], 'address'],
            'type invalid'            => [['type' => 'castle'], 'type'],
            'type empty'              => [['type' => ''], 'type'],
            'services too long'       => [['included_services' => str_repeat('a', 256)], 'included_services'],
            'description too long'    => [['description' => str_repeat('a', 1001)], 'description'],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidDataProvider')]
    public function testScenario2InvalidDataIsRejectedAndTheFieldErrorIsReported(array $overrides, string $errorField): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        $response = $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, $overrides));

        $response->assertRedirectTo('/admin/inmuebles/nuevo');
        $this->assertSame(PropertyController::VALIDATION_ERROR_MESSAGE, session()->getFlashdata('error'));

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey($errorField, $errors);
        $this->assertNotSame('', trim((string) $errors[$errorField]));

        $this->assertSame(0, $this->db->table('properties')->countAllResults(), 'Nothing must be stored.');
    }

    public function testScenario2EveryInvalidFieldGetsItsOwnErrorAtTheSameTime(): void
    {
        $admin = $this->sessionFor('admin');

        $this->postWithSession($admin, 'admin/inmuebles', [
            'owner_id'  => '',
            'address'   => '',
            'city'      => '',
            'type'      => 'apartment',
            'rooms'     => '0',
            'surface'   => '-1',
            'base_rent' => '0',
        ])->assertRedirectTo('/admin/inmuebles/nuevo');

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        foreach (['owner_id', 'address', 'city', 'rooms', 'surface', 'base_rent'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Missing the error of {$field}");
        }
    }

    public function testThePostedValuesAreKeptInTheFormAfterAValidationError(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'María', 'Gómez', '20111222', 'maria.gomez.hu07@example.com');

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($ownerId, ['city' => '', 'address' => 'Roca 1020']))
            ->assertRedirectTo('/admin/inmuebles/nuevo');

        $this->assertSame('Roca 1020', old('address'));
        $this->assertSame('420000', old('base_rent'));
    }

    // ---------------------------------------------------------------------------------------------
    // Owners (HU-05 integration)
    // ---------------------------------------------------------------------------------------------

    public function testOnlyActiveOwnersAreOfferedInTheSelectorAndAcceptedOnSave(): void
    {
        $admin         = $this->sessionFor('admin');
        $activeOwner   = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu07@example.com');
        $inactiveOwner = $this->createUser('owner', 'Hernán', 'Paz', '28349880', 'hernan.paz.hu07@example.com', '2026-10-01 10:00:00');
        $tenant        = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu07@example.com');

        $form = $this->withSession($admin)->get('admin/inmuebles/nuevo');
        $form->assertSee('Sosa, Ricardo');
        $form->assertDontSee('Paz, Hernán');
        $form->assertDontSee('Rojas, Camila');

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($inactiveOwner))
            ->assertRedirectTo('/admin/inmuebles/nuevo');
        $this->assertSame(PropertyController::INACTIVE_OWNER_MESSAGE, session()->getFlashdata('errors')['owner_id'] ?? null);

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($tenant))
            ->assertRedirectTo('/admin/inmuebles/nuevo');
        $this->assertSame(PropertyController::INVALID_OWNER_MESSAGE, session()->getFlashdata('errors')['owner_id'] ?? null);

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload(999999))
            ->assertRedirectTo('/admin/inmuebles/nuevo');
        $this->assertSame(PropertyController::INVALID_OWNER_MESSAGE, session()->getFlashdata('errors')['owner_id'] ?? null);

        $this->assertSame(0, $this->db->table('properties')->countAllResults(), 'Nothing must be stored.');

        $this->postWithSession($admin, 'admin/inmuebles', $this->validPayload($activeOwner))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(1, $this->db->table('properties')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: edition
    // ---------------------------------------------------------------------------------------------

    public function testScenario3AdminUpdatesPriceAndDescriptionAndTheModificationDateIsRecorded(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        $response = $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", [
            'owner_id'          => (string) $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => '4',
            'surface'           => '120.5',
            'included_services' => 'Agua',
            'base_rent'         => '700000',
            'description'       => 'Casa reciclada a nuevo',
        ]);

        $response->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(PropertyController::PROPERTY_UPDATED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('properties')->where('id', $propertyId)->get()->getRow();
        $this->assertSame('700000.00', $row->base_rent);
        $this->assertSame('Casa reciclada a nuevo', $row->description);
        $this->assertSame('Mitre 845', $row->address);
        $this->assertSame('available', $row->status);
        $this->assertSame('2026-01-01 10:00:00', $row->created_at);
        $this->assertGreaterThan(strtotime('2026-01-01 10:00:00'), strtotime($row->updated_at));
        $this->assertLessThanOrEqual(120, abs(time() - strtotime($row->updated_at)), 'updated_at must be the moment of the edition.');

        // The form shows the modification date.
        $edit = $this->withSession($admin)->get("admin/inmuebles/editar/{$propertyId}");
        $edit->assertStatus(200);
        $edit->assertSee('Última modificación');
        $edit->assertSee(date('d/m/Y'));
    }

    public function testTheEditionFormShowsTheStoredData(): void
    {
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        $response = $this->withSession($this->sessionFor('admin'))->get("admin/inmuebles/editar/{$propertyId}");

        $response->assertStatus(200);
        $response->assertSee('Editar inmueble: Mitre 845');
        $this->assertStringContainsString('value="Mitre 845"', (string) $response->getBody());
        $response->assertSee('Quiroga, Marta');
        $response->assertSee('Casa con jardín');
        $response->assertSee('650000');
        $response->assertDontSee('650000.00');
    }

    public function testTheStatusCanBeChangedBetweenAvailableAndMaintenance(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['status' => 'maintenance']))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame('maintenance', $this->statusOf($propertyId));

        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['status' => 'available']))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame('available', $this->statusOf($propertyId));

        // Leaving the status out keeps the current one.
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['status' => 'maintenance']));
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId));
        $this->assertSame('maintenance', $this->statusOf($propertyId));
    }

    public function testRentedCannotBeAssignedByHandAndANonSenseStatusIsRejected(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        foreach (['rented', 'vendido'] as $postedStatus) {
            $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['status' => $postedStatus]))
                ->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");
            $this->assertSame(PropertyController::INVALID_STATUS_MESSAGE, session()->getFlashdata('errors')['status'] ?? null);
            $this->assertSame('available', $this->statusOf($propertyId));
        }
    }

    public function testARentedPropertyKeepsItsStatusAndTheFormDoesNotOfferToChangeIt(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId, ['status' => 'rented']);

        $edit = $this->withSession($admin)->get("admin/inmuebles/editar/{$propertyId}");
        $edit->assertStatus(200);
        $edit->assertSee('Alquilado');
        $edit->assertSee('lo asigna el contrato');
        $edit->assertDontSee('status_maintenance');

        // Editing other data does not touch the status...
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['base_rent' => '800000']))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame('rented', $this->statusOf($propertyId));

        // ...and trying to change it is rejected.
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['status' => 'available']))
            ->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");
        $this->assertSame(PropertyController::RENTED_STATUS_LOCKED_MESSAGE, session()->getFlashdata('errors')['status'] ?? null);
        $this->assertSame('rented', $this->statusOf($propertyId));
    }

    public function testEditionRejectsInvalidDataAndKeepsTheStoredValues(): void
    {
        $admin      = $this->sessionFor('admin');
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, [
            'base_rent' => '0',
            'rooms'     => '-1',
            'city'      => '',
        ]))->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");

        $errors = session()->getFlashdata('errors');
        foreach (['base_rent', 'rooms', 'city'] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }

        $row = $this->db->table('properties')->where('id', $propertyId)->get()->getRow();
        $this->assertSame('650000.00', $row->base_rent);
        $this->assertSame('Cipolletti', $row->city);
        $this->assertSame('2026-01-01 10:00:00', $row->updated_at);
    }

    public function testEditionKeepsTheCurrentOwnerEvenIfDeactivatedButNewOwnersMustBeActive(): void
    {
        $admin         = $this->sessionFor('admin');
        $currentOwner  = $this->createUser('owner', 'Hernán', 'Paz', '28349880', 'hernan.paz.hu07@example.com');
        $activeOwner   = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu07@example.com');
        $otherInactive = $this->createUser('owner', 'Jorge', 'Díaz', '20555666', 'jorge.diaz.hu07@example.com', '2026-09-01 10:00:00');
        $propertyId    = $this->insertProperty($currentOwner);

        // The owner is deactivated afterwards (HU-05): the property keeps it.
        $this->db->table('users')->where('id', $currentOwner)->update(['deleted_at' => '2026-10-15 09:00:00']);

        $edit = $this->withSession($admin)->get("admin/inmuebles/editar/{$propertyId}");
        $edit->assertStatus(200);
        $edit->assertSee('Paz, Hernán');
        $edit->assertSee('(dado de baja)');
        $edit->assertDontSee('Díaz, Jorge');

        $list = $this->withSession($admin)->get('admin/inmuebles');
        $list->assertSee('Mitre 845');
        $list->assertSee('Hernán Paz');
        $list->assertSee('Dado de baja');

        // Saving other data with the same (deactivated) owner works.
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($currentOwner, ['base_rent' => '710000']))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame('710000.00', $this->db->table('properties')->where('id', $propertyId)->get()->getRow()->base_rent);

        // Moving it to another deactivated owner is rejected...
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($otherInactive))
            ->assertRedirectTo("/admin/inmuebles/editar/{$propertyId}");
        $this->assertSame(PropertyController::INACTIVE_OWNER_MESSAGE, session()->getFlashdata('errors')['owner_id'] ?? null);

        // ...and to an active owner is accepted.
        $this->postWithSession($admin, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($activeOwner))
            ->assertRedirectTo('/admin/inmuebles');
        $this->assertSame($activeOwner, (int) $this->db->table('properties')->where('id', $propertyId)->get()->getRow()->owner_id);
    }

    public function testEditingAnUnknownOrDeactivatedPropertyRedirectsWithANotFoundMessage(): void
    {
        $admin       = $this->sessionFor('admin');
        $ownerId     = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $deactivated = $this->insertProperty($ownerId, ['deleted_at' => '2026-10-01 10:00:00']);

        foreach ([999999, $deactivated] as $id) {
            $this->withSession($admin)->get("admin/inmuebles/editar/{$id}")->assertRedirectTo('/admin/inmuebles');
            $this->assertSame(PropertyController::PROPERTY_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));

            $this->postWithSession($admin, "admin/inmuebles/editar/{$id}", $this->validPayload($ownerId))
                ->assertRedirectTo('/admin/inmuebles');
            $this->assertSame(PropertyController::PROPERTY_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
        }

        $this->assertSame('650000.00', $this->db->table('properties')->where('id', $deactivated)->get()->getRow()->base_rent);
    }

    // ---------------------------------------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------------------------------------

    public function testTheListingShowsTheDataOfEachPropertyAndLeavesOutDeactivatedOnes(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');

        $this->insertProperty($ownerId, ['address' => 'Mitre 845', 'type' => 'house', 'rooms' => 4, 'base_rent' => 650000.00, 'status' => 'rented']);
        $this->insertProperty($ownerId, ['address' => 'Sarmiento 77, 1°A', 'type' => 'apartment', 'rooms' => 2, 'base_rent' => 350000.50, 'status' => 'maintenance']);
        $this->insertProperty($ownerId, ['address' => 'Roca 1020', 'type' => 'land', 'rooms' => null, 'status' => 'available']);
        $this->insertProperty($ownerId, ['address' => 'Calle Borrada 1', 'deleted_at' => '2026-10-01 10:00:00']);

        $list = $this->withSession($admin)->get('admin/inmuebles');

        $list->assertStatus(200);
        $list->assertSee('Mitre 845');
        $list->assertSee('Casa');
        $list->assertSee('4 amb.');
        $list->assertSee('$ 650.000');
        $list->assertSee('$ 350.000,50');
        $list->assertSee('Alquilado');
        $list->assertSee('En mantenimiento');
        $list->assertSee('Terreno');
        $list->assertSee('Marta Quiroga');
        $list->assertDontSee('Calle Borrada 1');
        $list->assertSee('de <strong>3</strong> inmuebles registrados');
    }

    public function testTheListingIsPaginated(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');

        foreach (['Alsina 1', 'Belgrano 2', 'Córdoba 3'] as $address) {
            $this->insertProperty($ownerId, ['address' => $address]);
        }

        $page1 = $this->withSession($admin)->get('admin/inmuebles?per_page=2&page=1');
        $page1->assertSee('1–2');
        $page1->assertSee('Córdoba 3');
        $page1->assertDontSee('Alsina 1');

        $page2 = $this->withSession($admin)->get('admin/inmuebles?per_page=2&page=2');
        $page2->assertSee('3–3');
        $page2->assertSee('Alsina 1');
    }

    public function testTheListingEscapesTheDataItShows(): void
    {
        $admin   = $this->sessionFor('admin');
        $ownerId = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $this->insertProperty($ownerId, ['address' => '<script>alert(1)</script>']);

        $body = (string) $this->withSession($admin)->get('admin/inmuebles')->getBody();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
    }

    // ---------------------------------------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function operatorRoleProvider(): array
    {
        return [
            'admin'    => ['admin'],
            'employee' => ['employee'],
        ];
    }

    #[DataProvider('operatorRoleProvider')]
    public function testAdministratorsAndEmployeesCanUseThePropertyScreens(string $roleName): void
    {
        $session    = $this->sessionFor($roleName);
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        foreach (['admin/inmuebles', 'admin/inmuebles/nuevo', "admin/inmuebles/editar/{$propertyId}"] as $uri) {
            $this->withSession($session)->get($uri)->assertStatus(200);
        }

        $this->postWithSession($session, 'admin/inmuebles', $this->validPayload($ownerId))->assertRedirectTo('/admin/inmuebles');
        $this->assertSame(2, $this->db->table('properties')->countAllResults());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function externalRoleProvider(): array
    {
        return [
            'owner'  => ['owner', '/propietario/dashboard'],
            'tenant' => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('externalRoleProvider')]
    public function testOwnersAndTenantsCannotReachAnyPropertyRoute(string $roleName, string $ownPanel): void
    {
        $session    = $this->sessionFor($roleName);
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        foreach (['admin/inmuebles', 'admin/inmuebles/nuevo', "admin/inmuebles/editar/{$propertyId}"] as $uri) {
            $this->withSession($session)->get($uri)->assertRedirectTo($ownPanel);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession($session, 'admin/inmuebles', $this->validPayload($ownerId))->assertRedirectTo($ownPanel);
        $this->postWithSession($session, "admin/inmuebles/editar/{$propertyId}", $this->validPayload($ownerId, ['base_rent' => '1']))
            ->assertRedirectTo($ownPanel);

        $this->assertSame(1, $this->db->table('properties')->countAllResults());
        $this->assertSame('650000.00', $this->db->table('properties')->where('id', $propertyId)->get()->getRow()->base_rent);
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $ownerId    = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu07@example.com');
        $propertyId = $this->insertProperty($ownerId);

        foreach (['admin/inmuebles', 'admin/inmuebles/nuevo', "admin/inmuebles/editar/{$propertyId}"] as $uri) {
            $this->get($uri)->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession([], 'admin/inmuebles', $this->validPayload($ownerId))->assertRedirectTo('/auth/login');
        $this->assertSame(1, $this->db->table('properties')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * Creates a user with the given role and returns the session data its login would store.
     *
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionFor(string $roleName): array
    {
        $email  = "{$roleName}.session.hu07@example.com";
        $userId = $this->createUser($roleName, 'Sesión', ucfirst($roleName), (string) (30000000 + crc32($roleName) % 99999), $email);

        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => $email,
            'user_name'    => 'Sesión ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ];
    }

    private function createUser(
        string $roleName,
        string $firstName,
        string $lastName,
        string $documentNumber,
        string $email,
        ?string $deletedAt = null
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
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert(array_merge([
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => 4,
            'surface'           => 120.50,
            'included_services' => 'Agua',
            'base_rent'         => 650000.00,
            'description'       => 'Casa con jardín',
            'status'            => 'available',
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * A complete, valid form submission; tests override only what they exercise.
     *
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function validPayload(int $ownerId, array $overrides = []): array
    {
        return array_merge([
            'owner_id'          => (string) $ownerId,
            'address'           => 'Belgrano 1250, 4°B',
            'city'              => 'Cipolletti',
            'type'              => 'apartment',
            'rooms'             => '3',
            'surface'           => '54.5',
            'included_services' => 'Agua, luz',
            'base_rent'         => '420000',
            'description'       => 'Luminoso, con balcón',
        ], $overrides);
    }

    private function statusOf(int $propertyId): string
    {
        return (string) $this->db->table('properties')->where('id', $propertyId)->get()->getRow()->status;
    }

    /**
     * Sends a POST with the given session data plus a valid session CSRF token.
     *
     * @param array<string, mixed>  $sessionData
     * @param array<string, string> $postData
     */
    private function postWithSession(array $sessionData, string $uri, array $postData)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, array_merge($postData, [$tokenName => $tokenHash]));
    }
}
