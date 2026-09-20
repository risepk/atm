<?php
// database/clean_demo_accounts.php - Purge dev/test accounts & establish 4 clean demo accounts

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Account.php';
require_once __DIR__ . '/../app/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../app/Helpers/CurrencyHelper.php';

echo "========================================================\n";
echo "Cleaning Development Data & Setting Up 4 Demo Accounts\n";
echo "========================================================\n\n";

$db = Database::getInstance()->getPdo();

// 1. Locate user1
$stmt = $db->prepare("SELECT id FROM users WHERE username = 'user1'");
$stmt->execute();
$user1Id = (int)$stmt->fetchColumn();

if (!$user1Id) {
    die("Error: User 'user1' not found in database.\n");
}
echo "Target Demo User: user1 (ID: {$user1Id})\n";

// 2. Define the 4 target demo accounts
$targetAccounts = [
    [
        'title' => 'Ali',
        'account_identifier' => '13',
        'bank' => 'Habib Bank Limited',
        'account_number' => '0417-79011691-01',
        'father_name' => 'Muhammad Tariq',
        'account_type' => 'HBL FreedomAccount',
        'iban' => 'PK36HABB0004177901169101',
        'address' => 'PESHAWAR-CITY BRANCH, PESHAWAR',
        'initial_balance' => '100000.00',
        'notes' => 'Primary Operating Account'
    ],
    [
        'title' => 'Ahmad',
        'account_identifier' => '57',
        'bank' => 'United Bank Limited',
        'account_number' => '1102-88239102-02',
        'father_name' => 'Ahmad Jan',
        'account_type' => 'Business Plus',
        'iban' => 'PK45UNIL0011028823910202',
        'address' => 'Kasi Road Branch, Quetta',
        'initial_balance' => '150000.00',
        'notes' => 'Commercial Trading Account'
    ],
    [
        'title' => 'Saeed',
        'account_identifier' => '91',
        'bank' => 'Meezan Bank Limited',
        'account_number' => '0104-55192038-03',
        'father_name' => 'Saeed Anwar',
        'account_type' => 'Meezan Current',
        'iban' => 'PK36MEZN0001045519203803',
        'address' => 'Main Branch, Karachi',
        'initial_balance' => '80000.00',
        'notes' => 'Islamic Business Portfolio'
    ],
    [
        'title' => 'Jawad',
        'account_identifier' => 'A12',
        'bank' => 'Bank Alfalah Limited',
        'account_number' => '0229-33819204-04',
        'father_name' => 'Jawad Khan',
        'account_type' => 'Alfalah Current',
        'iban' => 'PK78ALFH0002293381920404',
        'address' => 'City Branch, Lahore',
        'initial_balance' => '120000.00',
        'notes' => 'Corporate Liquidity Account'
    ]
];

$demoAccountIds = [];

