<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * In-app notifications (HU-22 to HU-24): one row per recipient.
 *
 * reference_type and reference_id point to the record that originated the
 * notification (a payment, an installment or a contract). They are polymorphic,
 * so there is no foreign key on reference_id; both are set or both are NULL.
 * Read notifications are kept as history (read_at).
 */
class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'type'           => [
                'type'       => 'ENUM',
                'constraint' => ['payment_submitted', 'payment_approved', 'payment_rejected', 'installment_due_soon', 'contract_expiring'],
            ],
            'message'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'reference_type' => ['type' => 'ENUM', 'constraint' => ['payment', 'installment', 'contract'], 'null' => true],
            'reference_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'read_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        // The unread counter (HU-22) filters by recipient and read state.
        $this->forge->addKey(['user_id', 'read_at'], false, false, 'idx_notifications_user_read_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_notifications_user_id');

        $this->forge->createTable('notifications', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('notifications');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_notifications_reference`
                    CHECK ((`reference_type` IS NULL AND `reference_id` IS NULL)
                        OR (`reference_type` IS NOT NULL AND `reference_id` IS NOT NULL))"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}
