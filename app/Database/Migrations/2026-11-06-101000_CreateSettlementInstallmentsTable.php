<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Installments included in each settlement (HU-19).
 *
 * The UNIQUE key on installment_id guarantees that an installment is settled
 * only once. That it was actually collected (paid, with an approved payment) is
 * checked by the application when the settlement is generated.
 */
class CreateSettlementInstallmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'settlement_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'installment_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('installment_id', 'uq_settlement_installments_installment_id');
        $this->forge->addKey('settlement_id', false, false, 'idx_settlement_installments_settlement_id');
        $this->forge->addForeignKey('settlement_id', 'settlements', 'id', 'RESTRICT', 'RESTRICT', 'fk_settlement_installments_settlement_id');
        $this->forge->addForeignKey('installment_id', 'installments', 'id', 'RESTRICT', 'RESTRICT', 'fk_settlement_installments_installment_id');

        $this->forge->createTable('settlement_installments', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('settlement_installments', true);
    }
}
