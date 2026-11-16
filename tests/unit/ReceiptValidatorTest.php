<?php

use App\Libraries\ReceiptValidator;
use App\Models\PaymentModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for server-side receipt validation and payment amount difference calculation (HU-16).
 *
 * @internal
 */
final class ReceiptValidatorTest extends CIUnitTestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    public function testDetectsValidPdf(): void
    {
        $path = $this->createTempFile("%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");
        $this->assertSame('application/pdf', ReceiptValidator::detectMimeType($path));
    }

    public function testDetectsValidJpeg(): void
    {
        $path = $this->createTempFile("\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9");
        $this->assertSame('image/jpeg', ReceiptValidator::detectMimeType($path));
    }

    public function testDetectsValidPng(): void
    {
        $path = $this->createTempFile("\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde");
        $this->assertSame('image/png', ReceiptValidator::detectMimeType($path));
    }

    public function testRejectsPhpPayloadDisguisedAsJpg(): void
    {
        $path = $this->createTempFile("<?php echo 'hacked'; ?>", '.jpg');
        $this->assertNull(ReceiptValidator::detectMimeType($path));
    }

    public function testRejectsPlainTextDisguisedAsPdf(): void
    {
        $path = $this->createTempFile('This is plain text pretending to be a PDF.', '.pdf');
        $this->assertNull(ReceiptValidator::detectMimeType($path));
    }

    public function testRejectsGifImage(): void
    {
        $path = $this->createTempFile("GIF89a\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00!\xF9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;", '.gif');
        $this->assertNull(ReceiptValidator::detectMimeType($path));
    }

    public function testRejectsEmptyOrMissingFile(): void
    {
        $empty = $this->createTempFile('');
        $this->assertNull(ReceiptValidator::detectMimeType($empty));
        $this->assertNull(ReceiptValidator::detectMimeType('/non/existent/path/file.pdf'));
    }

    public function testGenerateStoredNameIsRandomAndPreservesSanitizedExtension(): void
    {
        $first  = ReceiptValidator::generateStoredName('pdf');
        $second = ReceiptValidator::generateStoredName('pdf');

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}\.pdf$/', $first);
    }

    public function testSanitizeOriginalNameStripsPathsAndControlChars(): void
    {
        $this->assertSame('passwd', ReceiptValidator::sanitizeOriginalName('../../etc/passwd'));
        $this->assertSame('comprobante.pdf', ReceiptValidator::sanitizeOriginalName("C:\\fakepath\\comprobante\x00.pdf"));
        $this->assertSame('comprobante', ReceiptValidator::sanitizeOriginalName('   '));
    }

    public function testAmountDifferenceComputesExactCents(): void
    {
        $this->assertSame('0.00', PaymentModel::amountDifference('500000.00', '500000.00'));
        $this->assertSame('-1500.00', PaymentModel::amountDifference('498500.00', '500000.00'));
        $this->assertSame('2500.50', PaymentModel::amountDifference('502500.50', '500000.00'));
    }

    private function createTempFile(string $contents, string $suffix = ''): string
    {
        $base = tempnam(sys_get_temp_dir(), 'rcpt_');
        $path = $suffix !== '' ? $base . $suffix : $base;

        if ($path !== $base && is_file($base)) {
            @unlink($base);
        }

        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}

