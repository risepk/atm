<?php
// database/migration_add_account_identifier_and_statement_metadata.php

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Core/Database.php';

echo "=== MIGRATION: ADD ACCOUNT IDENTIFIER & STATEMENT METADATA ===\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

// 1. Check existing columns in accounts table
$cols = $pdo->query("DESCRIBE `accounts`")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('account_identifier', $cols)) {
    echo "Adding `account_identifier` column...\n";
    $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `account_identifier` VARCHAR(50) NULL AFTER `account_title`");
    $pdo->exec("ALTER TABLE `accounts` ADD KEY `idx_accounts_user_identifier` (`user_id`, `account_identifier`)");
    echo "Added `account_identifier` column.\n";
} else {
    echo "`account_identifier` already exists.\n";
}

if (!in_array('father_name', $cols)) {
    echo "Adding `father_name` column...\n";
    $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `father_name` VARCHAR(100) NULL AFTER `account_identifier`");
    echo "Added `father_name` column.\n";
} else {
    echo "`father_name` already exists.\n";
}

if (!in_array('account_type', $cols)) {
    echo "Adding `account_type` column...\n";
    $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `account_type` VARCHAR(50) NULL DEFAULT 'Current' AFTER `account_number`");
    echo "Added `account_type` column.\n";
} else {
    echo "`account_type` already exists.\n";
}

if (!in_array('iban', $cols)) {
    echo "Adding `iban` column...\n";
    $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `iban` VARCHAR(50) NULL AFTER `account_type`");
    echo "Added `iban` column.\n";
} else {
    echo "`iban` already exists.\n";
}

if (!in_array('address', $cols)) {
    echo "Adding `address` column...\n";
    $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `address` TEXT NULL AFTER `notes`");
    echo "Added `address` column.\n";
} else {
    echo "`address` already exists.\n";
}

// 2. Backfill existing accounts with identifiers like 13, 57, 91, A12
echo "Backfilling existing accounts with flexible identifiers...\n";
$accounts = $pdo->query("SELECT id, account_title, account_number, account_identifier FROM `accounts`")->fetchAll(PDO::FETCH_ASSOC);

$presetIdentifiers = [
    'Ali' => '13',
    'Ahmad' => '57',
    'Saeed' => '91',
    'Jawad' => 'A12'
];

foreach ($accounts as $acc) {
    if (empty($acc['account_identifier'])) {
        $assigned = null;
        foreach ($presetIdentifiers as $name => $idVal) {
            if (stripos($acc['account_title'], $name) !== false) {
                $assigned = $idVal;
                break;
            }
        }
        if (!$assigned) {
            $assigned = (string)$acc['id'];
        }
        $stmt = $pdo->prepare("UPDATE `accounts` SET `account_identifier` = ? WHERE `id` = ?");
        $stmt->execute([$assigned, $acc['id']]);
        echo " - Account #{$acc['id']} ('{$acc['account_title']}'): set identifier = '{$assigned}'\n";
    } else {
        echo " - Account #{$acc['id']} ('{$acc['account_title']}'): identifier already '{$acc['account_identifier']}'\n";
    }
}

// Ensure Ali has sample statement metadata for testing
$pdo->exec("UPDATE `accounts` SET 
    `father_name` = 'Muhammad Tariq',
    `account_type` = 'HBL FreedomAccount',
    `iban` = 'PK36HABB0004177901169101',
    `address` = 'PESHAWAR-CITY BRANCH, PESHAWAR'
    WHERE `account_identifier` = '13' AND (`father_name` IS NULL OR `father_name` = '')");

echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
