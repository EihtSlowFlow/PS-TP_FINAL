<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Metadata of the file attached to a payment (HU-16).
 *
 * The file itself lives in writable/uploads/comprobantes/ under stored_name (a
 * random name) and is never placed in public/. Access goes through a protected
 * controller.
 */
class CreatePaymentReceiptsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'payment_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'mime_type'     => ['type' => 'ENUM', 'constraint' => ['application/pdf', 'image/jpeg', 'image/png']],
            'size_bytes'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('stored_name', 'uq_payment_receipts_stored_name');
        $this->forge->addKey('payment_id', false, false, 'idx_payment_receipts_payment_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'RESTRICT', 'RESTRICT', 'fk_payment_receipts_payment_id');

        $this->forge->createTable('payment_receipts', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('payment_receipts');

        // 5242880 bytes = 5 MB, the maximum size accepted by HU-16.
        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_payment_receipts_size` CHECK (`size_bytes` BETWEEN 1 AND 5242880)"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('payment_receipts', true);
    }
}
