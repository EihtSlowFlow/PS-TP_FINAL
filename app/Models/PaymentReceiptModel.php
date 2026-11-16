<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistence for uploaded payment receipts (HU-16).
 *
 * Receipt files live in the private directory of the `receiptStorage` service (`writable/uploads/comprobantes/`)
 * under a random `stored_name` and are served only through an authorized controller action.
 */
class PaymentReceiptModel extends Model
{
    protected $table            = 'payment_receipts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'payment_id',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns receipt metadata along with its owning tenant_id and installment_id for authorization checks.
     *
     * @return array<string, mixed>|null
     */
    public function findForAccess(int $receiptId): ?array
    {
        $row = $this->builder()
            ->select(
                'payment_receipts.*, payments.installment_id, payments.status AS payment_status, '
                . 'contracts.tenant_id'
            )
            ->join('payments', 'payments.id = payment_receipts.payment_id')
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->where('payment_receipts.id', $receiptId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}

