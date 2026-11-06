<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Monthly rent installments (HU-13 to HU-15).
 *
 * "period" is the first day of the month the installment belongs to, so it sorts,
 * indexes and compares as a date. At most one installment exists per contract and
 * period, and numbers are sequential per contract.
 */
class CreateInstallmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'contract_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'number'      => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'period'      => ['type' => 'DATE'],
            'due_date'    => ['type' => 'DATE'],
            'base_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'late_fee'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['pending', 'overdue', 'under_review', 'paid'], 'default' => 'pending'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['contract_id', 'period'], 'uq_installments_contract_period');
        $this->forge->addUniqueKey(['contract_id', 'number'], 'uq_installments_contract_number');
        // Overdue detection and due-date alerts (HU-14, HU-23) filter by status and due date.
        $this->forge->addKey(['status', 'due_date'], false, false, 'idx_installments_status_due_date');
        $this->forge->addForeignKey('contract_id', 'contracts', 'id', 'RESTRICT', 'RESTRICT', 'fk_installments_contract_id');

        $this->forge->createTable('installments', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('installments');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_installments_number` CHECK (`number` > 0),
                ADD CONSTRAINT `chk_installments_period` CHECK (DAY(`period`) = 1),
                ADD CONSTRAINT `chk_installments_base_amount` CHECK (`base_amount` > 0),
                ADD CONSTRAINT `chk_installments_late_fee` CHECK (`late_fee` >= 0)"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('installments', true);
    }
}
