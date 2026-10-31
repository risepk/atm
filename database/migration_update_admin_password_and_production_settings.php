<?php
// database/migration_update_admin_password_and_production_settings.php
// ==============================================================================
// Production Migration: Update Admin Password and Production Readiness
// Sets admin password to 'Pakistan@786'
// ==============================================================================

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    $newPassword = 'Pakistan@786';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id, username FROM `users` WHERE `username` = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $update = $pdo->prepare("UPDATE `users` SET `password_hash` = ?, `updated_at` = NOW() WHERE `id` = ?");
        $update->execute([$newHash, $admin['id']]);
        echo "[MIGRATION SUCCESS] Admin password successfully updated to '{$newPassword}'.\n";
    } else {
        echo "[MIGRATION NOTICE] User 'admin' not found in database.\n";
    }
} catch (Exception $e) {
    echo "[MIGRATION FAILED] " . $e->getMessage() . "\n";
    exit(1);
}
