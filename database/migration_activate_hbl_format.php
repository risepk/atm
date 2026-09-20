<?php
// database/migration_activate_hbl_format.php

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = Database::getInstance()->getPdo();

    // Check if 'hbl' format exists
    $stmt = $pdo->prepare("SELECT id, is_active FROM `statement_formats` WHERE `format_code` = 'hbl'");
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $update = $pdo->prepare("UPDATE `statement_formats` 
            SET `format_name` = 'HBL Statement Layout', 
                `description` = 'Habib Bank Limited dense institutional statement layout with Brought Forward opening balance and multi-line particulars', 
                `is_active` = 1 
            WHERE `format_code` = 'hbl'");
        $update->execute();
        echo "HBL statement format activated successfully (ID: {$existing['id']}).\n";
    } else {
        $insert = $pdo->prepare("INSERT INTO `statement_formats` 
            (`format_code`, `format_name`, `description`, `config`, `is_active`) 
            VALUES ('hbl', 'HBL Statement Layout', 'Habib Bank Limited dense institutional statement layout with Brought Forward opening balance and multi-line particulars', '{\"columns\":[\"date\",\"value\",\"particulars\",\"debit\",\"credit\",\"balance\"]}', 1)");
        $insert->execute();
        echo "HBL statement format inserted and activated successfully.\n";
    }
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
