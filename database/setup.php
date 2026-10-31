<?php
// database/setup.php - Creates schema.sql, seeds.sql and initializes MariaDB

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$schemaSql = <<<SQL
-- ==========================================================
-- Account & Transaction Management System
-- Database Schema (DDL)
-- MySQL / MariaDB (InnoDB, utf8mb4_unicode_ci)
-- Note: Foreign keys are enforced in the application layer.
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `atm_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `atm_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_users_username` (`username`),
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_uuid` (`uuid`),
    KEY `idx_users_status_role` (`status`, `role`),
    KEY `idx_users_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Accounts Table
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `account_title` VARCHAR(100) NOT NULL,
    `account_identifier` VARCHAR(50) NULL,
    `account_number` VARCHAR(50) NULL,
    `bank_name` VARCHAR(100) NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'PKR',
    `initial_balance` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive', 'closed') NOT NULL DEFAULT 'active',
    `father_name` VARCHAR(100) NULL,
    `account_type` VARCHAR(50) NULL DEFAULT 'Current Account',
    `iban` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_accounts_uuid` (`uuid`),
    KEY `idx_accounts_user_id` (`user_id`),
    KEY `idx_accounts_user_identifier` (`user_id`, `account_identifier`),
    KEY `idx_accounts_user_status` (`user_id`, `status`),
    KEY `idx_accounts_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Narration Templates Table
CREATE TABLE IF NOT EXISTS `narration_templates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `pattern` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    UNIQUE KEY `idx_templates_uuid` (`uuid`),
    UNIQUE KEY `idx_templates_code` (`code`),
    KEY `idx_templates_active_sort` (`is_active`, `sort_order`),
    KEY `idx_templates_category` (`category`),
    KEY `idx_templates_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Narration Template Fields Table
CREATE TABLE IF NOT EXISTS `narration_template_fields` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `field_key` VARCHAR(50) NOT NULL,
    `field_label` VARCHAR(100) NOT NULL,
    `field_type` VARCHAR(50) NOT NULL,
    `placeholder` VARCHAR(100) NULL,
    `default_value` VARCHAR(255) NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `generator_rule` JSON NULL,
    `options_source` VARCHAR(100) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    KEY `idx_fields_template_sort` (`template_id`, `sort_order`),
    KEY `idx_fields_template_key` (`template_id`, `field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Narration Presets Table
