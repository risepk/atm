<?php
// database/migration_create_narration_custom_types.php
// ==============================================================================
// Migration: Create narration_custom_types table for user-defined patterns
// Safe and idempotent: preserves all existing data and templates intact.
// ==============================================================================

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `narration_custom_types` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type_code` VARCHAR(50) NOT NULL UNIQUE,
            `type_name` VARCHAR(100) NOT NULL,
            `pattern` VARCHAR(255) NOT NULL,
            `description` VARCHAR(255) NULL,
            `is_regeneratable` TINYINT(1) NOT NULL DEFAULT 1,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            INDEX `idx_custom_types_code` (`type_code`),
            INDEX `idx_custom_types_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[SUCCESS] Table narration_custom_types verified/created.\n";

    // Check if initial custom types exist; seed standard banking patterns if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `narration_custom_types`")->fetchColumn();
    if ($count === 0) {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            INSERT INTO `narration_custom_types` 
            (`type_code`, `type_name`, `pattern`, `description`, `is_regeneratable`, `is_active`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, ?, 1, 1, ?, ?)
        ");
        $defaults = [
            ['meezan_tid', 'Meezan Bank TID', 'FT26@@######', 'Meezan Bank FT transaction reference (e.g. FT26KR839201)'],
            ['raast_ref', 'Raast / 1Link Reference', 'BAHLPKKA########', 'Raast & 1Link 8-digit transaction reference'],
            ['hmb_branch_ref', 'Habib Metro Branch Ref', 'HMB-ISB-########', 'Habib Metropolitan branch reference with prefix'],
            ['custom_branch_acc', 'Custom Branch Masked A/C', '0417-XXXX####-01', 'Account with custom fixed branch and suffix'],
            ['atm_terminal_id', 'ATM Terminal Code', 'ATM-###-@@@', 'Custom ATM terminal code (e.g. ATM-042-KHI)']
        ];
        foreach ($defaults as $d) {
            $stmt->execute([$d[0], $d[1], $d[2], $d[3], $now, $now]);
        }
        echo "[SUCCESS] Seeded 5 standard starter custom types.\n";
    }

    echo "[MIGRATION COMPLETE] narration_custom_types ready.\n";
} catch (Throwable $e) {
    echo "[MIGRATION FAILED] " . $e->getMessage() . "\n";
    exit(1);
}
