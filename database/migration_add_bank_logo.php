<?php
// database/migration_add_bank_logo.php

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `master_banks` LIKE 'logo_path'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE `master_banks` ADD COLUMN `logo_path` VARCHAR(255) NULL AFTER `swift_code`");
        echo "Column 'logo_path' added successfully to 'master_banks'.\n";
    } else {
        echo "Column 'logo_path' already exists in 'master_banks'.\n";
    }
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