foreach ($targetAccounts as $acc) {
    // Check if account with this title exists for user1
    $checkStmt = $db->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_title = ? LIMIT 1");
    $checkStmt->execute([$user1Id, $acc['title']]);
    $existingId = $checkStmt->fetchColumn();

    if ($existingId) {
        $updateStmt = $db->prepare("
            UPDATE accounts 
            SET bank_name = ?, account_number = ?, account_identifier = ?, father_name = ?, account_type = ?, iban = ?, address = ?, initial_balance = ?, status = 'active', notes = ?, deleted_at = NULL, deleted_by = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([
            $acc['bank'],
            $acc['account_number'],
            $acc['account_identifier'],
            $acc['father_name'],
            $acc['account_type'],
            $acc['iban'],
            $acc['address'],
            $acc['initial_balance'],
            $acc['notes'],
            $existingId
        ]);
        $demoAccountIds[] = (int)$existingId;
        echo "  [UPDATED] Account '{$acc['title']}' (ID: #{$existingId}, Ident: '{$acc['account_identifier']}') at {$acc['bank']}\n";
    } else {
        $insertStmt = $db->prepare("
            INSERT INTO accounts (uuid, user_id, account_title, bank_name, account_number, account_identifier, father_name, account_type, iban, address, initial_balance, currency, status, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PKR', 'active', ?, NOW(), NOW())
        ");
        $uuid = SecurityHelper::uuid();
        $insertStmt->execute([
            $uuid,
            $user1Id,
            $acc['title'],
            $acc['bank'],
            $acc['account_number'],
            $acc['account_identifier'],
            $acc['father_name'],
            $acc['account_type'],
            $acc['iban'],
            $acc['address'],
            $acc['initial_balance'],
            $acc['notes']
        ]);
        $newId = (int)$db->lastInsertId();
        $demoAccountIds[] = $newId;
        echo "  [CREATED] Account '{$acc['title']}' (ID: #{$newId}, Ident: '{$acc['account_identifier']}') at {$acc['bank']}\n";
    }
}

// 3. Purge non-demo accounts & their transactions
$idsPlaceholder = implode(',', array_fill(0, count($demoAccountIds), '?'));

// Count accounts to delete
$countStmt = $db->prepare("SELECT COUNT(*) FROM accounts WHERE id NOT IN ($idsPlaceholder)");
$countStmt->execute($demoAccountIds);
$nonDemoCount = (int)$countStmt->fetchColumn();

if ($nonDemoCount > 0) {
    echo "\nPurging {$nonDemoCount} non-demo/test accounts...\n";

    // Delete transactions for non-demo accounts
    $delTxStmt = $db->prepare("DELETE FROM transactions WHERE account_id NOT IN ($idsPlaceholder)");
    $delTxStmt->execute($demoAccountIds);
    $deletedTx = $delTxStmt->rowCount();
    echo "  - Deleted {$deletedTx} obsolete test transactions.\n";

    // Delete non-demo accounts
    $delAccStmt = $db->prepare("DELETE FROM accounts WHERE id NOT IN ($idsPlaceholder)");
    $delAccStmt->execute($demoAccountIds);
    $deletedAcc = $delAccStmt->rowCount();
    echo "  - Deleted {$deletedAcc} obsolete test accounts.\n";
}

// 4. Reset transactions for demo accounts to ensure clean opening balances
$cleanTxStmt = $db->prepare("DELETE FROM transactions WHERE account_id IN ($idsPlaceholder)");
$cleanTxStmt->execute($demoAccountIds);
echo "  - Cleared existing test transactions for demo accounts to ensure clean opening balances.\n";

// 5. Clean up temporary test banks created during test runs (id >= 28)
$cleanBanksStmt = $db->prepare("
    DELETE FROM master_banks 
    WHERE id >= 28 AND (
        bank_name LIKE 'Test%' 
        OR bank_name LIKE 'Logo Test Bank%' 
        OR bank_name LIKE 'Statement Logo Bank%' 
        OR bank_name LIKE 'Updated Bank Name%'
        OR bank_name LIKE 'HBL Logo Test Bank%'
    )
");
$cleanBanksStmt->execute();
$cleanedBanks = $cleanBanksStmt->rowCount();
echo "  - Cleaned {$cleanedBanks} temporary test banks from master_banks.\n";

// 6. Clean up presets containing obsolete developer/personal names
$cleanPresetsStmt = $db->prepare("
    DELETE FROM narration_presets 
    WHERE preset_name LIKE '%Rahman%' 
       OR preset_name LIKE '%Chota Murad%' 
       OR field_values LIKE '%Rahman%' 
       OR field_values LIKE '%Chota Murad%'
");
$cleanPresetsStmt->execute();
$cleanedPresets = $cleanPresetsStmt->rowCount();
echo "  - Cleaned {$cleanedPresets} obsolete presets containing personal development names.\n";

// 7. Verify remaining accounts
echo "\n--- VERIFICATION: CURRENT ACCOUNTS IN SYSTEM ---\n";
$accModel = new Account();
$remaining = $accModel->getUserAccounts($user1Id);
foreach ($remaining as $r) {
    echo "#{$r['id']} [User {$r['user_id']}] {$r['account_title']} ({$r['bank_name']} - {$r['account_number']}) Balance: PKR {$r['current_balance']} [{$r['status']}]\n";
}

echo "\nCleanup completed successfully! Exactly " . count($remaining) . " clean accounts remain.\n";
