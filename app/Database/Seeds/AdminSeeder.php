<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Creates the initial administrator so HU-01 can be tried right after migrating.
 *
 * The credentials come from .env (seed.adminEmail, seed.adminPassword), never from
 * code. Safe to run more than once: nothing is created if an active user already
 * has that email.
 */
class AdminSeeder extends Seeder
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function run(): void
    {
        $email    = trim((string) env('seed.adminEmail', ''));
        $password = (string) env('seed.adminPassword', '');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Set seed.adminEmail and seed.adminPassword in .env before running AdminSeeder.');
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException('seed.adminPassword must have at least ' . self::MIN_PASSWORD_LENGTH . ' characters.');
        }

        $role = $this->db->table('roles')->where('name', 'admin')->get()->getRow();

        if ($role === null) {
            throw new RuntimeException('The "admin" role does not exist. Run RoleSeeder first.');
        }

        $exists = $this->db->table('users')
            ->where('email', $email)
            ->where('deleted_at', null)
            ->countAllResults() > 0;

        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => (string) env('seed.adminFirstName', 'Admin'),
            'last_name'       => (string) env('seed.adminLastName', 'InmoGestion'),
            'document_number' => (string) env('seed.adminDocument', '00000000'),
            'email'           => $email,
            'password_hash'   => password_hash($password, PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
}
