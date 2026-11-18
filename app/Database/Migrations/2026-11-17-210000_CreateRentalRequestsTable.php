<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rental requests submitted by tenants on available properties and resolved by the administration (HU-30, HU-31).
 *
 * Submitting a request does not reserve the property or change its commercial status (`properties.status` remains
 * "available" until the administration creates the contract in HU-31). At most one request may remain "pending" per
 * tenant and property at a time; because MySQL does not support partial unique indexes, the application enforces that
 * rule under a row lock inside a transaction.
 *
 *  - status:           pending (initial), approved (linked to a contract), rejected (with a mandatory reason) or
 *                      cancelled (withdrawn by the tenant while it was still pending).
 *  - rejection_reason: required whenever the request is "rejected" (checked by the database too).
 *  - resolved_by / resolved_at: the administrator or employee who approved or rejected it, and when.
 *  - contract_id:      the lease contract created from an approved request (HU-31).
 */
class CreateRentalRequestsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'property_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'tenant_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'desired_start_date' => ['type' => 'DATE'],
            'desired_months'     => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'contact_phone'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'message'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected', 'cancelled'], 'default' => 'pending'],
            'rejection_reason'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'resolved_by'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'resolved_at'        => ['type' => 'DATETIME', 'null' => true],
            'contract_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['property_id', 'status'], false, false, 'idx_rental_requests_property_status');
        $this->forge->addKey('tenant_id', false, false, 'idx_rental_requests_tenant_id');
        $this->forge->addKey('resolved_by', false, false, 'idx_rental_requests_resolved_by');
        $this->forge->addKey('contract_id', false, false, 'idx_rental_requests_contract_id');

        $this->forge->addForeignKey('property_id', 'properties', 'id', 'RESTRICT', 'RESTRICT', 'fk_rental_requests_property_id');
        $this->forge->addForeignKey('tenant_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_rental_requests_tenant_id');
        $this->forge->addForeignKey('resolved_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_rental_requests_resolved_by');
        $this->forge->addForeignKey('contract_id', 'contracts', 'id', 'RESTRICT', 'RESTRICT', 'fk_rental_requests_contract_id');

        $this->forge->createTable('rental_requests', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('rental_requests');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_rental_requests_desired_months` CHECK (`desired_months` > 0),
                ADD CONSTRAINT `chk_rental_requests_rejection_reason`
                    CHECK (`status` <> 'rejected' OR (`rejection_reason` IS NOT NULL AND `rejection_reason` <> ''))"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('rental_requests', true);
    }
}

