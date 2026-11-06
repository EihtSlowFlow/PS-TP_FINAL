<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Roles of the system (admin, employee, owner, tenant). Rows are created by RoleSeeder.
 */
class CreateRolesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name', 'uq_roles_name');

        $this->forge->createTable('roles', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('roles', true);
    }
}
