<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Properties managed by the agency (HU-07 to HU-09).
 *
 * Deactivation is a soft delete (deleted_at) and keeps who did it (deleted_by).
 * "Rooms" is nullable because a plot of land has none; the application still
 * requires it for the other types.
 */
class CreatePropertiesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'address'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'type'              => ['type' => 'ENUM', 'constraint' => ['apartment', 'house', 'commercial', 'land', 'office']],
            'rooms'             => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
            'surface'           => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'included_services' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'base_rent'         => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['available', 'rented', 'maintenance'], 'default' => 'available'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_by'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('owner_id', false, false, 'idx_properties_owner_id');
        $this->forge->addKey('deleted_by', false, false, 'idx_properties_deleted_by');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_properties_owner_id');
        $this->forge->addForeignKey('deleted_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_properties_deleted_by');

        $this->forge->createTable('properties', false, ['ENGINE' => 'InnoDB']);

        $table = $this->db->prefixTable('properties');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD CONSTRAINT `chk_properties_rooms` CHECK (`rooms` IS NULL OR `rooms` > 0),
                ADD CONSTRAINT `chk_properties_surface` CHECK (`surface` IS NULL OR `surface` > 0),
                ADD CONSTRAINT `chk_properties_base_rent` CHECK (`base_rent` > 0)"
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('properties', true);
    }
}
