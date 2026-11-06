<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Settlements paid to owners (HU-19 to HU-21): one row per contract and period.
 *
 * The commission percentage in force when the settlement is generated is stored
 * on the row, so later changes to the contract do not alter past settlements.
 * The transfer data is empty while the settlement is pending and mandatory once
 * it is transferred. That the transfer date is not in the future is checked by
 * the application.
 */
class CreateSettlementsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'contract_id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'period'                => ['type' => 'DATE'],
            'gross_amount'          => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'commission_percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'commission_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'net_amount'            => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'status'                => ['type' => 'ENUM', 'constraint' => ['pending_transfer', 'transferred'], 'default' => 'pending_transfer'],
            'transferred_at'        => ['type' => 'DATE', 'null' => true],
            'operation_number'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['contract_id', 'period'], 'uq_settlements_contract_period');
        // The pending balance per owner (dashboard, HU-25/26) filters by owner and status.
        $this->forge->addKey(['owner_id', 'status'], false, false, 'idx_settlements_owner_status');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_settlements_owner_id');
        $this->forge->addForeignKey('contract_id', 'contracts', 'id', 'RESTRICT', 'RESTRICT', 'fk_settlements_contract_id');

        $this->forge->createTable('settlements', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('settlements');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_settlements_period` CHECK (DAY(`period`) = 1),
                ADD CONSTRAINT `chk_settlements_gross_amount` CHECK (`gross_amount` > 0),
                ADD CONSTRAINT `chk_settlements_commission_percentage` CHECK (`commission_percentage` BETWEEN 0 AND 100),
                ADD CONSTRAINT `chk_settlements_amounts`
                    CHECK (`commission_amount` >= 0
                        AND `commission_amount` <= `gross_amount`
                        AND `net_amount` = `gross_amount` - `commission_amount`),
                ADD CONSTRAINT `chk_settlements_transfer`
                    CHECK ((`status` = 'pending_transfer' AND `transferred_at` IS NULL AND `operation_number` IS NULL)
                        OR (`status` = 'transferred' AND `transferred_at` IS NOT NULL
                            AND `operation_number` IS NOT NULL AND `operation_number` <> ''))"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('settlements', true);
    }
}
