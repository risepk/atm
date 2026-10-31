<?php
// database/migration_add_allied_statement_format.php

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    // Check if format already exists
    $stmt = $pdo->prepare("SELECT id FROM `statement_formats` WHERE `format_code` = ?");
    $stmt->execute(['allied']);
    $format = $stmt->fetch();

    if (!$format) {
        $insert = $pdo->prepare("INSERT INTO `statement_formats` (`format_code`, `format_name`, `is_active`) VALUES (?, ?, 1)");
        $insert->execute(['allied', 'Allied Bank Statement Layout']);
        echo "Statement format 'allied' (Allied Bank Statement Layout) inserted successfully.\n";
    } else {
        echo "Statement format 'allied' already exists.\n";
    }
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
