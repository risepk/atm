<?php
// database/migration_clean_demo_names.php
// Sanitizes personal/demo names across database records in atm_db

require_once __DIR__ . '/../app/Core/Database.php';

echo "=== Running Demo Name Sanitization Migration ===\n";

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. Update Users
    $userUpdates = [
        'user1' => 'Ahmed Khan',
        'user2' => 'Bilal Tariq'
    ];
    foreach ($userUpdates as $username => $cleanName) {
        $db->query("UPDATE `users` SET `full_name` = ? WHERE `username` = ?", [$cleanName, $username]);
        echo "Updated user '{$username}' to '{$cleanName}'\n";
    }

    // 2. Update Accounts
    $accUpdates = [
        'Rahman HBL' => 'Main HBL Account',
        'Rahman UBL' => 'Operations UBL Account',
        'ALI Allied' => 'Allied Trading Account',
        'Chota Murad Meezan' => 'Meezan Corporate Account'
    ];
    foreach ($accUpdates as $oldTitle => $newTitle) {
        $db->query("UPDATE `accounts` SET `account_title` = ? WHERE `account_title` = ?", [$newTitle, $oldTitle]);
        echo "Updated account '{$oldTitle}' -> '{$newTitle}'\n";
    }

    // 3. Update Presets
    $presets = $db->fetchAll("SELECT id, preset_name, field_values FROM `narration_presets`");
    foreach ($presets as $p) {
        $name = $p['preset_name'];
        $rawVals = $p['field_values'];
        
        $cleanedName = str_replace(
            ['G.K FABRICS', 'N.USMANI TRADING', 'MUHIB RAHMAN', 'CHOTA MURAD'],
            ['Apex Fabrics', 'Usmani Trading', 'Prime Logistics', 'Zenith Textiles'],
            $name
        );
        $cleanedVals = str_replace(
            ['G.K FABRICS', 'RAHMAN UD DIN', 'MUHIB RAHMAN', 'CHOTA MURAD'],
            ['Apex Fabrics', 'Prime Enterprises', 'Crescent Logistics', 'Zenith Textiles'],
            $rawVals
        );

        if ($cleanedName !== $name || $cleanedVals !== $rawVals) {
            $db->query("UPDATE `narration_presets` SET `preset_name` = ?, `field_values` = ? WHERE `id` = ?", [$cleanedName, $cleanedVals, $p['id']]);
            echo "Updated preset #{$p['id']}: '{$name}' -> '{$cleanedName}'\n";
        }
    }

    // 4. Update Transactions
    $transactions = $db->fetchAll("SELECT id, narration FROM `transactions` WHERE narration LIKE '%Rahman%' OR narration LIKE '%MURAD%' OR narration LIKE '%FABRICS%' OR narration LIKE '%MUHIB%'");
    foreach ($transactions as $tx) {
        $cleaned = str_replace(
            ['G.K FABRICS', 'RAHMAN UD DIN', 'MUHIB RAHMAN', 'CHOTA MURAD', 'Rahman', 'Muhammad Rahman'],
            ['Apex Fabrics', 'Prime Enterprises', 'Crescent Logistics', 'Zenith Textiles', 'Ahmed Khan', 'Ahmed Khan'],
            $tx['narration']
        );
        $db->query("UPDATE `transactions` SET `narration` = ? WHERE `id` = ?", [$cleaned, $tx['id']]);
        echo "Updated transaction #{$tx['id']} narration.\n";
    }

    $db->commit();
    echo "=== Demo Name Sanitization Completed Successfully! ===\n";
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "Error running migration: " . $e->getMessage() . "\n";
    exit(1);
}
