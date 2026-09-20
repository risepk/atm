<?php
// database/migration_activate_online_format.php

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = Database::getInstance()->getPdo();

    // Check if 'online' format exists
    $stmt = $pdo->prepare("SELECT id, is_active FROM `statement_formats` WHERE `format_code` = 'online'");
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $update = $pdo->prepare("UPDATE `statement_formats` 
            SET `format_name` = 'Online Banking E-Statement', 
                `description` = 'Modern digital banking e-statement format with channel details, net movement and summary analytics', 
                `is_active` = 1 
            WHERE `format_code` = 'online'");
        $update->execute();
        echo "Online statement format activated successfully (ID: {$existing['id']}).\n";
    } else {
        $insert = $pdo->prepare("INSERT INTO `statement_formats` 
            (`format_code`, `format_name`, `description`, `config`, `is_active`) 
            VALUES ('online', 'Online Banking E-Statement', 'Modern digital banking e-statement format with channel details, net movement and summary analytics', '{\"columns\":[\"date\",\"value_date\",\"description\",\"ref_no\",\"debit\",\"credit\",\"balance\",\"channel\"]}', 1)");
        $insert->execute();
        echo "Online statement format inserted and activated successfully.\n";
    }
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
