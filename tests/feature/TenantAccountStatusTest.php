<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance tests for HU-15: tenant account statement.
 *
 * @internal
 */
final class TenantAccountStatusTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-16';

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

    public function testAccountShowsOnlyOwnInstallmentsGroupedWithServerCalculatedAmountsAndApprovedPaymentDetails(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [$otherTenantId]            = $this->createUserSession('tenant');
        [$adminId]                  = $this->createUserSession('admin');

        $contractId = $this->createContract($tenantId, 'Own account property');
        $otherContractId = $this->createContract($otherTenantId, 'Other tenant property');

        $pendingId = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', InstallmentModel::STATUS_PENDING);
        $overdueId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PENDING);
        $reviewId  = $this->insertInstallment($contractId, '2026-10-01', '2026-10-10', InstallmentModel::STATUS_UNDER_REVIEW);
        $paidId    = $this->insertInstallment($contractId, '2026-09-01', '2026-09-10', InstallmentModel::STATUS_PAID);
        $otherId   = $this->insertInstallment($otherContractId, '2026-12-01', '2026-12-10', InstallmentModel::STATUS_PENDING);

        $reviewPaymentId = $this->insertPayment($reviewId, PaymentModel::STATUS_PENDING_VALIDATION, $adminId, '2026-11-15', 'deposit');
        $paidPaymentId   = $this->insertPayment($paidId, PaymentModel::STATUS_APPROVED, $adminId, '2026-09-09', 'bank_transfer');
        $this->insertReceipt($paidPaymentId);

        $response = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');

        $response->assertStatus(200);
        $response->assertSee('Pendiente');
        $response->assertSee('Vencida');
        $response->assertSee('En revisión');
        $response->assertSee('Pagada');
        $response->assertSee('Own account property');
        $response->assertDontSee('Other tenant property');
        $response->assertSee('6 días de atraso');
        $response->assertSee('$ 15.000');
        $response->assertSee('$ 515.000');
        $response->assertSee('09/09/2026');
        $response->assertSee('Transferencia bancaria');
        $response->assertSee('Ver comprobante aprobado');
        $response->assertSee(site_url('comprobantes/' . $this->receiptIdForPayment($paidPaymentId)));
        $response->assertSee(site_url('inquilino/cuotas/' . $pendingId . '/informar-pago'));
        $response->assertSee(site_url('inquilino/cuotas/' . $overdueId . '/informar-pago'));
        $response->assertSee('En validación');
        $response->assertDontSee(site_url('inquilino/cuotas/' . $reviewId . '/informar-pago'));
        $response->assertDontSee(site_url('inquilino/cuotas/' . $otherId . '/informar-pago'));

        $overdue = $this->db->table('installments')->where('id', $overdueId)->get()->getRowArray();
        $this->assertSame(InstallmentModel::STATUS_OVERDUE, $overdue['status']);
        $this->assertSame('15000.00', $overdue['late_fee']);
    }

    public function testAccountShowsAnEmptyStateWhenTenantHasNoInstallments(): void
    {
        [, $tenantSession] = $this->createUserSession('tenant');

        $response = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');

        $response->assertStatus(200);
        $response->assertSee('Todavía no tenés cuotas emitidas');
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName): array
    {
        $sequence = ++$this->sequence;
        $roleId   = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];
        $email    = "{$roleName}{$sequence}@hu15.example.test";

        $this->db->table('users')->insert([
            'role_id'         => $roleId,
            'first_name'      => ucfirst($roleName),
            'last_name'       => 'Test' . $sequence,
            'document_number' => (string) (30000000 + $sequence),
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

    private function createContract(int $tenantId, string $address): int
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
            'update_percentage'         => '10.00',
            'due_day'                   => 10,
            'commission_percentage'     => '8.00',
            'late_fee_daily_percentage' => '0.50',
            'status'                    => 'in_force',
            'created_at'                => '2026-11-01 10:00:00',
            'updated_at'                => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate, string $status): int
    {
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => ++$this->sequence,
            'period'      => $period,
            'due_date'    => $dueDate,
            'base_amount' => '500000.00',
            'late_fee'    => '0.00',
            'status'      => $status,
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertPayment(int $installmentId, string $status, int $adminId, string $paidOn, string $method): int
    {
        $this->db->table('payments')->insert([
            'installment_id'   => $installmentId,
            'amount'           => '500000.00',
            'paid_on'          => $paidOn,
            'method'           => $method,
            'reference_number' => 'HU15-' . $installmentId,
            'status'           => $status,
            'rejection_reason' => null,
            'resolved_by'      => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : $adminId,
            'resolved_at'      => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : '2026-09-10 10:00:00',
            'created_at'       => '2026-09-10 10:00:00',
            'updated_at'       => '2026-09-10 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertReceipt(int $paymentId): void
    {
        $this->db->table('payment_receipts')->insert([
            'payment_id'    => $paymentId,
            'original_name' => 'comprobante.pdf',
            'stored_name'   => 'hu15-receipt.pdf',
            'mime_type'     => 'application/pdf',
            'size_bytes'    => 100,
            'created_at'    => '2026-09-10 10:00:00',
            'updated_at'    => '2026-09-10 10:00:00',
        ]);
    }

    private function receiptIdForPayment(int $paymentId): int
    {
        return (int) $this->db->table('payment_receipts')
            ->where('payment_id', $paymentId)
            ->get()
            ->getRowArray()['id'];
    }
}
