<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Users of every role. Deactivation is a soft delete (deleted_at, RNF-02): a user
 * is "active" when deleted_at IS NULL, so there is no separate status column.
 *
 * Email and document number must be unique only among active users (HU-04), so
 * that someone who was deactivated can register again. A plain UNIQUE index
 * cannot express that, hence the generated columns below: they hold the value
 * while the user is active and NULL otherwise, and MySQL allows many NULLs in a
 * UNIQUE index.
 */
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'role_id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'first_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'last_name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'document_number' => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'password_hash'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('role_id', false, false, 'idx_users_role_id');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'RESTRICT', 'RESTRICT', 'fk_users_role_id');

        $this->forge->createTable('users', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('users');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD COLUMN `active_email` VARCHAR(255)
                    GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `email`, NULL)) VIRTUAL,
                ADD COLUMN `active_document_number` VARCHAR(20)
                    GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `document_number`, NULL)) VIRTUAL,
                ADD UNIQUE KEY `uq_users_active_email` (`active_email`),
                ADD UNIQUE KEY `uq_users_active_document_number` (`active_document_number`)"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
