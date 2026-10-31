-- database/migration_create_narration_custom_types.sql
-- ==============================================================================
-- Migration: Create narration_custom_types table for user-defined patterns
-- Safe & Non-Destructive: Leaves existing tables and client data 100% untouched.
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `narration_custom_types` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type_code` VARCHAR(50) NOT NULL UNIQUE,
    `type_name` VARCHAR(100) NOT NULL,
    `pattern` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_regeneratable` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_custom_types_code` (`type_code`),
    INDEX `idx_custom_types_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional default starter custom patterns (Only inserted if not already present)
INSERT IGNORE INTO `narration_custom_types` 
(`type_code`, `type_name`, `pattern`, `description`, `is_regeneratable`, `is_active`, `created_at`, `updated_at`)
VALUES
('meezan_tid', 'Meezan Bank TID', 'FT26@@######', 'Meezan Bank FT transaction reference (e.g. FT26KR839201)', 1, 1, NOW(), NOW()),
('raast_ref', 'Raast / 1Link Reference', 'BAHLPKKA########', 'Raast & 1Link 8-digit transaction reference', 1, 1, NOW(), NOW()),
('hmb_branch_ref', 'Habib Metro Branch Ref', 'HMB-ISB-########', 'Habib Metropolitan branch reference with prefix', 1, 1, NOW(), NOW()),
('custom_branch_acc', 'Custom Branch Masked A/C', '0417-XXXX####-01', 'Account with custom fixed branch and suffix', 1, 1, NOW(), NOW()),
('atm_terminal_id', 'ATM Terminal Code', 'ATM-###-@@@', 'Custom ATM terminal code (e.g. ATM-042-KHI)', 1, 1, NOW(), NOW());
