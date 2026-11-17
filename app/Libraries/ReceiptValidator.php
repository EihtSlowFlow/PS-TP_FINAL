<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Server-side validation of payment receipt uploads (HU-16).
 *
 * Never trusts the client-supplied extension or MIME type: inspects the file's
 * actual content with finfo and magic-byte signatures, enforces the 5 MB limit
 * and generates a random storage filename.
 */
class ReceiptValidator
{
    /** Maximum accepted receipt size in bytes (5 MB, matching chk_payment_receipts_size). */
    public const MAX_SIZE_BYTES = 5242880;

    /**
     * Allowed server-detected MIME types mapped to their canonical file extension.
     *
     * @var array<string, string>
     */
    public const ALLOWED_MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    /**
     * Validates an uploaded receipt file on the server.
     *
     * @param ReceiptStorage $storage Tells whether the file really arrived through an HTTP upload
     *
     * @return array{
     *     ok: true,
     *     mime_type: string,
     *     extension: string,
     *     size_bytes: int,
     *     original_name: string
     * }|array{
     *     ok: false,
     *     error: string
     * }
     */
    public static function validateUploadedFile(?UploadedFile $file, ReceiptStorage $storage): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Adjuntá el comprobante digital del pago (PDF, JPG o PNG).'];
        }

        $errorCode = $file->getError();

        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'El comprobante supera el tamaño máximo permitido de 5 MB.'];
        }

        if (! $storage->isUploaded($file) || $file->hasMoved()) {
            return ['ok' => false, 'error' => 'No se pudo procesar el comprobante adjunto. Intentá subirlo nuevamente.'];
        }

        $size = (int) $file->getSize();

        if ($size <= 0) {
            return ['ok' => false, 'error' => 'El comprobante adjunto está vacío.'];
        }

        if ($size > self::MAX_SIZE_BYTES) {
            return ['ok' => false, 'error' => 'El comprobante supera el tamaño máximo permitido de 5 MB.'];
        }

        $tempPath = $file->getTempName();

        if ($tempPath === '' || ! is_file($tempPath)) {
            return ['ok' => false, 'error' => 'No se pudo verificar el archivo temporal del comprobante.'];
        }

        $detectedMime = self::detectMimeType($tempPath);

        if ($detectedMime === null) {
            return ['ok' => false, 'error' => 'El comprobante debe ser un archivo válido en formato PDF, JPG o PNG.'];
        }

        return [
            'ok'            => true,
            'mime_type'     => $detectedMime,
            'extension'     => self::ALLOWED_MIMES[$detectedMime],
            'size_bytes'    => $size,
            'original_name' => self::sanitizeOriginalName((string) $file->getClientName()),
        ];
    }

    /**
     * Inspects a file on disk using finfo and magic-byte signatures, returning one
     * of the allowed MIME types or null when the content is not a valid PDF/JPG/PNG.
     */
    public static function detectMimeType(string $filePath): ?string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return null;
        }

        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $finfoMime = $finfo !== false ? finfo_file($finfo, $filePath) : false;

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! is_string($finfoMime) || ! array_key_exists($finfoMime, self::ALLOWED_MIMES)) {
            return null;
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 8);
        fclose($handle);

        if (! is_string($header)) {
            return null;
        }

        return match ($finfoMime) {
            'application/pdf' => str_starts_with($header, '%PDF-') ? 'application/pdf' : null,
            'image/jpeg'      => str_starts_with($header, "\xFF\xD8\xFF") ? 'image/jpeg' : null,
            'image/png'       => str_starts_with($header, "\x89PNG\r\n\x1a\n") ? 'image/png' : null,
            default           => null,
        };
    }

    /**
     * Generates a random, collision-resistant filename with the server-verified extension.
     */
    public static function generateStoredName(string $extension): string
    {
        $cleanExt = preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?: 'bin';

        return bin2hex(random_bytes(16)) . '.' . $cleanExt;
    }

    /**
     * Strips directory components and control characters from the client filename for safe display.
     */
    public static function sanitizeOriginalName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[\x00-\x1F\x7F]/u', '', $base) ?? '';
        $base = trim($base);

        if ($base === '') {
            return 'comprobante';
        }

        if (mb_strlen($base) > 255) {
            return mb_substr($base, 0, 255);
        }

        return $base;
    }
}
