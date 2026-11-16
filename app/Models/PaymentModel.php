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
     * Computes `paidAmount - expectedTotal` in exact cents, formatted with two decimals (e.g. "-1500.00" or "0.00").
     */
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

