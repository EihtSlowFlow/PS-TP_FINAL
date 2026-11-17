<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance tests for HU-17: review, approve and reject tenant-reported payments.
 *
 * @internal
 */
final class PaymentReviewTest extends CIUnitTestCase
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

        $_SESSION = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    public function testReviewQueueRendersAndApprovalResolvesPayment(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [$adminId, $adminSession]   = $this->createUserSession('admin');

        $contractId = $this->createContract($tenantId, 'Mitre 845');
        $installmentId = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', InstallmentModel::STATUS_PENDING);
        $paymentId = $this->insertPayment($installmentId, PaymentModel::STATUS_PENDING_VALIDATION, $adminId, '2026-11-15', 'bank_transfer', 'TRF-100');

        $queue = $this->withSession($adminSession)->get('admin/pagos');
        $queue->assertStatus(200);
        $queue->assertSee('Pagos');
        $queue->assertSee('Pendiente de validación');
        $queue->assertSee('Mitre 845');
        $queue->assertSee(site_url('admin/pagos/' . $paymentId));

        $detail = $this->withSession($adminSession)->get('admin/pagos/' . $paymentId);
        $detail->assertStatus(200);
        $detail->assertSee('Revisión de pago');
        $detail->assertSee('TRF-100');
        $detail->assertSee('Aprobar pago');
        $detail->assertSee('Rechazar pago');

        $response = $this->withSession($adminSession)->post('admin/pagos/' . $paymentId . '/aprobacion');
        $response->assertRedirectTo(site_url('admin/pagos'));
        $response->assertSessionHas('success');

        $payment = $this->db->table('payments')->where('id', $paymentId)->get()->getRowArray();
        $this->assertSame(PaymentModel::STATUS_APPROVED, $payment['status']);
        $this->assertNotNull($payment['resolved_by']);
        $this->assertNotNull($payment['resolved_at']);

        $installment = $this->db->table('installments')->where('id', $installmentId)->get()->getRowArray();
        $this->assertSame(InstallmentModel::STATUS_PAID, $installment['status']);
    }

    public function testRejectRequiresReasonAndReturnsInstallmentToPreviousState(): void
    {
        [$tenantId] = $this->createUserSession('tenant');
        [$adminId, $adminSession] = $this->createUserSession('admin');

        $contractId = $this->createContract($tenantId, 'San Martín 322');
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_OVERDUE);
        $paymentId = $this->insertPayment($installmentId, PaymentModel::STATUS_PENDING_VALIDATION, $adminId, '2026-11-15', 'deposit', 'DEP-555');

        $withoutReason = $this->withSession($adminSession)->post('admin/pagos/' . $paymentId . '/rechazo', [
            'rejection_reason' => '   ',
        ]);
        $withoutReason->assertRedirectTo(site_url('admin/pagos/' . $paymentId));
        $withoutReason->assertSessionHas('error');

        $withReason = $this->withSession($adminSession)->post('admin/pagos/' . $paymentId . '/rechazo', [
            'rejection_reason' => 'Comprobante ilegible',
        ]);
        $withReason->assertRedirectTo(site_url('admin/pagos'));
        $withReason->assertSessionHas('success');

        $payment = $this->db->table('payments')->where('id', $paymentId)->get()->getRowArray();
        $this->assertSame(PaymentModel::STATUS_REJECTED, $payment['status']);
        $this->assertSame('Comprobante ilegible', $payment['rejection_reason']);

        $installment = $this->db->table('installments')->where('id', $installmentId)->get()->getRowArray();
        $this->assertSame(InstallmentModel::STATUS_OVERDUE, $installment['status']);
    }

    public function testTenantAndAdminViewsDisplayPaymentHistory(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [$otherTenantId] = $this->createUserSession('tenant');
        [$adminId, $adminSession] = $this->createUserSession('admin');

        $tenantContract = $this->createContract($tenantId, 'Laprida 123');
        $otherContract = $this->createContract($otherTenantId, 'Belgrano 456');
        $tenantInstallment = $this->insertInstallment($tenantContract, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PAID);
        $otherInstallment = $this->insertInstallment($otherContract, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PAID);

        $tenantPaymentId = $this->insertPayment($tenantInstallment, PaymentModel::STATUS_APPROVED, $adminId, '2026-11-09', 'bank_transfer', 'TRF-777');
        $this->insertReceipt($tenantPaymentId, 'mi-comprobante.pdf');
        $otherPaymentId = $this->insertPayment($otherInstallment, PaymentModel::STATUS_REJECTED, $adminId, '2026-11-10', 'deposit', 'DEP-888');
        $this->insertReceipt($otherPaymentId, 'otro-comprobante.pdf');

        $tenantHistory = $this->withSession($tenantSession)->get('inquilino/mis-pagos');
        $tenantHistory->assertStatus(200);
        $tenantHistory->assertSee('Mis pagos');
        $tenantHistory->assertSee('TRF-777');
        $tenantHistory->assertDontSee('DEP-888');

        $adminHistory = $this->withSession($adminSession)->get('admin/pagos/historial');
        $adminHistory->assertStatus(200);
        $adminHistory->assertSee('Historial de pagos');
        $adminHistory->assertSee('TRF-777');
        $adminHistory->assertSee('DEP-888');

        $filtered = $this->withSession($adminSession)->get('admin/pagos/historial?status=' . PaymentModel::STATUS_REJECTED);
        $filtered->assertStatus(200);
        $filtered->assertSee('Rechazado');
        $filtered->assertDontSee('Aprobado');
    }

    public function testTenantCannotAccessOtherTenantReceipt(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [$otherTenantId, $otherSession] = $this->createUserSession('tenant');
        [$adminId] = $this->createUserSession('admin');

        $tenantContract = $this->createContract($tenantId, 'Yrigoyen 50');
        $otherContract = $this->createContract($otherTenantId, 'Mendoza 77');
        $tenantInstallment = $this->insertInstallment($tenantContract, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PAID);
        $otherInstallment = $this->insertInstallment($otherContract, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PAID);

        $otherPaymentId = $this->insertPayment($otherInstallment, PaymentModel::STATUS_APPROVED, $adminId, '2026-11-11', 'digital_gateway', 'PG-005');
        $receiptId = $this->insertReceipt($otherPaymentId, 'ajeno.pdf');

        $response = $this->withSession($tenantSession)->get('comprobantes/' . $receiptId);
        $response->assertStatus(403);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName): array
    {
        $sequence = ++$this->sequence;
        $roleId = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];
        $email = $roleName . $sequence . '@hu17.example.test';

        $this->db->table('users')->insert([
            'role_id' => $roleId,
            'first_name' => ucfirst($roleName),
            'last_name' => 'Test' . $sequence,
            'document_number' => (string) (40000000 + $sequence),
            'email' => $email,
            'phone' => '379400000' . $sequence,
            'address' => 'Dirección de prueba 123',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ]);
        $userId = (int) $this->db->insertID();

        return [$userId, [
            'is_logged_in' => true,
            'user_id' => $userId,
            'user_role' => $roleName,
            'user_name' => ucfirst($roleName) . ' Test' . $sequence,
            'user_email' => $email,
        ]];
    }

    private function createContract(int $tenantId, string $address): int
    {
        [$ownerId] = $this->createUserSession('owner');
        $this->db->table('properties')->insert([
            'owner_id' => $ownerId,
            'address' => $address,
            'city' => 'Corrientes',
            'type' => 'apartment',
            'rooms' => 2,
            'base_rent' => '500000.00',
            'description' => 'Inmueble de prueba',
            'status' => 'rented',
            'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'owner_id' => $ownerId,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'monthly_rent' => '500000.00',
            'update_percentage' => '10.00',
            'due_day' => 10,
            'commission_percentage' => '8.00',
            'late_fee_daily_percentage' => '0.50',
            'status' => 'in_force',
            'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate, string $status): int
    {
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number' => ++$this->sequence,
            'period' => $period,
            'due_date' => $dueDate,
            'base_amount' => '500000.00',
            'late_fee' => '0.00',
            'status' => $status,
            'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertPayment(int $installmentId, string $status, int $adminId, string $paidOn, string $method, string $reference): int
    {
        $this->db->table('payments')->insert([
            'installment_id' => $installmentId,
            'amount' => '500000.00',
            'paid_on' => $paidOn,
            'method' => $method,
            'reference_number' => $reference,
            'status' => $status,
            'rejection_reason' => $status === PaymentModel::STATUS_REJECTED ? 'Comprobante ilegible' : null,
            'resolved_by' => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : $adminId,
            'resolved_at' => $status === PaymentModel::STATUS_PENDING_VALIDATION ? null : '2026-11-15 10:00:00',
            'created_at' => '2026-11-14 10:00:00',
            'updated_at' => '2026-11-14 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertReceipt(int $paymentId, string $originalName): int
    {
        $this->db->table('payment_receipts')->insert([
            'payment_id' => $paymentId,
            'original_name' => $originalName,
            'stored_name' => 'receipt-' . $paymentId . '-' . bin2hex(random_bytes(8)) . '.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'created_at' => '2026-11-14 10:00:00',
            'updated_at' => '2026-11-14 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }
}
