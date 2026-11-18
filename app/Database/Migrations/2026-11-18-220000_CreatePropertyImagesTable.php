<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Photos of a property, loaded by its owner (HU-32) or by the administration (HU-07) and shown on the landing, the
 * catalog and the property sheet.
 *
 * Like the payment receipts, the file lives outside public/ (writable/uploads/inmuebles/) under stored_name, a random
 * name, and is served by a controller that only shows it to everybody once the property is public. `position` keeps
 * the order: the lowest one is the cover.
 */
class CreatePropertyImagesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'property_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'mime_type'     => ['type' => 'ENUM', 'constraint' => ['image/jpeg', 'image/png', 'image/webp']],
            'size_bytes'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'position'      => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('stored_name', 'uq_property_images_stored_name');
        $this->forge->addKey(['property_id', 'position'], false, false, 'idx_property_images_property_position');
        $this->forge->addForeignKey('property_id', 'properties', 'id', 'RESTRICT', 'RESTRICT', 'fk_property_images_property_id');

        $this->forge->createTable('property_images', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('property_images');

        // 5242880 bytes = 5 MB, the most a stored photo may weigh (PropertyImageProcessor::MAX_SIZE_BYTES).
        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_property_images_size` CHECK (`size_bytes` BETWEEN 1 AND 5242880)"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('property_images', true);
    }
}
