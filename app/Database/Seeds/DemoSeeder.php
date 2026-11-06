<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;
use Throwable;

/**
 * Loads the demo data of demo_data.sql, the same file that can be run by hand in
 * MySQL Workbench, so both ways produce identical data.
 *
 * Run it with: php spark db:seed DemoSeeder
 *
 * It refuses to run in production. The password of every demo user comes from .env
 * (seed.demoPassword) and is stored hashed with BCRYPT. Everything runs in one
 * transaction, and running it again does nothing.
 */
class DemoSeeder extends Seeder
{
    private const MIN_PASSWORD_LENGTH = 8;
    private const DEMO_ADMIN_EMAIL    = 'admin.demo@example.com';
    private const SQL_FILE            = __DIR__ . '/demo_data.sql';

    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('DemoSeeder must not run in production.');
        }

        $password = (string) env('seed.demoPassword', '');

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException('Set seed.demoPassword (at least ' . self::MIN_PASSWORD_LENGTH . ' characters) in .env.');
        }

        if ($this->db->table('users')->where('email', self::DEMO_ADMIN_EMAIL)->countAllResults() > 0) {
            return;
        }

        $sql = file_get_contents(self::SQL_FILE);

        if ($sql === false) {
            throw new RuntimeException('Cannot read ' . self::SQL_FILE . '.');
        }

        $this->db->transBegin();

        try {
            // The file keeps this value because it only sets a default when the variable is empty.
            $this->db->query('SET @demo_password_hash = ' . $this->db->escape(password_hash($password, PASSWORD_BCRYPT)));

            foreach ($this->splitStatements($sql) as $statement) {
                $this->db->query($statement);
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Splits the SQL file into statements. The file follows three rules: comments are
     * whole lines starting with two dashes, a statement ends with a semicolon at the end
     * of a line, and no text literal contains a semicolon.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $lines = [];

        foreach (preg_split('/\R/', $sql) as $line) {
            $line = rtrim($line);

            if ($line === '' || str_starts_with(ltrim($line), '--')) {
                continue;
            }

            $lines[] = $line;
        }

        $statements = preg_split('/;\s*\n/', implode("\n", $lines) . "\n");

        return array_values(array_filter(array_map('trim', $statements), static fn (string $s): bool => $s !== ''));
    }
}
