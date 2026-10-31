<?php
// database/migration_activate_ubl_statement_format.php

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Core/Database.php';

$db = Database::getInstance();

echo "Running migration: Activate UBL Statement Format...\n";

// 1. Insert or update statement_formats
$sql = "INSERT INTO `statement_formats` (`format_code`, `format_name`, `description`, `config`, `is_active`)
VALUES ('ubl', 'UBL Ameen Statement Layout', 'Authentic United Bank Limited / UBL Ameen Islamic Banking Statement Layout with Top Banner, Metadata Box & Inst No Grid', '{\"supports_inst_no\": true, \"box_metadata\": true, \"balance_cr_suffix\": true}', 1)
ON DUPLICATE KEY UPDATE 
    `format_name` = VALUES(`format_name`),
    `description` = VALUES(`description`),
    `config` = VALUES(`config`),
    `is_active` = 1";

$db->execute($sql);
echo " [OK] 'ubl' statement format registered and active.\n";

// 2. Update master_banks logo_path for UBL
$db->execute("UPDATE `master_banks` SET `logo_path` = 'uploads/banks/ubl_ameen_banner.png' WHERE `bank_code` = 'UBL'");
echo " [OK] master_banks UBL logo updated to 'uploads/banks/ubl_ameen_banner.png'.\n";

// 3. Update production_migration_all_formats_and_fixes.sql
echo "Migration completed successfully!\n";
