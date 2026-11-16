<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Boundary between the payment receipt flow (HU-16) and the PHP upload machinery.
 *
 * It is the only place that asks PHP whether a file really arrived through an HTTP upload and that moves it to the
 * private storage directory, so tests can replace it (registered as the `receiptStorage` service) instead of the
 * production code carrying test-only branches.
 */
class ReceiptStorage
{
    /**
     * Absolute directory where receipt files are stored, outside the public web root.
     */
    public function directory(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes';
    }

    /**
     * Absolute path of a stored receipt. Only the base name is used, so a stored name can never point elsewhere.
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
     * Moves the uploaded file into the private directory under the given random name.
     */
    public function store(UploadedFile $file, string $storedName): bool
    {
        $dir = $this->directory();

        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            return false;
        }

        return $file->move($dir, basename($storedName), true);
    }

    /**
     * Removes a stored receipt, if it exists.
     */
    public function delete(string $storedName): void
    {
        $path = $this->path($storedName);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

