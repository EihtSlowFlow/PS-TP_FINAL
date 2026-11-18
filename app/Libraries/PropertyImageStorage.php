<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Boundary between the property photos and the disk / PHP upload machinery (registered as the `propertyImageStorage`
 * service, so tests can swap it, like {@see ReceiptStorage}).
 *
 * The photos are written after {@see PropertyImageProcessor} re-encodes them, so this class writes bytes instead of
 * moving the uploaded file.
 */
class PropertyImageStorage
{
    /**
     * Absolute directory of the photos, outside the public web root.
     */
    public function directory(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'inmuebles';
    }

    /**
     * Absolute path of a stored photo. Only the base name is used, so a stored name can never point elsewhere.
     */
    public function path(string $storedName): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . basename($storedName);
    }

    /**
     * Whether the file really arrived through an HTTP upload without errors.
     */
    public function isUploaded(UploadedFile $file): bool
    {
        return $file->isValid();
    }

    /**
     * Writes a processed photo under the given random name.
     */
    public function write(string $storedName, string $contents): bool
    {
        $dir = $this->directory();

        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            return false;
        }

        return file_put_contents($this->path($storedName), $contents, LOCK_EX) === strlen($contents);
    }

    /**
     * Removes a stored photo, if it exists.
     */
    public function delete(string $storedName): void
    {
        $path = $this->path($storedName);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
