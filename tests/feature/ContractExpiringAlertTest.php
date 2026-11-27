<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\ContractModel;
use App\Models\NotificationModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance tests for HU-24: expiring contract alerts (60-day window) for
 * administrators, employees, and owners, including boundary values (-1, 0, 1, 60, 61 days),
 * owner scope isolation, idempotency, and dismissal on contract closure.
 *
 * @internal
 */
final class ContractExpiringAlertTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-01';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    public function testInForceContractsWithinSixtyDaysAlertStaffAndOwnerWithDirectLinks(): void
    {
        [$adminId, $adminSession]       = $this->createUserSession('admin');
        [$employeeId, $employeeSession] = $this->createUserSession('employee');
        [$ownerId, $ownerSession]       = $this->createUserSession('owner');
        [$tenantId]                     = $this->createUserSession('tenant', 'Camila', 'Rojas');

        // Boundary values inside the 60-day window (TODAY = 2026-11-01):
        // 0 days (2026-11-01), 1 day (2026-11-02), and 60 days (2026-12-31).
        $todayId    = $this->createContract($ownerId, $tenantId, 'Mitre 100', '2026-11-01');
        $tomorrowId = $this->createContract($ownerId, $tenantId, 'Belgrano 200', '2026-11-02');
        $day60Id    = $this->createContract($ownerId, $tenantId, 'Sarmiento 300', '2026-12-31');

        // Admin dashboard displays the alert banner with property, tenant, end date, countdown, and admin link.
        $adminDash = $this->withSession($adminSession)->get('admin/dashboard');
        $adminDash->assertStatus(200);
        $adminDash->assertSeeElement('#expiring-contracts-alerts');
        $adminDash->assertSee('Hay 3 contratos que vencen en los próximos 60 días', '#expiring-contracts-alerts');
        $adminDash->assertSee('Mitre 100', '#expiring-contracts-alerts');
        $adminDash->assertSee('Camila Rojas', '#expiring-contracts-alerts');
        $adminDash->assertSee('01/11/2026', '#expiring-contracts-alerts');
        $adminDash->assertSee('Vence hoy', '#expiring-contracts-alerts');
        $adminDash->assertSee('Belgrano 200', '#expiring-contracts-alerts');
        $adminDash->assertSee('02/11/2026', '#expiring-contracts-alerts');
        $adminDash->assertSee('Vence mañana', '#expiring-contracts-alerts');
        $adminDash->assertSee('Sarmiento 300', '#expiring-contracts-alerts');
        $adminDash->assertSee('31/12/2026', '#expiring-contracts-alerts');
        $adminDash->assertSee('Vence en 60 días', '#expiring-contracts-alerts');
        $adminDash->assertSee(site_url('admin/contratos/' . $todayId));
        $adminDash->assertSee(site_url('admin/contratos/' . $tomorrowId));
        $adminDash->assertSee(site_url('admin/contratos/' . $day60Id));
        $adminDash->assertSeeElement('#unread-notifications-badge');

        // Employee also receives their own notifications.
        $_SESSION = [];
        $empDash  = $this->withSession($employeeSession)->get('admin/dashboard');
        $empDash->assertStatus(200);
        $empDash->assertSeeElement('#expiring-contracts-alerts');
        $empDash->assertSeeElement('#unread-notifications-badge');

        // Owner dashboard shows the alert banner with owner contract links.
        $_SESSION  = [];
        $ownerDash = $this->withSession($ownerSession)->get('propietario/dashboard');
        $ownerDash->assertStatus(200);
        $ownerDash->assertSeeElement('#expiring-contracts-alerts');
        $ownerDash->assertSee('Tenés 3 contratos que vencen en los próximos 60 días', '#expiring-contracts-alerts');
        $ownerDash->assertSee(site_url('propietario/contratos/' . $todayId));
        $ownerDash->assertSee(site_url('propietario/contratos/' . $day60Id));
        $ownerDash->assertSeeElement('#unread-notifications-badge');

        $notificationModel = model(NotificationModel::class);
        $expectedIds       = [$todayId, $tomorrowId, $day60Id];
        sort($expectedIds);

        foreach ([$adminId, $employeeId, $ownerId] as $recipientId) {
            $rows = $notificationModel->findForUser($recipientId, 'unread');
            $this->assertCount(3, $rows);

            $refIds = array_map(static fn (array $r): int => (int) $r['reference_id'], $rows);
            sort($refIds);
            $this->assertSame($expectedIds, $refIds);

            foreach ($rows as $row) {
                $this->assertSame(NotificationModel::TYPE_CONTRACT_EXPIRING, $row['type']);
                $this->assertSame(NotificationModel::REF_CONTRACT, $row['reference_type']);
                $this->assertStringContainsString('Camila Rojas', (string) $row['message']);
            }
        }

        // Opening an expiring-contract notification with open_target=1 marks it read and redirects to the contract file.
        $adminFirstNotif = $notificationModel->findForUser($adminId, 'unread')[0];
        $_SESSION        = [];
        $csrfName        = config('Security')->tokenName;
        $openAdmin       = $this->withSession($adminSession)->post(
            'notificaciones/' . (int) $adminFirstNotif['id'] . '/leer',
            [$csrfName => csrf_hash(), 'open_target' => '1']
        );
        $openAdmin->assertRedirectTo(site_url('admin/contratos/' . (int) $adminFirstNotif['reference_id']));
        $this->assertSame(2, $notificationModel->countUnreadForUser($adminId));

        $ownerFirstNotif = $notificationModel->findForUser($ownerId, 'unread')[0];
        $_SESSION        = [];
        $openOwner       = $this->withSession($ownerSession)->post(
            'notificaciones/' . (int) $ownerFirstNotif['id'] . '/leer',
            [$csrfName => csrf_hash(), 'open_target' => '1']
        );
        $openOwner->assertRedirectTo(site_url('propietario/contratos/' . (int) $ownerFirstNotif['reference_id']));
        $this->assertSame(2, $notificationModel->countUnreadForUser($ownerId));
    }

    public function testContractsOutsideSixtyDaysPastEndOrNotInForceTriggerNoAlerts(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$ownerId, $ownerSession] = $this->createUserSession('owner');
        [$tenantId]               = $this->createUserSession('tenant');

        // 61 days away (2027-01-01), past end date (-1 day: 2026-10-31), finished within window, rescinded within window.
        $this->createContract($ownerId, $tenantId, 'Lejana 61', '2027-01-01', ContractModel::STATUS_IN_FORCE);
        $this->createContract($ownerId, $tenantId, 'Pasada -1', '2026-10-31', ContractModel::STATUS_IN_FORCE);
        $this->createContract($ownerId, $tenantId, 'Finalizada 15', '2026-11-16', ContractModel::STATUS_FINISHED);
        $this->createContract($ownerId, $tenantId, 'Rescindida 15', '2026-11-16', ContractModel::STATUS_RESCINDED);

        $adminDash = $this->withSession($adminSession)->get('admin/dashboard');
        $adminDash->assertStatus(200);
        $adminDash->assertDontSeeElement('#expiring-contracts-alerts');
        $adminDash->assertDontSeeElement('#unread-notifications-badge');

        $_SESSION  = [];
        $ownerDash = $this->withSession($ownerSession)->get('propietario/dashboard');
        $ownerDash->assertStatus(200);
        $ownerDash->assertDontSeeElement('#expiring-contracts-alerts');
        $ownerDash->assertDontSeeElement('#unread-notifications-badge');

        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($adminId));
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($ownerId));
    }

    public function testOwnerSeesOnlyOwnExpiringContractsAndTenantReceivesNone(): void
    {
        [$ownerAId, $ownerASession] = $this->createUserSession('owner');
        [$ownerBId, $ownerBSession] = $this->createUserSession('owner');
        [$tenantId, $tenantSession] = $this->createUserSession('tenant', 'Lucas', 'Perez');

        $contractAId = $this->createContract($ownerAId, $tenantId, 'Avenida A 101', '2026-12-01');
        $this->createContract($ownerBId, $tenantId, 'Avenida B 202', '2027-06-01');

        // Owner B sees no expiring alert and gets 0 notifications.
        $dashB = $this->withSession($ownerBSession)->get('propietario/dashboard');
        $dashB->assertStatus(200);
        $dashB->assertDontSeeElement('#expiring-contracts-alerts');
        $dashB->assertDontSee('Avenida A 101');

        $_SESSION = [];
        $listB    = $this->withSession($ownerBSession)->get('propietario/contratos');
        $listB->assertStatus(200);
        $listB->assertDontSeeElement('#expiring-contracts-alerts');
        $listB->assertDontSee('Avenida A 101');
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($ownerBId));

        // Owner A sees only their own expiring contract.
        $_SESSION = [];
        $listA    = $this->withSession($ownerASession)->get('propietario/contratos');
        $listA->assertStatus(200);
        $listA->assertSeeElement('#expiring-contracts-alerts');
        $listA->assertSee('Avenida A 101', '#expiring-contracts-alerts');
        $listA->assertSee('Lucas Perez', '#expiring-contracts-alerts');
        $listA->assertSee(site_url('propietario/contratos/' . $contractAId));
        $listA->assertDontSee('Avenida B 202', '#expiring-contracts-alerts');
        $this->assertSame(1, model(NotificationModel::class)->countUnreadForUser($ownerAId));

        // Tenant receives no contract_expiring notifications.
        $_SESSION   = [];
        $tenantPage = $this->withSession($tenantSession)->get('notificaciones');
        $tenantPage->assertStatus(200);
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantId));
    }

    public function testAlertIsPersistedOncePerRecipientAndNotRecreatedAfterRead(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$ownerId, $ownerSession] = $this->createUserSession('owner');
        [$tenantId]               = $this->createUserSession('tenant');

        $this->createContract($ownerId, $tenantId, 'Rivadavia 550', '2026-12-15');

        // Repeated visits across dashboard, contracts list, and notifications center do not duplicate.
        $this->withSession($adminSession)->get('admin/dashboard')->assertStatus(200);
        $_SESSION = [];
        $this->withSession($adminSession)->get('admin/contratos')->assertStatus(200);
        $_SESSION = [];
        $this->withSession($adminSession)->get('notificaciones')->assertStatus(200);

        $model = model(NotificationModel::class);
        $rows  = $model->findForUser($adminId, 'all');
        $this->assertCount(1, $rows);
        $this->assertSame(1, $model->countUnreadForUser($adminId));

        // Marking as read keeps it read on subsequent visits.
        $_SESSION = [];
        $csrfName = config('Security')->tokenName;
        $this->withSession($adminSession)
            ->post('notificaciones/' . (int) $rows[0]['id'] . '/leer', [$csrfName => csrf_hash()])
            ->assertRedirectTo(site_url('notificaciones'));

        $_SESSION = [];
        $this->withSession($adminSession)->get('admin/dashboard')->assertStatus(200);
        $_SESSION = [];
        $this->withSession($adminSession)->get('notificaciones')->assertStatus(200);

        $this->assertSame(1, $model->countAllForUser($adminId));
        $this->assertSame(0, $model->countUnreadForUser($adminId));

        // Direct visit to /notificaciones by owner syncs immediately on first load.
        $_SESSION   = [];
        $ownerNotif = $this->withSession($ownerSession)->get('notificaciones');
        $ownerNotif->assertStatus(200);
        $ownerNotif->assertSee('Contrato por vencer', '#notification-list');
        $ownerNotif->assertSee('Rivadavia 550', '#notification-list');
        $ownerNotif->assertSee('15/12/2026', '#notification-list');
        $this->assertSame(1, $model->countUnreadForUser($ownerId));
    }

    public function testClosingContractDismissesUnreadAlertsAndRemovesBanner(): void
    {
        [$adminId, $adminSession] = $this->createUserSession('admin');
        [$ownerId, $ownerSession] = $this->createUserSession('owner');
        [$tenantId]               = $this->createUserSession('tenant');

        $contractId = $this->createContract($ownerId, $tenantId, 'San Martin 900', '2026-12-10');

        // Trigger initial alerts for both admin and owner.
        $this->withSession($adminSession)->get('admin/dashboard')->assertSeeElement('#expiring-contracts-alerts');
        $_SESSION = [];
        $this->withSession($ownerSession)->get('propietario/dashboard')->assertSeeElement('#expiring-contracts-alerts');

        $model = model(NotificationModel::class);
        $this->assertSame(1, $model->countUnreadForUser($adminId));
        $this->assertSame(1, $model->countUnreadForUser($ownerId));

        // Closing the contract marks both unread alerts as read and removes the banner.
        $closed = model(ContractModel::class)->closeContract($contractId, ContractModel::STATUS_FINISHED, 'Entrega de llaves');
        $this->assertTrue($closed['success']);

        $this->assertSame(0, $model->countUnreadForUser($adminId));
        $this->assertSame(0, $model->countUnreadForUser($ownerId));
        $this->assertSame(1, $model->countAllForUser($adminId));
        $this->assertSame(1, $model->countAllForUser($ownerId));

        $_SESSION = [];
        $this->withSession($adminSession)->get('admin/dashboard')->assertDontSeeElement('#expiring-contracts-alerts');
        $_SESSION = [];
        $this->withSession($ownerSession)->get('propietario/dashboard')->assertDontSeeElement('#expiring-contracts-alerts');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(
        string $roleName,
        string $firstName = 'Usuario',
        string $lastName = 'Hu24'
    ): array {
        $this->sequence++;
        $role  = $this->db->table('roles')->where('name', $roleName)->get()->getRowArray();
        $now   = Time::now()->toDateTimeString();
        $email = sprintf('%s.%d@hu24.example.test', $roleName, $this->sequence);

        $this->db->table('users')->insert([
            'role_id'         => (int) $role['id'],
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (36000000 + $this->sequence),
            'email'           => $email,
            'phone'           => '2994222222',
            'address'         => 'Calle Prueba 456',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $id = (int) $this->db->insertID();

        return [$id, [
            'is_logged_in' => true,
            'user_id'      => $id,
            'user_role'    => $roleName,
            'user_name'    => trim($firstName . ' ' . $lastName),
            'user_email'   => $email,
        ]];
    }

    private function createContract(
        int $ownerId,
        int $tenantId,
        string $address,
        string $endDate,
        string $status = ContractModel::STATUS_IN_FORCE
    ): int {
        $now = Time::now()->toDateTimeString();

        $this->db->table('properties')->insert([
            'owner_id'        => $ownerId,
            'address'         => $address,
            'city'            => 'Neuquen',
            'type'            => 'apartment',
            'rooms'           => 2,
            'base_rent'       => '500000.00',
            'description'     => 'Inmueble de prueba HU-24',
            'status'          => $status === ContractModel::STATUS_IN_FORCE ? 'rented' : 'available',
            'approval_status' => 'approved',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $propertyId = (int) $this->db->insertID();

        $contractData = [
            'property_id'               => $propertyId,
            'owner_id'                  => $ownerId,
            'tenant_id'                 => $tenantId,
            'start_date'                => '2025-01-01',
            'end_date'                  => $endDate,
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '10.00',
            'due_day'                   => 10,
            'commission_percentage'     => '8.00',
            'late_fee_daily_percentage' => '0.50',
            'status'                    => $status,
            'created_at'                => $now,
            'updated_at'                => $now,
        ];

        if ($status !== ContractModel::STATUS_IN_FORCE) {
            $contractData['closed_at']      = self::TODAY;
            $contractData['closing_reason'] = 'Cierre de prueba';
        }

        $this->db->table('contracts')->insert($contractData);

        return (int) $this->db->insertID();
    }
}

