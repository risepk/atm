-- database/migration_activate_bankislami_statement_format.sql
-- Activates BankIslami Pakistan Statement Layout in statement_formats table

INSERT INTO `statement_formats` (`format_code`, `format_name`, `description`, `config`, `is_active`)
VALUES ('bankislami', 'BankIslami Statement Layout', 'Authentic BankIslami Pakistan Statement Layout with Top Banner, Customer Info Box & Summary Block', '{\"supports_summary\": true, \"box_address\": true}', 1)
ON DUPLICATE KEY UPDATE 
    `format_name` = VALUES(`format_name`),
    `description` = VALUES(`description`),
    `config` = VALUES(`config`),
    `is_active` = 1;

UPDATE `master_banks` 
SET `logo_path` = 'uploads/banks/bank_islami_logo.png'
WHERE `bank_code` = 'BIPL' AND (`logo_path` IS NULL OR `logo_path` = '');
