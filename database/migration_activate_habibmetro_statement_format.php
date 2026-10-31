<?php
// database/migration_activate_habibmetro_statement_format.php
// Activates 'habibmetro' format in statement_formats table

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    
    // Check if 'habibmetro' format exists
    $existing = $db->query("SELECT * FROM `statement_formats` WHERE `format_code` = 'habibmetro'")->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $db->execute("
            UPDATE `statement_formats` 
            SET `format_name` = 'Habib Metropolitan Bank Statement Layout',
                `description` = 'Authentic Habib Metropolitan Bank Statement Layout with Header & Metadata Grid',
                `is_active` = 1,
                `config` = '{\"supports_qr\": true, \"supports_end_of_date_balance\": true}'
            WHERE `format_code` = 'habibmetro'
        ");
        echo "Migration SUCCESS: Activated existing 'habibmetro' statement format (ID: {$existing['id']}).\n";
    } else {
        $db->execute("
            INSERT INTO `statement_formats` 
            (`format_code`, `format_name`, `description`, `config`, `is_active`) 
            VALUES 
            ('habibmetro', 'Habib Metropolitan Bank Statement Layout', 'Authentic Habib Metropolitan Bank Statement Layout with Header & Metadata Grid', '{\"supports_qr\": true, \"supports_end_of_date_balance\": true}', 1)
        ");
        echo "Migration SUCCESS: Inserted and activated new 'habibmetro' statement format.\n";
    }
} catch (Exception $e) {
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
