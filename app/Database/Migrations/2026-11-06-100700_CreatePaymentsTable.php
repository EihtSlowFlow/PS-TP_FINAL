<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Payments reported by tenants and resolved by an employee (HU-16, HU-17).
 *
 * Rejected payments are never deleted (audit trail). A payment is either still
 * pending, with no resolver, or resolved, with resolver and timestamp; a rejection
 * always carries a reason. Whether the amount matches the installment, and that
 * the operation date is not in the future, are checked by the application.
 */
class CreatePaymentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'installment_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'paid_on'          => ['type' => 'DATE'],
            'method'           => ['type' => 'ENUM', 'constraint' => ['bank_transfer', 'deposit', 'digital_gateway']],
            'reference_number' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending_validation', 'approved', 'rejected'], 'default' => 'pending_validation'],
            'rejection_reason' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'resolved_by'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'resolved_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('installment_id', false, false, 'idx_payments_installment_id');
        $this->forge->addKey('resolved_by', false, false, 'idx_payments_resolved_by');
        // The admin review queue (HU-17) lists payments by status.
        $this->forge->addKey(['status', 'created_at'], false, false, 'idx_payments_status_created_at');
        $this->forge->addForeignKey('installment_id', 'installments', 'id', 'RESTRICT', 'RESTRICT', 'fk_payments_installment_id');
        $this->forge->addForeignKey('resolved_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_payments_resolved_by');

        $this->forge->createTable('payments', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('payments');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_payments_amount` CHECK (`amount` > 0),
                ADD CONSTRAINT `chk_payments_rejection_reason`
                    CHECK (`status` <> 'rejected' OR (`rejection_reason` IS NOT NULL AND `rejection_reason` <> '')),
                ADD CONSTRAINT `chk_payments_resolution`
                    CHECK ((`status` = 'pending_validation' AND `resolved_by` IS NULL AND `resolved_at` IS NULL)
                        OR (`status` <> 'pending_validation' AND `resolved_by` IS NOT NULL AND `resolved_at` IS NOT NULL))"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('payments', true);
    }
}
