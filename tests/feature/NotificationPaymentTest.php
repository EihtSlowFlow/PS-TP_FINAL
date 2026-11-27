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
 * Acceptance tests for HU-22: in-app payment notifications, unread counter,
 * mark-as-read transitions, and strict per-user isolation.
 *
 * @internal
 */
final class NotificationPaymentTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-27';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 15:45:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    public function testReportingPaymentNotifiesAllActiveStaffAndIncrementsUnreadCounter(): void
    {
        [$adminId, $adminSession]       = $this->createUserSession('admin', 'Laura', 'Benitez');
        [$employeeId, $employeeSession] = $this->createUserSession('employee', 'Martin', 'Lopez');
        [$deletedAdminId]               = $this->createUserSession('admin', 'Ex', 'Admin', true);
        [$ownerId, $ownerSession]       = $this->createUserSession('owner', 'Carlos', 'Gomez');
        [$tenantId, $tenantSession]     = $this->createUserSession('tenant', 'Camila', 'Rojas');

        $contractId    = $this->createContract($ownerId, $tenantId, 'Belgrano 1250, 4B');
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PENDING);

        $result = model(PaymentModel::class)->registerTenantPayment(
            $installmentId,
            $tenantId,
            [
                'amount'           => '380000.00',
                'paid_on'          => self::TODAY,
                'method'           => PaymentModel::METHOD_BANK_TRANSFER,
                'reference_number' => 'TRF-8821',
            ],
            [
                'original_name' => 'comprobante.pdf',
                'stored_name'   => 'stored-test-8821.pdf',
                'mime_type'     => 'application/pdf',
                'size_bytes'    => 1024,
            ],
            static fn (): bool => true
        );

        $this->assertTrue($result['ok']);
        $paymentId = (int) $result['payment_id'];

        $notificationModel = model(NotificationModel::class);

        // Both active admin and active employee receive one unread payment_submitted notification.
        foreach ([$adminId, $employeeId] as $staffId) {
            $rows = $notificationModel->findForUser($staffId, 'unread');
            $this->assertCount(1, $rows);
            $this->assertSame(NotificationModel::TYPE_PAYMENT_SUBMITTED, $rows[0]['type']);
            $this->assertSame(NotificationModel::REF_PAYMENT, $rows[0]['reference_type']);
            $this->assertSame($paymentId, (int) $rows[0]['reference_id']);
            $this->assertStringContainsString('Camila Rojas', (string) $rows[0]['message']);
            $this->assertStringContainsString('Noviembre 2026', (string) $rows[0]['message']);
            $this->assertStringContainsString('Belgrano 1250, 4B', (string) $rows[0]['message']);
        }

        // Deactivated staff, owner, and tenant must not receive payment_submitted.
        $this->assertSame(0, $notificationModel->countAllForUser($deletedAdminId));
        $this->assertSame(0, $notificationModel->countAllForUser($ownerId));
        $this->assertSame(0, $notificationModel->countAllForUser($tenantId));

        // Topbar badge reflects the unread counter for admin and employee, not for owner/tenant.
        foreach ([$adminSession, $employeeSession] as $staffSession) {
            $_SESSION = [];
            $page     = $this->withSession($staffSession)->get('admin/dashboard');
            $page->assertStatus(200);
            $page->assertSeeElement('#unread-notifications-badge');
            $page->assertSee('Camila Rojas');
        }

        $_SESSION = [];
        $this->withSession($ownerSession)->get('propietario/dashboard')->assertDontSeeElement('#unread-notifications-badge');

        $_SESSION = [];
        $this->withSession($tenantSession)->get('inquilino/estado-cuenta')->assertDontSeeElement('#unread-notifications-badge');
    }

    public function testRolledBackPaymentCreatesNoNotifications(): void
    {
        [$adminId]  = $this->createUserSession('admin');
        [$ownerId]  = $this->createUserSession('owner');
        [$tenantId] = $this->createUserSession('tenant');

        $contractId    = $this->createContract($ownerId, $tenantId, 'Mitre 845');
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_PENDING);

        $result = model(PaymentModel::class)->registerTenantPayment(
            $installmentId,
            $tenantId,
            [
                'amount'           => '380000.00',
                'paid_on'          => self::TODAY,
                'method'           => PaymentModel::METHOD_BANK_TRANSFER,
                'reference_number' => 'TRF-FAIL',
            ],
            [
                'original_name' => 'comprobante.pdf',
                'stored_name'   => 'stored-fail.pdf',
                'mime_type'     => 'application/pdf',
                'size_bytes'    => 1024,
            ],
            static fn (): bool => false
        );

        $this->assertFalse($result['ok']);
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($adminId));
    }

    public function testApprovingPaymentNotifiesOwningTenant(): void
    {
        [$adminId, $adminSession]   = $this->createUserSession('admin');
        [$ownerId]                  = $this->createUserSession('owner');
        [$tenantId, $tenantSession] = $this->createUserSession('tenant', 'Camila', 'Rojas');
        [$otherTenantId]            = $this->createUserSession('tenant', 'Otro', 'Inquilino');

        $contractId    = $this->createContract($ownerId, $tenantId, 'San Martin 500');
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_UNDER_REVIEW);
        $paymentId     = $this->insertPayment($installmentId, PaymentModel::STATUS_PENDING_VALIDATION);

        $response = $this->postAs($adminSession, 'admin/pagos/' . $paymentId . '/aprobacion');
        $response->assertRedirectTo(site_url('admin/pagos'));

        $rows = model(NotificationModel::class)->findForUser($tenantId, 'unread');
        $this->assertCount(1, $rows);
        $this->assertSame(NotificationModel::TYPE_PAYMENT_APPROVED, $rows[0]['type']);
        $this->assertSame(NotificationModel::REF_PAYMENT, $rows[0]['reference_type']);
        $this->assertSame($paymentId, (int) $rows[0]['reference_id']);
        $this->assertStringContainsString('Noviembre 2026', (string) $rows[0]['message']);
        $this->assertStringContainsString('San Martin 500', (string) $rows[0]['message']);
        $this->assertStringContainsString('aprobado', (string) $rows[0]['message']);

        // Other tenant is unaffected.
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($otherTenantId));

        $_SESSION = [];
        $page     = $this->withSession($tenantSession)->get('notificaciones');
        $page->assertStatus(200);
        $page->assertSee('Pago aprobado');
        $page->assertSee('San Martin 500');
        $page->assertSeeElement('#unread-notifications-badge');
    }

    public function testRejectingPaymentNotifiesTenantWithReasonAndResubmitHint(): void
    {
        [$adminId, $adminSession]   = $this->createUserSession('admin');
        [$ownerId]                  = $this->createUserSession('owner');
        [$tenantId, $tenantSession] = $this->createUserSession('tenant', 'Camila', 'Rojas');

        $contractId    = $this->createContract($ownerId, $tenantId, 'Sarmiento 210');
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', InstallmentModel::STATUS_UNDER_REVIEW);
        $paymentId     = $this->insertPayment($installmentId, PaymentModel::STATUS_PENDING_VALIDATION);

        // Missing reason fails and creates no notification.
        $failed = $this->postAs($adminSession, 'admin/pagos/' . $paymentId . '/rechazo', ['rejection_reason' => '   ']);
        $failed->assertRedirectTo(site_url('admin/pagos/' . $paymentId));
        $this->assertSame(0, model(NotificationModel::class)->countAllForUser($tenantId));

        // Valid rejection creates the payment_rejected notification with the reason.
        $_SESSION = [];
        $response = $this->postAs($adminSession, 'admin/pagos/' . $paymentId . '/rechazo', [
            'rejection_reason' => 'Comprobante borroso e ilegible',
        ]);
        $response->assertRedirectTo(site_url('admin/pagos'));

        $rows = model(NotificationModel::class)->findForUser($tenantId, 'unread');
        $this->assertCount(1, $rows);
        $this->assertSame(NotificationModel::TYPE_PAYMENT_REJECTED, $rows[0]['type']);
        $this->assertSame($paymentId, (int) $rows[0]['reference_id']);
        $this->assertStringContainsString('Comprobante borroso e ilegible', (string) $rows[0]['message']);
        $this->assertStringContainsString('reenviar el comprobante', (string) $rows[0]['message']);

        $_SESSION = [];
        $page     = $this->withSession($tenantSession)->get('notificaciones');
        $page->assertStatus(200);
        $page->assertSee('Pago rechazado');
        $page->assertSee('Comprobante borroso e ilegible');
        $page->assertSee('Reenviar comprobante');
    }

    public function testMarkAsReadOpenTargetAndMarkAllAsReadUpdateCounterAndPreserveHistory(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [$otherId]                  = $this->createUserSession('tenant');

        $service = service('notifications');
        $service->notifyPaymentApproved($tenantId, 10, 'Octubre 2026', 'Mitre 100');
        $service->notifyPaymentRejected($tenantId, 11, 'Noviembre 2026', 'Mitre 100', 'Monto insuficiente');
        $service->notifyPaymentApproved($tenantId, 12, 'Diciembre 2026', 'Mitre 100');
        $service->notifyPaymentApproved($otherId, 99, 'Diciembre 2026', 'Otra 999');

        $model = model(NotificationModel::class);
        $all   = $model->findForUser($tenantId, 'all');
        $this->assertCount(3, $all);
        $this->assertSame(3, $model->countUnreadForUser($tenantId));

        $firstId  = (int) $all[0]['id'];
        $secondId = (int) $all[1]['id'];

        // 1) Mark single notification as read without redirecting to target.
        $res1 = $this->postAs($tenantSession, 'notificaciones/' . $firstId . '/leer');
        $res1->assertRedirectTo(site_url('notificaciones'));
        $this->assertSame(2, $model->countUnreadForUser($tenantId));
        $this->assertSame(3, $model->countAllForUser($tenantId));

        // 2) Open notification with open_target=1 -> marks as read and redirects to target screen.
        $_SESSION = [];
        $res2     = $this->postAs($tenantSession, 'notificaciones/' . $secondId . '/leer', ['open_target' => '1']);
        $res2->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $this->assertSame(1, $model->countUnreadForUser($tenantId));

        // 3) Filter unread shows only the remaining unread item; filter read shows the 2 read items.
        // Scope assertions to #notification-list because the topbar bell popover always lists the 5 most recent items.
        $_SESSION = [];
        $unreadPage = $this->withSession($tenantSession)->get('notificaciones?estado=unread');
        $unreadPage->assertStatus(200);
        $unreadPage->assertSee('Octubre 2026', '#notification-list');
        $unreadPage->assertDontSee('Diciembre 2026', '#notification-list');

        $_SESSION = [];
        $readPage = $this->withSession($tenantSession)->get('notificaciones?estado=read');
        $readPage->assertStatus(200);
        $readPage->assertSee('Diciembre 2026', '#notification-list');
        $readPage->assertSee('Noviembre 2026', '#notification-list');
        $readPage->assertDontSee('Octubre 2026', '#notification-list');

        // 4) Mark all as read clears only the current user's unread count; other user stays untouched.
        $_SESSION = [];
        $resAll   = $this->postAs($tenantSession, 'notificaciones/leer-todas');
        $resAll->assertRedirectTo(site_url('notificaciones'));
        $this->assertSame(0, $model->countUnreadForUser($tenantId));
        $this->assertSame(3, $model->countAllForUser($tenantId));
        $this->assertSame(1, $model->countUnreadForUser($otherId));

        // Badge disappears once unread count reaches 0.
        $_SESSION = [];
        $this->withSession($tenantSession)->get('notificaciones')->assertDontSeeElement('#unread-notifications-badge');
    }

    public function testPopoverActionsReturnToTheCurrentPageButNeverToAnExternalUrl(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        service('notifications')->notifyPaymentApproved($tenantId, 10, 'Octubre 2026', 'Mitre 100');
        $id = (int) model(NotificationModel::class)->findForUser($tenantId)[0]['id'];

        $internal = $this->postAs(
            $tenantSession + ['_ci_previous_url' => site_url('inquilino/mis-pagos')],
            'notificaciones/' . $id . '/leer',
            ['origin' => 'popover']
        );
        $internal->assertRedirectTo(site_url('inquilino/mis-pagos'));

        $_SESSION = [];
        $external = $this->postAs(
            $tenantSession + ['_ci_previous_url' => 'https://evil.example.com/phish'],
            'notificaciones/leer-todas',
            ['origin' => 'popover']
        );
        $external->assertRedirectTo(site_url('notificaciones'));
    }

    public function testUserIsolationAndGuestProtection(): void
    {
        [$tenantAId, $sessionA] = $this->createUserSession('tenant');
        [$tenantBId, $sessionB] = $this->createUserSession('tenant');

        service('notifications')->notifyPaymentApproved($tenantBId, 55, 'Noviembre 2026', 'Rivadavia 900');
        $bRows = model(NotificationModel::class)->findForUser($tenantBId);
        $bId   = (int) $bRows[0]['id'];

        // Guest cannot access notifications.
        $this->get('notificaciones')->assertRedirectTo(site_url('auth/login'));

        // User A does not see User B's notifications.
        $_SESSION = [];
        $pageA    = $this->withSession($sessionA)->get('notificaciones');
        $pageA->assertStatus(200);
        $pageA->assertDontSee('Rivadavia 900');

        // User A cannot mark User B's notification as read.
        $_SESSION = [];
        $attempt  = $this->postAs($sessionA, 'notificaciones/' . $bId . '/leer');
        $attempt->assertRedirectTo(site_url('notificaciones'));
        $attempt->assertSessionHas('error');

        // User B's notification remains unread.
        $this->assertSame(1, model(NotificationModel::class)->countUnreadForUser($tenantBId));

        // User B still sees it.
        $_SESSION = [];
        $pageB    = $this->withSession($sessionB)->get('notificaciones');
        $pageB->assertStatus(200);
        $pageB->assertSee('Rivadavia 900');
    }

    public function testExpandedNotificationTypesAndNotifyOnceWorkCleanly(): void
    {
        [$ownerId]  = $this->createUserSession('owner');
        [$tenantId] = $this->createUserSession('tenant');

        $service = service('notifications');

        $this->assertTrue($service->notifyPropertyApproved($ownerId, 42, 'Alem 320'));
        $this->assertTrue($service->notifyRentalRequestApproved($tenantId, 77, 'Alem 320'));

        // notifyOnce prevents duplicates even for the same reference.
        $this->assertTrue($service->notifyOnce(
            $tenantId,
            NotificationModel::TYPE_INSTALLMENT_DUE_SOON,
            'Tu cuota vence en 3 días.',
            NotificationModel::REF_INSTALLMENT,
            501
        ));
        $this->assertFalse($service->notifyOnce(
            $tenantId,
            NotificationModel::TYPE_INSTALLMENT_DUE_SOON,
            'Tu cuota vence en 3 días.',
            NotificationModel::REF_INSTALLMENT,
            501
        ));

        $this->assertSame(1, model(NotificationModel::class)->countAllForUser($ownerId));
        $this->assertSame(2, model(NotificationModel::class)->countAllForUser($tenantId));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $data
     */
    private function postAs(array $session, string $path, array $data = []): \CodeIgniter\Test\TestResponse
    {
        $csrfName = config('Security')->tokenName;
        $data[$csrfName] ??= csrf_hash();

        return $this->withSession($session)->post($path, $data);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(
        string $roleName,
        string $firstName = 'Usuario',
        string $lastName = 'Prueba',
        bool $softDeleted = false
    ): array {
        $this->sequence++;
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRowArray();
        $now  = Time::now()->toDateTimeString();

        $email = sprintf('%s.%d@example.test', $roleName, $this->sequence);
        $this->db->table('users')->insert([
            'role_id'         => (int) $role['id'],
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (30000000 + $this->sequence),
            'email'           => $email,
            'phone'           => '2994000000',
            'address'         => 'Calle Falsa 123',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $softDeleted ? $now : null,
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

    private function createContract(int $ownerId, int $tenantId, string $address): int
    {
        $this->sequence++;
        $now = Time::now()->toDateTimeString();

        $this->db->table('properties')->insert([
            'owner_id'        => $ownerId,
            'type'            => 'apartment',
            'address'         => $address,
            'city'            => 'Neuquen',
            'rooms'           => 2,
            'base_rent'       => '380000.00',
            'description'     => 'Inmueble de prueba',
            'status'          => 'rented',
            'approval_status' => 'approved',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'owner_id'                  => $ownerId,
            'tenant_id'                 => $tenantId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '380000.00',
            'commission_percentage'     => '10.00',
            'update_percentage'         => '15.00',
            'late_fee_daily_percentage' => '0.50',
            'due_day'                   => 10,
            'status'                    => ContractModel::STATUS_IN_FORCE,
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate, string $status): int
    {
        $now = Time::now()->toDateTimeString();

        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => $period,
            'base_amount' => '380000.00',
            'late_fee'    => '0.00',
            'due_date'    => $dueDate,
            'status'      => $status,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function insertPayment(int $installmentId, string $status): int
    {
        $now = Time::now()->toDateTimeString();

        $this->db->table('payments')->insert([
            'installment_id'   => $installmentId,
            'amount'           => '380000.00',
            'paid_on'          => self::TODAY,
            'method'           => PaymentModel::METHOD_BANK_TRANSFER,
            'reference_number' => 'TRF-500',
            'status'           => $status,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        return (int) $this->db->insertID();
    }
}

