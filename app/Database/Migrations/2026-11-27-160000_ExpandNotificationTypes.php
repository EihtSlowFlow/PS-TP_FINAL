<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Expands the notification ENUM types and reference types to cover rental requests
 * (HU-30/HU-31) and owner property submissions (HU-32/HU-33), as planned in HU-22 (#47).
 */
class ExpandNotificationTypes extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('notifications');

        $this->db->query(
            "ALTER TABLE `{$table}`
                MODIFY COLUMN `type` ENUM(
                    'payment_submitted',
                    'payment_approved',
                    'payment_rejected',
                    'installment_due_soon',
                    'contract_expiring',
                    'rental_request_submitted',
                    'rental_request_approved',
                    'rental_request_rejected',
                    'property_submitted',
                    'property_approved',
                    'property_rejected'
                ) NOT NULL,
                MODIFY COLUMN `reference_type` ENUM(
                    'payment',
                    'installment',
                    'contract',
                    'rental_request',
                    'property'
                ) NULL"
        );
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('notifications');

        $this->db->query(
            "DELETE FROM `{$table}`
             WHERE `type` NOT IN (
                 'payment_submitted',
                 'payment_approved',
                 'payment_rejected',
                 'installment_due_soon',
                 'contract_expiring'
             )
             OR (`reference_type` IS NOT NULL AND `reference_type` NOT IN ('payment', 'installment', 'contract'))"
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
                MODIFY COLUMN `type` ENUM(
                    'payment_submitted',
                    'payment_approved',
                    'payment_rejected',
                    'installment_due_soon',
                    'contract_expiring'
                ) NOT NULL,
                MODIFY COLUMN `reference_type` ENUM(
                    'payment',
                    'installment',
                    'contract'
                ) NULL"
        );
    }
}

