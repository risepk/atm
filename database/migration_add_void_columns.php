<?php
// database/migration_add_void_columns.php

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = Database::getInstance()->getPdo();

    $cols = $pdo->query("DESCRIBE `transactions`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('voided_at', $cols)) {
        $pdo->exec("ALTER TABLE `transactions` ADD COLUMN `voided_at` DATETIME NULL AFTER `status`");
        echo "Added 'voided_at' column.\n";
    }

    if (!in_array('voided_by', $cols)) {
        $pdo->exec("ALTER TABLE `transactions` ADD COLUMN `voided_by` BIGINT UNSIGNED NULL AFTER `voided_at`");
        echo "Added 'voided_by' column.\n";
    }

    if (!in_array('void_reason', $cols)) {
        $pdo->exec("ALTER TABLE `transactions` ADD COLUMN `void_reason` VARCHAR(255) NULL AFTER `voided_by`");
        echo "Added 'void_reason' column.\n";
    }

    echo "Void columns migration completed.\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
