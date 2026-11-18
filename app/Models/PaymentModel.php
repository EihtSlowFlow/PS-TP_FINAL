<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Persistence for tenant-reported payments and their lifecycle (HU-16, HU-17).
 */
class PaymentModel extends Model
{
    public const STATUS_PENDING_VALIDATION = 'pending_validation';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_REJECTED           = 'rejected';

    /**
     * Spanish labels for each payment status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING_VALIDATION => 'Pendiente de validación',
        self::STATUS_APPROVED           => 'Aprobado',
        self::STATUS_REJECTED           => 'Rechazado',
    ];

    public const METHOD_BANK_TRANSFER   = 'bank_transfer';
    public const METHOD_DEPOSIT         = 'deposit';
    public const METHOD_DIGITAL_GATEWAY = 'digital_gateway';

    /**
     * Allowed payment methods and their Spanish labels.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        self::METHOD_BANK_TRANSFER   => 'Transferencia bancaria',
        self::METHOD_DEPOSIT         => 'Depósito bancario',
        self::METHOD_DIGITAL_GATEWAY => 'Pasarela digital',
    ];

    /** Error codes returned by {@see registerTenantPayment()}. */
    public const ERROR_FORBIDDEN_OR_NOT_FOUND = 'forbidden_or_not_found';
    public const ERROR_INVALID_STATUS         = 'invalid_status';
    public const ERROR_CONTRACT_CLOSED        = 'contract_closed';
    public const ERROR_PERSISTENCE            = 'persistence';

    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'installment_id',
        'amount',
        'paid_on',
        'method',
        'reference_number',
        'status',
        'rejection_reason',
        'resolved_by',
        'resolved_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label of a payment status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Returns the Spanish label of a payment method.
     */
    public static function labelForMethod(string $method): string
    {
        return self::METHODS[$method] ?? $method;
    }

    /**
     * The latest approved or pending-validation payment for each installment in an account statement.
     *
     * @param list<int> $installmentIds
     *
     * @return array<int, array<string, mixed>> Keyed by installment id.
     */
    public function findTenantAccountDetails(array $installmentIds): array
    {
        if ($installmentIds === []) {
            return [];
        }

        $rows = $this->builder()
            ->select(
                'payments.id, payments.installment_id, payments.amount, payments.paid_on, payments.method, '
                . 'payments.status, payment_receipts.id AS receipt_id'
            )
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('payment_receipts', 'payment_receipts.payment_id = payments.id', 'left')
            ->whereIn('payments.installment_id', $installmentIds)
            ->groupStart()
                ->groupStart()
                    ->where('installments.status', InstallmentModel::STATUS_PAID)
                    ->where('payments.status', self::STATUS_APPROVED)
                ->groupEnd()
                ->orGroupStart()
                    ->where('installments.status', InstallmentModel::STATUS_UNDER_REVIEW)
                    ->where('payments.status', self::STATUS_PENDING_VALIDATION)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('payments.id', 'DESC')
            ->get()
            ->getResultArray();

        $details = [];

        foreach ($rows as $row) {
            $installmentId = (int) $row['installment_id'];

            if (! isset($details[$installmentId])) {
                $details[$installmentId] = $row;
            }
        }

        return $details;
    }

