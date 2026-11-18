<?php

namespace Tests\Support\Libraries;

use App\Libraries\PropertyImageStorage;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Test double for {@see PropertyImageStorage}: accepts the temporary files PHPUnit creates (they did not arrive through
 * an HTTP POST upload) and keeps the photos in their own directory, emptied after each test.
 */
class TestPropertyImageStorage extends PropertyImageStorage
{
    public function directory(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'inmuebles_test';
    }

    public function isUploaded(UploadedFile $file): bool
    {
        return $file->getError() === UPLOAD_ERR_OK && is_file($file->getTempName());
    }

    /**
     * Removes every stored photo (called from tearDown).
     */
    public function clear(): void
    {
        foreach (glob($this->directory() . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
    }
}
