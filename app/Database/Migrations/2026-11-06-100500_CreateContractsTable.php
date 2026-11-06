<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rental contracts (HU-10 to HU-12).
 *
 * owner_id is kept here, besides properties.owner_id, so the history survives if
 * the property later changes hands. The application must make sure it matches
 * the property's owner when the contract is created.
 *
 * Only one contract may be in force per property: active_property_id holds the
 * property id while the contract is in force and NULL otherwise, and its UNIQUE
 * index lets the database reject a second one.
 */
class CreateContractsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'property_id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'tenant_id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'owner_id'                  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'start_date'                => ['type' => 'DATE'],
            'end_date'                  => ['type' => 'DATE'],
            'monthly_rent'              => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'update_percentage'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'due_day'                   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'commission_percentage'     => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'late_fee_daily_percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'status'                    => ['type' => 'ENUM', 'constraint' => ['in_force', 'finished', 'rescinded'], 'default' => 'in_force'],
            'closed_at'                 => ['type' => 'DATE', 'null' => true],
            'closing_reason'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('property_id', false, false, 'idx_contracts_property_id');
        $this->forge->addKey('tenant_id', false, false, 'idx_contracts_tenant_id');
        $this->forge->addKey('owner_id', false, false, 'idx_contracts_owner_id');
        // Contract expiry alerts (HU-24) filter by status and end date.
        $this->forge->addKey(['status', 'end_date'], false, false, 'idx_contracts_status_end_date');
        $this->forge->addForeignKey('property_id', 'properties', 'id', 'RESTRICT', 'RESTRICT', 'fk_contracts_property_id');
        $this->forge->addForeignKey('tenant_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_contracts_tenant_id');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_contracts_owner_id');

        $this->forge->createTable('contracts', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('contracts');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD COLUMN `active_property_id` INT UNSIGNED
                    GENERATED ALWAYS AS (IF(`status` = 'in_force', `property_id`, NULL)) VIRTUAL,
                ADD UNIQUE KEY `uq_contracts_active_property_id` (`active_property_id`),
                ADD CONSTRAINT `chk_contracts_monthly_rent` CHECK (`monthly_rent` > 0),
                ADD CONSTRAINT `chk_contracts_dates` CHECK (`end_date` > `start_date`),
                ADD CONSTRAINT `chk_contracts_due_day` CHECK (`due_day` BETWEEN 1 AND 28),
                ADD CONSTRAINT `chk_contracts_update_percentage` CHECK (`update_percentage` BETWEEN 0 AND 100),
                ADD CONSTRAINT `chk_contracts_commission_percentage` CHECK (`commission_percentage` BETWEEN 0 AND 100),
                ADD CONSTRAINT `chk_contracts_late_fee_percentage` CHECK (`late_fee_daily_percentage` BETWEEN 0 AND 100),
                ADD CONSTRAINT `chk_contracts_closing` CHECK (`status` <> 'in_force' OR (`closed_at` IS NULL AND `closing_reason` IS NULL))"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('contracts', true);
    }
}
