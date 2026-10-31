<?php
// database/migration_add_bank_specific_and_regeneratable_templates.php
// ==============================================================================
// Migration: Add bank_id to narration_templates and is_regeneratable to narration_template_fields
// Idempotent and safe: preserves all existing data and templates intact.
// ==============================================================================

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    // 1. Add bank_id to narration_templates
    try {
        $pdo->query("SELECT `bank_id` FROM `narration_templates` LIMIT 1");
        echo "[INFO] Column bank_id already exists in narration_templates.\n";
    } catch (Throwable $e) {
        $pdo->exec("ALTER TABLE `narration_templates` ADD COLUMN `bank_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `category`");
        try {
            $pdo->exec("ALTER TABLE `narration_templates` ADD INDEX `idx_templates_bank_id` (`bank_id`)");
        } catch (Throwable $ie) {
            // Index might already exist
        }
        echo "[SUCCESS] Added column bank_id to narration_templates.\n";
    }

    // 2. Add is_regeneratable to narration_template_fields
    try {
        $pdo->query("SELECT `is_regeneratable` FROM `narration_template_fields` LIMIT 1");
        echo "[INFO] Column is_regeneratable already exists in narration_template_fields.\n";
    } catch (Throwable $e) {
        $pdo->exec("ALTER TABLE `narration_template_fields` ADD COLUMN `is_regeneratable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required`");
        echo "[SUCCESS] Added column is_regeneratable to narration_template_fields.\n";
    }

    echo "[MIGRATION COMPLETE] Schema updated successfully.\n";
} catch (Throwable $e) {
    echo "[MIGRATION FAILED] " . $e->getMessage() . "\n";
    exit(1);
}
