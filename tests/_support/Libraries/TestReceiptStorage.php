<?php

namespace Tests\Support\Libraries;

use App\Libraries\ReceiptStorage;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Test double for {@see ReceiptStorage} that copies temporary files created by PHPUnit
 * without requiring an HTTP POST upload, and can simulate a disk write failure.
 */
class TestReceiptStorage extends ReceiptStorage
{
    public bool $failOnStore = false;

    public function isUploaded(UploadedFile $file): bool
    {
        return $file->getError() === UPLOAD_ERR_OK && is_file($file->getTempName());
    }

    public function store(UploadedFile $file, string $storedName): bool
    {
        if ($this->failOnStore) {
            return false;
        }

        $dir = $this->directory();

        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            return false;
        }

        return copy($file->getTempName(), $this->path($storedName));
    }
}

