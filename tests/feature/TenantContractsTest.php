<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\ContractModel;
use App\Models\PropertyModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance tests for HU-34: the tenant consults their own contracts (read only).
 *
 * @internal
 */
final class TenantContractsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-18';

    /** Commission of the agency: a distinctive value, so a leak in any page is easy to detect. */
    private const COMMISSION = '7.35';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    // ---- Scenario 1: contract list -------------------------------------------------------------------------------

    public function testListShowsOnlyOwnContractsWithTheOnesInForceFirst(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        [$otherTenantId]      = $this->createUserSession('tenant');

        // Inserted so that the natural order (newest start date first) would put the contract in force LAST.
        $inForceId   = $this->createContract($tenantId, 'Calle Vigente 100', ['start_date' => '2024-06-01', 'end_date' => '2027-05-31']);
        $finishedId  = $this->createContract($tenantId, 'Calle Finalizada 200', [
            'start_date'     => '2025-01-01',
            'end_date'       => '2025-12-31',
            'status'         => ContractModel::STATUS_FINISHED,
            'closed_at'      => '2025-12-31',
            'closing_reason' => 'Fin del plazo pactado',
        ]);
        $rescindedId = $this->createContract($tenantId, 'Calle Rescindida 300', [
            'start_date'     => '2025-06-01',
            'end_date'       => '2026-05-31',
            'status'         => ContractModel::STATUS_RESCINDED,
            'closed_at'      => '2025-09-15',
            'closing_reason' => 'Acuerdo entre las partes',
        ]);
        $this->createContract($otherTenantId, 'Calle Ajena 999');

        $response = $this->withSession($session)->get('inquilino/contratos');

        $response->assertStatus(200);
        $response->assertSee('Mis contratos');
        $response->assertSee('3 contratos');
        $response->assertSee('Calle Vigente 100');
        $response->assertSee('Calle Finalizada 200');
        $response->assertSee('Calle Rescindida 300');
        $response->assertDontSee('Calle Ajena 999');

        // Status, term and monthly rent come straight from the stored contract.
        $response->assertSee('Vigente');
        $response->assertSee('Finalizado');
        $response->assertSee('Rescindido');
        $response->assertSee('01/06/2024');
        $response->assertSee('31/05/2027');
        $response->assertSee('$ 500.000');

        foreach ([$inForceId, $finishedId, $rescindedId] as $contractId) {
            $response->assertSee(site_url('inquilino/contratos/' . $contractId));
        }

        $body = (string) $response->getBody();
        $this->assertLessThan(strpos($body, 'Calle Rescindida 300'), strpos($body, 'Calle Vigente 100'), 'The contract in force comes first.');
        $this->assertLessThan(strpos($body, 'Calle Finalizada 200'), strpos($body, 'Calle Rescindida 300'), 'Then the most recent ones.');
    }

    public function testListNeverShowsTheCommissionNorTheOwner(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $this->createContract($tenantId, 'Calle Discreta 1');

        $response = $this->withSession($session)->get('inquilino/contratos');
        $response->assertStatus(200);
        $response->assertSee('Calle Discreta 1');

        $this->assertNoInternalDataIn((string) $response->getBody());
    }

    public function testEmptyStateOffersTheCatalogAndMyRequests(): void
    {
        [, $session] = $this->createUserSession('tenant');

        $response = $this->withSession($session)->get('inquilino/contratos');

        $response->assertStatus(200);
        $response->assertSee('Todavía no tenés contratos');
        $response->assertSee('0 contratos');
        $response->assertSee('btn-empty-contracts-search');
        $response->assertSee(site_url('catalogo-inmuebles'));
        $response->assertSee('btn-empty-contracts-requests');
        $response->assertSee(site_url('inquilino/solicitudes'));
    }

    public function testHistoryStaysConsultableWhenThePropertyAndTheOwnerWereDeactivated(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, 'Calle Historica 7', [
            'status'         => ContractModel::STATUS_FINISHED,
            'closed_at'      => '2026-06-30',
            'closing_reason' => 'Fin del plazo pactado',
        ]);

        $contract = $this->db->table('contracts')->where('id', $contractId)->get()->getRowArray();
        $this->db->table('properties')->where('id', $contract['property_id'])->update(['deleted_at' => '2026-07-01 10:00:00']);
        $this->db->table('users')->where('id', $contract['owner_id'])->update(['deleted_at' => '2026-07-01 10:00:00']);

        $list = $this->withSession($session)->get('inquilino/contratos');
        $list->assertStatus(200);
        $list->assertSee('Calle Historica 7');

        $file = $this->withSession($session)->get('inquilino/contratos/' . $contractId);
        $file->assertStatus(200);
        $file->assertSee('Calle Historica 7');
        $file->assertSee('Finalizado');
    }

    // ---- Scenario 2: contract file -------------------------------------------------------------------------------

    public function testFileShowsThePropertyAndTheAgreedTermsWithoutCommissionNorOwner(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, 'Mitre 845', [
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '12.50',
            'due_day'                   => 10,
            'late_fee_daily_percentage' => '0.50',
        ]);

        $response = $this->withSession($session)->get('inquilino/contratos/' . $contractId);

        $response->assertStatus(200);
        $response->assertSee('Contrato #' . $contractId);
        $response->assertSee('Mitre 845');
        $response->assertSee('Corrientes');
        $response->assertSee(PropertyModel::labelForType(PropertyModel::TYPE_APARTMENT));
        $response->assertSee('01/01/2026');
        $response->assertSee('31/12/2027');
        $response->assertSee('24 meses');
        $response->assertSee('$ 500.000');
        $response->assertSee('12,5 %');
        $response->assertSee('Día 10 de cada mes');
        $response->assertSee('0,5 % por cada día de atraso');
        $response->assertSee('Vigente');

        // Accesses to the account statement and to the payments (Scenario 2).
        $response->assertSee(site_url('inquilino/estado-cuenta'));
        $response->assertSee(site_url('inquilino/mis-pagos'));
        $response->assertSee(site_url('inquilino/contratos'));

        // A contract in force has no closing data.
        $response->assertDontSee('Cerrado el');

        $this->assertNoInternalDataIn((string) $response->getBody());
    }

    public function testFileOfAClosedContractShowsTheClosingDateAndReason(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $finishedId           = $this->createContract($tenantId, 'Calle Cerrada 10', [
            'status'         => ContractModel::STATUS_FINISHED,
            'closed_at'      => '2026-06-30',
            'closing_reason' => 'Fin del plazo pactado',
        ]);
        $rescindedId = $this->createContract($tenantId, 'Calle Rescindida 20', [
            'status'         => ContractModel::STATUS_RESCINDED,
            'closed_at'      => '2026-08-15',
            'closing_reason' => null,
        ]);

        $finished = $this->withSession($session)->get('inquilino/contratos/' . $finishedId);
        $finished->assertStatus(200);
        $finished->assertSee('Finalizado');
        $finished->assertSee('Cerrado el');
        $finished->assertSee('30/06/2026');
        $finished->assertSee('Fin del plazo pactado');

        $rescinded = $this->withSession($session)->get('inquilino/contratos/' . $rescindedId);
        $rescinded->assertStatus(200);
        $rescinded->assertSee('Rescindido');
        $rescinded->assertSee('15/08/2026');
        $rescinded->assertSee('Sin motivo registrado');
    }

    public function testQueriesForTheTenantNeverSelectTheCommissionNorTheOwner(): void
    {
        [$tenantId] = $this->createUserSession('tenant');
        $contractId = $this->createContract($tenantId, 'Calle Columnas 5');

        $model = model(ContractModel::class);

        foreach ([$model->findForTenant($tenantId)[0], $model->findOneForTenant($contractId, $tenantId)] as $row) {
            foreach (['commission_percentage', 'owner_id', 'tenant_id', 'created_at', 'updated_at'] as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $row, "The tenant query must not select {$forbidden}.");
            }

            foreach (array_keys($row) as $column) {
                $this->assertStringStartsNotWith('owner_', (string) $column);
            }
        }
    }

    // ---- Scenario 3: another tenant's contract -------------------------------------------------------------------

    public function testTheContractOfAnotherTenantAnswers404(): void
    {
        [$ownerTenantId]       = $this->createUserSession('tenant');
        [, $intruderSession]   = $this->createUserSession('tenant');
        $contractId            = $this->createContract($ownerTenantId, 'Calle Privada 321');

        $this->assertNotFound($intruderSession, 'inquilino/contratos/' . $contractId);
    }

    public function testUnknownOrMalformedIdentifiersAnswer404(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, 'Calle Propia 1');

        $this->assertNotFound($session, 'inquilino/contratos/' . ($contractId + 1000));
        $this->assertNotFound($session, 'inquilino/contratos/0');
        $this->assertNotFound($session, 'inquilino/contratos/abc');
        $this->assertNotFound($session, 'inquilino/contratos/' . $contractId . 'abc');
        $this->assertNotFound($session, 'inquilino/contratos/-1');
        // Too long for an integer: it must be a plain 404, not a type error.
        $this->assertNotFound($session, 'inquilino/contratos/99999999999999999999');

        // The own contract is still reachable.
        $this->withSession($session)->get('inquilino/contratos/' . $contractId)->assertStatus(200);
    }

    public function testTheContractPagesAreReadOnly(): void
    {
        foreach (array_keys(service('routes')->getRoutes('POST')) as $route) {
            $this->assertStringStartsNotWith('inquilino/contratos', (string) $route, 'HU-34 only exposes GET routes.');
        }

        $getRoutes = array_filter(
            array_keys(service('routes')->getRoutes('GET')),
            static fn ($route): bool => str_starts_with((string) $route, 'inquilino/contratos')
        );
        $this->assertCount(2, $getRoutes, 'The list and the file of a contract.');
    }

    // ---- Scenario 5: access from the account statement -----------------------------------------------------------

    public function testAccountStatementLinksToTheFileOfTheContractInForce(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        [$otherTenantId]      = $this->createUserSession('tenant');

        $contractId = $this->createContract($tenantId, 'Mitre 845', ['end_date' => '2027-12-31']);
        $this->insertInstallment($contractId, '2026-12-01', '2026-12-10');
        $otherId = $this->createContract($otherTenantId, 'Calle Ajena 555');

        $response = $this->withSession($session)->get('inquilino/estado-cuenta');

        $response->assertStatus(200);
        $response->assertSee('Tu contrato vigente');
        $response->assertSee('Ver contrato digital');
        $response->assertSee(site_url('inquilino/contratos/' . $contractId));
        $response->assertSee('Contrato #' . $contractId . ' vigente hasta el 31/12/2027');
        $response->assertDontSee(site_url('inquilino/contratos/' . $otherId));
        $response->assertDontSee('Calle Ajena 555');

        // The link leads to the file of that contract.
        $this->withSession($session)->get('inquilino/contratos/' . $contractId)->assertStatus(200);
    }

    public function testAccountStatementLinksToTheContractEvenBeforeTheFirstInstallmentIsIssued(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, 'Mitre 845');

        $response = $this->withSession($session)->get('inquilino/estado-cuenta');

        $response->assertStatus(200);
        $response->assertSee('Todavía no tenés cuotas emitidas');
        $response->assertSee('Ver contrato digital');
        $response->assertSee(site_url('inquilino/contratos/' . $contractId));
    }

    public function testAccountStatementOffersNoContractLinkWithoutAContractInForce(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $this->createContract($tenantId, 'Calle Cerrada 10', [
            'status'         => ContractModel::STATUS_FINISHED,
            'closed_at'      => '2026-06-30',
            'closing_reason' => 'Fin del plazo pactado',
        ]);

        $response = $this->withSession($session)->get('inquilino/estado-cuenta');

        $response->assertStatus(200);
        $response->assertDontSee('Ver contrato digital');
        $response->assertDontSee('Tu contrato vigente');
    }

    // ---- Menu and access control ---------------------------------------------------------------------------------

    public function testTheTenantMenuHasAMiContratoEntryThatIsActiveOnTheContractPages(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, 'Calle Menu 1');

        foreach (['inquilino/estado-cuenta' => false, 'inquilino/contratos' => true, 'inquilino/contratos/' . $contractId => true] as $path => $active) {
            $body = (string) $this->withSession($session)->get($path)->getBody();

            $this->assertMatchesRegularExpression(
                '/<a href="' . preg_quote(site_url('inquilino/contratos'), '/') . '" class="sidebar-link' . ($active ? ' is-active' : '') . '">\s*<span>Mi contrato<\/span>/',
                $body,
                "Menu entry on {$path}"
            );
        }

        [, $ownerSession] = $this->createUserSession('owner');
        $ownerBody        = (string) $this->withSession($ownerSession)->get('propietario/contratos')->getBody();
        $this->assertStringNotContainsString('inquilino/contratos', $ownerBody);
    }

    public function testOnlyTenantsCanOpenTheContractPages(): void
    {
        [$tenantId]  = $this->createUserSession('tenant');
        $contractId  = $this->createContract($tenantId, 'Calle Solo Inquilinos 8');

        foreach (['inquilino/contratos', 'inquilino/contratos/' . $contractId] as $path) {
            // Guest: back to the login (an explicit empty session, otherwise the previous request's one is reused).
            $_SESSION = [];
            $guest    = $this->withSession([])->get($path);
            $guest->assertRedirect();
            $this->assertStringContainsString('auth/login', $guest->response()->getHeaderLine('Location'));

            // Other roles: sent to their own panel, without seeing anything.
            foreach (['owner', 'admin', 'employee'] as $role) {
                [, $roleSession] = $this->createUserSession($role);
                $other           = $this->withSession($roleSession)->get($path);

                $other->assertRedirect();
                $this->assertStringNotContainsString('auth/login', $other->response()->getHeaderLine('Location'));
                $this->assertStringNotContainsString('Calle Solo Inquilinos 8', (string) $other->getBody());
            }
        }
    }

    // ---- Helpers -------------------------------------------------------------------------------------------------

    /**
     * The tenant must never see the commission of the agency nor any data of the owner.
     */
    private function assertNoInternalDataIn(string $body): void
    {
        $text = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        foreach (['Comisión', 'comisión', 'commission', '7,35', '7.35', 'Neto mensual', 'Propietario', 'Reservada', 'Eulalia', 'owner_id', 'eulalia.reservada@hu34.example.test'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $text, "Internal data leaked: {$forbidden}");
        }
    }

    /**
     * Asserts that GET {path} is a 404 (CodeIgniter throws PageNotFoundException in the test environment).
     *
     * @param array<string, mixed> $session
     */
    private function assertNotFound(array $session, string $path): void
    {
        try {
            $response = $this->withSession($session)->get($path);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertSame(404, $response->response()->getStatusCode(), "{$path} must answer 404");
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName, string $firstName = '', string $lastName = ''): array
    {
        $sequence = ++$this->sequence;
        $roleId   = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];
        $email    = "{$roleName}{$sequence}@hu34.example.test";
        $first    = $firstName !== '' ? $firstName : ucfirst($roleName);
        $last     = $lastName !== '' ? $lastName : 'Test' . $sequence;

        $this->db->table('users')->insert([
            'role_id'         => $roleId,
            'first_name'      => $first,
            'last_name'       => $last,
            'document_number' => (string) (37000000 + $sequence),
            'email'           => $email,
            'phone'           => '379400000' . $sequence,
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => '2026-11-01 10:00:00',
            'updated_at'      => '2026-11-01 10:00:00',
        ]);
        $userId = (int) $this->db->insertID();

        return [$userId, [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_role'    => $roleName,
            'user_name'    => $first . ' ' . $last,
            'user_email'   => $email,
        ]];
    }

    /**
     * Creates an owner with a recognizable name, a property and a contract of the tenant over it.
     *
     * @param array<string, mixed> $overrides Columns of the contract to override
     */
    private function createContract(int $tenantId, string $address, array $overrides = []): int
    {
        [$ownerId] = $this->createUserSession('owner', 'Eulalia', 'Reservada');

        $this->db->table('properties')->insert([
            'owner_id'        => $ownerId,
            'address'         => $address,
            'city'            => 'Corrientes',
            'type'            => PropertyModel::TYPE_APARTMENT,
            'rooms'           => 2,
            'base_rent'       => '500000.00',
            'description'     => 'Inmueble de prueba',
            'status'          => PropertyModel::STATUS_RENTED,
            'approval_status' => PropertyModel::APPROVAL_APPROVED,
            'created_at'      => '2026-11-01 10:00:00',
            'updated_at'      => '2026-11-01 10:00:00',
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert(array_merge([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '10.00',
            'due_day'                   => 10,
            'commission_percentage'     => self::COMMISSION,
            'late_fee_daily_percentage' => '0.50',
            'status'                    => ContractModel::STATUS_IN_FORCE,
            'created_at'                => '2026-11-01 10:00:00',
            'updated_at'                => '2026-11-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate): int
    {
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => $period,
            'due_date'    => $dueDate,
            'base_amount' => '500000.00',
            'late_fee'    => '0.00',
            'status'      => 'pending',
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }
}
