<?php
// database/migration_activate_meezan_statement_format.php
// Activates 'meezan' format in statement_formats table

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    
    // Check if 'meezan' format exists
    $existing = $db->query("SELECT * FROM `statement_formats` WHERE `format_code` = 'meezan'")->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $db->execute("
            UPDATE `statement_formats` 
            SET `format_name` = 'Meezan Bank Statement Layout',
                `description` = 'Authentic Meezan Bank Quick Account Transactions Layout',
                `is_active` = 1,
                `config` = '{\"shariah_compliant\": true, \"supports_end_of_date_balance\": true}'
            WHERE `format_code` = 'meezan'
        ");
        echo "Migration SUCCESS: Activated existing 'meezan' statement format (ID: {$existing['id']}).\n";
    } else {
        $db->execute("
            INSERT INTO `statement_formats` 
            (`format_code`, `format_name`, `description`, `config`, `is_active`) 
            VALUES 
            ('meezan', 'Meezan Bank Statement Layout', 'Authentic Meezan Bank Quick Account Transactions Layout', '{\"shariah_compliant\": true, \"supports_end_of_date_balance\": true}', 1)
        ");
        echo "Migration SUCCESS: Inserted and activated new 'meezan' statement format.\n";
    }
} catch (Exception $e) {
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
