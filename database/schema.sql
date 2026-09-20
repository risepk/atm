-- ==========================================================
-- Account & Transaction Management System
-- Database Schema (DDL)
-- MySQL / MariaDB (InnoDB, utf8mb4_unicode_ci)
-- Note: Foreign keys are enforced in the application layer.
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `atm_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `atm_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_users_username` (`username`),
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_uuid` (`uuid`),
    KEY `idx_users_status_role` (`status`, `role`),
    KEY `idx_users_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Accounts Table
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `account_title` VARCHAR(100) NOT NULL,
    `account_identifier` VARCHAR(50) NULL,
    `account_number` VARCHAR(50) NULL,
    `bank_name` VARCHAR(100) NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'PKR',
    `initial_balance` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive', 'closed') NOT NULL DEFAULT 'active',
    `father_name` VARCHAR(100) NULL,
    `account_type` VARCHAR(50) NULL DEFAULT 'Current Account',
    `iban` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_accounts_uuid` (`uuid`),
    KEY `idx_accounts_user_id` (`user_id`),
    KEY `idx_accounts_user_identifier` (`user_id`, `account_identifier`),
    KEY `idx_accounts_user_status` (`user_id`, `status`),
    KEY `idx_accounts_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Narration Templates Table
CREATE TABLE IF NOT EXISTS `narration_templates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `pattern` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    UNIQUE KEY `idx_templates_uuid` (`uuid`),
    UNIQUE KEY `idx_templates_code` (`code`),
    KEY `idx_templates_active_sort` (`is_active`, `sort_order`),
    KEY `idx_templates_category` (`category`),
    KEY `idx_templates_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Narration Template Fields Table
CREATE TABLE IF NOT EXISTS `narration_template_fields` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `field_key` VARCHAR(50) NOT NULL,
    `field_label` VARCHAR(100) NOT NULL,
    `field_type` VARCHAR(50) NOT NULL,
    `placeholder` VARCHAR(100) NULL,
    `default_value` VARCHAR(255) NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `generator_rule` JSON NULL,
    `options_source` VARCHAR(100) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    KEY `idx_fields_template_sort` (`template_id`, `sort_order`),
    KEY `idx_fields_template_key` (`template_id`, `field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Narration Presets Table
CREATE TABLE IF NOT EXISTS `narration_presets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `preset_name` VARCHAR(100) NOT NULL,
    `field_values` JSON NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    UNIQUE KEY `idx_presets_uuid` (`uuid`),
    KEY `idx_presets_template_user` (`template_id`, `user_id`),
    KEY `idx_presets_user_active` (`user_id`, `is_active`),
    KEY `idx_presets_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `template_id` BIGINT UNSIGNED NULL,
    `template_version` INT UNSIGNED NULL,
    `transaction_date` DATE NOT NULL,
    `debit` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `credit` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `narration` TEXT NOT NULL,
    `template_payload` JSON NULL,
    `reference_no` VARCHAR(100) NULL,
    `status` ENUM('posted', 'voided') NOT NULL DEFAULT 'posted',
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_tx_uuid` (`uuid`),
    KEY `idx_tx_account_date` (`account_id`, `transaction_date`),
    KEY `idx_tx_user_date` (`user_id`, `transaction_date`),
    KEY `idx_tx_account_status_deleted` (`account_id`, `status`, `deleted_at`),
    KEY `idx_tx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Master Banks Table
CREATE TABLE IF NOT EXISTS `master_banks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bank_name` VARCHAR(100) NOT NULL,
    `bank_code` VARCHAR(20) NOT NULL,
    `swift_code` VARCHAR(20) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `idx_banks_code` (`bank_code`),
    KEY `idx_banks_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    KEY `idx_audit_user_action` (`user_id`, `action`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Statement Formats Table
CREATE TABLE IF NOT EXISTS `statement_formats` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `format_code` VARCHAR(50) NOT NULL,
    `format_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `config` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_statement_code` (`format_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;