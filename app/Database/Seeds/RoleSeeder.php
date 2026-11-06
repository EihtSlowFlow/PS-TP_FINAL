<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Creates the three roles of the system. Safe to run more than once.
 */
class RoleSeeder extends Seeder
{
    /**
     * Values of roles.name. The application shows their Spanish labels.
     */
    public const ROLES = ['admin', 'owner', 'tenant'];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::ROLES as $name) {
            $exists = $this->db->table('roles')->where('name', $name)->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('roles')->insert([
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
