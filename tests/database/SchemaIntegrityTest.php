<?php

use App\Database\Seeds\AdminSeeder;
use App\Database\Seeds\DemoSeeder;
use App\Database\Seeds\RoleSeeder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Integrity rules of the InmoGestión schema (issue #22).
 *
 * Runs the application migrations on the dedicated "tests" connection (a
 * disposable MySQL/MariaDB database, see database.tests.* in .env) and checks
 * that the database itself rejects duplicates and invalid data.
 *
 * @internal
 */
final class SchemaIntegrityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    protected function tearDown(): void
    {
        unset($_ENV['seed.adminEmail'], $_ENV['seed.adminPassword'], $_ENV['seed.demoPassword']);

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Structure
    // ------------------------------------------------------------------

    public function testMigrationsCreateEveryTable(): void
    {
        $expected = [
            'roles', 'users', 'password_resets', 'properties', 'contracts', 'installments',
            'payments', 'payment_receipts', 'settlements', 'settlement_installments', 'notifications',
        ];

        foreach ($expected as $table) {
            $this->assertTrue($this->db->tableExists($table), "Missing table {$table}");
        }
    }

    // ------------------------------------------------------------------
    // Users and roles
    // ------------------------------------------------------------------

    public function testRoleNameIsUnique(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->table('roles')->insert(['name' => 'admin']);
    }

    public function testActiveUsersCannotShareEmail(): void
    {
        $this->newUser('owner', 'dup@example.com', '11111111');

        $this->expectException(DatabaseException::class);

        $this->newUser('tenant', 'dup@example.com', '22222222');
    }

    public function testActiveUsersCannotShareDocumentNumber(): void
    {
        $this->newUser('owner', 'a@example.com', '11111111');

        $this->expectException(DatabaseException::class);

        $this->newUser('tenant', 'b@example.com', '11111111');
    }

    public function testDeactivatedUserDoesNotBlockTheSameEmailAndDocument(): void
    {
        $this->newUser('tenant', 'again@example.com', '33333333', '2026-01-01 10:00:00');

        $newId = $this->newUser('tenant', 'again@example.com', '33333333');

        $this->assertGreaterThan(0, $newId);
    }

    public function testUserRequiresAnExistingRole(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->table('users')->insert([
            'role_id'         => 9999,
            'first_name'      => 'No',
            'last_name'       => 'Role',
            'document_number' => '44444444',
            'email'           => 'norole@example.com',
            'password_hash'   => $this->dummyHash(),
        ]);
    }

    public function testUserWithPropertiesCannotBeDeletedPhysically(): void
    {
        $ownerId = $this->newUser('owner', 'owner@example.com', '55555555');
        $this->newProperty($ownerId);

        $this->expectException(DatabaseException::class);

        $this->db->table('users')->where('id', $ownerId)->delete();
    }

    // ------------------------------------------------------------------
    // Properties and contracts
    // ------------------------------------------------------------------

    public function testPropertyRequiresPositiveRent(): void
    {
        $ownerId = $this->newUser('owner', 'owner@example.com', '55555555');

        $this->expectException(DatabaseException::class);

        $this->newProperty($ownerId, ['base_rent' => '0.00']);
    }

    public function testOnlyOneContractCanBeInForcePerProperty(): void
    {
        [$ownerId, $tenantId, $propertyId] = $this->peopleAndProperty();
        $this->newContract($propertyId, $tenantId, $ownerId);

        $this->expectException(DatabaseException::class);

        $this->newContract($propertyId, $tenantId, $ownerId);
    }

    public function testPropertyCanHaveANewContractOnceTheOldOneIsClosed(): void
    {
        [$ownerId, $tenantId, $propertyId] = $this->peopleAndProperty();
        $this->newContract($propertyId, $tenantId, $ownerId, [
            'status'    => 'finished',
            'closed_at' => '2026-06-30',
        ]);

        $secondId = $this->newContract($propertyId, $tenantId, $ownerId);

        $this->assertGreaterThan(0, $secondId);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('provideInvalidContractValues')]
    public function testContractRejectsInvalidValues(array $overrides): void
    {
        [$ownerId, $tenantId, $propertyId] = $this->peopleAndProperty();

        $this->expectException(DatabaseException::class);

        $this->newContract($propertyId, $tenantId, $ownerId, $overrides);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidContractValues(): iterable
    {
        yield 'zero rent' => [['monthly_rent' => '0.00']];

        yield 'end before start' => [['start_date' => '2026-06-01', 'end_date' => '2026-05-01']];

        yield 'due day above 28' => [['due_day' => 29]];

        yield 'due day zero' => [['due_day' => 0]];

        yield 'commission above 100' => [['commission_percentage' => '100.01']];

        yield 'negative late fee percentage' => [['late_fee_daily_percentage' => '-1.00']];

        yield 'closing data on a contract in force' => [['closed_at' => '2026-06-30']];
    }

    // ------------------------------------------------------------------
    // Installments
    // ------------------------------------------------------------------

    public function testInstallmentIsUniquePerContractAndPeriod(): void
    {
        $contractId = $this->newContractWithPeople();
        $this->newInstallment($contractId, 1, '2026-07-01');

        $this->expectException(DatabaseException::class);

        $this->newInstallment($contractId, 2, '2026-07-01');
    }

    public function testInstallmentNumberIsUniquePerContract(): void
    {
        $contractId = $this->newContractWithPeople();
        $this->newInstallment($contractId, 1, '2026-07-01');

        $this->expectException(DatabaseException::class);

        $this->newInstallment($contractId, 1, '2026-08-01');
    }

    public function testInstallmentRequiresAnExistingContract(): void
    {
        $this->expectException(DatabaseException::class);

        $this->newInstallment(9999, 1, '2026-07-01');
    }

    public function testInstallmentPeriodMustBeTheFirstDayOfTheMonth(): void
    {
        $contractId = $this->newContractWithPeople();

        $this->expectException(DatabaseException::class);

        $this->newInstallment($contractId, 1, '2026-07-15');
    }

    // ------------------------------------------------------------------
    // Payments and receipts
    // ------------------------------------------------------------------

    public function testRejectedPaymentRequiresAReason(): void
    {
        $installmentId = $this->newInstallmentWithPeople();
        $adminId       = $this->newUser('admin', 'admin@example.com', '66666666');

        $this->expectException(DatabaseException::class);

        $this->newPayment($installmentId, [
            'status'      => 'rejected',
            'resolved_by' => $adminId,
            'resolved_at' => '2026-07-12 10:00:00',
        ]);
    }

    public function testResolvedPaymentRequiresResolverAndTimestamp(): void
    {
        $installmentId = $this->newInstallmentWithPeople();

        $this->expectException(DatabaseException::class);

        $this->newPayment($installmentId, ['status' => 'approved']);
    }

    public function testRejectedPaymentWithReasonIsKept(): void
    {
        $installmentId = $this->newInstallmentWithPeople();
        $adminId       = $this->newUser('admin', 'admin@example.com', '66666666');

        $paymentId = $this->newPayment($installmentId, [
            'status'           => 'rejected',
            'rejection_reason' => 'Illegible receipt',
            'resolved_by'      => $adminId,
            'resolved_at'      => '2026-07-12 10:00:00',
        ]);

        $this->assertGreaterThan(0, $paymentId);
    }

    public function testReceiptSizeCannotExceedFiveMegabytes(): void
    {
        $paymentId = $this->newPayment($this->newInstallmentWithPeople());

        $this->expectException(DatabaseException::class);

        $this->db->table('payment_receipts')->insert([
            'payment_id'    => $paymentId,
            'original_name' => 'big.pdf',
            'stored_name'   => 'abc123.pdf',
            'mime_type'     => 'application/pdf',
            'size_bytes'    => 5242881,
        ]);
    }

    public function testReceiptStoredNameIsUnique(): void
    {
        $paymentId = $this->newPayment($this->newInstallmentWithPeople());
        $row       = [
            'payment_id'    => $paymentId,
            'original_name' => 'a.pdf',
            'stored_name'   => 'same-name.pdf',
            'mime_type'     => 'application/pdf',
            'size_bytes'    => 1000,
        ];
        $this->db->table('payment_receipts')->insert($row);

        $this->expectException(DatabaseException::class);

        $this->db->table('payment_receipts')->insert($row);
    }

    // ------------------------------------------------------------------
    // Settlements and notifications
    // ------------------------------------------------------------------

    public function testSettlementIsUniquePerContractAndPeriod(): void
    {
        [$ownerId, $contractId] = $this->ownerAndContract();
        $this->newSettlement($ownerId, $contractId);

        $this->expectException(DatabaseException::class);

        $this->newSettlement($ownerId, $contractId);
    }

    public function testSettlementNetMustEqualGrossMinusCommission(): void
    {
        [$ownerId, $contractId] = $this->ownerAndContract();

        $this->expectException(DatabaseException::class);

        $this->newSettlement($ownerId, $contractId, ['net_amount' => '230000.00']);
    }

    public function testTransferredSettlementRequiresDateAndOperationNumber(): void
    {
        [$ownerId, $contractId] = $this->ownerAndContract();

        $this->expectException(DatabaseException::class);

        $this->newSettlement($ownerId, $contractId, ['status' => 'transferred']);
    }

    public function testInstallmentCanBeSettledOnlyOnce(): void
    {
        [$ownerId, $contractId] = $this->ownerAndContract();
        $installmentId          = $this->newInstallment($contractId, 1, '2026-07-01');
        $first                  = $this->newSettlement($ownerId, $contractId);
        $second                 = $this->newSettlement($ownerId, $contractId, ['period' => '2026-08-01']);

        $this->db->table('settlement_installments')->insert(['settlement_id' => $first, 'installment_id' => $installmentId]);

        $this->expectException(DatabaseException::class);

        $this->db->table('settlement_installments')->insert(['settlement_id' => $second, 'installment_id' => $installmentId]);
    }

    public function testNotificationReferenceIsAllOrNothing(): void
    {
        $userId = $this->newUser('tenant', 'tenant@example.com', '77777777');

        $this->expectException(DatabaseException::class);

        $this->db->table('notifications')->insert([
            'user_id'        => $userId,
            'type'           => 'payment_approved',
            'message'        => 'Approved',
            'reference_type' => 'payment',
        ]);
    }

    // ------------------------------------------------------------------
    // Seeders
    // ------------------------------------------------------------------

    public function testRoleSeederCreatesTheFourRolesOnlyOnce(): void
    {
        $this->seed(RoleSeeder::class);

        $names = array_column($this->db->table('roles')->orderBy('name')->get()->getResultArray(), 'name');

        $this->assertSame(['admin', 'employee', 'owner', 'tenant'], $names);
    }

    public function testAdminSeederCreatesAnActiveAdministratorWithAHashedPassword(): void
    {
        $_ENV['seed.adminEmail']    = 'admin.test@example.com';
        $_ENV['seed.adminPassword'] = 'a-test-password';

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $users = $this->db->table('users')->where('email', 'admin.test@example.com')->get()->getResultArray();

        $this->assertCount(1, $users);
        $this->assertNull($users[0]['deleted_at']);
        $this->assertNotSame('a-test-password', $users[0]['password_hash']);
        $this->assertTrue(password_verify('a-test-password', $users[0]['password_hash']));
    }

    public function testDemoSeederLoadsConsistentDataOnlyOnce(): void
    {
        $_ENV['seed.demoPassword'] = 'a-demo-password';

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $expected = [
            'users' => 9, 'properties' => 8, 'contracts' => 6, 'installments' => 24, 'payments' => 19,
            'settlements' => 15, 'settlement_installments' => 15, 'notifications' => 7,
        ];

        foreach ($expected as $table => $count) {
            $this->assertSame($count, $this->db->table($table)->countAllResults(), "Unexpected row count in {$table}");
        }

        $demoUser = $this->db->table('users')->where('email', 'tenant1.demo@example.com')->get()->getRow();
        $this->assertTrue(password_verify('a-demo-password', $demoUser->password_hash));

        // Every paid installment has exactly one approved payment, and nothing else does.
        $mismatches = $this->db->query(
            "SELECT i.id FROM installments i
             WHERE (i.status = 'paid') <> (
                 (SELECT COUNT(*) FROM payments p WHERE p.installment_id = i.id AND p.status = 'approved') = 1
             )"
        )->getResultArray();
        $this->assertSame([], $mismatches);
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    private function dummyHash(): string
    {
        return '$2y$10$' . str_repeat('x', 53);
    }

    private function roleId(string $name): int
    {
        return (int) $this->db->table('roles')->where('name', $name)->get()->getRow()->id;
    }

    private function newUser(string $role, string $email, string $document, ?string $deletedAt = null): int
    {
        $this->db->table('users')->insert([
            'role_id'         => $this->roleId($role),
            'first_name'      => 'Test',
            'last_name'       => 'User',
            'document_number' => $document,
            'email'           => $email,
            'password_hash'   => $this->dummyHash(),
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert($overrides + [
            'owner_id'  => $ownerId,
            'address'   => 'Test street 123',
            'city'      => 'Test city',
            'type'      => 'apartment',
            'rooms'     => 3,
            'base_rent' => '250000.00',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newContract(int $propertyId, int $tenantId, int $ownerId, array $overrides = []): int
    {
        $this->db->table('contracts')->insert($overrides + [
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-06-01',
            'end_date'                  => '2028-05-31',
            'monthly_rent'              => '250000.00',
            'due_day'                   => 10,
            'commission_percentage'     => '10.00',
            'late_fee_daily_percentage' => '0.50',
        ]);

        return (int) $this->db->insertID();
    }

    private function newInstallment(int $contractId, int $number, string $period): int
    {
        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => $number,
            'period'      => $period,
            'due_date'    => $period,
            'base_amount' => '250000.00',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newPayment(int $installmentId, array $overrides = []): int
    {
        $this->db->table('payments')->insert($overrides + [
            'installment_id'   => $installmentId,
            'amount'           => '250000.00',
            'paid_on'          => '2026-07-10',
            'method'           => 'bank_transfer',
            'reference_number' => 'REF-1',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newSettlement(int $ownerId, int $contractId, array $overrides = []): int
    {
        $this->db->table('settlements')->insert($overrides + [
            'owner_id'              => $ownerId,
            'contract_id'           => $contractId,
            'period'                => '2026-07-01',
            'gross_amount'          => '250000.00',
            'commission_percentage' => '10.00',
            'commission_amount'     => '25000.00',
            'net_amount'            => '225000.00',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @return array{int, int, int} owner id, tenant id, property id
     */
    private function peopleAndProperty(): array
    {
        $ownerId  = $this->newUser('owner', 'owner@example.com', '10000001');
        $tenantId = $this->newUser('tenant', 'tenant@example.com', '10000002');

        return [$ownerId, $tenantId, $this->newProperty($ownerId)];
    }

    private function newContractWithPeople(): int
    {
        return $this->ownerAndContract()[1];
    }

    /**
     * @return array{int, int} owner id, contract id
     */
    private function ownerAndContract(): array
    {
        [$ownerId, $tenantId, $propertyId] = $this->peopleAndProperty();

        return [$ownerId, $this->newContract($propertyId, $tenantId, $ownerId)];
    }

    private function newInstallmentWithPeople(): int
    {
        return $this->newInstallment($this->newContractWithPeople(), 1, '2026-07-01');
    }
}
