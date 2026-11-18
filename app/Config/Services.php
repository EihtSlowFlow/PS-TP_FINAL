<?php

namespace Config;

use App\Libraries\PropertyImageStorage;
use App\Libraries\ReceiptStorage;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Private storage of payment receipts (HU-16). A service so tests can swap the upload machinery.
     */
    public static function receiptStorage(bool $getShared = true): ReceiptStorage
    {
        if ($getShared) {
            return static::getSharedInstance('receiptStorage');
        }

        return new ReceiptStorage();
    }

    /**
     * Private storage of the photos of the properties. A service so tests can swap the upload machinery.
     */
    public static function propertyImageStorage(bool $getShared = true): PropertyImageStorage
    {
        if ($getShared) {
            return static::getSharedInstance('propertyImageStorage');
        }

        return new PropertyImageStorage();
    }
}
