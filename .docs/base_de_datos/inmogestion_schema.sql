-- InmoGestión 2026 - database schema (reference copy)
--
-- Generated on 2026-11-06 from the CodeIgniter migrations in app/Database/Migrations (issue #22),
-- with MySQL 8.4. Tables are listed in dependency order.
--
-- USE IT TO: draw the EER diagram in MySQL Workbench
--   File > New Model > File > Import > Reverse Engineer MySQL Create Script... > pick this file > Execute
--   (it only reads the file, it does not touch any database),
-- or to compare it with a live database (Database > Synchronize Model / Schema Inspector).
--
-- IT IS NOT THE SOURCE OF TRUTH: the migrations are. To create the application's database run
-- php spark migrate; do not build it from this file, because CodeIgniter's migrations table would
-- not know the tables already exist. Nothing here drops or replaces anything: every statement is
-- CREATE ... IF NOT EXISTS.
--
-- REGENERATE IT whenever a migration changes (see .docs/base_de_datos/MODELO_DE_DATOS.md).

CREATE DATABASE IF NOT EXISTS `inmogestion2026` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inmogestion2026`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `document_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `active_email` varchar(255) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`email`,NULL)) VIRTUAL,
  `active_document_number` varchar(20) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`document_number`,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_active_email` (`active_email`),
  UNIQUE KEY `uq_users_active_document_number` (`active_document_number`),
  KEY `idx_users_role_id` (`role_id`),
  CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token_hash` (`token_hash`),
  KEY `idx_password_resets_user_id` (`user_id`),
  CONSTRAINT `fk_password_resets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `properties` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int unsigned NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('apartment','house','commercial','land','office') COLLATE utf8mb4_general_ci NOT NULL,
  `rooms` tinyint unsigned DEFAULT NULL,
  `surface` decimal(8,2) DEFAULT NULL,
  `included_services` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `base_rent` decimal(12,2) NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` enum('available','rented','maintenance') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_properties_owner_id` (`owner_id`),
  KEY `idx_properties_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_properties_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_properties_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_properties_base_rent` CHECK ((`base_rent` > 0)),
  CONSTRAINT `chk_properties_rooms` CHECK (((`rooms` is null) or (`rooms` > 0))),
  CONSTRAINT `chk_properties_surface` CHECK (((`surface` is null) or (`surface` > 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int unsigned NOT NULL,
  `tenant_id` int unsigned NOT NULL,
  `owner_id` int unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(12,2) NOT NULL,
  `update_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `due_day` tinyint unsigned NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL,
  `late_fee_daily_percentage` decimal(5,2) NOT NULL,
  `status` enum('in_force','finished','rescinded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'in_force',
  `closed_at` date DEFAULT NULL,
  `closing_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `active_property_id` int unsigned GENERATED ALWAYS AS (if((`status` = _utf8mb4'in_force'),`property_id`,NULL)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contracts_active_property_id` (`active_property_id`),
  KEY `idx_contracts_property_id` (`property_id`),
  KEY `idx_contracts_tenant_id` (`tenant_id`),
  KEY `idx_contracts_owner_id` (`owner_id`),
  KEY `idx_contracts_status_end_date` (`status`,`end_date`),
  CONSTRAINT `fk_contracts_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_contracts_property_id` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_contracts_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_contracts_closing` CHECK (((`status` <> _utf8mb4'in_force') or ((`closed_at` is null) and (`closing_reason` is null)))),
  CONSTRAINT `chk_contracts_commission_percentage` CHECK ((`commission_percentage` between 0 and 100)),
  CONSTRAINT `chk_contracts_dates` CHECK ((`end_date` > `start_date`)),
  CONSTRAINT `chk_contracts_due_day` CHECK ((`due_day` between 1 and 28)),
  CONSTRAINT `chk_contracts_late_fee_percentage` CHECK ((`late_fee_daily_percentage` between 0 and 100)),
  CONSTRAINT `chk_contracts_monthly_rent` CHECK ((`monthly_rent` > 0)),
  CONSTRAINT `chk_contracts_update_percentage` CHECK ((`update_percentage` between 0 and 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `installments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int unsigned NOT NULL,
  `number` smallint unsigned NOT NULL,
  `period` date NOT NULL,
  `due_date` date NOT NULL,
  `base_amount` decimal(12,2) NOT NULL,
  `late_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','overdue','under_review','paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_installments_contract_period` (`contract_id`,`period`),
  UNIQUE KEY `uq_installments_contract_number` (`contract_id`,`number`),
  KEY `idx_installments_status_due_date` (`status`,`due_date`),
  CONSTRAINT `fk_installments_contract_id` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_installments_base_amount` CHECK ((`base_amount` > 0)),
  CONSTRAINT `chk_installments_late_fee` CHECK ((`late_fee` >= 0)),
  CONSTRAINT `chk_installments_number` CHECK ((`number` > 0)),
  CONSTRAINT `chk_installments_period` CHECK ((dayofmonth(`period`) = 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `installment_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_on` date NOT NULL,
  `method` enum('bank_transfer','deposit','digital_gateway') COLLATE utf8mb4_general_ci NOT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending_validation','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending_validation',
  `rejection_reason` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resolved_by` int unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_installment_id` (`installment_id`),
  KEY `idx_payments_resolved_by` (`resolved_by`),
  KEY `idx_payments_status_created_at` (`status`,`created_at`),
  CONSTRAINT `fk_payments_installment_id` FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payments_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_payments_amount` CHECK ((`amount` > 0)),
  CONSTRAINT `chk_payments_rejection_reason` CHECK (((`status` <> _utf8mb4'rejected') or ((`rejection_reason` is not null) and (`rejection_reason` <> _utf8mb4'')))),
  CONSTRAINT `chk_payments_resolution` CHECK ((((`status` = _utf8mb4'pending_validation') and (`resolved_by` is null) and (`resolved_at` is null)) or ((`status` <> _utf8mb4'pending_validation') and (`resolved_by` is not null) and (`resolved_at` is not null))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payment_receipts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` int unsigned NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `stored_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` enum('application/pdf','image/jpeg','image/png') COLLATE utf8mb4_general_ci NOT NULL,
  `size_bytes` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_receipts_stored_name` (`stored_name`),
  KEY `idx_payment_receipts_payment_id` (`payment_id`),
  CONSTRAINT `fk_payment_receipts_payment_id` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_payment_receipts_size` CHECK ((`size_bytes` between 1 and 5242880))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `settlements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int unsigned NOT NULL,
  `contract_id` int unsigned NOT NULL,
  `period` date NOT NULL,
  `gross_amount` decimal(12,2) NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL,
  `commission_amount` decimal(12,2) NOT NULL,
  `net_amount` decimal(12,2) NOT NULL,
  `status` enum('pending_transfer','transferred') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending_transfer',
  `transferred_at` date DEFAULT NULL,
  `operation_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settlements_contract_period` (`contract_id`,`period`),
  KEY `idx_settlements_owner_status` (`owner_id`,`status`),
  CONSTRAINT `fk_settlements_contract_id` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_settlements_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_settlements_amounts` CHECK (((`commission_amount` >= 0) and (`commission_amount` <= `gross_amount`) and (`net_amount` = (`gross_amount` - `commission_amount`)))),
  CONSTRAINT `chk_settlements_commission_percentage` CHECK ((`commission_percentage` between 0 and 100)),
  CONSTRAINT `chk_settlements_gross_amount` CHECK ((`gross_amount` > 0)),
  CONSTRAINT `chk_settlements_period` CHECK ((dayofmonth(`period`) = 1)),
  CONSTRAINT `chk_settlements_transfer` CHECK ((((`status` = _utf8mb4'pending_transfer') and (`transferred_at` is null) and (`operation_number` is null)) or ((`status` = _utf8mb4'transferred') and (`transferred_at` is not null) and (`operation_number` is not null) and (`operation_number` <> _utf8mb4''))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `settlement_installments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `settlement_id` int unsigned NOT NULL,
  `installment_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settlement_installments_installment_id` (`installment_id`),
  KEY `idx_settlement_installments_settlement_id` (`settlement_id`),
  CONSTRAINT `fk_settlement_installments_installment_id` FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_settlement_installments_settlement_id` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` enum('payment_submitted','payment_approved','payment_rejected','installment_due_soon','contract_expiring') COLLATE utf8mb4_general_ci NOT NULL,
  `message` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `reference_type` enum('payment','installment','contract') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read_at` (`user_id`,`read_at`),
  CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_notifications_reference` CHECK ((((`reference_type` is null) and (`reference_id` is null)) or ((`reference_type` is not null) and (`reference_id` is not null))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
