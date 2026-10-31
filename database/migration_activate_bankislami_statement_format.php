<?php
// database/migration_activate_bankislami_statement_format.php
// Activates 'bankislami' format in statement_formats table and links logo in master_banks

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    
    // 1. Check if 'bankislami' format exists in statement_formats
    $existing = $db->query("SELECT * FROM `statement_formats` WHERE `format_code` = 'bankislami'")->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $db->execute("
            UPDATE `statement_formats` 
            SET `format_name` = 'BankIslami Statement Layout',
                `description` = 'Authentic BankIslami Pakistan Statement Layout with Top Banner, Customer Info Box & Summary Block',
                `is_active` = 1,
                `config` = '{\"supports_summary\": true, \"box_address\": true}'
            WHERE `format_code` = 'bankislami'
        ");
        echo "Migration SUCCESS: Activated existing 'bankislami' statement format (ID: {$existing['id']}).\n";
    } else {
        $db->execute("
            INSERT INTO `statement_formats` 
            (`format_code`, `format_name`, `description`, `config`, `is_active`) 
            VALUES 
            ('bankislami', 'BankIslami Statement Layout', 'Authentic BankIslami Pakistan Statement Layout with Top Banner, Customer Info Box & Summary Block', '{\"supports_summary\": true, \"box_address\": true}', 1)
        ");
        echo "Migration SUCCESS: Inserted and activated new 'bankislami' statement format.\n";
    }

    // 2. Link BankIslami logo in master_banks if not set
    $db->execute("
        UPDATE `master_banks`
        SET `logo_path` = 'uploads/banks/bank_islami_logo.png'
        WHERE `bank_code` = 'BIPL' AND (`logo_path` IS NULL OR `logo_path` = '')
    ");
    echo "Migration SUCCESS: Master bank BIPL linked with logo.\n";

} catch (Exception $e) {
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
