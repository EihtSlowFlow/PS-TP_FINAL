<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Server-side validation and preparation of the photos of a property.
 *
 * Like {@see ReceiptValidator} it never trusts the client's extension or MIME type: the content is checked with finfo,
 * the magic bytes and getimagesize(). A valid photo is then re-encoded with GD as a JPEG of at most
 * {@see self::MAX_SIDE} pixels per side: this drops every metadata block of the original (a phone photo carries the
 * GPS position where it was taken, which must not be published), applies the EXIF rotation and keeps the files light.
 */
class PropertyImageProcessor
{
    /** Most a photo may weigh when it arrives (and when it is stored: chk_property_images_size). */
    public const MAX_SIZE_BYTES = 5242880;

    /** Most photos a property may have. */
    public const MAX_IMAGES_PER_PROPERTY = 8;

    /** Longest side, in pixels, of a stored photo. */
    public const MAX_SIDE = 1600;

    /** JPEG quality of the re-encoded photos. */
    public const JPEG_QUALITY = 82;

    /**
     * Server-detected MIME types accepted, mapped to the extension used when GD is not available.
     *
     * @var array<string, string>
     */
    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public const TOO_MANY_MESSAGE = 'Un inmueble puede tener hasta 8 fotos.';

    /**
     * Validates one uploaded photo.
     *
     * @return array{ok: true, mime_type: string, original_name: string, temp_path: string}|array{ok: false, error: string}
     */
    public static function validate(UploadedFile $file, PropertyImageStorage $storage): array
    {
        $name      = self::sanitizeOriginalName((string) $file->getClientName());
        $errorCode = $file->getError();

        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => "La foto «{$name}» es más grande de lo que acepta el servidor. Probá con una foto más liviana."];
        }

        if (! $storage->isUploaded($file) || $file->hasMoved()) {
            return ['ok' => false, 'error' => "No se pudo procesar la foto «{$name}». Intentá subirla nuevamente."];
        }

        $size = (int) $file->getSize();

        if ($size <= 0) {
            return ['ok' => false, 'error' => "La foto «{$name}» está vacía."];
        }

        if ($size > self::MAX_SIZE_BYTES) {
            return ['ok' => false, 'error' => "La foto «{$name}» supera los 5 MB permitidos."];
        }

        $tempPath = $file->getTempName();
        $mime     = $tempPath !== '' ? self::detectMimeType($tempPath) : null;

        if ($mime === null) {
            return ['ok' => false, 'error' => "«{$name}» no es una foto válida: subí imágenes JPG, PNG o WebP."];
        }

        return ['ok' => true, 'mime_type' => $mime, 'original_name' => $name, 'temp_path' => $tempPath];
    }

    /**
     * Returns the MIME type of an image file on disk when it is a real JPG, PNG or WebP picture, or null.
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
        $header = $handle !== false ? fread($handle, 12) : false;

        if ($handle !== false) {
            fclose($handle);
        }

        if (! is_string($header)) {
            return null;
        }

        $signatureMatches = match ($finfoMime) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png'  => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'image/webp' => str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP',
            default      => false,
        };

        // getimagesize() reads the real dimensions: a file that only imitates the header is rejected here.
        $info = $signatureMatches ? @getimagesize($filePath) : false;

        return is_array($info) && $info[0] > 0 && $info[1] > 0 ? $finfoMime : null;
    }

    /**
     * Prepares a validated photo for storage: a JPEG without metadata, upright and at most MAX_SIDE pixels per side.
     * Without GD the original bytes are kept as they are.
     *
     * @return array{contents: string, mime_type: string, extension: string}|null Null when the image cannot be decoded
     */
    public static function prepare(string $tempPath, string $mimeType): ?array
    {
        $original = @file_get_contents($tempPath);

        if (! is_string($original) || $original === '') {
            return null;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return ['contents' => $original, 'mime_type' => $mimeType, 'extension' => self::ALLOWED_MIMES[$mimeType]];
        }

        $image = @imagecreatefromstring($original);

        if ($image === false) {
            return null;
        }

        if ($mimeType === 'image/jpeg') {
            $image = self::applyExifOrientation($image, $tempPath);
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $scale  = min(1, self::MAX_SIDE / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        // A transparent PNG or WebP is laid on white instead of turning black in the JPEG.
        imagefill($target, 0, 0, (int) imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        imagedestroy($image);

        ob_start();
        $written  = imagejpeg($target, null, self::JPEG_QUALITY);
        $contents = (string) ob_get_clean();
        imagedestroy($target);

        if (! $written || $contents === '' || strlen($contents) > self::MAX_SIZE_BYTES) {
            return null;
        }

        return ['contents' => $contents, 'mime_type' => 'image/jpeg', 'extension' => 'jpg'];
    }

    /**
     * Random, collision-resistant file name with the given extension.
     */
    public static function generateStoredName(string $extension): string
    {
        $cleanExt = preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?: 'jpg';

        return bin2hex(random_bytes(16)) . '.' . $cleanExt;
    }

    /**
     * Strips directory components and control characters from the client file name, for safe display.
     */
    public static function sanitizeOriginalName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $base) ?? '');

        if ($base === '') {
            return 'foto';
        }

        return mb_strlen($base) > 255 ? mb_substr($base, 0, 255) : $base;
    }

    /**
     * Turns a JPEG upright according to its EXIF orientation (phones store the rotation there instead of rotating).
     */
    private static function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif        = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated instanceof \GdImage && $rotated !== $image) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
