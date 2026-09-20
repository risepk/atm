<?php
// database/migration_add_ibft_rapid_templates.php

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = Database::getInstance()->getPdo();
    $now = date('Y-m-d H:i:s');

    function uuidv4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // 1. IBFT Outward Transfer
    $stmt = $pdo->prepare("SELECT id FROM `narration_templates` WHERE `code` = 'IBFT_OUTWARD'");
    $stmt->execute();
    $ibftOutId = $stmt->fetchColumn();

    if (!$ibftOutId) {
        $insert = $pdo->prepare("INSERT INTO `narration_templates` 
            (`uuid`, `name`, `code`, `category`, `pattern`, `description`, `version`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
            VALUES (?, 'IBFT Outward Transfer', 'IBFT_OUTWARD', 'transfer', 'IBFT OUTWARD TRANSFER - {bank} - A/C {account} - {title} - STAN: {stan}', 'Interbank outward funds transfer narration', 1, 1, 2, ?, ?)");
        $insert->execute([uuidv4(), $now, $now]);
        $ibftOutId = $pdo->lastInsertId();

        $fields = [
            ['bank', 'Beneficiary Bank', 'bank', 'Select bank', 'HBL', 1, null, 'master_banks', 1],
            ['account', 'Beneficiary Account / IBAN', 'account', 'e.g. 0417-79011691-01', '0417-79011691-01', 1, null, null, 2],
            ['title', 'Beneficiary Title', 'text', 'Beneficiary title', 'Usman Khan', 1, null, null, 3],
            ['stan', 'STAN', 'stan', '6-digit STAN', '', 1, '{"type":"stan","length":6}', null, 4]
        ];
        $fStmt = $pdo->prepare("INSERT INTO `narration_template_fields`
            (`template_id`, `field_key`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `generator_rule`, `options_source`, `sort_order`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($fields as $f) {
            $fStmt->execute([$ibftOutId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
        }
        echo "Created 'IBFT Outward Transfer' template (ID: {$ibftOutId}).\n";
    } else {
        echo "'IBFT Outward Transfer' template already exists (ID: {$ibftOutId}).\n";
    }

    // 2. IBFT Incoming Transfer
    $stmt2 = $pdo->prepare("SELECT id FROM `narration_templates` WHERE `code` = 'IBFT_INCOMING'");
    $stmt2->execute();
    $ibftInId = $stmt2->fetchColumn();

    if (!$ibftInId) {
        $insert2 = $pdo->prepare("INSERT INTO `narration_templates` 
            (`uuid`, `name`, `code`, `category`, `pattern`, `description`, `version`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
            VALUES (?, 'IBFT Incoming Transfer', 'IBFT_INCOMING', 'transfer', 'IBFT INCOMING TRANSFER - {bank} - A/C {account} - {title} - STAN: {stan}', 'Interbank incoming funds transfer narration', 1, 1, 3, ?, ?)");
        $insert2->execute([uuidv4(), $now, $now]);
        $ibftInId = $pdo->lastInsertId();

        $fields2 = [
            ['bank', 'Remitter Bank', 'bank', 'Select bank', 'HBL', 1, null, 'master_banks', 1],
            ['account', 'Remitter Account / IBAN', 'account', 'e.g. 0417-79011691-01', '0417-79011691-01', 1, null, null, 2],
            ['title', 'Remitter Title', 'text', 'Remitter title', 'Ahmad Ali', 1, null, null, 3],
            ['stan', 'STAN', 'stan', '6-digit STAN', '', 1, '{"type":"stan","length":6}', null, 4]
        ];
        $fStmt2 = $pdo->prepare("INSERT INTO `narration_template_fields`
            (`template_id`, `field_key`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `generator_rule`, `options_source`, `sort_order`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($fields2 as $f) {
            $fStmt2->execute([$ibftInId, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $now, $now]);
        }
        echo "Created 'IBFT Incoming Transfer' template (ID: {$ibftInId}).\n";
    } else {
        echo "'IBFT Incoming Transfer' template already exists (ID: {$ibftInId}).\n";
    }

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
