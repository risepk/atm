<?php
// database/migration_expand_account_title_and_identifier_length.php
// ==============================================================================
// Migration: Expand account_title and account_identifier length
// Allows unrestricted account numbers, codes, and titles (>100 chars)
// ==============================================================================

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    $pdo->exec("ALTER TABLE `accounts` MODIFY COLUMN `account_title` VARCHAR(255) NOT NULL");
    $pdo->exec("ALTER TABLE `accounts` MODIFY COLUMN `account_identifier` VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE `accounts` MODIFY COLUMN `account_number` VARCHAR(100) NULL");

    echo "[MIGRATION SUCCESS] Account title, identifier, and number columns expanded successfully.\n";
} catch (Exception $e) {
    echo "[MIGRATION FAILED] " . $e->getMessage() . "\n";
    exit(1);
}
