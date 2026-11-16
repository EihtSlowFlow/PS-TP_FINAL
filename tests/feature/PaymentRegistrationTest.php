<?php

use App\Database\Seeds\RoleSeeder;
use App\Models\ContractModel;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Libraries\TestReceiptStorage;

/**
 * Automated acceptance, boundary, atomicity and security tests for HU-16:
 * tenant payment registration and digital receipt upload.
 *
 * @internal
 */
final class PaymentRegistrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-13';

    private int $sequence = 0;

    private TestReceiptStorage $storage;

    /** @var list<string> */
    private array $tempFiles = [];

    /** @var list<string> */
    private array $storedReceipts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION             = [];
        $_FILES               = [];
        service('superglobals')->setFilesArray([]);
        $this->sequence       = 0;
        $this->tempFiles      = [];
        $this->storedReceipts = [];
        $this->storage        = new TestReceiptStorage();
        Services::injectMock('receiptStorage', $this->storage);
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        Services::resetSingle('receiptStorage');
        $_SESSION = [];
        $_FILES   = [];
        service('superglobals')->setFilesArray([]);

        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        foreach ($this->storedReceipts as $stored) {
            $this->storage->delete($stored);
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: Valid payment registration
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function validPaymentProvider(): array
    {
        return [
            'bank_transfer_pdf'   => ['bank_transfer', 'pdf', 'comprobante.pdf', 'application/pdf'],
            'deposit_jpg'         => ['deposit', 'jpg', 'ticket.jpg', 'image/jpeg'],
            'digital_gateway_png' => ['digital_gateway', 'png', 'captura.png', 'image/png'],
        ];
    }

    #[DataProvider('validPaymentProvider')]
    public function testScenario1RegistersValidPaymentAndMovesInstallmentToUnderReview(
        string $method,
        string $type,
        string $clientName,
        string $expectedMime
    ): void {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $form = $this->withSession($session)->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $form->assertStatus(200);
        $form->assertSee('Informar pago');

        $this->attachReceipt($type, $clientName, $expectedMime);

        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => $method,
            'reference_number' => 'TRF-99482104',
        ]);

        $response->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $response->assertSessionHas('success');

        $installment = $this->fetchInstallment($installmentId);
        $this->assertSame(InstallmentModel::STATUS_UNDER_REVIEW, $installment['status']);

        $payment = $this->fetchLatestPayment($installmentId);
        $this->assertNotNull($payment);
        $this->assertSame(PaymentModel::STATUS_PENDING_VALIDATION, $payment['status']);
        $this->assertSame('500000.00', $payment['amount']);
        $this->assertSame(self::TODAY, $payment['paid_on']);
        $this->assertSame($method, $payment['method']);
        $this->assertSame('TRF-99482104', $payment['reference_number']);
        $this->assertNull($payment['resolved_by']);
        $this->assertNull($payment['resolved_at']);

        $receipt = $this->fetchReceiptForPayment((int) $payment['id']);
        $this->assertNotNull($receipt);
        $this->storedReceipts[] = (string) $receipt['stored_name'];

        $this->assertSame($clientName, $receipt['original_name']);
        $this->assertSame($expectedMime, $receipt['mime_type']);
        $this->assertNotSame($clientName, $receipt['stored_name']);
        $this->assertFileExists($this->storage->path((string) $receipt['stored_name']));
        $this->assertFileDoesNotExist(FCPATH . 'uploads/comprobantes/' . $receipt['stored_name']);
    }

    public function testScenario1RegistersPaymentOnOverdueInstallmentAndNotesAmountDifference(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        // Due 2026-11-10, TODAY is 2026-11-13 -> 3 days late = 7,500 late fee, total 507,500.
        $installmentId = $this->insertInstallment($contractId, '2026-11-01', '2026-11-10', 'pending');

        $form = $this->withSession($session)->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $form->assertStatus(200);
        $form->assertSee('3 días de atraso');
        $form->assertSee('$ 507.500');

        $this->attachReceipt('pdf', 'pago_parcial.pdf', 'application/pdf');

        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-111222',
        ]);

        $response->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $this->assertStringContainsString('diferencia respecto al monto exigible', (string) session()->getFlashdata('success'));

        $payment = $this->fetchLatestPayment($installmentId);
        $this->assertNotNull($payment);
        $this->assertSame('500000.00', $payment['amount']);
        $receipt = $this->fetchReceiptForPayment((int) $payment['id']);
        $this->assertNotNull($receipt);
        $this->storedReceipts[] = (string) $receipt['stored_name'];

        $installment = $this->fetchInstallment($installmentId);
        $this->assertSame(InstallmentModel::STATUS_UNDER_REVIEW, $installment['status']);
        $this->assertSame('7500.00', $installment['late_fee']);
    }

    public function testScenario1AcceptsCommaDecimalAndExactFiveMbAndPngWithPdfExtension(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        // Exact 5 MB PNG bytes disguised with .pdf filename -> server uses actual PNG type and .png extension.
        $pngHeader = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde";
        $exact5Mb  = $pngHeader . str_repeat('0', 5242880 - strlen($pngHeader));
        $this->attachRawFile($exact5Mb, 'disguised.pdf', 'application/pdf');

        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '250000,50',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-EXACT-5MB',
        ]);

        $response->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $payment = $this->fetchLatestPayment($installmentId);
        $this->assertNotNull($payment);
        $this->assertSame('250000.50', $payment['amount']);

        $receipt = $this->fetchReceiptForPayment((int) $payment['id']);
        $this->assertNotNull($receipt);
        $this->storedReceipts[] = (string) $receipt['stored_name'];
        $this->assertSame('image/png', $receipt['mime_type']);
        $this->assertStringEndsWith('.png', (string) $receipt['stored_name']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2 & 3: File validation ("without storing anything")
    // ---------------------------------------------------------------------------------------------

    public function testScenario2RejectsSpoofedFileAndStoresNothing(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $filesBefore   = $this->listStoredFiles();
        $receiptsCount = $this->db->table('payment_receipts')->countAllResults();

        // Client claims it is image/jpeg and names it .jpg, but contents are PHP code.
        $this->attachRawFile("<?php phpinfo(); ?>", 'malicious.jpg', 'image/jpeg');

        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-999',
        ]);

        $response->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('receipt', $errors);
        $this->assertStringContainsString('archivo válido', $errors['receipt']);

        $this->assertNull($this->fetchLatestPayment($installmentId));
        $this->assertSame($receiptsCount, $this->db->table('payment_receipts')->countAllResults());
        $this->assertSame($filesBefore, $this->listStoredFiles());
        $this->assertSame(InstallmentModel::STATUS_PENDING, $this->fetchInstallment($installmentId)['status']);
    }

    public function testScenario2RejectsEmptyFileAndGifFile(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $this->attachRawFile('', 'vacio.pdf', 'application/pdf');
        $emptyResp = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-EMPTY',
        ]);
        $emptyResp->assertRedirect();
        $this->assertStringContainsString('vacío', (string) (session()->getFlashdata('errors')['receipt'] ?? ''));

        $this->attachRawFile("GIF89a\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;", 'anim.gif', 'image/gif');
        $gifResp = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-GIF',
        ]);
        $gifResp->assertRedirect();
        $this->assertStringContainsString('archivo válido', (string) (session()->getFlashdata('errors')['receipt'] ?? ''));
        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    public function testScenario3RejectsOversizedReceiptWithSizeLimitMessageAndStoresNothing(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $filesBefore   = $this->listStoredFiles();
        $receiptsCount = $this->db->table('payment_receipts')->countAllResults();

        $oversized = "%PDF-1.4\n" . str_repeat('A', 5242881);
        $this->attachRawFile($oversized, 'grande.pdf', 'application/pdf');

        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-888',
        ]);

        $response->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('receipt', $errors);
        $this->assertStringContainsString('5 MB', $errors['receipt']);
        $this->assertNull($this->fetchLatestPayment($installmentId));
        $this->assertSame($receiptsCount, $this->db->table('payment_receipts')->countAllResults());
        $this->assertSame($filesBefore, $this->listStoredFiles());
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: Invalid form fields & boundary cases
    // ---------------------------------------------------------------------------------------------

    public function testScenario4RejectsInvalidFieldsAndBoundaryCases(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        // Case A: negative amount, future date, bad method, empty reference, missing receipt.
        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '-500',
            'paid_on'          => '2026-11-14',
            'method'           => 'cash',
            'reference_number' => '',
        ]);

        $response->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('paid_on', $errors);
        $this->assertArrayHasKey('method', $errors);
        $this->assertArrayHasKey('reference_number', $errors);
        $this->assertArrayHasKey('receipt', $errors);

        // Case B: zero amount, 3 decimals, invalid calendar date, >100 char reference.
        foreach (['0', 'abc', '100.123'] as $badAmount) {
            $resp = $this->postPayment($session, $installmentId, [
                'amount'           => $badAmount,
                'paid_on'          => '2026-02-30',
                'method'           => 'bank_transfer',
                'reference_number' => str_repeat('X', 101),
            ]);
            $resp->assertRedirect();
            $errs = session()->getFlashdata('errors');
            $this->assertArrayHasKey('amount', $errs);
            $this->assertArrayHasKey('paid_on', $errs);
            $this->assertArrayHasKey('reference_number', $errs);
        }

        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: Foreign, paid, under_review, double POST, or closed contract
    // ---------------------------------------------------------------------------------------------

    public function testScenario5RejectsForeignInstallment(): void
    {
        [$ownerTenantId]        = $this->createUserSession('tenant');
        [, $otherTenantSession] = $this->createUserSession('tenant');
        $contractId             = $this->createContract($ownerTenantId);
        $installmentId          = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $get = $this->withSession($otherTenantSession)->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $get->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $get->assertSessionHas('error');

        $this->attachReceipt('pdf', 'comprobante.pdf', 'application/pdf');

        $post = $this->postPayment($otherTenantSession, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-001',
        ]);

        $post->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $post->assertSessionHas('error');
        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonReportableStatusProvider(): array
    {
        return [
            'paid'         => [InstallmentModel::STATUS_PAID],
            'under_review' => [InstallmentModel::STATUS_UNDER_REVIEW],
        ];
    }

    #[DataProvider('nonReportableStatusProvider')]
    public function testScenario5RejectsPaidOrUnderReviewInstallment(string $status): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', $status);

        $get = $this->withSession($session)->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $get->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $get->assertSessionHas('error');

        $this->attachReceipt('pdf', 'comprobante.pdf', 'application/pdf');

        $post = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-002',
        ]);

        $post->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $post->assertSessionHas('error');
        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    public function testDoubleSubmissionOnSameInstallmentRejectsSecondPost(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $this->attachReceipt('pdf', 'primero.pdf', 'application/pdf');
        $first = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-FIRST',
        ]);
        $first->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $first->assertSessionHas('success');

        $firstPayment          = $this->fetchLatestPayment($installmentId);
        $firstReceipt          = $this->fetchReceiptForPayment((int) $firstPayment['id']);
        $this->storedReceipts[] = (string) $firstReceipt['stored_name'];

        $this->attachReceipt('pdf', 'segundo.pdf', 'application/pdf');
        $second = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-SECOND',
        ]);
        $second->assertRedirectTo(site_url('inquilino/estado-cuenta'));
        $second->assertSessionHas('error');

        $count = $this->db->table('payments')->where('installment_id', $installmentId)->countAllResults();
        $this->assertSame(1, $count);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function closedContractProvider(): array
    {
        return [
            'finished'  => [ContractModel::STATUS_FINISHED, 'Contrato finalizado'],
            'rescinded' => [ContractModel::STATUS_RESCINDED, 'Contrato rescindido'],
        ];
    }

    #[DataProvider('closedContractProvider')]
    public function testClosedContractShowsNoticeDisablesControlsAndRejectsPost(string $contractStatus, string $expectedNotice): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId, $contractStatus);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $form = $this->withSession($session)->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $form->assertStatus(200);
        $form->assertSee($expectedNotice);

        $body = (string) $form->getBody();
        $this->assertMatchesRegularExpression('/id="receipt"[^>]*disabled/', $body);
        $this->assertMatchesRegularExpression('/id="btn-submit-payment"[^>]*disabled/', $body);

        $this->attachReceipt('pdf', 'comprobante.pdf', 'application/pdf');
        $post = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-CLOSED',
        ]);

        $post->assertRedirectTo(site_url("inquilino/cuotas/{$installmentId}/informar-pago"));
        $this->assertStringContainsString($expectedNotice, (string) session()->getFlashdata('error'));
        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    // ---------------------------------------------------------------------------------------------
    // Transaction atomicity & rollback
    // ---------------------------------------------------------------------------------------------

    public function testRollbackWhenFileStoreFailsLeavesNoPaymentOrReceiptAndKeepsInstallmentPending(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $this->storage->failOnStore = true;
        $filesBefore                = $this->listStoredFiles();
        $receiptsBefore             = $this->db->table('payment_receipts')->countAllResults();

        $this->attachReceipt('pdf', 'comprobante.pdf', 'application/pdf');
        $response = $this->postPayment($session, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-FAIL-DISK',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull($this->fetchLatestPayment($installmentId));
        $this->assertSame($receiptsBefore, $this->db->table('payment_receipts')->countAllResults());
        $this->assertSame($filesBefore, $this->listStoredFiles());
        $this->assertSame(InstallmentModel::STATUS_PENDING, $this->fetchInstallment($installmentId)['status']);
    }

    public function testRollbackWhenReceiptDbInsertFailsDeletesMovedFileAndRollsBackPayment(): void
    {
        [$tenantId]    = $this->createUserSession('tenant');
        $contractId    = $this->createContract($tenantId);
        $installmentId = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $storedName  = 'rollback_' . bin2hex(random_bytes(8)) . '.pdf';
        $movedTarget = $this->storage->path($storedName);

        // size_bytes = 0 violates chk_payment_receipts_size after $moveFile() has written the file.
        $result = model(PaymentModel::class)->registerTenantPayment(
            $installmentId,
            $tenantId,
            [
                'amount'           => '500000.00',
                'paid_on'          => self::TODAY,
                'method'           => 'bank_transfer',
                'reference_number' => 'TRF-ROLLBACK',
            ],
            [
                'original_name' => 'comprobante.pdf',
                'stored_name'   => $storedName,
                'mime_type'     => 'application/pdf',
                'size_bytes'    => 0,
            ],
            static function () use ($movedTarget): bool {
                $dir = dirname($movedTarget);
                if (! is_dir($dir)) {
                    mkdir($dir, 0750, true);
                }

                return file_put_contents($movedTarget, '%PDF-1.4') !== false;
            }
        );

        $this->assertFalse($result['ok']);
        $this->assertSame(PaymentModel::ERROR_PERSISTENCE, $result['error']);
        $this->assertTrue($this->db->transStatus(), 'A failed payment must not leave the shared connection in a failed transaction state.');
        $this->assertFileDoesNotExist($movedTarget);
        $this->assertNull($this->fetchLatestPayment($installmentId));
        $this->assertSame(InstallmentModel::STATUS_PENDING, $this->fetchInstallment($installmentId)['status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Role access control, CSRF, and protected receipt endpoint
    // ---------------------------------------------------------------------------------------------

    public function testFormAndPostRejectGuestAdminEmployeeAndOwner(): void
    {
        [$tenantId]    = $this->createUserSession('tenant');
        $contractId    = $this->createContract($tenantId);
        $installmentId = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $guest = $this->get("inquilino/cuotas/{$installmentId}/informar-pago");
        $guest->assertRedirectTo(site_url('auth/login'));

        foreach (['admin', 'employee', 'owner'] as $role) {
            [, $otherSession] = $this->createUserSession($role);
            $get              = $this->withSession($otherSession)->get("inquilino/cuotas/{$installmentId}/informar-pago");
            $get->assertRedirect();
            $get->assertSessionHas('error');

            $post = $this->postPayment($otherSession, $installmentId, [
                'amount'           => '500000.00',
                'paid_on'          => self::TODAY,
                'method'           => 'bank_transfer',
                'reference_number' => 'TRF-ROLE',
            ]);
            $post->assertRedirect();
            $post->assertSessionHas('error');
        }

        $this->assertNull($this->fetchLatestPayment($installmentId));
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        [$tenantId, $session] = $this->createUserSession('tenant');
        $contractId           = $this->createContract($tenantId);
        $installmentId        = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $this->expectException(SecurityException::class);

        $this->withSession($session)->post("inquilino/cuotas/{$installmentId}/informar-pago", [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-NOCSRF',
        ]);
    }

    public function testReceiptEndpointAllowsOwningTenantAndOperatorsAndDeniesOthers(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        [, $otherTenantSession]     = $this->createUserSession('tenant');
        [, $ownerSession]           = $this->createUserSession('owner');
        [, $adminSession]           = $this->createUserSession('admin');
        [, $employeeSession]        = $this->createUserSession('employee');

        $contractId    = $this->createContract($tenantId);
        $installmentId = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        // Include quotes and CRLF in original_name to verify Content-Disposition header sanitization.
        $this->attachReceipt('pdf', "mi\"recibo\r\nEvil: 1.pdf", 'application/pdf');
        $this->postPayment($tenantSession, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-777',
        ]);

        $payment                = $this->fetchLatestPayment($installmentId);
        $receipt                = $this->fetchReceiptForPayment((int) $payment['id']);
        $this->storedReceipts[] = (string) $receipt['stored_name'];
        $receiptId              = (int) $receipt['id'];

        // Unauthenticated visitor -> redirected to login.
        session()->destroy();
        $_SESSION = [];
        $guest    = $this->withSession([])->get("comprobantes/{$receiptId}");
        $guest->assertRedirectTo(site_url('auth/login'));

        // Other tenant and owner -> 403 Forbidden.
        $this->withSession($otherTenantSession)->get("comprobantes/{$receiptId}")->assertStatus(403);
        $this->withSession($ownerSession)->get("comprobantes/{$receiptId}")->assertStatus(403);

        // Owning tenant, admin, and employee -> 200 OK with nosniff and safe Content-Disposition.
        foreach ([$tenantSession, $adminSession, $employeeSession] as $allowedSession) {
            $ok = $this->withSession($allowedSession)->get("comprobantes/{$receiptId}");
            $ok->assertStatus(200);
            $ok->assertHeader('Content-Type', 'application/pdf');
            $ok->assertHeader('X-Content-Type-Options', 'nosniff');
            $disposition = $ok->response()->getHeaderLine('Content-Disposition');
            $this->assertStringNotContainsString("\r", $disposition);
            $this->assertStringNotContainsString("\n", $disposition);
        }
    }

    public function testReceiptEndpointReturns404WhenMissingOrDeletedFromDisk(): void
    {
        [$tenantId, $tenantSession] = $this->createUserSession('tenant');
        $contractId                 = $this->createContract($tenantId);
        $installmentId              = $this->insertInstallment($contractId, '2026-12-01', '2026-12-10', 'pending');

        $this->attachReceipt('pdf', 'temporal.pdf', 'application/pdf');
        $this->postPayment($tenantSession, $installmentId, [
            'amount'           => '500000.00',
            'paid_on'          => self::TODAY,
            'method'           => 'bank_transfer',
            'reference_number' => 'TRF-404',
        ]);

        $payment                = $this->fetchLatestPayment($installmentId);
        $receipt                = $this->fetchReceiptForPayment((int) $payment['id']);
        $receiptId              = (int) $receipt['id'];

        $this->storage->delete((string) $receipt['stored_name']);

        try {
            $this->withSession($tenantSession)->get("comprobantes/{$receiptId}");
            $this->fail('Expected PageNotFoundException when stored receipt file is missing on disk.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(PageNotFoundException::class);
        $this->withSession($tenantSession)->get('comprobantes/999999');
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $data
     */
    private function postPayment(array $session, int $installmentId, array $data): \CodeIgniter\Test\TestResponse
    {
        $data[csrf_token()] = csrf_hash();

        return $this->withSession($session)->post("inquilino/cuotas/{$installmentId}/informar-pago", $data);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function createUserSession(string $roleName): array
    {
        $seq    = ++$this->sequence;
        $roleId = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];

        $this->db->table('users')->insert([
            'role_id'         => $roleId,
            'first_name'      => ucfirst($roleName),
            'last_name'       => 'User' . $seq,
            'document_number' => (string) (30000000 + $seq),
            'email'           => "{$roleName}{$seq}@example.test",
            'phone'           => '379400000' . $seq,
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => '2026-11-01 10:00:00',
            'updated_at'      => '2026-11-01 10:00:00',
        ]);

        $userId = (int) $this->db->insertID();

        return [$userId, [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_role'    => $roleName,
            'user_name'    => ucfirst($roleName) . ' User' . $seq,
            'user_email'   => "{$roleName}{$seq}@example.test",
        ]];
    }

    private function createContract(int $tenantId, string $status = ContractModel::STATUS_IN_FORCE): int
    {
        [$ownerId] = $this->createUserSession('owner');
        $seq       = ++$this->sequence;

        $this->db->table('properties')->insert([
            'owner_id'    => $ownerId,
            'address'     => 'Mitre ' . (100 + $seq),
            'city'        => 'Corrientes',
            'type'        => 'apartment',
            'rooms'       => 2,
            'base_rent'   => '500000.00',
            'description' => 'Depto de prueba',
            'status'      => $status === ContractModel::STATUS_IN_FORCE ? 'rented' : 'available',
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);
        $propertyId = (int) $this->db->insertID();

        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => '500000.00',
            'update_percentage'         => '10.00',
            'due_day'                   => 10,
            'commission_percentage'     => '8.00',
            'late_fee_daily_percentage' => '0.50',
            'status'                    => $status,
            'closed_at'                 => $status === ContractModel::STATUS_IN_FORCE ? null : '2026-11-05',
            'closing_reason'            => $status === ContractModel::STATUS_IN_FORCE ? null : 'Cierre de prueba',
            'created_at'                => '2026-11-01 10:00:00',
            'updated_at'                => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function insertInstallment(int $contractId, string $period, string $dueDate, string $status): int
    {
        $seq = ++$this->sequence;

        $this->db->table('installments')->insert([
            'contract_id' => $contractId,
            'number'      => $seq,
            'period'      => $period,
            'due_date'    => $dueDate,
            'base_amount' => '500000.00',
            'late_fee'    => '0.00',
            'status'      => $status,
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function attachReceipt(string $type, string $clientName, string $clientMime): void
    {
        $content = match ($type) {
            'pdf'   => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
            'jpg'   => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9",
            'png'   => "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde",
            default => '',
        };

        $this->attachRawFile($content, $clientName, $clientMime);
    }

    private function attachRawFile(string $content, string $clientName, string $clientMime): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'hu16_');
        file_put_contents($tmpPath, $content);
        $this->tempFiles[] = $tmpPath;

        $_FILES['receipt'] = [
            'name'     => $clientName,
            'type'     => $clientMime,
            'tmp_name' => $tmpPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($content),
        ];
        service('superglobals')->setFilesArray($_FILES);
    }

    /**
     * @return list<string>
     */
    private function listStoredFiles(): array
    {
        $dir = $this->storage->directory();

        if (! is_dir($dir)) {
            return [];
        }

        $entries = scandir($dir) ?: [];
        sort($entries);

        return array_values($entries);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchInstallment(int $id): array
    {
        return (array) $this->db->table('installments')->where('id', $id)->get()->getRowArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLatestPayment(int $installmentId): ?array
    {
        $row = $this->db->table('payments')
            ->where('installment_id', $installmentId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchReceiptForPayment(int $paymentId): ?array
    {
        $row = $this->db->table('payment_receipts')
            ->where('payment_id', $paymentId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}