    /**
     * Computes `paidAmount - expectedTotal` in exact cents, formatted with two decimals (e.g. "-1500.00" or "0.00").
     */
    /**
     * Lists all payments waiting for an admin/employee review ordered by oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function findPendingValidationQueue(): array
    {
        $rows = $this->builder()
            ->select(
                'payments.id AS payment_id, payments.amount, payments.paid_on, payments.method, payments.reference_number, '
                . 'payments.created_at AS submitted_at, payments.status, '
                . 'installments.id AS installment_id, installments.number AS installment_number, installments.period, '
                . 'installments.base_amount, installments.late_fee, installments.due_date, installments.status AS installment_status, '
                . 'contracts.id AS contract_id, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'property.address AS property_address, property.city AS property_city, '
                . 'receipt.id AS receipt_id, receipt.original_name AS receipt_original_name, receipt.mime_type AS receipt_mime_type, receipt.size_bytes AS receipt_size_bytes'
            )
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->join('properties AS property', 'property.id = contracts.property_id')
            ->join('payment_receipts AS receipt', 'receipt.payment_id = payments.id', 'left')
            ->where('payments.status', self::STATUS_PENDING_VALIDATION)
            ->orderBy('payments.created_at', 'ASC')
            ->orderBy('payments.id', 'ASC')
            ->get()
            ->getResultArray();

        return array_values($rows);
    }

    /**
     * Returns the full data needed to review one pending payment.
     *
     * @return array<string, mixed>|null
     */
    public function findForReview(int $paymentId): ?array
    {
        $row = $this->builder()
            ->select(
                'payments.*, '
                . 'installments.id AS installment_id, installments.number AS installment_number, installments.period, '
                . 'installments.base_amount, installments.late_fee, installments.due_date, installments.status AS installment_status, '
                . 'contracts.id AS contract_id, contracts.owner_id, contracts.status AS contract_status, '
                . 'tenant.id AS tenant_id, tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'property.address AS property_address, property.city AS property_city, '
                . 'receipt.id AS receipt_id, receipt.original_name AS receipt_original_name, receipt.mime_type AS receipt_mime_type, receipt.size_bytes AS receipt_size_bytes'
            )
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->join('properties AS property', 'property.id = contracts.property_id')
            ->join('payment_receipts AS receipt', 'receipt.payment_id = payments.id', 'left')
            ->where('payments.id', $paymentId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Lists a tenant's payment history, newest first, including receipt metadata when present.
     *
     * @return list<array<string, mixed>>
     */
    public function findTenantHistory(int $tenantId, ?string $status = null): array
    {
        $builder = $this->builder()
            ->select(
                'payments.id AS payment_id, payments.amount, payments.paid_on, payments.method, payments.reference_number, payments.status, '
                . 'payments.rejection_reason, payments.created_at, payments.resolved_at, '
                . 'installments.id AS installment_id, installments.number AS installment_number, installments.period, '
                . 'installments.base_amount, installments.late_fee, '
                . 'payment_receipts.id AS receipt_id, payment_receipts.original_name AS receipt_original_name'
            )
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('payment_receipts', 'payment_receipts.payment_id = payments.id', 'left')
            ->where('contracts.tenant_id', $tenantId)
            ->orderBy('payments.created_at', 'DESC')
            ->orderBy('payments.id', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('payments.status', $status);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Lists the complete payment history for admin/employee filtering by status and period.
     *
     * @param string|null $status
     * @param string|null $period
     *
     * @return list<array<string, mixed>>
     */
    public function findAdminHistory(?string $status = null, ?string $period = null): array
    {
        $builder = $this->builder()
            ->select(
                'payments.id AS payment_id, payments.amount, payments.paid_on, payments.method, payments.reference_number, payments.status, '
                . 'payments.rejection_reason, payments.created_at, payments.resolved_at, '
                . 'installments.id AS installment_id, installments.number AS installment_number, installments.period, '
                . 'installments.base_amount, installments.late_fee, '
                . 'contracts.id AS contract_id, contracts.status AS contract_status, '
                . 'tenant.id AS tenant_id, tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'property.address AS property_address, property.city AS property_city, '
                . 'payment_receipts.id AS receipt_id, payment_receipts.original_name AS receipt_original_name'
            )
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->join('properties AS property', 'property.id = contracts.property_id')
            ->join('payment_receipts', 'payment_receipts.payment_id = payments.id', 'left')
            ->orderBy('payments.created_at', 'DESC')
            ->orderBy('payments.id', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('payments.status', $status);
        }

        if ($period !== null && $period !== '') {
            if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
                $builder->where('SUBSTR(installments.period, 1, 7) =', $period);
            } else {
                $builder->where('installments.period', $period);
            }
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Returns unique payment periods ordered newest first.
     *
     * @return list<string>
     */
    public function periodsWithPayments(): array
    {
        $rows = $this->builder()
            ->select('DISTINCT SUBSTR(installments.period, 1, 7) AS period')
            ->join('installments', 'installments.id = payments.installment_id')
            ->orderBy('period', 'DESC')
            ->get()
            ->getResultArray();

        return array_values(array_map(static fn (array $row): string => (string) ($row['period'] ?? ''), $rows));
    }

    /**
     * Resolves a pending payment; approves it or rejects it with a required reason.
     *
     * @return array{ok: bool, error?: string, payment_status?: string, installment_status?: string}
     */
    public function resolvePendingPayment(int $paymentId, int $resolverId, bool $approve, ?string $rejectionReason = null): array
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        // The row is locked inside the transaction: under autocommit `FOR UPDATE` is released as soon as the SELECT
        // ends, so two operators could both read the payment as pending and resolve it twice.
        $db->transBegin();

        try {
            $row = $db->query(
                'SELECT p.*, i.id AS installment_id, i.status AS installment_status, i.due_date, i.base_amount, i.late_fee '
                . 'FROM payments p '
                . 'INNER JOIN installments i ON i.id = p.installment_id '
                . 'WHERE p.id = ? '
                . 'FOR UPDATE',
                [$paymentId]
            )->getRowArray();

            if ($row === null) {
                $db->transRollback();

                return ['ok' => false, 'error' => 'not_found'];
            }

            if ((string) $row['status'] !== self::STATUS_PENDING_VALIDATION) {
                $db->transRollback();

                return ['ok' => false, 'error' => 'invalid_status'];
            }

            if ($approve) {
                $update = [
                    'status'           => self::STATUS_APPROVED,
                    'rejection_reason' => null,
                    'resolved_by'      => $resolverId,
                    'resolved_at'      => $now,
                    'updated_at'       => $now,
                ];
                $installmentStatus = InstallmentModel::STATUS_PAID;
            } else {
                $reason = trim((string) ($rejectionReason ?? ''));

                if ($reason === '') {
                    $db->transRollback();

                    return ['ok' => false, 'error' => 'missing_reason'];
                }

                $update = [
                    'status'           => self::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                    'resolved_by'      => $resolverId,
                    'resolved_at'      => $now,
                    'updated_at'       => $now,
                ];
                $installmentStatus = (string) $row['due_date'] < InstallmentModel::today()
                    ? InstallmentModel::STATUS_OVERDUE
                    : InstallmentModel::STATUS_PENDING;
            }

            $updatedPayment = $db->table('payments')
                ->where('id', $paymentId)
                ->update($update);

            if ($updatedPayment === false) {
                throw new RuntimeException('Could not resolve payment record.');
            }

            $updatedInstallment = $db->table('installments')
                ->where('id', (int) $row['installment_id'])
                ->update([
                    'status'     => $installmentStatus,
                    'updated_at' => $now,
                ]);

            if ($updatedInstallment === false) {
                throw new RuntimeException('Could not update installment state after payment resolution.');
            }

            $db->transCommit();

            return [
                'ok' => true,
                'payment_status' => $approve ? self::STATUS_APPROVED : self::STATUS_REJECTED,
                'installment_status' => $installmentStatus,
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            $db->resetTransStatus();
            log_message('error', 'Failed to resolve payment {id}: {message}', ['id' => $paymentId, 'message' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'persistence'];
        }
    }

    public static function amountDifference(string|float $paidAmount, string|float $expectedTotal): string
    {
        $paidCents     = (int) round(((float) $paidAmount) * 100);
        $expectedCents = (int) round(((float) $expectedTotal) * 100);
        $diff          = $paidCents - $expectedCents;
        $sign          = $diff < 0 ? '-' : '';
        $abs           = abs($diff);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }

    /**
     * Registers a tenant payment and its receipt atomically inside a database transaction (HU-16 Scenarios 1 and 5).
     *
     * Locks the installment row first (`FOR UPDATE`) so concurrent submissions cannot register two active payments
     * for the same installment. Moves the uploaded file via `$moveFile` only after all DB checks pass, and removes
     * any moved file if the transaction fails.
     *
     * @param array{amount: string, paid_on: string, method: string, reference_number: string} $paymentData
     * @param array{original_name: string, stored_name: string, mime_type: string, size_bytes: int} $receiptData
     * @param callable(): bool $moveFile Callback that persists the uploaded file to disk and returns true on success
     *
     * @return array{
     *     ok: true,
     *     payment_id: int,
     *     receipt_id: int,
     *     expected_total: string,
     *     amount_difference: string
     * }|array{
     *     ok: false,
     *     error: string,
     *     contract_status?: string
     * }
     */
    public function registerTenantPayment(
        int $installmentId,
        int $tenantId,
        array $paymentData,
        array $receiptData,
        callable $moveFile
    ): array {
        $db        = $this->db;
        $storage   = service('receiptStorage');
        $fileMoved = false;

        $db->transBegin();

        try {
            $installmentsTable = $db->prefixTable('installments');
            $contractsTable    = $db->prefixTable('contracts');

            $row = $db->query(
                "SELECT i.id, i.status, i.base_amount, i.late_fee, c.tenant_id, c.status AS contract_status
                 FROM `{$installmentsTable}` i
                 INNER JOIN `{$contractsTable}` c ON c.id = i.contract_id
                 WHERE i.id = ?
                 FOR UPDATE",
                [$installmentId]
            )->getRowArray();

            if ($row === null || (int) $row['tenant_id'] !== $tenantId) {
                $db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_FORBIDDEN_OR_NOT_FOUND];
            }

            if (! in_array((string) $row['status'], [InstallmentModel::STATUS_PENDING, InstallmentModel::STATUS_OVERDUE], true)) {
                $db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_INVALID_STATUS];
            }

            // A finished or rescinded contract no longer takes payments (HU-16).
            if ((string) $row['contract_status'] !== ContractModel::STATUS_IN_FORCE) {
                $db->transRollback();

                return [
                    'ok'              => false,
                    'error'           => self::ERROR_CONTRACT_CLOSED,
                    'contract_status' => (string) $row['contract_status'],
                ];
            }

            $now = date('Y-m-d H:i:s');

            $insertedPayment = $db->table('payments')->insert([
                'installment_id'   => $installmentId,
                'amount'           => $paymentData['amount'],
                'paid_on'          => $paymentData['paid_on'],
                'method'           => $paymentData['method'],
                'reference_number' => $paymentData['reference_number'],
                'status'           => self::STATUS_PENDING_VALIDATION,
                'rejection_reason' => null,
                'resolved_by'      => null,
                'resolved_at'      => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if ($insertedPayment === false) {
                throw new RuntimeException('Could not insert payment record.');
            }

            $paymentId = (int) $db->insertID();

            if (! $moveFile()) {
                throw new RuntimeException('Could not store receipt file on disk.');
            }

            $fileMoved = true;

            $insertedReceipt = $db->table('payment_receipts')->insert([
                'payment_id'    => $paymentId,
                'original_name' => $receiptData['original_name'],
                'stored_name'   => $receiptData['stored_name'],
                'mime_type'     => $receiptData['mime_type'],
                'size_bytes'    => $receiptData['size_bytes'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            if ($insertedReceipt === false) {
                throw new RuntimeException('Could not insert payment receipt record.');
            }

            $receiptId = (int) $db->insertID();

            $updatedInstallment = $db->table('installments')
                ->where('id', $installmentId)
                ->update([
                    'status'     => InstallmentModel::STATUS_UNDER_REVIEW,
                    'updated_at' => $now,
                ]);

            if ($updatedInstallment === false || $db->transStatus() === false) {
                throw new RuntimeException('Could not update installment status to under_review.');
            }

            $db->transCommit();

            $expectedTotal = InstallmentModel::sumAmounts([$row['base_amount'], $row['late_fee']]);

            return [
                'ok'                => true,
                'payment_id'        => $paymentId,
                'receipt_id'        => $receiptId,
                'expected_total'    => $expectedTotal,
                'amount_difference' => self::amountDifference($paymentData['amount'], $expectedTotal),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            // A failed query leaves the connection's transaction status "failed", which would make the
            // next transaction on this connection roll back too; the rollback above already discarded everything.
            $db->resetTransStatus();

            if ($fileMoved) {
                $storage->delete($receiptData['stored_name']);
            }

            log_message('error', 'Failed to register payment for installment {id}: {message}', [
                'id'      => $installmentId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }
    }
}
