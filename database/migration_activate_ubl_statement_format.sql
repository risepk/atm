-- database/migration_activate_ubl_statement_format.sql
-- Activates UBL (United Bank Limited / UBL Ameen) Statement Layout in statement_formats table

INSERT INTO `statement_formats` (`format_code`, `format_name`, `description`, `config`, `is_active`)
VALUES ('ubl', 'UBL Ameen Statement Layout', 'Authentic United Bank Limited / UBL Ameen Islamic Banking Statement Layout with Top Banner, Metadata Box & Inst No Grid', '{"supports_inst_no": true, "box_metadata": true, "balance_cr_suffix": true}', 1)
ON DUPLICATE KEY UPDATE 
    `format_name` = VALUES(`format_name`),
    `description` = VALUES(`description`),
    `config` = VALUES(`config`),
    `is_active` = 1;

UPDATE `master_banks` 
SET `logo_path` = 'uploads/banks/ubl_ameen_banner.png'
WHERE `bank_code` = 'UBL' AND (`logo_path` IS NULL OR `logo_path` = '');
