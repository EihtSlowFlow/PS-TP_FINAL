<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\ContractModel;
use App\Models\InstallmentModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Acceptance tests for HU-23: tenant alerts for installments due within 5 days,
 * including boundary values (0, 1, 5, 6, -1 days), idempotency, payment dismissal,
 * and strict per-tenant isolation.
 *
 * @internal
 */
final class InstallmentDueSoonAlertTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-15';

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

    public function testPendingInstallmentsWithinFiveDaysShowBannerAndPersistSingleNotifications(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $contractId                 = $this->createContract($tenantId, 'Mitre 845');

        // Boundary values inside window: 0 days (today), 1 day (tomorrow), 5 days (upper bound).
        $dueTodayId    = $this->insertInstallment($contractId, '2026-11-01', '2026-11-15', InstallmentModel::STATUS_PENDING);
        $dueTomorrowId = $this->insertInstallment($contractId, '2026-12-01', '2026-11-16', InstallmentModel::STATUS_PENDING);
        $dueIn5DaysId  = $this->insertInstallment($contractId, '2027-01-01', '2026-11-20', InstallmentModel::STATUS_PENDING);

        $page = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');
        $page->assertStatus(200);
        $page->assertSeeElement('#due-soon-alerts');
        $page->assertSee('Próximo vencimiento', '#due-soon-alerts');
        $page->assertSee('Tenés 3 cuotas próximas a vencer', '#due-soon-alerts');
        $page->assertSee('Noviembre 2026', '#due-soon-alerts');
        $page->assertSee('15/11/2026', '#due-soon-alerts');
        $page->assertSee('Vence hoy', '#due-soon-alerts');
        $page->assertSee('Diciembre 2026', '#due-soon-alerts');
        $page->assertSee('16/11/2026', '#due-soon-alerts');
        $page->assertSee('Vence mañana', '#due-soon-alerts');
        $page->assertSee('Enero 2027', '#due-soon-alerts');
        $page->assertSee('20/11/2026', '#due-soon-alerts');
        $page->assertSee('Vence en 5 días', '#due-soon-alerts');
        $page->assertSee(site_url('inquilino/cuotas/' . $dueTodayId . '/informar-pago'));
        $page->assertSeeElement('#unread-notifications-badge');

        $notificationModel = model(NotificationModel::class);
        $rows              = $notificationModel->findForUser($tenantId, 'unread');
        $this->assertCount(3, $rows);

        $referenceIds = array_map(static fn (array $r): int => (int) $r['reference_id'], $rows);
        sort($referenceIds);
        $expectedIds = [$dueTodayId, $dueTomorrowId, $dueIn5DaysId];
        sort($expectedIds);
        $this->assertSame($expectedIds, $referenceIds);

        foreach ($rows as $row) {
            $this->assertSame(NotificationModel::TYPE_INSTALLMENT_DUE_SOON, $row['type']);
            $this->assertSame(NotificationModel::REF_INSTALLMENT, $row['reference_type']);
            $this->assertStringContainsString('Mitre 845', (string) $row['message']);
        }
    }

    public function testFarPaidUnderReviewAndClosedContractInstallmentsDoNotTriggerAlerts(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $activeContractId           = $this->createContract($tenantId, 'Belgrano 1250', ContractModel::STATUS_IN_FORCE);
        $closedContractId           = $this->createContract($tenantId, 'Cerrada 400', ContractModel::STATUS_FINISHED);

        // 6 days away (just outside the 5-day window), paid within window, under_review within window, closed contract.
        $this->insertInstallment($activeContractId, '2026-11-01', '2026-11-21', InstallmentModel::STATUS_PENDING);
        $this->insertInstallment($activeContractId, '2026-10-01', '2026-11-17', InstallmentModel::STATUS_PAID);
        $this->insertInstallment($activeContractId, '2026-09-01', '2026-11-18', InstallmentModel::STATUS_UNDER_REVIEW);
        $this->insertInstallment($closedContractId, '2026-11-01', '2026-11-17', InstallmentModel::STATUS_PENDING);

        $page = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');
        $page->assertStatus(200);
        $page->assertDontSeeElement('#due-soon-alerts');
        $page->assertDontSeeElement('#unread-notifications-badge');

        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantId));
    }

    public function testOverdueInstallmentIsShownAsOverdueWithLateFeeAndNeverAsDueSoon(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $contractId                 = $this->createContract($tenantId, 'San Martin 500');

        // Due yesterday (-1 day): starts pending in DB before refreshLateFees runs.
        $this->insertInstallment($contractId, '2026-11-01', '2026-11-14', InstallmentModel::STATUS_PENDING);

        // Visiting /notificaciones directly before refreshLateFees must still NOT create a due-soon alert.
        $notifPage = $this->withSession($tenantSession)->get('notificaciones');
        $notifPage->assertStatus(200);
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantId));

        // Visiting account status refreshes late fee, shows overdue with late fee, and no due-soon banner.
        $_SESSION = [];
        $page     = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');
        $page->assertStatus(200);
        $page->assertDontSeeElement('#due-soon-alerts');
        $page->assertSee('Vencida');
        $page->assertSee('1 día de atraso');
        $page->assertSee('$ 2.500'); // 500000 * 0.50% * 1 day
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantId));
    }

    public function testAlertIsPersistedOnlyOnceAndNotRecreatedAfterBeingMarkedRead(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $contractId                 = $this->createContract($tenantId, 'Rivadavia 310');
        $this->insertInstallment($contractId, '2026-11-01', '2026-11-18', InstallmentModel::STATUS_PENDING);

        // First visit creates 1 notification.
        $this->withSession($tenantSession)->get('inquilino/estado-cuenta')->assertStatus(200);
        $_SESSION = [];
        // Second and third visits do not duplicate it.
        $this->withSession($tenantSession)->get('notificaciones')->assertStatus(200);
        $_SESSION = [];
        $this->withSession($tenantSession)->get('inquilino/estado-cuenta')->assertStatus(200);

        $model = model(NotificationModel::class);
        $rows  = $model->findForUser($tenantId, 'all');
        $this->assertCount(1, $rows);
        $this->assertSame(1, $model->countUnreadForUser($tenantId));

        // Mark it as read via POST.
        $_SESSION = [];
        $csrfName = config('Security')->tokenName;
        $this->withSession($tenantSession)
            ->post('notificaciones/' . (int) $rows[0]['id'] . '/leer', [$csrfName => csrf_hash()])
            ->assertRedirectTo(site_url('notificaciones'));

        $this->assertSame(0, $model->countUnreadForUser($tenantId));

        // Revisiting account status or notifications must NOT recreate the notification.
        $_SESSION = [];
        $this->withSession($tenantSession)->get('inquilino/estado-cuenta')->assertStatus(200);
        $_SESSION = [];
        $this->withSession($tenantSession)->get('notificaciones')->assertStatus(200);

        $this->assertSame(1, $model->countAllForUser($tenantId));
        $this->assertSame(0, $model->countUnreadForUser($tenantId));
    }

    public function testAlertDisappearsOncePaymentIsReportedAndSurvivesRollback(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $contractId                 = $this->createContract($tenantId, 'Sarmiento 210');
        $installmentId              = $this->insertInstallment($contractId, '2026-11-01', '2026-11-18', InstallmentModel::STATUS_PENDING);

        // Initial visit creates the alert.
        $page = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');
        $page->assertSeeElement('#due-soon-alerts');
        $page->assertSeeElement('#unread-notifications-badge');
        $this->assertSame(1, model(NotificationModel::class)->countUnreadForUser($tenantId));

        // Failed payment submission (storage rollback) keeps the unread alert intact.
        $failed = model(PaymentModel::class)->registerTenantPayment(
            $installmentId,
            $tenantId,
            [
                'amount'           => '500000.00',
                'paid_on'          => self::TODAY,
                'method'           => PaymentModel::METHOD_BANK_TRANSFER,
                'reference_number' => 'TRF-FAIL',
            ],
            [
                'original_name' => 'comp.pdf',
                'stored_name'   => 'fail.pdf',
                'mime_type'     => 'application/pdf',
                'size_bytes'    => 512,
            ],
            static fn (): bool => false
        );
        $this->assertFalse($failed['ok']);
        $this->assertSame(1, model(NotificationModel::class)->countUnreadForUser($tenantId));

        // Successful payment submission dismisses the banner and marks the due-soon alert as read.
        $ok = model(PaymentModel::class)->registerTenantPayment(
            $installmentId,
            $tenantId,
            [
                'amount'           => '500000.00',
                'paid_on'          => self::TODAY,
                'method'           => PaymentModel::METHOD_BANK_TRANSFER,
                'reference_number' => 'TRF-OK',
            ],
            [
                'original_name' => 'comp.pdf',
                'stored_name'   => 'ok.pdf',
                'mime_type'     => 'application/pdf',
                'size_bytes'    => 512,
            ],
            static fn (): bool => true
        );
        $this->assertTrue($ok['ok']);

        $this->assertSame(0, model(NotificationModel::class)->countUnreadForUser($tenantId));
        $this->assertSame(1, model(NotificationModel::class)->countAllForUser($tenantId));

        $_SESSION = [];
        $after    = $this->withSession($tenantSession)->get('inquilino/estado-cuenta');
        $after->assertStatus(200);
        $after->assertDontSeeElement('#due-soon-alerts');
        $after->assertDontSeeElement('#unread-notifications-badge');
    }

    public function testTenantSeesOnlyOwnAlertsAndDirectNotificationsVisitSyncsImmediately(): void
    {
        [$tenantAId, $sessionA] = $this->createUserSession('tenant');
        [$tenantBId, $sessionB] = $this->createUserSession('tenant');

        $contractA = $this->createContract($tenantAId, 'Propiedad A 100');
        $this->insertInstallment($contractA, '2026-11-01', '2026-11-17', InstallmentModel::STATUS_PENDING);

        // Tenant B sees no alerts and gets no notifications.
        $pageB = $this->withSession($sessionB)->get('inquilino/estado-cuenta');
        $pageB->assertStatus(200);
        $pageB->assertDontSeeElement('#due-soon-alerts');
        $pageB->assertDontSee('Propiedad A 100');
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantBId));

        // Tenant A visits /notificaciones directly (without opening account status first): alert is synced immediately.
        $_SESSION = [];
        $notifA   = $this->withSession($sessionA)->get('notificaciones');
        $notifA->assertStatus(200);
        $notifA->assertSee('Cuota próxima a vencer', '#notification-list');
        $notifA->assertSee('Noviembre 2026', '#notification-list');
        $notifA->assertSee('17/11/2026', '#notification-list');
        $notifA->assertSee('Propiedad A 100', '#notification-list');
        $notifA->assertSeeElement('#unread-notifications-badge');
        $this->assertSame(1, model(NotificationModel::class)->countUnreadForUser($tenantAId));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName): array
    {
        $this->sequence++;
        $role  = $this->db->table('roles')->where('name', $roleName)->get()->getRowArray();
        $now   = Time::now()->toDateTimeString();
        $email = sprintf('%s.%d@hu23.example.test', $roleName, $this->sequence);

        $this->db->table('users')->insert([
            'role_id'         => (int) $role['id'],
            'first_name'      => ucfirst($roleName),
            'last_name'       => 'Hu23_' . $this->sequence,
            'document_number' => (string) (35000000 + $this->sequence),
            'email'           => $email,
            'phone'           => '2994111111',
            'address'         => 'Calle Prueba 123',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $id = (int) $this->db->insertID();

        return [$id, [
            'is_logged_in' => true,
            'user_id'      => $id,
            'user_role'    => $roleName,
            'user_name'    => ucfirst($roleName) . ' Hu23_' . $this->sequence,
            'user_email'   => $email,
        ]];
    }

    private function createContract(
        int $tenantId,
        string $address,
        string $status = ContractModel::STATUS_IN_FORCE
    ): int {
        [$ownerId] = $this->createUserSession('owner');
        $now       = Time::now()->toDateTimeString();

        $this->db->table('properties')->insert([
            'owner_id'        => $ownerId,
            'address'         => $address,
            'city'            => 'Neuquen',
            'type'            => 'apartment',
            'rooms'           => 2,
            'base_rent'       => '500000.00',
            'description'     => 'Inmueble de prueba HU-23',
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
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '10.00',
            'due_day'                   => 15,
            'commission_percentage'     => '8.00',
            'late_fee_daily_percentage' => '0.50',
            'status'                    => $status,
            'created_at'                => $now,
            'updated_at'                => $now,
        ];

        if ($status !== ContractModel::STATUS_IN_FORCE) {
            $contractData['closed_at']      = self::TODAY;
            $contractData['closing_reason'] = 'Finalización de prueba';
        }

        $this->db->table('contracts')->insert($contractData);

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate, string $status): int
    {
        $this->sequence++;
        $now = Time::now()->toDateTimeString();

        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => $this->sequence,
            'period'      => $period,
            'due_date'    => $dueDate,
            'base_amount' => '500000.00',
            'late_fee'    => '0.00',
            'status'      => $status,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return (int) $this->db->insertID();
    }
}

