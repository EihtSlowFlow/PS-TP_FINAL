<?php

use App\Controllers\ContractController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\ContractModel;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance, atomicity and security tests for HU-10: lease contract creation.
 *
 * @internal
 */
final class ContractCreationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const BLOCKING_TRIGGER = 'trg_hu10_block_property_update';

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TRIGGER IF EXISTS ' . self::BLOCKING_TRIGGER);
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: valid creation
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
    public function testScenario1OperatorCreatesAContractInForceAndThePropertyBecomesRented(string $roleName): void
    {
        $session = $this->sessionFor($roleName);
        $fixture = $this->fixture();

        $response = $this->postWithSession($session, 'admin/contratos', $this->validPayload($fixture));

        $contract = $this->db->table('contracts')->get()->getRow();
        $this->assertNotNull($contract, 'The contract must be stored.');

        $response->assertRedirectTo('/admin/contratos/' . $contract->id);
        $this->assertSame(ContractController::CONTRACT_CREATED_MESSAGE, session()->getFlashdata('success'));

        $this->assertSame($fixture['property'], (int) $contract->property_id);
        $this->assertSame($fixture['tenant'], (int) $contract->tenant_id);
        $this->assertSame($fixture['owner'], (int) $contract->owner_id);
        $this->assertSame('2026-12-01', $contract->start_date);
        $this->assertSame('2028-11-30', $contract->end_date);
        $this->assertSame('510000.00', $contract->monthly_rent);
        $this->assertSame('12.50', $contract->update_percentage);
        $this->assertSame('10.00', $contract->commission_percentage);
        $this->assertSame('10', (string) $contract->due_day);
        $this->assertSame('0.50', $contract->late_fee_daily_percentage);
        $this->assertSame('in_force', $contract->status);
        $this->assertNull($contract->closed_at);
        $this->assertNull($contract->closing_reason);
        $this->assertNotNull($contract->created_at);

        $this->assertSame('rented', $this->propertyStatus($fixture['property']));
    }

    public function testScenario1TheContractFileShowsTheDataOfTheNewContract(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture));
        $contractId = (int) $this->db->table('contracts')->get()->getRow()->id;

        $file = $this->withSession($admin)->get("admin/contratos/{$contractId}");

        $file->assertStatus(200);
        $file->assertSee("Contrato #{$contractId}");
        $file->assertSee('Vigente');
        $file->assertSee('Alquilado');
        $file->assertSee('Mitre 845');
        $file->assertSee('Quiroga, Marta');
        $file->assertSee('Rojas, Camila');
        $file->assertSee('01/12/2026');
        $file->assertSee('30/11/2028');
        $file->assertSee('24 meses');
        $file->assertSee('$ 510.000');
        $file->assertSee('12,5 %');
        $file->assertSee('Día 10 de cada mes');
        $file->assertSee('$ 51.000');
        $file->assertSee('$ 459.000');
        $file->assertSee('$ 2.550');
    }

    public function testTheContractOwnerIsAlwaysTheOwnerOfThePropertyWhateverTheFormSends(): void
    {
        $admin      = $this->sessionFor('admin');
        $fixture    = $this->fixture();
        $otherOwner = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu10@example.com');

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, ['owner_id' => (string) $otherOwner]));

        $contract = $this->db->table('contracts')->get()->getRow();
        $this->assertNotNull($contract);
        $this->assertSame($fixture['owner'], (int) $contract->owner_id);
    }

    public function testDecimalsAcceptACommaAndTheCurrencySymbol(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, [
            'monthly_rent'              => '$ 510000,50',
            'update_percentage'         => '8,25',
            'commission_percentage'     => '7,5 %',
            'late_fee_daily_percentage' => '0,5',
        ]));

        $contract = $this->db->table('contracts')->get()->getRow();
        $this->assertNotNull($contract);
        $this->assertSame('510000.50', $contract->monthly_rent);
        $this->assertSame('8.25', $contract->update_percentage);
        $this->assertSame('7.50', $contract->commission_percentage);
        $this->assertSame('0.50', $contract->late_fee_daily_percentage);
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function boundaryValuesProvider(): array
    {
        return [
            'percentages at 0 and due day 1'     => [['update_percentage' => '0', 'commission_percentage' => '0', 'late_fee_daily_percentage' => '0', 'due_day' => '1']],
            'percentages at 100 and due day 28'  => [['update_percentage' => '100', 'commission_percentage' => '100', 'late_fee_daily_percentage' => '100', 'due_day' => '28']],
            'smallest rent'                      => [['monthly_rent' => '0.01']],
            'one day contract (end = start + 1)' => [['start_date' => '2026-12-31', 'end_date' => '2027-01-01']],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('boundaryValuesProvider')]
    public function testValuesAtTheLimitsOfTheRulesAreAccepted(array $overrides): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, $overrides))
            ->assertRedirectTo('/admin/contratos/' . $this->latestContractId());

        $this->assertSame(1, $this->db->table('contracts')->countAllResults());
    }

    public function testTheFormOffersOnlyAvailablePropertiesAndActiveTenants(): void
    {
        $admin        = $this->sessionFor('admin');
        $owner        = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $deactivated  = $this->createUser('owner', 'Hernán', 'Paz', '28349880', 'hernan.paz.hu10@example.com', '2026-10-01 10:00:00');
        $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');
        $this->createUser('tenant', 'Esteban', 'Duarte', '32784112', 'esteban.duarte.hu10@example.com', '2026-10-01 10:00:00');

        $this->insertProperty($owner, ['address' => 'Disponible 100']);
        $this->insertProperty($owner, ['address' => 'Alquilada 200', 'status' => 'rented']);
        $this->insertProperty($owner, ['address' => 'Mantenimiento 300', 'status' => 'maintenance']);
        $this->insertProperty($owner, ['address' => 'Borrada 400', 'deleted_at' => '2026-10-01 10:00:00']);
        $this->insertProperty($deactivated, ['address' => 'DueñoDeBaja 500']);

        $form = $this->withSession($admin)->get('admin/contratos/nuevo');

        $form->assertStatus(200);
        $form->assertSee('Nuevo contrato de locación');
        $form->assertSee('Disponible 100');
        $form->assertSee('Rojas, Camila');
        foreach (['Alquilada 200', 'Mantenimiento 300', 'Borrada 400', 'DueñoDeBaja 500', 'Duarte, Esteban'] as $hidden) {
            $form->assertDontSee($hidden);
        }
        $form->assertDontSee('novalidate');

        // Owners are not tenants: the tenant selector lists nobody but the active tenant.
        $this->assertSame(1, preg_match('#<select\s[^>]*id="tenant_id".*?</select>#s', (string) $form->getBody(), $tenantSelect));
        $this->assertStringContainsString('Rojas, Camila', $tenantSelect[0]);
        $this->assertStringNotContainsString('Quiroga', $tenantSelect[0]);
    }

    public function testTheFormPreselectsThePropertyOfTheQueryStringAndSuggestsItsBaseRent(): void
    {
        $fixture = $this->fixture();

        $form = $this->withSession($this->sessionFor('admin'))->get('admin/contratos/nuevo?property_id=' . $fixture['property']);

        $form->assertStatus(200);
        $body = (string) $form->getBody();

        $this->assertMatchesRegularExpression('/<option\s+value="' . $fixture['property'] . '"[^>]*\sselected/s', $body);
        $form->assertSee('Quiroga, Marta');
        $this->assertStringContainsString('value="650000"', $body, 'The base rent of the property is suggested as the monthly rent.');
    }

    public function testTheFormExplainsThatNoPropertyIsAvailable(): void
    {
        $this->withSession($this->sessionFor('admin'))->get('admin/contratos/nuevo')
            ->assertSee('No hay inmuebles disponibles para alquilar');
    }

    public function testThePropertyListingOffersToCreateAContractOnlyForAvailableProperties(): void
    {
        $admin = $this->sessionFor('admin');
        $owner = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $free  = $this->insertProperty($owner, ['address' => 'Libre 1']);
        $taken = $this->insertProperty($owner, ['address' => 'Ocupada 2', 'status' => 'rented']);

        $body = (string) $this->withSession($admin)->get('admin/inmuebles')->getBody();

        $this->assertStringContainsString("admin/contratos/nuevo?property_id={$free}", $body);
        $this->assertStringNotContainsString("admin/contratos/nuevo?property_id={$taken}", $body);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: property not available
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function unavailablePropertyProvider(): array
    {
        return [
            'rented with a contract in force'    => ['rented_with_contract'],
            'rented status without a contract'   => ['rented'],
            'in maintenance'                     => ['maintenance'],
            'deactivated (logical deletion)'     => ['deactivated'],
            'does not exist'                     => ['missing'],
        ];
    }

    #[DataProvider('unavailablePropertyProvider')]
    public function testScenario2AnUnavailablePropertyIsRejectedAndNothingChanges(string $kind): void
    {
        $admin  = $this->sessionFor('admin');
        $owner  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $tenant = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');
        $other  = $this->createUser('tenant', 'Esteban', 'Duarte', '32784112', 'esteban.duarte.hu10@example.com');

        $propertyId     = 999999;
        $expectedStatus = null;

        if ($kind !== 'missing') {
            $overrides = match ($kind) {
                'rented_with_contract', 'rented' => ['status' => 'rented'],
                'maintenance'                    => ['status' => 'maintenance'],
                'deactivated'                    => ['deleted_at' => '2026-10-01 10:00:00'],
            };

            $propertyId     = $this->insertProperty($owner, $overrides);
            $expectedStatus = (string) ($overrides['status'] ?? 'available');
        }

        $existingContracts = 0;
        if ($kind === 'rented_with_contract') {
            $this->insertContract($propertyId, $other, $owner);
            $existingContracts = 1;
        }

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload(
            ['property' => $propertyId, 'tenant' => $tenant, 'owner' => $owner]
        ))->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('errors')['property_id'] ?? null);

        $this->assertSame($existingContracts, $this->db->table('contracts')->countAllResults(), 'No contract may be created.');

        if ($expectedStatus !== null) {
            $this->assertSame($expectedStatus, $this->propertyStatus($propertyId), 'The property must keep its status.');
        }
    }

    public function testAPropertyWithAContractInForceIsRejectedEvenIfItsStatusSaysAvailable(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();
        $other   = $this->createUser('tenant', 'Esteban', 'Duarte', '32784112', 'esteban.duarte.hu10@example.com');

        // Inconsistent data on purpose: the model must still refuse a second contract in force.
        $this->insertContract($fixture['property'], $other, $fixture['owner']);

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture))->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(1, $this->db->table('contracts')->countAllResults());
    }

    public function testSubmittingTheSameContractTwiceCreatesOnlyOne(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture))
            ->assertRedirectTo('/admin/contratos/' . $this->latestContractId());

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture))
            ->assertRedirectTo('/admin/contratos/nuevo');
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        $this->assertSame(1, $this->db->table('contracts')->countAllResults());
        $this->assertSame('rented', $this->propertyStatus($fixture['property']));
    }

    public function testAPropertyWhoseOwnerWasDeactivatedCannotBeContracted(): void
    {
        $admin  = $this->sessionFor('admin');
        $owner  = $this->createUser('owner', 'Hernán', 'Paz', '28349880', 'hernan.paz.hu10@example.com', '2026-10-01 10:00:00');
        $tenant = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');
        $property = $this->insertProperty($owner);

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload(['property' => $property, 'tenant' => $tenant, 'owner' => $owner]))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::INACTIVE_OWNER_MESSAGE, session()->getFlashdata('errors')['property_id'] ?? null);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
        $this->assertSame('available', $this->propertyStatus($property));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: invalid dates
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: string|null}>
     */
    public static function invalidDatesProvider(): array
    {
        return [
            'end equals start'    => [['start_date' => '2026-12-01', 'end_date' => '2026-12-01'], 'end_date', ContractController::INVALID_DATE_ORDER_MESSAGE],
            'end before start'    => [['start_date' => '2026-12-01', 'end_date' => '2026-11-30'], 'end_date', ContractController::INVALID_DATE_ORDER_MESSAGE],
            'end a year before'   => [['start_date' => '2026-12-01', 'end_date' => '2025-12-01'], 'end_date', ContractController::INVALID_DATE_ORDER_MESSAGE],
            'start empty'         => [['start_date' => ''], 'start_date', null],
            'end empty'           => [['end_date' => ''], 'end_date', null],
            'start with text'     => [['start_date' => 'mañana'], 'start_date', null],
            'start day/month/year' => [['start_date' => '01/12/2026'], 'start_date', null],
            'end impossible date' => [['end_date' => '2028-02-30'], 'end_date', null],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidDatesProvider')]
    public function testScenario3InvalidDatesAreRejectedAndNothingIsStored(array $overrides, string $errorField, ?string $expectedMessage): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, $overrides))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::VALIDATION_ERROR_MESSAGE, session()->getFlashdata('error'));

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey($errorField, $errors);
        if ($expectedMessage !== null) {
            $this->assertSame($expectedMessage, $errors[$errorField]);
        }

        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
        $this->assertSame('available', $this->propertyStatus($fixture['property']));
    }

    // ---------------------------------------------------------------------------------------------
    // Field validation
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidFieldsProvider(): array
    {
        return [
            'rent zero'                    => [['monthly_rent' => '0'], 'monthly_rent'],
            'rent negative'                => [['monthly_rent' => '-5'], 'monthly_rent'],
            'rent empty'                   => [['monthly_rent' => ''], 'monthly_rent'],
            'rent text'                    => [['monthly_rent' => 'caro'], 'monthly_rent'],
            'rent with 3 decimals'         => [['monthly_rent' => '100.555'], 'monthly_rent'],
            'rent too big'                 => [['monthly_rent' => '10000000000'], 'monthly_rent'],
            'update negative'              => [['update_percentage' => '-1'], 'update_percentage'],
            'update over 100'              => [['update_percentage' => '100.5'], 'update_percentage'],
            'update empty'                 => [['update_percentage' => ''], 'update_percentage'],
            'update text'                  => [['update_percentage' => 'poco'], 'update_percentage'],
            'update with 3 decimals'       => [['update_percentage' => '10.123'], 'update_percentage'],
            'commission negative'          => [['commission_percentage' => '-0.5'], 'commission_percentage'],
            'commission over 100'          => [['commission_percentage' => '101'], 'commission_percentage'],
            'commission empty'             => [['commission_percentage' => ''], 'commission_percentage'],
            'late fee negative'            => [['late_fee_daily_percentage' => '-2'], 'late_fee_daily_percentage'],
            'late fee over 100'            => [['late_fee_daily_percentage' => '150'], 'late_fee_daily_percentage'],
            'late fee empty'               => [['late_fee_daily_percentage' => ''], 'late_fee_daily_percentage'],
            'due day zero'                 => [['due_day' => '0'], 'due_day'],
            'due day 29'                   => [['due_day' => '29'], 'due_day'],
            'due day 31'                   => [['due_day' => '31'], 'due_day'],
            'due day negative'             => [['due_day' => '-3'], 'due_day'],
            'due day decimal'              => [['due_day' => '5.5'], 'due_day'],
            'due day text'                 => [['due_day' => 'diez'], 'due_day'],
            'due day empty'                => [['due_day' => ''], 'due_day'],
            'property empty'               => [['property_id' => ''], 'property_id'],
            'property text'                => [['property_id' => 'casa'], 'property_id'],
            'tenant empty'                 => [['tenant_id' => ''], 'tenant_id'],
            'tenant text'                  => [['tenant_id' => 'camila'], 'tenant_id'],
            'tenant zero'                  => [['tenant_id' => '0'], 'tenant_id'],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidFieldsProvider')]
    public function testInvalidFieldsAreRejectedWithTheirOwnErrorAndNothingIsStored(array $overrides, string $errorField): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, $overrides))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey($errorField, $errors);
        $this->assertNotSame('', trim((string) $errors[$errorField]));

        $this->assertSame(0, $this->db->table('contracts')->countAllResults(), 'Nothing must be stored.');
        $this->assertSame('available', $this->propertyStatus($fixture['property']), 'The property must stay available.');
    }

    public function testEveryInvalidFieldGetsItsOwnErrorAtTheSameTime(): void
    {
        $this->postWithSession($this->sessionFor('admin'), 'admin/contratos', [
            'property_id'               => '',
            'tenant_id'                 => '',
            'start_date'                => '',
            'end_date'                  => '',
            'monthly_rent'              => '0',
            'update_percentage'         => '-1',
            'commission_percentage'     => '101',
            'due_day'                   => '40',
            'late_fee_daily_percentage' => '',
        ])->assertRedirectTo('/admin/contratos/nuevo');

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        foreach (['property_id', 'tenant_id', 'start_date', 'end_date', 'monthly_rent', 'update_percentage', 'commission_percentage', 'due_day', 'late_fee_daily_percentage'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Missing the error of {$field}");
        }
    }

    public function testThePostedValuesAreKeptInTheFormAfterAnError(): void
    {
        $fixture = $this->fixture();

        $this->postWithSession($this->sessionFor('admin'), 'admin/contratos', $this->validPayload($fixture, ['due_day' => '40', 'monthly_rent' => '777000']))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame('777000', old('monthly_rent'));
        $this->assertSame((string) $fixture['property'], old('property_id'));
        $this->assertSame('2026-12-01', old('start_date'));
    }

    // ---------------------------------------------------------------------------------------------
    // Tenants (HU-05 integration)
    // ---------------------------------------------------------------------------------------------

    public function testADeactivatedTenantIsNotOfferedAndAPostWithTheirIdIsRejected(): void
    {
        $admin       = $this->sessionFor('admin');
        $fixture     = $this->fixture();
        $deactivated = $this->createUser('tenant', 'Esteban', 'Duarte', '32784112', 'esteban.duarte.hu10@example.com', '2026-10-01 10:00:00');

        $this->withSession($admin)->get('admin/contratos/nuevo')->assertDontSee('Duarte, Esteban');

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, ['tenant_id' => (string) $deactivated]))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::INVALID_TENANT_MESSAGE, session()->getFlashdata('errors')['tenant_id'] ?? null);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
        $this->assertSame('available', $this->propertyStatus($fixture['property']));
    }

    public function testOnlyUsersWithTheTenantRoleCanBeTheTenantOfAContract(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();
        $notTenants = [
            'an owner'   => $fixture['owner'],
            'a staff member' => $this->createUser('employee', 'Laura', 'Benítez', '27111222', 'laura.benitez.hu10@example.com'),
            'a missing user' => 999999,
        ];

        foreach ($notTenants as $who => $userId) {
            $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, ['tenant_id' => (string) $userId]))
                ->assertRedirectTo('/admin/contratos/nuevo');
            $this->assertSame(ContractController::INVALID_TENANT_MESSAGE, session()->getFlashdata('errors')['tenant_id'] ?? null, "Accepted {$who}");
        }

        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: atomicity
    // ---------------------------------------------------------------------------------------------

    public function testScenario4AFailureWhileStoringTheContractLeavesThePropertyUntouched(): void
    {
        $fixture = $this->fixture();

        // The controller would reject these dates; going straight to the model proves the database also does.
        $result = model(ContractModel::class)->createAndRentProperty([
            'property_id'               => $fixture['property'],
            'tenant_id'                 => $fixture['tenant'],
            'start_date'                => '2026-12-01',
            'end_date'                  => '2026-11-01',
            'monthly_rent'              => '510000',
            'update_percentage'         => '0',
            'due_day'                   => 10,
            'commission_percentage'     => '10',
            'late_fee_daily_percentage' => '0.5',
        ]);

        $this->assertNull($result['id']);
        $this->assertSame(ContractModel::ERROR_PERSISTENCE, $result['error']);
        $this->assertSame(0, $this->db->table('contracts')->countAllResults(), 'No contract may remain.');
        $this->assertSame('available', $this->propertyStatus($fixture['property']));
    }

    public function testScenario4AFailureWhileUpdatingThePropertyDiscardsTheContract(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        // Any update of a property fails from now on, after the contract row was already inserted.
        $this->db->query(
            'CREATE TRIGGER ' . self::BLOCKING_TRIGGER . ' BEFORE UPDATE ON properties FOR EACH ROW '
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'blocked for the atomicity test'"
        );

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame(ContractController::SAVE_ERROR_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('contracts')->countAllResults(), 'The contract must be rolled back with the failed update.');
        $this->assertSame('available', $this->propertyStatus($fixture['property']));

        // Once the fault is gone the same request works: the failed attempt left no lock or leftover behind.
        $this->db->query('DROP TRIGGER IF EXISTS ' . self::BLOCKING_TRIGGER);

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture))
            ->assertRedirectTo('/admin/contratos/' . $this->latestContractId());
        $this->assertSame(1, $this->db->table('contracts')->countAllResults());
        $this->assertSame('rented', $this->propertyStatus($fixture['property']));
    }

    public function testTheTypedValuesSurviveAFailedSave(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->db->query(
            'CREATE TRIGGER ' . self::BLOCKING_TRIGGER . ' BEFORE UPDATE ON properties FOR EACH ROW '
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'blocked for the atomicity test'"
        );

        $this->postWithSession($admin, 'admin/contratos', $this->validPayload($fixture, ['monthly_rent' => '777000']))
            ->assertRedirectTo('/admin/contratos/nuevo');

        $this->assertSame('777000', old('monthly_rent'));
        $this->assertSame((string) $fixture['tenant'], old('tenant_id'));
    }

    // ---------------------------------------------------------------------------------------------
    // Contract file
    // ---------------------------------------------------------------------------------------------

    public function testTheContractFileKeepsShowingDeactivatedPartiesForTheHistory(): void
    {
        $admin  = $this->sessionFor('admin');
        $owner  = $this->createUser('owner', 'Hernán', 'Paz', '28349880', 'hernan.paz.hu10@example.com', '2026-10-01 10:00:00');
        $tenant = $this->createUser('tenant', 'Esteban', 'Duarte', '32784112', 'esteban.duarte.hu10@example.com', '2026-10-02 10:00:00');
        $property   = $this->insertProperty($owner, ['address' => 'Roca 1020', 'status' => 'rented', 'deleted_at' => '2026-10-03 10:00:00']);
        $contractId = $this->insertContract($property, $tenant, $owner);

        $file = $this->withSession($admin)->get("admin/contratos/{$contractId}");

        $file->assertStatus(200);
        $file->assertSee('Paz, Hernán');
        $file->assertSee('Duarte, Esteban');
        $file->assertSee('Roca 1020');
        $file->assertSee('Dado de baja');
    }

    public function testTheContractFileOfAClosedContractShowsItsStatusAndClosingData(): void
    {
        $owner  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $tenant = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');
        $property   = $this->insertProperty($owner);
        $contractId = $this->insertContract($property, $tenant, $owner, [
            'status'         => 'rescinded',
            'closed_at'      => '2027-03-15',
            'closing_reason' => 'Mudanza anticipada',
        ]);

        $file = $this->withSession($this->sessionFor('admin'))->get("admin/contratos/{$contractId}");

        $file->assertStatus(200);
        $file->assertSee('Rescindido');
        $file->assertSee('15/03/2027');
        $file->assertSee('Mudanza anticipada');
    }

    public function testTheFileOfAMissingContractRedirectsWithAMessage(): void
    {
        $this->withSession($this->sessionFor('admin'))->get('admin/contratos/999999')->assertRedirectTo('/admin/contratos');

        $this->assertSame(ContractController::CONTRACT_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    public function testAdministratorsCanSearchContractsAndFilterHistoricalStatuses(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();
        $this->insertContract($fixture['property'], $fixture['tenant'], $fixture['owner']);

        $finishedProperty = $this->insertProperty($fixture['owner'], [
            'address' => 'Sarmiento 120',
            'status'  => 'rented',
        ]);
        $this->insertContract($finishedProperty, $fixture['tenant'], $fixture['owner'], ['status' => 'finished']);

        $otherOwner  = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu11@example.com');
        $otherTenant = $this->createUser('tenant', 'Lucía', 'Vega', '29413876', 'lucia.vega.hu11@example.com');
        $otherProperty = $this->insertProperty($otherOwner, [
            'address' => 'San Martín 450',
            'status'  => 'rented',
        ]);
        $this->insertContract($otherProperty, $otherTenant, $otherOwner, ['status' => 'rescinded']);

        $allContracts = $this->withSession($admin)->get('admin/contratos');
        $allContracts->assertStatus(200);
        $allContracts->assertSee('Mitre 845');
        $allContracts->assertSee('Sarmiento 120');
        $allContracts->assertSee('San Martín 450');
        $allContracts->assertSee('Vigente');
        $allContracts->assertSee('Finalizado');
        $allContracts->assertSee('Rescindido');

        $tenantSearch = $this->withSession($admin)->get('admin/contratos?q=Camila');
        $tenantSearch->assertStatus(200);
        $tenantSearch->assertSee('Mitre 845');
        $tenantSearch->assertSee('Sarmiento 120');
        $tenantSearch->assertDontSee('San Martín 450');

        $ownerSearch = $this->withSession($admin)->get('admin/contratos?q=Ricardo');
        $ownerSearch->assertSee('San Martín 450');
        $ownerSearch->assertDontSee('Mitre 845');

        $addressSearch = $this->withSession($admin)->get('admin/contratos?q=Sarmiento');
        $addressSearch->assertSee('Sarmiento 120');
        $addressSearch->assertDontSee('Mitre 845');

        $historicalFilter = $this->withSession($admin)->get('admin/contratos?status=finished');
        $historicalFilter->assertSee('Sarmiento 120');
        $historicalFilter->assertSee('Finalizado');
        $historicalFilter->assertDontSee('Mitre 845');
        $historicalFilter->assertDontSee('San Martín 450');
    }

    public function testOwnerSeesOnlyOwnContractsAndMayOpenOnlyOwnContractFiles(): void
    {
        $fixture = $this->fixture();
        $ownId   = $this->insertContract($fixture['property'], $fixture['tenant'], $fixture['owner']);

        $otherOwner   = $this->createUser('owner', 'Ricardo', 'Sosa', '24118905', 'ricardo.sosa.hu11@example.com');
        $otherTenant  = $this->createUser('tenant', 'Lucía', 'Vega', '29413876', 'lucia.vega.hu11@example.com');
        $otherProperty = $this->insertProperty($otherOwner, [
            'address' => 'San Martín 450',
            'status'  => 'rented',
        ]);
        $otherId = $this->insertContract($otherProperty, $otherTenant, $otherOwner);

        $ownerSession = [
            'is_logged_in' => true,
            'user_id'      => $fixture['owner'],
            'user_email'   => 'marta.quiroga.hu10@example.com',
            'user_name'    => 'Marta Quiroga',
            'user_role'    => 'owner',
        ];

        $list = $this->withSession($ownerSession)->get('propietario/contratos');
        $list->assertStatus(200);
        $list->assertSee('Mitre 845');
        $list->assertSee('camila.rojas.hu10@example.com');
        $list->assertSee('$ 500.000');
        $list->assertSee('8 %');
        $list->assertDontSee('San Martín 450');
        $list->assertDontSee('lucia.vega.hu11@example.com');

        $ownFile = $this->withSession($ownerSession)->get('propietario/contratos/' . $ownId);
        $ownFile->assertStatus(200);
        $ownFile->assertSee('Contrato #' . $ownId);

        $foreignFile = $this->withSession($ownerSession)->get('propietario/contratos/' . $otherId);
        $foreignFile->assertRedirectTo('/propietario/contratos');
        $this->assertSame(ContractController::OWNER_CONTRACT_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    public function testTheDataShownByTheFormAndTheFileIsEscaped(): void
    {
        $admin  = $this->sessionFor('admin');
        $owner  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $tenant = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');
        $property   = $this->insertProperty($owner, ['address' => '<script>alert(1)</script>']);
        $contractId = $this->insertContract($property, $tenant, $owner);

        $form = (string) $this->withSession($admin)->get('admin/contratos/nuevo')->getBody();
        $file = (string) $this->withSession($admin)->get("admin/contratos/{$contractId}")->getBody();

        foreach ([$form, $file] as $body) {
            $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $file);
    }

    // ---------------------------------------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------------------------------------

    #[DataProvider('operatorRoleProvider')]
    public function testAdministratorsAndEmployeesCanUseTheContractScreens(string $roleName): void
    {
        $session    = $this->sessionFor($roleName);
        $fixture    = $this->fixture();
        $contractId = $this->insertContract($this->insertProperty($fixture['owner'], ['address' => 'Otra 5', 'status' => 'rented']), $fixture['tenant'], $fixture['owner']);

        foreach (['admin/contratos', 'admin/contratos/nuevo', "admin/contratos/{$contractId}"] as $uri) {
            $this->withSession($session)->get($uri)->assertStatus(200);
        }

        $this->postWithSession($session, 'admin/contratos', $this->validPayload($fixture))
            ->assertRedirectTo('/admin/contratos/' . $this->latestContractId());
        $this->assertSame(2, $this->db->table('contracts')->countAllResults());
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
    public function testOwnersAndTenantsCannotReachAdministrativeContractRoutes(string $roleName, string $ownPanel): void
    {
        $session    = $this->sessionFor($roleName);
        $fixture    = $this->fixture();
        $contractId = $this->insertContract($this->insertProperty($fixture['owner'], ['address' => 'Otra 5', 'status' => 'rented']), $fixture['tenant'], $fixture['owner']);

        foreach (['admin/contratos/nuevo', "admin/contratos/{$contractId}"] as $uri) {
            $this->withSession($session)->get($uri)->assertRedirectTo($ownPanel);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession($session, 'admin/contratos', $this->validPayload($fixture))->assertRedirectTo($ownPanel);

        $this->assertSame(1, $this->db->table('contracts')->countAllResults(), 'No contract may be created by an external role.');
        $this->assertSame('available', $this->propertyStatus($fixture['property']));
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $fixture    = $this->fixture();
        $contractId = $this->insertContract($this->insertProperty($fixture['owner'], ['address' => 'Otra 5', 'status' => 'rented']), $fixture['tenant'], $fixture['owner']);

        foreach (['admin/contratos', 'admin/contratos/nuevo', "admin/contratos/{$contractId}", 'propietario/contratos'] as $uri) {
            $this->get($uri)->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession([], 'admin/contratos', $this->validPayload($fixture))->assertRedirectTo('/auth/login');
        $this->assertSame(1, $this->db->table('contracts')->countAllResults());
    }

    public function testThePostWithoutACsrfTokenIsRejected(): void
    {
        $session = $this->sessionFor('admin');
        $fixture = $this->fixture();

        $this->expectException(SecurityException::class);

        $this->withSession($session)->post('admin/contratos', $this->validPayload($fixture));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function closeStatusProvider(): array
    {
        return [
            'finalized' => [ContractModel::STATUS_FINISHED, 'Vencimiento del contrato', ContractController::CONTRACT_FINISHED_MESSAGE],
            'rescinded' => [ContractModel::STATUS_RESCINDED, 'Rescisión por incumplimiento', ContractController::CONTRACT_RESCINDED_MESSAGE],
        ];
    }

    public function testClosureConfirmationShowsThePropertyForFinalizationAndRescission(): void
    {
        $admin   = $this->sessionFor('admin');
        $fixture = $this->fixture();

        foreach ([
            ['finalizar', ContractModel::STATUS_FINISHED, 'Finalizado'],
            ['rescindir', ContractModel::STATUS_RESCINDED, 'Rescindido'],
        ] as $index => [$action, $status, $statusLabel]) {
            $propertyId = $index === 0 ? $fixture['property'] : $this->insertProperty($fixture['owner']);
            $contractId = $this->insertContract($propertyId, $fixture['tenant'], $fixture['owner']);
            $this->db->table('properties')->where('id', $propertyId)->update(['status' => 'rented']);

            $confirmation = $this->postWithSession($admin, "admin/contratos/{$contractId}/{$action}", []);

            $confirmation->assertOK();
            $confirmation->assertSee('Mitre 845');
            $confirmation->assertSee($statusLabel);
            $this->assertSame(ContractModel::STATUS_IN_FORCE, $this->db->table('contracts')->where('id', $contractId)->get()->getRow()->status);
            $this->assertSame('rented', $this->propertyStatus($propertyId));
        }
    }

    #[DataProvider('closeStatusProvider')]
    public function testClosingAContractClosesTheLeaseAndReturnsThePropertyToAvailable(string $status, string $reason, string $successMessage): void
    {
        $admin  = $this->sessionFor('admin');
        $fixture = $this->fixture();
        $contractId = $this->insertContract($fixture['property'], $fixture['tenant'], $fixture['owner']);

        $response = $this->postWithSession($admin, "admin/contratos/{$contractId}/cerrar", [
            'status'         => $status,
            'closing_reason' => $reason,
            'confirmed'      => '1',
        ]);

        $response->assertRedirectTo('/admin/contratos/' . $contractId);
        $this->assertSame($successMessage, session()->getFlashdata('success'));

        $contract = $this->db->table('contracts')->where('id', $contractId)->get()->getRow();
        $this->assertNotNull($contract);
        $this->assertSame($status, $contract->status);
        $this->assertSame(date('Y-m-d'), $contract->closed_at);
        $this->assertSame($reason, $contract->closing_reason);
        $this->assertSame('available', $this->propertyStatus($fixture['property']));
    }

    public function testClosingAnAlreadyClosedContractIsRejected(): void
    {
        $admin  = $this->sessionFor('admin');
        $fixture = $this->fixture();
        $contractId = $this->insertContract($fixture['property'], $fixture['tenant'], $fixture['owner']);

        $this->postWithSession($admin, "admin/contratos/{$contractId}/cerrar", [
            'status'         => ContractModel::STATUS_FINISHED,
            'closing_reason' => 'Vencimiento',
            'confirmed'      => '1',
        ]);

        $second = $this->postWithSession($admin, "admin/contratos/{$contractId}/cerrar", [
            'status'         => ContractModel::STATUS_FINISHED,
            'closing_reason' => 'Reintento',
            'confirmed'      => '1',
        ]);

        $second->assertRedirectTo('/admin/contratos/' . $contractId);
        $this->assertSame(ContractController::CONTRACT_ALREADY_CLOSED_MESSAGE, session()->getFlashdata('error'));
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * Creates the minimum data for a contract: an active owner with one available property and an active tenant.
     *
     * @return array{owner: int, tenant: int, property: int}
     */
    private function fixture(): array
    {
        $owner  = $this->createUser('owner', 'Marta', 'Quiroga', '21650312', 'marta.quiroga.hu10@example.com');
        $tenant = $this->createUser('tenant', 'Camila', 'Rojas', '39421084', 'camila.rojas.hu10@example.com');

        return [
            'owner'    => $owner,
            'tenant'   => $tenant,
            'property' => $this->insertProperty($owner),
        ];
    }

    /**
     * Creates a user with the given role and returns the session data its login would store.
     *
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionFor(string $roleName): array
    {
        $email  = "{$roleName}.session.hu10@example.com";
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
     * Inserts a contract straight into the table (bypassing the application), by default one in force.
     *
     * @param array<string, mixed> $overrides
     */
    private function insertContract(int $propertyId, int $tenantId, int $ownerId, array $overrides = []): int
    {
        $this->db->table('contracts')->insert(array_merge([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => 500000.00,
            'update_percentage'         => 10,
            'due_day'                   => 10,
            'commission_percentage'     => 8,
            'late_fee_daily_percentage' => 0.5,
            'status'                    => 'in_force',
            'created_at'                => '2026-01-01 10:00:00',
            'updated_at'                => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * A complete, valid form submission; tests override only what they exercise.
     *
     * @param array{owner?: int, tenant: int, property: int} $fixture
     * @param array<string, string>                          $overrides
     *
     * @return array<string, string>
     */
    private function validPayload(array $fixture, array $overrides = []): array
    {
        return array_merge([
            'property_id'               => (string) $fixture['property'],
            'tenant_id'                 => (string) $fixture['tenant'],
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '510000',
            'update_percentage'         => '12.5',
            'commission_percentage'     => '10',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ], $overrides);
    }

    private function propertyStatus(int $propertyId): string
    {
        return (string) $this->db->table('properties')->where('id', $propertyId)->get()->getRow()->status;
    }

    private function latestContractId(): int
    {
        $row = $this->db->table('contracts')->selectMax('id')->get()->getRow();

        return (int) ($row->id ?? 0);
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
