<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Review of the properties that owners publish themselves (HU-32, HU-33).
 *
 * `approval_status` is an axis independent from `status` (the commercial state) and from the logical deactivation:
 * a property only reaches the public catalog, the contract selector and the occupancy indicators once it is
 * "approved". The default is "approved" so every property that already exists, and every one the administration
 * loads (HU-07), stays visible; only the owner's own submission is stored as "pending".
 *
 *  - submitted_by:     the owner who published it; NULL when the administration loaded it.
 *  - rejection_reason: required whenever the property is "rejected" (checked by the database too).
 *  - reviewed_by / reviewed_at: who resolved the review and when; NULL while it is pending.
 */
class AddApprovalToProperties extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('properties');

        $this->db->query(
            "ALTER TABLE `{$table}`
                ADD COLUMN `approval_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `status`,
                ADD COLUMN `submitted_by` INT UNSIGNED NULL DEFAULT NULL AFTER `approval_status`,
                ADD COLUMN `rejection_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `submitted_by`,
                ADD COLUMN `reviewed_by` INT UNSIGNED NULL DEFAULT NULL AFTER `rejection_reason`,
                ADD COLUMN `reviewed_at` DATETIME NULL DEFAULT NULL AFTER `reviewed_by`,
                ADD KEY `idx_properties_approval_status` (`approval_status`),
                ADD KEY `idx_properties_submitted_by` (`submitted_by`),
                ADD KEY `idx_properties_reviewed_by` (`reviewed_by`),
                ADD CONSTRAINT `fk_properties_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
                ADD CONSTRAINT `fk_properties_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
                ADD CONSTRAINT `chk_properties_rejection_reason` CHECK (`approval_status` <> 'rejected' OR `rejection_reason` IS NOT NULL)"
        );
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('properties');

        $this->db->query(
            "ALTER TABLE `{$table}`
                DROP CONSTRAINT `chk_properties_rejection_reason`,
                DROP FOREIGN KEY `fk_properties_reviewed_by`,
                DROP FOREIGN KEY `fk_properties_submitted_by`,
                DROP KEY `idx_properties_reviewed_by`,
                DROP KEY `idx_properties_submitted_by`,
                DROP KEY `idx_properties_approval_status`,
                DROP COLUMN `reviewed_at`,
                DROP COLUMN `reviewed_by`,
                DROP COLUMN `rejection_reason`,
                DROP COLUMN `submitted_by`,
                DROP COLUMN `approval_status`"
        );
    }
}
