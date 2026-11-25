<?php

use App\Controllers\SettlementController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\RoleFilter;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;
use App\Models\SettlementModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acceptance tests for HU-19: generate the owner settlements of the collected installments, with the commission agreed
 * in the contract.
 *
 * @internal
 */
final class SettlementGenerationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-24';
    private const URL   = 'admin/liquidaciones/generar';

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

    public function testTheScreenListsOnlyTheCollectedInstallmentsThatAreNotSettled(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $collected = $this->createContract($tenantId, 'Belgrano 1250', '8.00');
        $paid      = $this->insertInstallment($collected['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($paid, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-OK');

        // Not collected: pending, under review, overdue with a rejected payment.
        $pending = $this->createContract($tenantId, 'Pendiente 100', '8.00');
        $this->insertInstallment($pending['contract_id'], '2026-11-01', InstallmentModel::STATUS_PENDING);

        $review       = $this->createContract($tenantId, 'Revision 200', '8.00');
        $underReview  = $this->insertInstallment($review['contract_id'], '2026-11-01', InstallmentModel::STATUS_UNDER_REVIEW);
        $this->insertPayment($underReview, PaymentModel::STATUS_PENDING_VALIDATION, $adminId, 'TRF-REV');

        $rejected        = $this->createContract($tenantId, 'Rechazado 300', '8.00');
        $rejectedInstall = $this->insertInstallment($rejected['contract_id'], '2026-10-01', InstallmentModel::STATUS_OVERDUE);
        $this->insertPayment($rejectedInstall, PaymentModel::STATUS_REJECTED, $adminId, 'TRF-RECH');

        // Collected but already settled.
        $settled        = $this->createContract($tenantId, 'Liquidado 400', '8.00');
        $settledInstall = $this->insertInstallment($settled['contract_id'], '2026-09-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($settledInstall, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-LIQ');
        $this->assertNull(model(SettlementModel::class)->generate([$settledInstall])['error']);

        $page = $this->withSession($adminSession)->get(self::URL);
        $page->assertStatus(200);
        $page->assertSee('Generar liquidaciones');
        $page->assertSeeElement('#installment-' . $paid);
        $page->assertSee('Belgrano 1250');
        $page->assertSee('$ 500.000');
        $page->assertSee('$ 40.000');
        $page->assertSee('$ 460.000');

        foreach (['Pendiente 100', 'Revision 200', 'Rechazado 300', 'Liquidado 400'] as $address) {
            $page->assertDontSee($address);
        }

        $this->assertCount(1, model(SettlementModel::class)->findSettleableInstallments());
    }

    public function testGeneratingStoresTheCommissionAndNetAsPendingTransfer(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $contract    = $this->createContract($tenantId, 'San Martín 322', '8.50');
        $installment = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        // What was collected, not the rent, is the gross.
        $this->insertPayment($installment, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-1', '512345.67');

        $response = $this->postAs($adminSession, self::URL, ['installment_ids' => [(string) $installment]]);
        $response->assertRedirectTo(site_url(self::URL));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Se generó 1 liquidación', (string) session()->getFlashdata('success'));

        $settlements = $this->db->table('settlements')->get()->getResultArray();
        $this->assertCount(1, $settlements);
        $settlement = $settlements[0];

        $this->assertSame($contract['owner_id'], (int) $settlement['owner_id']);
        $this->assertSame($contract['contract_id'], (int) $settlement['contract_id']);
        $this->assertSame('2026-10-01', $settlement['period']);
        $this->assertSame('512345.67', $settlement['gross_amount']);
        $this->assertSame('8.50', $settlement['commission_percentage']);
        $this->assertSame('43549.38', $settlement['commission_amount']);
        $this->assertSame('468796.29', $settlement['net_amount']);
        $this->assertSame(SettlementModel::STATUS_PENDING_TRANSFER, $settlement['status']);
        $this->assertNull($settlement['transferred_at']);
        $this->assertNull($settlement['operation_number']);

        $link = $this->db->table('settlement_installments')->get()->getResultArray();
        $this->assertCount(1, $link);
        $this->assertSame((int) $settlement['id'], (int) $link[0]['settlement_id']);
        $this->assertSame($installment, (int) $link[0]['installment_id']);

        // The screen that follows shows what was generated and no longer offers the installment.
        $page = $this->withSession()->get(self::URL);
        $page->assertStatus(200);
        $page->assertSeeElement('#generated-settlements');
        $page->assertSee('Pendiente de transferencia');
        $page->assertSee('$ 468.796,29');
        $page->assertDontSeeElement('#installment-' . $installment);
        $page->assertSeeElement('#settleable-empty');
    }

    public function testSeveralInstallmentsAreSettledTogetherOnePerContractAndPeriod(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $contract = $this->createContract($tenantId, 'Mitre 845', '10.00');
        $october  = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $november = $this->insertInstallment($contract['contract_id'], '2026-11-01', InstallmentModel::STATUS_PAID);
        $other    = $this->createContract($tenantId, 'Lavalle 910', '0.00');
        $zero     = $this->insertInstallment($other['contract_id'], '2026-11-01', InstallmentModel::STATUS_PAID);

        foreach ([$october, $november, $zero] as $id) {
            $this->insertPayment($id, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-' . $id);
        }

        $response = $this->postAs($adminSession, self::URL, [
            'installment_ids' => [(string) $october, (string) $november, (string) $zero],
        ]);
        $response->assertRedirectTo(site_url(self::URL));
        $this->assertStringContainsString(
            'Se generaron 3 liquidaciones «Pendiente de transferencia»: bruto $ 1.500.000, comisión $ 100.000 y neto a transferir $ 1.400.000.',
            (string) session()->getFlashdata('success')
        );

        $rows = $this->db->table('settlements')->orderBy('id')->get()->getResultArray();
        $this->assertCount(3, $rows);
        $this->assertSame(['2026-10-01', '2026-11-01'], array_column(array_slice($rows, 0, 2), 'period'));
        // A contract with 0 % commission: the owner gets the whole amount.
        $this->assertSame('0.00', $rows[2]['commission_amount']);
        $this->assertSame('500000.00', $rows[2]['net_amount']);
    }

    public function testThePercentageIsTheOneInTheContractWhenTheSettlementIsGenerated(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $contract    = $this->createContract($tenantId, 'Laprida 123', '8.00');
        $installment = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($installment, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-9');

        $this->db->table('contracts')->where('id', $contract['contract_id'])->update(['commission_percentage' => '12.50']);

        $this->postAs($adminSession, self::URL, ['installment_ids' => [(string) $installment]])->assertSessionHas('success');

        // A later change to the contract does not alter the settlement already generated.
        $this->db->table('contracts')->where('id', $contract['contract_id'])->update(['commission_percentage' => '20.00']);

        $settlement = $this->db->table('settlements')->get()->getRowArray();
        $this->assertSame('12.50', $settlement['commission_percentage']);
        $this->assertSame('62500.00', $settlement['commission_amount']);
        $this->assertSame('437500.00', $settlement['net_amount']);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function notCollectedProvider(): array
    {
        return [
            'pending installment'                   => [InstallmentModel::STATUS_PENDING, null],
            'overdue installment, payment rejected' => [InstallmentModel::STATUS_OVERDUE, PaymentModel::STATUS_REJECTED],
            'payment under review'                  => [InstallmentModel::STATUS_UNDER_REVIEW, PaymentModel::STATUS_PENDING_VALIDATION],
            'paid without an approved payment'      => [InstallmentModel::STATUS_PAID, PaymentModel::STATUS_REJECTED],
        ];
    }

    #[DataProvider('notCollectedProvider')]
    public function testAnInstallmentThatWasNotCollectedIsRejectedAndNothingIsGenerated(string $status, ?string $paymentStatus): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        // A valid installment goes in the same request: the operation is all or nothing.
        $valid      = $this->createContract($tenantId, 'Valida 10', '8.00');
        $validId    = $this->insertInstallment($valid['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($validId, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-V');

        $contract    = $this->createContract($tenantId, 'No cobrada 20', '8.00');
        $installment = $this->insertInstallment($contract['contract_id'], '2026-10-01', $status);

        if ($paymentStatus !== null) {
            $this->insertPayment($installment, $paymentStatus, $adminId, 'TRF-X');
        }

        $response = $this->postAs($adminSession, self::URL, ['installment_ids' => [(string) $validId, (string) $installment]]);
        $response->assertRedirectTo(site_url(self::URL));
        $this->assertSame(
            sprintf(SettlementController::NOT_COLLECTED_MESSAGE, 'N.º ' . $this->numberOf($installment) . ' del contrato #' . $contract['contract_id'] . ' (Octubre 2026)'),
            session()->getFlashdata('error')
        );

        $this->assertSame(0, $this->db->table('settlements')->countAllResults());
        $this->assertSame(0, $this->db->table('settlement_installments')->countAllResults());
    }

    public function testAnInstallmentCannotBeSettledTwice(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $contract    = $this->createContract($tenantId, 'Córdoba 55', '8.00');
        $installment = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($installment, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-D');

        $this->postAs($adminSession, self::URL, ['installment_ids' => [(string) $installment]])->assertSessionHas('success');

        // A stale page or a hand-made request sends it again.
        $_SESSION = [];
        $again    = $this->postAs($adminSession, self::URL, ['installment_ids' => [(string) $installment]]);
        $again->assertRedirectTo(site_url(self::URL));
        $this->assertSame(
            sprintf(SettlementController::ALREADY_SETTLED_MESSAGE, 'N.º ' . $this->numberOf($installment) . ' del contrato #' . $contract['contract_id'] . ' (Octubre 2026)'),
            session()->getFlashdata('error')
        );

        $this->assertSame(1, $this->db->table('settlements')->countAllResults());
        $this->assertSame(1, $this->db->table('settlement_installments')->countAllResults());

        // The database refuses a second link too, whatever the application does.
        $result = model(SettlementModel::class)->generate([$installment]);
        $this->assertSame(SettlementModel::ERROR_ALREADY_SETTLED, $result['error']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidSelectionProvider(): array
    {
        return [
            'nothing selected'        => [[], SettlementController::NOTHING_SELECTED_MESSAGE],
            'empty list'              => [['installment_ids' => ''], SettlementController::NOTHING_SELECTED_MESSAGE],
            'id that does not exist'  => [['installment_ids' => ['999999']], SettlementController::NOT_FOUND_MESSAGE],
            'id that is not a number' => [['installment_ids' => ['1 OR 1=1']], SettlementController::NOT_FOUND_MESSAGE],
            'zero'                    => [['installment_ids' => ['0']], SettlementController::NOT_FOUND_MESSAGE],
            'scalar instead of list'  => [['installment_ids' => '1'], SettlementController::NOT_FOUND_MESSAGE],
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    #[DataProvider('invalidSelectionProvider')]
    public function testAnInvalidSelectionIsRejected(array $post, string $message): void
    {
        [, $adminSession] = $this->createUserSession('admin');

        $response = $this->postAs($adminSession, self::URL, $post);
        $response->assertRedirectTo(site_url(self::URL));
        $this->assertSame($message, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('settlements')->countAllResults());
    }

    public function testTheOwnerFilterLimitsTheList(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$tenantId]               = $this->createUserSession('tenant');

        $first  = $this->createContract($tenantId, 'Primero 1', '8.00');
        $second = $this->createContract($tenantId, 'Segundo 2', '8.00');

        foreach ([$first, $second] as $contract) {
            $id = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
            $this->insertPayment($id, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-' . $id);
        }

        $page = $this->withSession($adminSession)->get(self::URL . '?owner=' . $first['owner_id']);
        $page->assertStatus(200);
        $page->assertSee('Primero 1');
        $page->assertDontSee('Segundo 2');

        $_SESSION = [];
        $invalid  = $this->withSession($adminSession)->get(self::URL . '?owner=abc');
        $invalid->assertStatus(200);
        $invalid->assertSeeElement('#owner-error');
        $invalid->assertSee('Primero 1');
        $invalid->assertSee('Segundo 2');
    }

    public function testOnlyTheAdministratorSeesTheMenuEntry(): void
    {
        [, $adminSession]    = $this->createUserSession('admin');
        [, $employeeSession] = $this->createUserSession('employee');

        $admin = $this->withSession($adminSession)->get('admin/dashboard');
        $admin->assertSee(site_url(self::URL));

        $_SESSION = [];
        $employee = $this->withSession($employeeSession)->get('admin/dashboard');
        $employee->assertStatus(200);
        $employee->assertDontSee(site_url(self::URL));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function forbiddenRoleProvider(): array
    {
        return [
            'employee' => ['employee', '/admin/dashboard'],
            'owner'    => ['owner', '/propietario/dashboard'],
            'tenant'   => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('forbiddenRoleProvider')]
    public function testOtherRolesCannotSeeOrGenerateSettlements(string $role, string $redirect): void
    {
        [$adminId] = $this->createUserSession('admin');
        [$tenantId] = $this->createUserSession('tenant');
        [, $session] = $this->createUserSession($role);

        $contract    = $this->createContract($tenantId, 'Prohibido 9', '8.00');
        $installment = $this->insertInstallment($contract['contract_id'], '2026-10-01', InstallmentModel::STATUS_PAID);
        $this->insertPayment($installment, PaymentModel::STATUS_APPROVED, $adminId, 'TRF-P');

        $this->withSession($session)->get(self::URL)->assertRedirectTo($redirect);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $_SESSION = [];
        $this->postAs($session, self::URL, ['installment_ids' => [(string) $installment]])->assertRedirectTo($redirect);
        $this->assertSame(0, $this->db->table('settlements')->countAllResults());
    }

    public function testAVisitorIsSentToTheLogin(): void
    {
        $_SESSION = [];
        $this->withSession([])->get(self::URL)->assertRedirectTo('/auth/login');
    }

    private function numberOf(int $installmentId): int
    {
        return (int) $this->db->table('installments')->where('id', $installmentId)->get()->getRowArray()['number'];
    }

    /**
     * @param array<string, mixed> $sessionData
     * @param array<string, mixed> $postData
     */
    private function postAs(array $sessionData, string $uri, array $postData = []): TestResponse
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, array_merge($postData, [$tokenName => $tokenHash]));
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName): array
    {
        $sequence = ++$this->sequence;
        $roleId   = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];
        $email    = $roleName . $sequence . '@hu19.example.test';

        $this->db->table('users')->insert([
            'role_id'         => $roleId,
            'first_name'      => ucfirst($roleName),
            'last_name'       => 'Test' . $sequence,
            'document_number' => (string) (41000000 + $sequence),
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
            'user_name'    => ucfirst($roleName) . ' Test' . $sequence,
            'user_email'   => $email,
        ]];
    }

    /**
     * @return array{contract_id: int, owner_id: int}
     */
    private function createContract(int $tenantId, string $address, string $commission): array
    {
        [$ownerId] = $this->createUserSession('owner');

        $this->db->table('properties')->insert([
            'owner_id'    => $ownerId,
            'address'     => $address,
            'city'        => 'Corrientes',
            'type'        => 'apartment',
            'rooms'       => 2,
            'base_rent'   => '500000.00',
            'description' => 'Inmueble de prueba',
            'status'      => 'rented',
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '0.00',
            'due_day'                   => 10,
            'commission_percentage'     => $commission,
            'late_fee_daily_percentage' => '0.50',
            // Several contracts per test, one property each: only one may be in force per property.
            'status'                    => 'in_force',
            'created_at'                => '2026-11-01 10:00:00',
            'updated_at'                => '2026-11-01 10:00:00',
        ]);

        return ['contract_id' => (int) $this->db->insertID(), 'owner_id' => $ownerId];
    }

    private function insertInstallment(int $contractId, string $period, string $status): int
    {
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => ++$this->sequence,
            'period'      => $period,
            'due_date'    => substr($period, 0, 8) . '10',
            'base_amount' => '500000.00',
            'late_fee'    => '0.00',
            'status'      => $status,
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertPayment(int $installmentId, string $status, int $adminId, string $reference, string $amount = '500000.00'): int
    {
        $this->db->table('payments')->insert([
            'installment_id'   => $installmentId,
            'amount'           => $amount,
            'paid_on'          => '2026-11-09',
            'method'           => 'bank_transfer',
            'reference_number' => $reference,
            'status'           => $status,
            'rejection_reason' => $status === PaymentModel::STATUS_REJECTED ? 'Comprobante ilegible' : null,
            'resolved_by'      => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : $adminId,
            'resolved_at'      => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : '2026-11-10 10:00:00',
            'created_at'       => '2026-11-09 10:00:00',
            'updated_at'       => '2026-11-09 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }
}