CREATE TABLE IF NOT EXISTS `narration_presets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `preset_name` VARCHAR(100) NOT NULL,
    `field_values` JSON NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    UNIQUE KEY `idx_presets_uuid` (`uuid`),
    KEY `idx_presets_template_user` (`template_id`, `user_id`),
    KEY `idx_presets_user_active` (`user_id`, `is_active`),
    KEY `idx_presets_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `template_id` BIGINT UNSIGNED NULL,
    `template_version` INT UNSIGNED NULL,
    `transaction_date` DATE NOT NULL,
    `debit` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `credit` DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    `narration` TEXT NOT NULL,
    `template_payload` JSON NULL,
    `reference_no` VARCHAR(100) NULL,
    `status` ENUM('posted', 'voided') NOT NULL DEFAULT 'posted',
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `deleted_by` BIGINT UNSIGNED NULL,
    UNIQUE KEY `idx_tx_uuid` (`uuid`),
    KEY `idx_tx_account_date` (`account_id`, `transaction_date`),
    KEY `idx_tx_user_date` (`user_id`, `transaction_date`),
    KEY `idx_tx_account_status_deleted` (`account_id`, `status`, `deleted_at`),
    KEY `idx_tx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Master Banks Table
CREATE TABLE IF NOT EXISTS `master_banks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bank_name` VARCHAR(100) NOT NULL,
    `bank_code` VARCHAR(20) NOT NULL,
    `swift_code` VARCHAR(20) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `idx_banks_code` (`bank_code`),
    KEY `idx_banks_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    KEY `idx_audit_user_action` (`user_id`, `action`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Statement Formats Table
CREATE TABLE IF NOT EXISTS `statement_formats` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `format_code` VARCHAR(50) NOT NULL,
    `format_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `config` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `idx_statement_code` (`format_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

file_put_contents(__DIR__ . '/schema.sql', $schemaSql);
echo "Written schema.sql\n";

$pdo->exec($schemaSql);
echo "Executed schema DDL in MariaDB\n";

// Now prepare seeds
$adminHash = password_hash('Pakistan@786', PASSWORD_DEFAULT);
$userHash = password_hash('User@123456', PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

function gen_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Check if admin exists
$stmt = $pdo->query("SELECT COUNT(*) FROM `atm_db`.`users` WHERE username = 'admin'");
if ($stmt->fetchColumn() == 0) {
    $uuidAdmin = gen_uuid();
    $uuidUser1 = gen_uuid();
    $uuidUser2 = gen_uuid();

    $stmt = $pdo->prepare("INSERT INTO `atm_db`.`users` 
        (uuid, username, email, password_hash, full_name, role, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([$uuidAdmin, 'admin', 'admin@system.local', $adminHash, 'System Administrator', 'admin', 'active', $now, $now]);
    $adminId = $pdo->lastInsertId();

    $stmt->execute([$uuidUser1, 'user1', 'user1@system.local', $userHash, 'Ahmed Khan', 'user', 'active', $now, $now]);
    $user1Id = $pdo->lastInsertId();

    $stmt->execute([$uuidUser2, 'user2', 'user2@system.local', $userHash, 'Bilal Tariq', 'user', 'active', $now, $now]);
    $user2Id = $pdo->lastInsertId();
    echo "Seeded users: admin, user1, user2\n";
} else {
    $user1Id = $pdo->query("SELECT id FROM `atm_db`.`users` WHERE username = 'user1'")->fetchColumn();
    $adminId = $pdo->query("SELECT id FROM `atm_db`.`users` WHERE username = 'admin'")->fetchColumn();
}

// Seed Master Banks
$banks = [
    ['Habib Bank Limited', 'HBL', 'HABBPKKA', 1],
    ['United Bank Limited', 'UBL', 'UNILPKKA', 2],
    ['Meezan Bank Limited', 'MEEZAN', 'MEZNPKKA', 3],
    ['Bank Alfalah Limited', 'BAFL', 'ALFHPKKA', 4],
    ['Allied Bank Limited', 'ABL', 'ABPAPKKAA', 5],
    ['MCB Bank Limited', 'MCB', 'MUCBPKKA', 6],
    ['Standard Chartered Bank', 'SCB', 'SCBLPKKA', 7],
    ['Faysal Bank Limited', 'FBL', 'FAYSPKKA', 8],
    ['The Bank of Punjab', 'BOP', 'BOPKPKKA', 9],
    ['National Bank of Pakistan', 'NBP', 'NBPAPKKAA', 10]
];

$stmtBank = $pdo->prepare("INSERT IGNORE INTO `atm_db`.`master_banks` (bank_name, bank_code, swift_code, is_active, sort_order) VALUES (?, ?, ?, 1, ?)");
foreach ($banks as $b) {
    $stmtBank->execute([$b[0], $b[1], $b[2], $b[3]]);
}
echo "Seeded master banks\n";

// Seed Example Accounts for user1
$stmtAccCount = $pdo->query("SELECT COUNT(*) FROM `atm_db`.`accounts` WHERE user_id = " . intval($user1Id));
if ($stmtAccCount->fetchColumn() == 0) {
    $accounts = [
        ['Main HBL Account', '00427901584203', 'Habib Bank Limited', 100000.00],
        ['Operations UBL Account', '09241002345671', 'United Bank Limited', 50000.00],
        ['Allied Trading Account', '00100458920101', 'Allied Bank Limited', 25000.00],
        ['Meezan Corporate Account', '01020104889211', 'Meezan Bank Limited', 80000.00]
    ];
    $stmtAcc = $pdo->prepare("INSERT INTO `atm_db`.`accounts` 
        (uuid, user_id, account_title, account_number, bank_name, currency, initial_balance, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'PKR', ?, 'active', ?, ?)");
    foreach ($accounts as $acc) {
        $stmtAcc->execute([gen_uuid(), $user1Id, $acc[0], $acc[1], $acc[2], $acc[3], $now, $now]);
    }
    echo "Seeded accounts 1, 2, 3, 4 for user1\n";
}

// Seed Narration Templates & Fields
$stmtTplCount = $pdo->query("SELECT COUNT(*) FROM `atm_db`.`narration_templates`");
if ($stmtTplCount->fetchColumn() == 0) {
    // 1. IBFT
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'IBFT Transfer', 'IBFT', 'transfer', 'IBFT From {sender_name} - {sender_bank} - {sender_account} to {receiver_name} - {receiver_bank} - {receiver_account}', 'Inter Bank Funds Transfer description pattern', 1, 1, 1, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $ibftId = $pdo->lastInsertId();

    $fieldsIbft = [
        ['sender_name', 'Sender Name', 'text', 'Sender full name', '', 1, null, null, 1],
        ['sender_bank', 'Sender Bank', 'bank', 'Select sender bank', '', 1, null, 'master_banks', 2],
        ['sender_account', 'Sender Account / IBAN', 'account', 'Sender account or IBAN', '', 1, null, null, 3],
        ['receiver_name', 'Receiver Name', 'text', 'Receiver beneficiary name', '', 1, null, null, 4],
        ['receiver_bank', 'Receiver Bank', 'bank', 'Select receiver bank', '', 1, null, 'master_banks', 5],
        ['receiver_account', 'Receiver Account / IBAN', 'account', 'Receiver account or IBAN', '', 1, null, null, 6]
    ];
    $stmtF = $pdo->prepare("INSERT INTO `atm_db`.`narration_template_fields` 
        (template_id, field_key, field_label, field_type, placeholder, default_value, is_required, generator_rule, options_source, sort_order, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($fieldsIbft as $f) {
        $stmtF->execute([$ibftId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    // Seed Presets for IBFT
    $presets = [
        ['Apex Fabrics', json_encode([
            'sender_name' => 'Apex Fabrics',
            'sender_bank' => 'Meezan Bank Limited',
            'sender_account' => '01600103064767',
            'receiver_name' => 'Prime Enterprises',
            'receiver_bank' => 'Bank Alfalah Limited',
            'receiver_account' => '01861005149674'
        ])],
        ['Usmani Trading', json_encode([
            'sender_name' => 'Usmani Trading',
            'sender_bank' => 'Habib Bank Limited',
            'sender_account' => '00427901584203',
            'receiver_name' => 'Crescent Logistics',
            'receiver_bank' => 'Meezan Bank Limited',
            'receiver_account' => '01020104889211'
        ])],
        ['Prime Logistics', json_encode([
            'sender_name' => 'Prime Logistics',
            'sender_bank' => 'Allied Bank Limited',
            'sender_account' => '00100458920101',
            'receiver_name' => 'Zenith Textiles',
            'receiver_bank' => 'United Bank Limited',
            'receiver_account' => '09241002345671'
        ])]
    ];
    $stmtP = $pdo->prepare("INSERT INTO `atm_db`.`narration_presets` 
        (uuid, template_id, user_id, preset_name, field_values, is_active, sort_order, created_at, updated_at) 
        VALUES (?, ?, NULL, ?, ?, 1, ?, ?, ?)");
    $pOrder = 1;
    foreach ($presets as $p) {
        $stmtP->execute([gen_uuid(), $ibftId, $p[0], $p[1], $pOrder++, $now, $now]);
    }

    // 2. Raast P2P
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'Raast P2P Funds Transfer', 'RAAST_P2P', 'transfer', 'Raast P2P Funds Transfer from {sender_name} {sender_iban} {code} [Ref: {reference_no}] <TRANS.ID: {transaction_id}>', 'Instant Raast payment description', 1, 1, 2, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $raastId = $pdo->lastInsertId();

    $fieldsRaast = [
        ['sender_name', 'Sender Name', 'text', 'Sender name', '', 1, null, null, 1],
        ['sender_iban', 'Sender IBAN', 'iban', 'PK.. (24 digits)', '', 1, null, null, 2],
        ['code', 'Code', 'text', 'Country or transfer code', 'PK', 0, null, null, 3],
        ['reference_no', 'Reference No', 'reference_no', 'Bank Reference No', '', 1, json_encode(['type' => 'bank_ref', 'prefix' => 'BAHLPKKA', 'length' => 16]), null, 4],
        ['transaction_id', 'Transaction ID', 'transaction_id', 'Raast Transaction ID', '', 1, json_encode(['type' => 'alphanumeric', 'prefix' => 'FT26', 'length' => 16]), null, 5]
    ];
    foreach ($fieldsRaast as $f) {
        $stmtF->execute([$raastId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    // 3. Funds Transfer
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'Funds Transfer', 'FUNDS_TRANSFER', 'transfer', 'Funds Transfer From {sender_name} - {sender_account} to {receiver_name} - {receiver_account}', 'Internal bank transfer', 1, 1, 3, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $ftId = $pdo->lastInsertId();

    $fieldsFt = [
        ['sender_name', 'Sender Name', 'text', 'Sender name', '', 1, null, null, 1],
        ['sender_account', 'Sender Account', 'account', 'Sender account number', '', 1, null, null, 2],
        ['receiver_name', 'Receiver Name', 'text', 'Receiver name', '', 1, null, null, 3],
        ['receiver_account', 'Receiver Account', 'account', 'Receiver account number', '', 1, null, null, 4]
    ];
    foreach ($fieldsFt as $f) {
        $stmtF->execute([$ftId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    // 4. ATM Cash Withdrawal
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'ATM Cash Withdrawal', 'ATM_CASH', 'cash', 'Cash withdrawal from ATM | {atm_code} | STAN: {stan}', 'ATM transaction narration', 1, 1, 4, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $atmId = $pdo->lastInsertId();

    $fieldsAtm = [
        ['atm_code', 'ATM Code / Location', 'text', 'e.g. ATM-042-KHI', 'ATM-042-KHI', 1, null, null, 1],
        ['stan', 'STAN (System Trace Audit Number)', 'stan', '6-digit STAN', '', 1, json_encode(['type' => 'stan', 'length' => 6]), null, 2]
    ];
    foreach ($fieldsAtm as $f) {
        $stmtF->execute([$atmId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    // 5. Credit Card Bill Payment
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'Credit Card Bill Payment', 'CREDIT_CARD_BILL', 'billing', 'Alfalah Credit Card Bill Payment | {masked_card_number}', 'Credit card payment narration', 1, 1, 5, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $ccId = $pdo->lastInsertId();

    $fieldsCc = [
        ['masked_card_number', 'Masked Card Number', 'card_number', 'XXXX-XXXX-XXXX-1234', '5120-XXXX-XXXX-8921', 1, null, null, 1]
    ];
    foreach ($fieldsCc as $f) {
        $stmtF->execute([$ccId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    // 6. Bank Charge
    $uuid = gen_uuid();
    $pdo->prepare("INSERT INTO `atm_db`.`narration_templates` 
        (uuid, name, code, category, pattern, description, version, is_active, sort_order, created_by, created_at, updated_at) 
        VALUES (?, 'Bank Charge', 'BANK_CHARGE', 'fee', '{description}', 'Service charges and tax deductions', 1, 1, 6, ?, ?, ?)")
        ->execute([$uuid, $adminId, $now, $now]);
    $bcId = $pdo->lastInsertId();

    $fieldsBc = [
        ['description', 'Charge Description', 'dropdown', 'Select charge type', 'Annual SMS Alert Fee', 1, null, 'Annual SMS Alert Fee,Withholding Tax u/s 236P,Excise Duty / FED on Transactions,Cheque Book Issuance Fee,e-Banking Maintenance Fee', 1]
    ];
    foreach ($fieldsBc as $f) {
        $stmtF->execute([$bcId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
    }

    echo "Seeded 6 narration templates and dynamic fields\n";
}

// Seed Statement Formats
$stmtFmtCount = $pdo->query("SELECT COUNT(*) FROM `atm_db`.`statement_formats`");
if ($stmtFmtCount->fetchColumn() == 0) {
    $formats = [
        ['standard', 'Standard Bank Statement', 'Clean standard ledger statement with running balance', json_encode(['columns' => ['date', 'narration', 'debit', 'credit', 'balance']])],
        ['hbl', 'HBL Format Statement', 'Habib Bank Limited format with transaction references and branch codes', json_encode(['header_logo' => 'hbl.png', 'branch_code' => '0042'])],
        ['meezan', 'Meezan Bank Shariah Statement', 'Meezan Bank Islamic banking statement format', json_encode(['currency_symbol' => 'PKR', 'shariah_compliant' => true])]
    ];
    $stmtStmt = $pdo->prepare("INSERT INTO `atm_db`.`statement_formats` (format_code, format_name, description, config, is_active) VALUES (?, ?, ?, ?, 1)");
    foreach ($formats as $fmt) {
        $stmtStmt->execute([$fmt[0], $fmt[1], $fmt[2], $fmt[3]]);
    }
    echo "Seeded statement formats\n";
}

echo "Database initialization and seeding completed successfully!\n";
