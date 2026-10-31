-- ==============================================================================
-- PRODUCTION MIGRATION SCRIPT FOR PHPMYADMIN (ATM SYSTEM)
-- Covers: Allied Bank, Meezan Bank, HBL, Habib Metro (HMB), BankIslami (BIPL), UBL, Account Title Fix & Admin Password
-- ==============================================================================

-- 1. STATEMENT FORMATS (Register and Activate Allied, Meezan, HBL, Habib Metro, BankIslami, UBL)
INSERT INTO `statement_formats` (`format_code`, `format_name`, `description`, `config`, `is_active`)
VALUES 
('allied', 'Allied Bank Statement Layout', 'Authentic Allied Bank Limited Statement Layout', NULL, 1),
('meezan', 'Meezan Bank Statement Layout', 'Authentic Meezan Bank Quick Account Transactions Layout', '{"shariah_compliant": true, "supports_end_of_date_balance": true}', 1),
('hbl', 'HBL Statement Layout', 'Habib Bank Limited dense institutional statement layout with Brought Forward opening balance and multi-line particulars', '{"columns":["date","value","particulars","debit","credit","balance"]}', 1),
('habibmetro', 'Habib Metropolitan Bank Statement Layout', 'Authentic Habib Metropolitan Bank Statement Layout with Header & Metadata Grid', '{"supports_qr": true, "supports_end_of_date_balance": true}', 1),
('bankislami', 'BankIslami Statement Layout', 'Authentic BankIslami Pakistan Statement Layout with Top Banner, Customer Info Box & Summary Block', '{"supports_summary": true, "box_address": true}', 1),
('ubl', 'UBL Ameen Statement Layout', 'Authentic United Bank Limited / UBL Ameen Islamic Banking Statement Layout with Top Banner, Metadata Box & Inst No Grid', '{"supports_inst_no": true, "box_metadata": true, "balance_cr_suffix": true}', 1)
ON DUPLICATE KEY UPDATE 
    `format_name` = VALUES(`format_name`),
    `description` = VALUES(`description`),
    `config` = VALUES(`config`),
    `is_active` = 1;

-- 2. ACCOUNTS TABLE - EXPAND COLUMNS (Allows Account Code / Title > 100 without restriction)
ALTER TABLE `accounts` MODIFY COLUMN `account_title` VARCHAR(255) NOT NULL;
ALTER TABLE `accounts` MODIFY COLUMN `account_identifier` VARCHAR(255) NULL;
ALTER TABLE `accounts` MODIFY COLUMN `account_number` VARCHAR(100) NULL;

-- 3. MASTER BANKS - ENSURE HABIB METROPOLITAN BANK AND BANKISLAMI EXIST
INSERT INTO `master_banks` (`bank_name`, `bank_code`, `is_active`, `sort_order`) 
SELECT 'Habib Metropolitan Bank', 'HMB', 1, 13
WHERE NOT EXISTS (
    SELECT 1 FROM `master_banks` WHERE `bank_code` = 'HMB' OR `bank_name` LIKE '%Habib Metro%'
);

INSERT INTO `master_banks` (`bank_name`, `bank_code`, `logo_path`, `is_active`, `sort_order`) 
SELECT 'BankIslami Pakistan Limited', 'BIPL', 'uploads/banks/bank_islami_logo.png', 1, 14
WHERE NOT EXISTS (
    SELECT 1 FROM `master_banks` WHERE `bank_code` = 'BIPL' OR `bank_name` LIKE '%BankIslami%' OR `bank_name` LIKE '%Bank Islami%'
);

UPDATE `master_banks` 
SET `logo_path` = 'uploads/banks/bank_islami_logo.png'
WHERE `bank_code` = 'BIPL' AND (`logo_path` IS NULL OR `logo_path` = '');

UPDATE `master_banks` 
SET `logo_path` = 'uploads/banks/ubl_ameen_banner.png'
WHERE `bank_code` = 'UBL' AND (`logo_path` IS NULL OR `logo_path` = '' OR `logo_path` NOT LIKE '%ubl%');

