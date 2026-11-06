<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Reference data every environment needs: roles and the initial administrator.
 * Run with: php spark db:seed DatabaseSeeder
 *
 * Demo data for development is separate (DemoSeeder) and never runs from here.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('RoleSeeder');
        $this->call('AdminSeeder');
    }
}
