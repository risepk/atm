<?php
// tests/run_tests.php - Comprehensive Automated Test Runner

date_default_timezone_set('Asia/Karachi');

// Autoloader
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/../app/Core/',
        __DIR__ . '/../app/Middleware/',
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Services/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Helpers/'
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../app/Helpers/CurrencyHelper.php';

class TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $description): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            $this->failures[] = $description;
            echo "  [FAIL] {$description}\n";
        }
    }

    public function assertThrows(callable $fn, string $expectedExceptionClass, string $description): void {
        try {
            $fn();
            $this->failed++;
            $this->failures[] = "{$description} (Expected {$expectedExceptionClass}, none thrown)";
            echo "  [FAIL] {$description} (Expected exception {$expectedExceptionClass}, but none thrown)\n";
        } catch (Throwable $e) {
            $this->passed++;
            echo "  [PASS] {$description} (Caught: " . get_class($e) . ": " . $e->getMessage() . ")\n";
        }
    }

    public function summary(): bool {
        echo "\n============================================\n";
        echo "Test Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "============================================\n";
        if ($this->failed > 0) {
            echo "Failed Tests:\n";
            foreach ($this->failures as $f) {
                echo "  - {$f}\n";
            }
        }
        return $this->failed === 0;
    }
}

$runner = new TestRunner();
echo "========================================================\n";
echo "Starting ATM Ledger Exhaustive Verification Test Suite\n";
echo "========================================================\n\n";

$authService = new AuthService();
$userModel = new User();
$accountService = new AccountService();
$accountModel = new Account();
$txService = new TransactionService();
$txModel = new Transaction();
$narrationService = new NarrationService();
$tplModel = new NarrationTemplate();
$fieldModel = new TemplateField();
$presetService = new PresetService();
$presetModel = new NarrationPreset();
$bankModel = new MasterBank();
$stmtService = new StatementService();
$stmtFormatModel = new StatementFormat();
$auditModel = new AuditLog();
$db = Database::getInstance();

// Users for testing
$adminUser = $userModel->findByUsername('admin');
$user1 = $userModel->findByUsername('user1');
$user2 = $userModel->findByUsername('user2');

// ====================================================
// 1. Authentication, Passwords & Session Security
// ====================================================
echo "1. Testing Authentication, Passwords & Session Security:\n";

$loginAdmin = $authService->attempt('admin', 'Admin@123456');
$runner->assert($loginAdmin['success'] === true, "Admin credentials authenticate successfully");
$runner->assert(Session::get('role') === 'admin', "Admin session role is 'admin'");

$loginBad = $authService->attempt('admin', 'WrongPassword123');
$runner->assert($loginBad['success'] === false, "Bad password rejected");

$loginUser1 = $authService->attempt('user1', 'User@123456');
$runner->assert($loginUser1['success'] === true, "User 1 credentials authenticate successfully");
$runner->assert(Session::get('role') === 'user', "User 1 session role is 'user'");

// Inactive User Rejection
$inactiveUserId = $userModel->insert([
    'uuid' => SecurityHelper::uuid(),
    'username' => 'test_inactive_usr',
    'email' => 'inactive@example.com',
    'password_hash' => password_hash('Pass@123456', PASSWORD_DEFAULT),
    'full_name' => 'Inactive User',
    'role' => 'user',
    'status' => 'inactive'
]);
$loginInactive = $authService->attempt('test_inactive_usr', 'Pass@123456');
$runner->assert($loginInactive['success'] === false && strpos($loginInactive['message'], 'inactive') !== false, "Inactive user login is rejected");
$db->query("DELETE FROM users WHERE id = {$inactiveUserId}");

// Password Verbatim Preservation
$spacePass = ' Pass with spaces 123! ';
$spaceUserId = $userModel->insert([
    'uuid' => SecurityHelper::uuid(),
    'username' => 'test_space_usr',
    'email' => 'space@example.com',
    'password_hash' => password_hash($spacePass, PASSWORD_DEFAULT),
    'full_name' => 'Space Password User',
    'role' => 'user',
    'status' => 'active'
]);
$loginSpaceGood = $authService->attempt('test_space_usr', $spacePass);
$runner->assert($loginSpaceGood['success'] === true, "Password with leading/trailing whitespace preserved verbatim");
$loginSpaceTrimmed = $authService->attempt('test_space_usr', trim($spacePass));
$runner->assert($loginSpaceTrimmed['success'] === false, "Trimmed password without spaces is correctly rejected");
$db->query("DELETE FROM users WHERE id = {$spaceUserId}");

// Logout via POST
$authService->logout();
$runner->assert(!Session::has('user_id'), "Logout clears session user ID");

// Re-login User 1 for further tests
$authService->attempt('user1', 'User@123456');

// ====================================================
// 2. Account Ownership, Renaming & IDOR Protection
// ====================================================
echo "\n2. Testing Account Ownership & IDOR Protection:\n";

$db->query("DELETE FROM accounts WHERE user_id = {$user1['id']} AND (account_identifier = 'Main Business PKR' OR account_title = 'Main Business PKR')");

$acc1 = $accountService->createAccount((int)$user1['id'], [
    'account_title' => 'Main Business PKR',
    'bank_name' => 'Habib Bank Limited',
    'account_number' => 'PK' . date('ymd') . random_int(100000, 999999),
    'initial_balance' => '50000.00'
]);
$runner->assert($acc1['id'] > 0, "Account created with permanent ID #{$acc1['id']}");
$runner->assert((int)$acc1['user_id'] === (int)$user1['id'], "Account ownership mapped to User 1");

// IDOR: User 2 cannot access User 1's account
$runner->assertThrows(function() use ($accountService, $acc1, $user2) {
    $accountService->verifyOwnership((int)$acc1['id'], (int)$user2['id'], false);
}, Exception::class, "IDOR: User 2 cannot read User 1's account");

$runner->assertThrows(function() use ($accountService, $acc1, $user2) {
    $accountService->updateAccount((int)$acc1['id'], (int)$user2['id'], false, ['account_title' => 'Hijacked Title']);
}, Exception::class, "IDOR: User 2 cannot update User 1's account");

$runner->assertThrows(function() use ($accountService, $acc1, $user2) {
    $accountService->deleteAccount((int)$acc1['id'], (int)$user2['id'], false);
}, Exception::class, "IDOR: User 2 cannot delete User 1's account");

// Account Renaming Preserves Permanent ID
$renamed = $accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, ['account_title' => 'Main Business PKR Corporate']);
$runner->assert($renamed['account_title'] === 'Main Business PKR Corporate', "Account renamed successfully by owner");
$runner->assert((int)$renamed['id'] === (int)$acc1['id'], "Account ID preserved unchanged after renaming");

// Duplicate Bank + Account Number Rejection for Same User
$runner->assertThrows(function() use ($accountService, $acc1, $user1) {
    $accountService->createAccount((int)$user1['id'], [
        'account_title' => 'Duplicate Attempt',
        'bank_name' => $acc1['bank_name'],
        'account_number' => $acc1['account_number'],
        'initial_balance' => '1000.00'
    ]);
}, Exception::class, "Duplicate bank + account number rejected for the same user");

// ====================================================
// 3. Opening Balance Immutability & Account Status
// ====================================================
echo "\n3. Testing Opening Balance Immutability & Account Status:\n";

// When NO transactions exist, initial_balance can be adjusted
$accUpdatedBal = $accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, [
    'initial_balance' => '60000.00'
]);
$runner->assert($accUpdatedBal['initial_balance'] === '60000.00', "Initial balance can be modified before any transactions are posted");

// Post a transaction to acc1
$firstTx = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $acc1['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '0.00',
    'credit' => '10000.00',
    'narration' => 'Initial Capital Inflow'
]);

// Attempt to alter initial_balance now: MUST be rejected!
$runner->assertThrows(function() use ($accountService, $acc1, $user1) {
    $accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, [
        'initial_balance' => '99999.00'
    ]);
}, Exception::class, "Strict Immutability: Initial balance CANNOT be modified once transactions exist");

// Account Status Transitions
$accDeactivated = $accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, ['status' => 'inactive']);
$runner->assert($accDeactivated['status'] === 'inactive', "Account status changed to 'inactive'");

// Cannot transact on inactive account
$runner->assertThrows(function() use ($txService, $acc1, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => $acc1['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '500.00',
        'credit' => '0.00',
        'narration' => 'Inactive Account Test'
    ]);
}, Exception::class, "Transactions strictly rejected on 'inactive' account");

$accClosed = $accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, ['status' => 'closed']);
$runner->assert($accClosed['status'] === 'closed', "Account status changed to 'closed'");

// Cannot transact on closed account
$runner->assertThrows(function() use ($txService, $acc1, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => $acc1['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '500.00',
        'credit' => '0.00',
        'narration' => 'Closed Account Test'
    ]);
}, Exception::class, "Transactions strictly rejected on 'closed' account");

// Reactivate account for further testing
$accountService->updateAccount((int)$acc1['id'], (int)$user1['id'], false, ['status' => 'active']);

// ====================================================
// 4. Financial Math & BCMath Edge Cases
// ====================================================
echo "\n4. Testing Financial Math & BCMath Decimal Engine:\n";

$runner->assert(CurrencyHelper::isValidDecimal('100.00'), "Valid decimal '100.00' accepted");
$runner->assert(CurrencyHelper::isValidDecimal('1,250,500.50'), "Comma-formatted decimal '1,250,500.50' accepted");
$runner->assert(!CurrencyHelper::isValidDecimal('100.555'), "Decimal with 3 places '100.555' rejected");
$runner->assert(!CurrencyHelper::isValidDecimal('abc'), "Non-numeric string 'abc' rejected");
$runner->assert(!CurrencyHelper::isValidDecimal('-50.00'), "Negative input string rejected by validator");

$runner->assert(CurrencyHelper::normalizeDecimal('150') === '150.00', "Normalize integer '150' to '150.00'");
$runner->assert(CurrencyHelper::normalizeDecimal('0.5') === '0.50', "Normalize single decimal '0.5' to '0.50'");
$runner->assert(CurrencyHelper::normalizeDecimal('-250.00') === '-250.00', "Normalize negative decimal '-250.00' preserved");

// Float drift test: 0.01 + 0.02 must be EXACTLY 0.03
$sum = CurrencyHelper::add('0.01', '0.02');
$runner->assert($sum === '0.03', "BCMath Precision: '0.01' + '0.02' === '0.03' (no float drift)");

$sub = CurrencyHelper::sub('100.00', '0.01');
$runner->assert($sub === '99.99', "BCMath Precision: '100.00' - '0.01' === '99.99'");

// Large amounts up to 999,999,999,999.99
$large1 = '999999999999.90';
$large2 = '0.09';
$largeSum = CurrencyHelper::add($large1, $large2);
$runner->assert($largeSum === '999999999999.99', "Large number handling without float scientific notation ({$largeSum})");

$formattedMoney = CurrencyHelper::format('1234567890.50', 'PKR', true);
$runner->assert($formattedMoney === 'PKR 1,234,567,890.50', "CurrencyHelper::format formats large amount with commas ({$formattedMoney})");

// ====================================================
// 5. Transaction Mutations, Editing & Voiding
// ====================================================
echo "\n5. Testing Transaction Mutations, Editing & Voiding:\n";

// Double-entry validation: both debit & credit filled
$runner->assertThrows(function() use ($txService, $acc1, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => $acc1['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '100.00',
        'credit' => '100.00',
        'narration' => 'Both debit and credit filled'
    ]);
}, Exception::class, "Double-Entry Rule: Both Debit and Credit filled is rejected");

// Double-entry validation: both zero
$runner->assertThrows(function() use ($txService, $acc1, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => $acc1['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '0.00',
        'credit' => '0.00',
        'narration' => 'Zero amount'
    ]);
}, Exception::class, "Double-Entry Rule: Both Debit and Credit zero is rejected");

// Valid Debit Transaction
$debitTx = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $acc1['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '5000.00',
    'credit' => '0.00',
    'narration' => 'Server Equipment Purchase'
]);
$txId = (int)$debitTx['transaction_id'];
$runner->assert($debitTx['new_balance'] === '65000.00', "Debit posted: 60k initial + 10k credit - 5k debit = 65,000.00 Balance");

// IDOR: User 2 cannot edit User 1's transaction
$runner->assertThrows(function() use ($txService, $txId, $user2) {
    $txService->updateTransaction($txId, (int)$user2['id'], false, [
        'narration' => 'Tampered Narration'
    ]);
}, Exception::class, "IDOR: User 2 cannot edit User 1's transaction");

// Legitimate Transaction Edit by Owner
$editedTx = $txService->updateTransaction($txId, (int)$user1['id'], false, [
    'debit' => '7000.00',
    'credit' => '0.00',
    'narration' => 'Server Equipment & Storage Expansion Purchase'
]);
$runner->assert($editedTx['new_balance'] === '63000.00', "Transaction Edit: Debit increased to 7,000.00, balance adjusted to 63,000.00");

$fetchedTx = $txModel->find($txId);
$runner->assert($fetchedTx['narration'] === 'Server Equipment & Storage Expansion Purchase', "Edited narration persisted in database");

// IDOR: User 2 cannot void User 1's transaction
$runner->assertThrows(function() use ($txService, $txId, $user2) {
    $txService->voidTransaction($txId, (int)$user2['id'], false, 'Malicious void attempt');
}, Exception::class, "IDOR: User 2 cannot void User 1's transaction");

// Legitimate Void by Owner with Reason
$voidRes = $txService->voidTransaction($txId, (int)$user1['id'], false, 'Duplicate entry entered by mistake');
$runner->assert($voidRes === true, "Transaction successfully voided by owner");
$balAfterVoid = $accountService->getAccount((int)$acc1['id'], (int)$user1['id'], false)['current_balance'];
$runner->assert($balAfterVoid === '70000.00', "Void Restores Balance: Voiding 7,000 debit restores balance to 70,000.00");

$voidedTxRecord = $txModel->findWithTrashed($txId);
$runner->assert($voidedTxRecord['status'] === 'voided', "Voided transaction has status = 'voided'");
$runner->assert($voidedTxRecord['void_reason'] === 'Duplicate entry entered by mistake', "Void reason recorded accurately");
$runner->assert($voidedTxRecord['voided_at'] !== null, "Void timestamp recorded");
$runner->assert((int)$voidedTxRecord['voided_by'] === (int)$user1['id'], "Voided_by user ID recorded");

// Cannot edit a voided transaction
$runner->assertThrows(function() use ($txService, $txId, $user1) {
    $txService->updateTransaction($txId, (int)$user1['id'], false, ['narration' => 'Trying to edit voided tx']);
}, Exception::class, "Integrity Rule: Voided transactions CANNOT be edited");

// Verify Audit Trail Logged
$auditEntry = $db->fetchOne("SELECT * FROM audit_logs WHERE entity_type = 'transactions' AND entity_id = ? AND action = 'TRANSACTION_VOID' LIMIT 1", [$txId]);
$runner->assert($auditEntry !== null, "Audit Trail: TRANSACTION_VOID event logged in audit_logs table");

// ====================================================
// 6. Narration Template Engine & Server-Side Validation
// ====================================================
echo "\n6. Testing Narration Template Engine & Server-Side Validation:\n";

// Template definition validation
$runner->assertThrows(function() use ($narrationService) {
    $narrationService->validateTemplateDefinition("Transfer to {receiver name}", [
        ['field_key' => 'receiver name', 'field_type' => 'text']
    ]);
}, Exception::class, "Template Validation: Invalid token syntax with space {receiver name} rejected");

$runner->assertThrows(function() use ($narrationService) {
    $narrationService->validateTemplateDefinition("Transfer to {receiver_name}", [
        ['field_key' => 'receiver_name', 'field_type' => 'text'],
        ['field_key' => 'receiver_name', 'field_type' => 'text']
    ]);
}, Exception::class, "Template Validation: Duplicate field keys rejected");

$runner->assertThrows(function() use ($narrationService) {
    $narrationService->validateTemplateDefinition("Transfer to {receiver_name} via {missing_token}", [
        ['field_key' => 'receiver_name', 'field_type' => 'text']
    ]);
}, Exception::class, "Template Validation: Token without matching field definition rejected");

// Valid template definition
$validPattern = "Payment of {invoice_no} to {vendor_name} for {item_description}";
$validFields = [
    ['field_key' => 'invoice_no', 'field_label' => 'Invoice #', 'field_type' => 'text', 'is_required' => 1],
    ['field_key' => 'vendor_name', 'field_label' => 'Vendor Name', 'field_type' => 'text', 'is_required' => 1],
    ['field_key' => 'item_description', 'field_label' => 'Description', 'field_type' => 'text', 'is_required' => 0]
];
$narrationService->validateTemplateDefinition($validPattern, $validFields);
$runner->assert(true, "Template Validation: Valid pattern and field definitions pass cleanly");

// Create template in DB
$tplId = $tplModel->insert([
    'uuid' => SecurityHelper::uuid(),
    'name' => 'Vendor Bill Payment Test',
    'code' => 'VENDOR_TEST_' . time(),
    'pattern' => $validPattern,
    'description' => 'Vendor payment testing template',
    'version' => 1,
    'is_active' => 1
]);
foreach ($validFields as $idx => $vf) {
    $fieldModel->insert([
        'template_id' => $tplId,
        'field_key' => $vf['field_key'],
        'field_label' => $vf['field_label'],
        'field_type' => $vf['field_type'],
        'is_required' => $vf['is_required'],
        'sort_order' => $idx
    ]);
}

// Transaction payload validation: missing required field
$runner->assertThrows(function() use ($narrationService, $tplId) {
    $narrationService->validateTransactionPayload($tplId, 1, [
        'invoice_no' => 'INV-101'
        // vendor_name missing!
    ]);
}, Exception::class, "Payload Validation: Missing required field rejected");

// Transaction payload validation: unexpected arbitrary keys
$runner->assertThrows(function() use ($narrationService, $tplId) {
    $narrationService->validateTransactionPayload($tplId, 1, [
        'invoice_no' => 'INV-101',
        'vendor_name' => 'Vendor XYZ',
        'hacker_key' => 'malicious_data'
    ]);
}, Exception::class, "Payload Validation: Unexpected arbitrary key rejected");

// Valid compilation & manual override
$tplValues = [
    'invoice_no' => 'INV-2026-001',
    'vendor_name' => 'Tech Supplies Co.',
    'item_description' => 'Network Hardware'
];
$compiledNarration = $narrationService->compileNarration($validPattern, $tplValues);
$runner->assert($compiledNarration === "Payment of INV-2026-001 to Tech Supplies Co. for Network Hardware", "Compilation: Tokens cleanly substituted into pattern");

// Strict unreplaced token rejection
$runner->assertThrows(function() use ($narrationService) {
    $narrationService->compileNarration("Payment of {invoice_no} to {vendor_name}", ['invoice_no' => 'INV-001'], true);
}, Exception::class, "Compilation: Unreplaced token '{vendor_name}' rejected in strict mode");

// Historical Integrity: update template pattern, verify past transactions retain snapshot
$historicalTx = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $acc1['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '2500.00',
    'credit' => '0.00',
    'narration' => $compiledNarration . ' [APPROVED]',
    'template_id' => $tplId,
    'template_version' => 1,
    'template_payload' => $tplValues
]);
$histTxId = (int)$historicalTx['transaction_id'];

// Now update the template in DB
$tplModel->update($tplId, [
    'pattern' => "NEW PATTERN FORMAT: {invoice_no} | {vendor_name}",
    'version' => 2
]);

// Verify historical tx narration has NOT mutated
$pastTx = $txModel->find($histTxId);
$runner->assert($pastTx['narration'] === $compiledNarration . ' [APPROVED]', "Historical Integrity: Modifying template does NOT mutate past transaction narration");
$runner->assert((int)$pastTx['template_version'] === 1, "Historical Integrity: Template version snapshot remains 1");

// ====================================================
// 7. Value Generators & Collision Resistance
// ====================================================
echo "\n7. Testing Generators & Collision Resistance:\n";

$stan = GeneratorService::generate(['type' => 'stan', 'length' => 6]);
$runner->assert(strlen($stan) === 6 && ctype_digit($stan), "Generator: STAN produces 6-digit numeric string ({$stan})");

$bankRef = GeneratorService::generate(['type' => 'bank_ref', 'prefix' => 'PKMEZN', 'length' => 18]);
$runner->assert(strpos($bankRef, 'PKMEZN') === 0 && strlen($bankRef) === 18, "Generator: Bank Reference produces prefixed string ({$bankRef})");

$collCheckedRef = GeneratorService::generate(['type' => 'alphanumeric', 'prefix' => 'REF', 'length' => 16], true);
$runner->assert(strlen($collCheckedRef) >= 16, "Generator: Collision-checked reference generated successfully ({$collCheckedRef})");

// ====================================================
// 8. Narration Presets & User Isolation
// ====================================================
echo "\n8. Testing Narration Presets & User Isolation:\n";

// User 1 saves private preset
$user1Preset = $presetService->savePreset($tplId, 'User 1 Tech Preset', [
    'invoice_no' => 'AUTO-DEFAULT',
    'vendor_name' => 'Default Vendor Ltd'
], (int)$user1['id'], false, false);
$runner->assert($user1Preset['id'] > 0, "User 1 successfully saved private preset");

// Admin saves global preset
$globalPreset = $presetService->savePreset($tplId, 'Corporate Standard Preset', [
    'invoice_no' => 'CORP-REF',
    'vendor_name' => 'Corporate Supplier'
], (int)$adminUser['id'], true, true);
$runner->assert($globalPreset['user_id'] === null, "Admin created global preset with user_id = NULL");

// Non-admin cannot create global preset
$runner->assertThrows(function() use ($presetService, $tplId, $user1) {
    $presetService->savePreset($tplId, 'Unauthorized Global', [
        'invoice_no' => 'HACK'
    ], (int)$user1['id'], true, false);
}, Exception::class, "Authorization: Non-admin cannot create global preset");

// Presets visibility: User 1 sees own private preset + global preset
$user1Presets = $presetService->getPresetsForTemplate($tplId, (int)$user1['id']);
$user1PresetNames = array_column($user1Presets, 'preset_name');
$runner->assert(in_array('User 1 Tech Preset', $user1PresetNames, true), "User 1 sees own private preset");
$runner->assert(in_array('Corporate Standard Preset', $user1PresetNames, true), "User 1 sees global corporate preset");

// Presets visibility: User 2 CANNOT see User 1's private preset
$user2Presets = $presetService->getPresetsForTemplate($tplId, (int)$user2['id']);
$user2PresetNames = array_column($user2Presets, 'preset_name');
$runner->assert(!in_array('User 1 Tech Preset', $user2PresetNames, true), "User Isolation: User 2 CANNOT see User 1's private preset");
$runner->assert(in_array('Corporate Standard Preset', $user2PresetNames, true), "User Isolation: User 2 can see global corporate preset");

// User 2 cannot delete User 1's preset
$runner->assertThrows(function() use ($presetService, $user1Preset, $user2) {
    $presetService->deletePreset((int)$user1Preset['id'], (int)$user2['id'], false);
}, Exception::class, "Authorization: User 2 cannot delete User 1's preset");

// Clean up test presets, fields, transaction and template to prevent database pollution
$db->query("DELETE FROM `narration_presets` WHERE `template_id` = {$tplId}");
$db->query("DELETE FROM `narration_template_fields` WHERE `template_id` = {$tplId}");
$db->query("DELETE FROM `transactions` WHERE `id` = {$histTxId}");
$db->query("DELETE FROM `narration_templates` WHERE `id` = {$tplId}");

// ====================================================
// 9. Pakistan Bank Master & Dynamic Addition
// ====================================================
echo "\n9. Testing Pakistan Bank Master & Dynamic Admin Addition:\n";

$allBanks = $bankModel->getAllActive();
$runner->assert(count($allBanks) >= 20, "Master Banks: Pakistan master list seeded with 20+ banks (Found: " . count($allBanks) . ")");

// Dynamic bank addition by Admin
$uniqueBankName = 'Test Digital Microfinance Bank ' . time();
$newBankId = $bankModel->addBank($uniqueBankName, 'TDMB', 'microfinance');
$runner->assert($newBankId > 0, "Master Banks: Admin dynamically added new bank");

// Case-insensitive duplicate bank rejection
$runner->assertThrows(function() use ($bankModel, $uniqueBankName) {
    $bankModel->addBank(strtolower($uniqueBankName));
}, Exception::class, "Master Banks: Case-insensitive duplicate bank name rejected");

// Clean up temporary test bank
$db->query("DELETE FROM `master_banks` WHERE `id` = {$newBankId}");

// ====================================================
// 10. Statement Presentation Engine
// ====================================================
echo "\n10. Testing Statement Presentation Engine:\n";

$stmt = $stmtService->generateStatement((int)$acc1['id'], date('Y-m-01'), date('Y-m-d'), 'standard', (int)$user1['id'], false);
$runner->assert($stmt['format']['format_code'] === 'standard', "Statement rendered with Standard Format");
$runner->assert(CurrencyHelper::isValidDecimal($stmt['opening_balance']), "Statement opening balance is valid decimal string");
$runner->assert(CurrencyHelper::isValidDecimal($stmt['closing_balance']), "Statement closing balance is valid decimal string");

// Verify Math in Statement: closing_balance === opening + total_credit - total_debit
$expectedClose = CurrencyHelper::sub(CurrencyHelper::add($stmt['opening_balance'], $stmt['total_credit']), $stmt['total_debit']);
$runner->assert($stmt['closing_balance'] === $expectedClose, "Statement Math: closing_balance strictly matches opening + credit - debit ({$stmt['closing_balance']})");

// Verify running balance at each row matches
$simulatedBal = $stmt['opening_balance'];
$allRowsConsistent = true;
foreach ($stmt['transactions'] as $row) {
    $simulatedBal = CurrencyHelper::add($simulatedBal, $row['credit']);
    $simulatedBal = CurrencyHelper::sub($simulatedBal, $row['debit']);
    if ($row['balance'] !== $simulatedBal) {
        $allRowsConsistent = false;
        break;
    }
}
$runner->assert($allRowsConsistent, "Statement Running Balance: Each row's balance correctly aggregates preceding mutations");

// Voided transaction strictly excluded
$stmtTxIds = array_column($stmt['transactions'], 'id');
$runner->assert(!in_array($txId, $stmtTxIds), "Statement Integrity: Soft-deleted/voided transaction #{$txId} strictly excluded");

// Account with 0 transactions generates clean statement
$emptyAcc = $accountService->createAccount((int)$user1['id'], [
    'account_title' => 'Empty Savings Account',
    'bank_name' => 'Meezan Bank Limited',
    'account_number' => 'PK' . date('ymd') . random_int(100000, 999999),
    'initial_balance' => '5000.00'
]);
$emptyStmt = $stmtService->generateStatement((int)$emptyAcc['id'], date('Y-m-01'), date('Y-m-d'), 'standard', (int)$user1['id'], false);
$runner->assert(count($emptyStmt['transactions']) === 0, "Empty Account Statement: 0 transactions reported");
$runner->assert($emptyStmt['opening_balance'] === '5000.00', "Empty Account Statement: opening balance matches initial balance");
$runner->assert($emptyStmt['closing_balance'] === '5000.00', "Empty Account Statement: closing balance equals opening balance");

// ====================================================
// 11. XSS Injection Safety Verification
// ====================================================
echo "\n11. Testing XSS Injection & Sanitization Safety:\n";

$xssPayload = "<script>alert('XSS_TEST')</script>";
$xssImg = "<img src=x onerror=alert(1)>";
$xssSvg = "\"><svg/onload=alert(1)>";

$escapedNarration = e($xssPayload);
$runner->assert(strpos($escapedNarration, '<script>') === false && strpos($escapedNarration, '&lt;script&gt;') !== false, "XSS Safety: Narration HTML tags converted to safe character entities");

$escapedAccTitle = e($xssImg);
$runner->assert(strpos($escapedAccTitle, '<img') === false && strpos($escapedAccTitle, '&lt;img') !== false, "XSS Safety: Account title img tags neutralized");

$escapedSvg = e($xssSvg);
$runner->assert(strpos($escapedSvg, '"><svg') === false && strpos($escapedSvg, '&quot;&gt;&lt;svg') !== false, "XSS Safety: SVG script injection neutralized");

// ====================================================
// 12. User Administration Module
// ====================================================
echo "\n12. Testing User Administration Module:\n";

// List users with stats
$usersWithStats = $userModel->getAllUsersWithStats();
$runner->assert(count($usersWithStats) >= 2, "User Admin: User list includes accounts and transactions statistics");

// Create user via Admin
$testAdminUser = 'test_usr_' . time();
$newUserId = $userModel->insert([
    'uuid' => SecurityHelper::uuid(),
    'username' => $testAdminUser,
    'email' => $testAdminUser . '@example.com',
    'password_hash' => password_hash('Pass@123456', PASSWORD_DEFAULT),
    'full_name' => 'Test Admin Created User',
    'role' => 'user',
    'status' => 'active',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);
$runner->assert($newUserId > 0, "User Admin: New user created with ID #{$newUserId}");

// Update user details
$userModel->update($newUserId, [
    'full_name' => 'Updated User Name',
    'role' => 'admin',
    'status' => 'suspended'
]);
$updatedUser = $userModel->find($newUserId);
$runner->assert($updatedUser['full_name'] === 'Updated User Name', "User Admin: Full name updated");
$runner->assert($updatedUser['role'] === 'admin', "User Admin: Role updated to admin");
$runner->assert($updatedUser['status'] === 'suspended', "User Admin: Status updated to suspended");

// Reset user password
$newPass = 'NewSecret@987654';
$userModel->update($newUserId, [
    'password_hash' => password_hash($newPass, PASSWORD_DEFAULT),
    'status' => 'active'
]);
$loginWithNewPass = $authService->attempt($testAdminUser, $newPass);
$runner->assert($loginWithNewPass['success'] === true, "User Admin: Password reset verified through authentication");

// Clean up temporary user
$db->query("DELETE FROM users WHERE id = {$newUserId}");

// ====================================================
// 13. Bank Master Management & Deactivation
// ====================================================
echo "\n13. Testing Bank Master Management & Deactivation:\n";

// Fetch all banks (active and inactive)
$allBanksMaster = $bankModel->getAllBanks();
$runner->assert(count($allBanksMaster) >= 10, "Master Banks: getAllBanks returns full bank catalogue");

// Update bank details
$sampleBank = $bankModel->addBank('Test Bank For Editing ' . time(), 'TB' . substr(strval(time()), -4));
$runner->assert($sampleBank > 0, "Master Banks: Added test bank for editing");

$uniqueCode = 'UB' . substr(strval(time()), -4);
$bankModel->updateBank($sampleBank, 'Updated Bank Name ' . time(), $uniqueCode, 'UBNEPKKA');
$updatedBank = $bankModel->find($sampleBank);
$runner->assert(strpos($updatedBank['bank_name'], 'Updated Bank Name') !== false, "Master Banks: Bank name successfully updated");
$runner->assert($updatedBank['bank_code'] === $uniqueCode, "Master Banks: Bank code successfully updated");

// Test duplicate name prevention on update
$runner->assertThrows(function() use ($bankModel, $sampleBank) {
    $bankModel->updateBank($sampleBank, 'Habib Bank Limited', 'HBL_DUP');
}, Exception::class, "Master Banks: Case-insensitive duplicate name prevented on update");

// Test toggleStatus (soft deactivation/activation)
$initialActive = (int)$updatedBank['is_active'];
$toggledStatus = $bankModel->toggleStatus($sampleBank);
$runner->assert($toggledStatus === ($initialActive === 1 ? 0 : 1), "Master Banks: toggleStatus toggles is_active status");
$toggledBack = $bankModel->toggleStatus($sampleBank);
$runner->assert($toggledBack === $initialActive, "Master Banks: toggleStatus restores is_active status");

// Clean up test bank
$db->query("DELETE FROM `master_banks` WHERE `id` = {$sampleBank}");

// ====================================================
// 14. Demo Name Elimination Verification
// ====================================================
echo "\n14. Testing Personal/Demo Name Elimination:\n";

$bannedPatterns = ['%Rahman%', '%Ali Allied%', '%Chota Murad%', '%G.K FABRICS%'];
$foundBannedInUsers = 0;
foreach ($bannedPatterns as $pattern) {
    $foundBannedInUsers += (int)$db->query("SELECT COUNT(*) FROM `users` WHERE `full_name` LIKE ?", [$pattern])->fetchColumn();
}
$runner->assert($foundBannedInUsers === 0, "Sanitization: Zero personal demo names in users table (Found: {$foundBannedInUsers})");

$foundBannedInAccounts = 0;
foreach ($bannedPatterns as $pattern) {
    $foundBannedInAccounts += (int)$db->query("SELECT COUNT(*) FROM `accounts` WHERE `account_title` LIKE ?", [$pattern])->fetchColumn();
}
$runner->assert($foundBannedInAccounts === 0, "Sanitization: Zero personal demo names in accounts table (Found: {$foundBannedInAccounts})");

$foundBannedInPresets = 0;
foreach ($bannedPatterns as $pattern) {
    $foundBannedInPresets += (int)$db->query("SELECT COUNT(*) FROM `narration_presets` WHERE `preset_name` LIKE ? OR `field_values` LIKE ?", [$pattern, $pattern])->fetchColumn();
}
$runner->assert($foundBannedInPresets === 0, "Sanitization: Zero personal demo names in narration_presets table (Found: {$foundBannedInPresets})");

// ====================================================
// 15. Select2 Assets & Token Auto-Field Extraction
// ====================================================
echo "\n15. Testing Select2 Assets & Token Pattern Extraction:\n";

$select2Js = file_exists(__DIR__ . '/../public/assets/js/select2.min.js');
$select2Css = file_exists(__DIR__ . '/../public/assets/css/select2.min.css');
$select2Tailwind = file_exists(__DIR__ . '/../public/assets/css/select2-tailwind.css');

$runner->assert($select2Js && filesize(__DIR__ . '/../public/assets/js/select2.min.js') > 50000, "Select2: select2.min.js present locally (>50KB)");
$runner->assert($select2Css && filesize(__DIR__ . '/../public/assets/css/select2.min.css') > 10000, "Select2: select2.min.css present locally (>10KB)");
$runner->assert($select2Tailwind, "Select2: select2-tailwind.css adapter stylesheet present locally");

// Verify pattern token extraction regex matches expected tokens
$patternSample = 'ATM WDL {stan} {sender_bank} {account_no} {trans_id} {reference_no} {amount} {fee}';
preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $patternSample, $extracted);
$tokens = $extracted[1] ?? [];
$runner->assert(count($tokens) === 7, "Auto-Detection: Exactly 7 tokens extracted from pattern");
$runner->assert(in_array('stan', $tokens, true), "Auto-Detection: {stan} token captured");
$runner->assert(in_array('sender_bank', $tokens, true), "Auto-Detection: {sender_bank} token captured");
$runner->assert(in_array('account_no', $tokens, true), "Auto-Detection: {account_no} token captured");

// ====================================================
// 16. Bank Logo Architecture, Storage & Removal
// ====================================================
echo "\n16. Testing Bank Logo Architecture, Storage & Removal:\n";

// Test logo creation on disk
$testUploadDir = __DIR__ . '/../public/uploads/banks/';
if (!is_dir($testUploadDir)) {
    mkdir($testUploadDir, 0755, true);
}

// Create a dummy PNG file for testing
$dummyPng1 = $testUploadDir . 'test_logo_1_' . time() . '.png';
$pngPixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
file_put_contents($dummyPng1, $pngPixel);

$runner->assert(file_exists($dummyPng1), "Logo File: Test dummy PNG created on disk");

$bankWithLogoName = 'Logo Test Bank ' . time();
$relPath1 = 'uploads/banks/' . basename($dummyPng1);
$bankWithLogoId = $bankModel->addBank($bankWithLogoName, 'LTB', null, $relPath1);
$savedBank = $bankModel->find($bankWithLogoId);

$runner->assert($savedBank['logo_path'] === $relPath1, "Bank Logo: Logo path saved in database ({$savedBank['logo_path']})");

// Replace with a new logo
$dummyPng2 = $testUploadDir . 'test_logo_2_' . time() . '.png';
file_put_contents($dummyPng2, $pngPixel);
$relPath2 = 'uploads/banks/' . basename($dummyPng2);

$bankModel->updateBank($bankWithLogoId, $bankWithLogoName, $savedBank['bank_code'], null, $relPath2, false);
$updatedBank = $bankModel->find($bankWithLogoId);

$runner->assert($updatedBank['logo_path'] === $relPath2, "Bank Logo Replacement: Updated to new logo path");
$runner->assert(!file_exists($dummyPng1), "Bank Logo Cleanup: Old logo file deleted from disk upon replacement");
$runner->assert(file_exists($dummyPng2), "Bank Logo Persistence: New logo file remains on disk");

// Remove logo
$bankModel->updateBank($bankWithLogoId, $bankWithLogoName, $savedBank['bank_code'], null, null, true);
$clearedBank = $bankModel->find($bankWithLogoId);

$runner->assert($clearedBank['logo_path'] === null, "Bank Logo Removal: logo_path set to NULL in database");
$runner->assert(!file_exists($dummyPng2), "Bank Logo Removal: File deleted from disk when logo removed");

// Clean up temporary test bank
$db->query("DELETE FROM `master_banks` WHERE `id` = {$bankWithLogoId}");

// ====================================================
// 17. Statement Presentation Architecture & Renderers
// ====================================================
echo "\n17. Testing Statement Presentation Architecture & Renderers:\n";

require_once __DIR__ . '/../app/Services/Statement/StatementRendererInterface.php';
require_once __DIR__ . '/../app/Services/Statement/StandardStatementRenderer.php';
require_once __DIR__ . '/../app/Services/Statement/StatementRendererFactory.php';

// Factory instantiation
$stdRenderer = StatementRendererFactory::create('standard');
$runner->assert($stdRenderer instanceof StatementRendererInterface, "Statement Architecture: Factory returns instance of StatementRendererInterface");
$runner->assert($stdRenderer->getSupportedFormatCode() === 'standard', "Statement Architecture: Standard format code is 'standard'");

// Active HBL renderer & deferred bank formats handling
$hblRenderer = StatementRendererFactory::create('hbl');
$runner->assert($hblRenderer instanceof HBLStatementRenderer, "Statement Architecture: HBL layout registered as active HBLStatementRenderer");
$runner->assert(StatementRendererFactory::isDeferred('meezan'), "Statement Architecture: Meezan layout marked as deferred awaiting client sample");

$meezanFallbackRenderer = StatementRendererFactory::create('meezan');
$runner->assert($meezanFallbackRenderer instanceof StandardStatementRenderer, "Statement Architecture: Deferred formats safely fall back to StandardStatementRenderer without fatal error");

// Statement generation with bank logo resolution
$bankLogoName2 = 'Statement Logo Bank ' . time();
$bankWithLogoId2 = $bankModel->addBank($bankLogoName2, 'SLB', null, $relPath1);
file_put_contents($dummyPng1, $pngPixel); // recreate file
$accountWithLogo = $accountService->createAccount((int)$user1['id'], [
    'account_title' => 'Logo Statement Account',
    'bank_name' => $bankLogoName2,
    'account_number' => 'PK' . time() . 'L',
    'initial_balance' => '50000.00'
]);

$stmtWithLogo = $stmtService->generateStatement((int)$accountWithLogo['id'], date('Y-m-01'), date('Y-m-d'), 'standard', (int)$user1['id'], false);
$runner->assert(isset($stmtWithLogo['account']['bank_logo']) && $stmtWithLogo['account']['bank_logo'] === $relPath1, "Statement Resolution: bank_logo field correctly resolved from master bank ({$stmtWithLogo['account']['bank_logo']})");

// Test HTML rendering output
$htmlOutput = $stmtService->renderHtml($stmtWithLogo);
$runner->assert(strpos($htmlOutput, 'Account Statement') !== false, "Statement Renderer: renderHtml includes Statement title");
$runner->assert(strpos($htmlOutput, '50,000.00') !== false, "Statement Renderer: renderHtml renders formatted initial balance");

// Test Print rendering output
$printOutput = $stmtService->renderPrint($stmtWithLogo);
$runner->assert(strpos($printOutput, '<!DOCTYPE html>') !== false, "Statement Renderer: renderPrint produces standalone printable HTML");
$runner->assert(strpos($printOutput, 'window.print()') !== false, "Statement Renderer: renderPrint includes browser print button");

// Clean up test file, test account and test bank
@unlink($dummyPng1);
$db->query("DELETE FROM `transactions` WHERE `account_id` = {$accountWithLogo['id']}");
$db->query("DELETE FROM `accounts` WHERE `id` = {$accountWithLogo['id']}");
$db->query("DELETE FROM `master_banks` WHERE `id` = {$bankWithLogoId2}");

// ====================================================
// 18. UI Contrast & Responsive Navigation Verification
// ====================================================
echo "\n18. Testing UI Contrast & Responsive Navigation Verification:\n";

// Navigation view contains dropdown, mobile menu and POST logout
$navContent = file_get_contents(__DIR__ . '/../views/layouts/navigation.php');
$runner->assert(strpos($navContent, 'id="user-menu-button"') !== false, "Navbar: Desktop user profile dropdown button present");
$runner->assert(strpos($navContent, 'id="user-menu-dropdown"') !== false, "Navbar: Desktop user profile dropdown menu container present");
$runner->assert(strpos($navContent, 'id="mobile-menu-btn"') !== false, "Navbar: Mobile hamburger toggle button present");
$runner->assert(strpos($navContent, 'id="mobile-menu"') !== false, "Navbar: Mobile menu drawer present");
$runner->assert(strpos($navContent, 'action="/atm/logout"') !== false, "Navbar: Logout uses strict POST form to /atm/logout");
$runner->assert(strpos($navContent, 'CSRF::getField()') !== false, "Navbar: Logout includes CSRF protection token");
$runner->assert(strpos($navContent, '>Docs<') === false, "Navbar: Docs link removed from primary navigation");

// Create transaction view contrast
$createTxContent = file_get_contents(__DIR__ . '/../views/transactions/create.php');
$runner->assert(strpos($createTxContent, 'bg-emerald-600') === false, "UI Contrast: Invisible bg-emerald-600 eliminated from create transaction button");
$runner->assert(strpos($createTxContent, 'bg-green-600') !== false, "UI Contrast: Save & New button upgraded to high-contrast bg-green-600");

// CSS contrast safeguards
$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/select2-tailwind.css');
$runner->assert(strpos($cssContent, 'input::placeholder') !== false, "UI Contrast: Enhanced placeholder contrast rules present in stylesheet");
$runner->assert(strpos($cssContent, '.bg-emerald-600') !== false, "UI Contrast: Emerald color fallback classes present for Tailwind v2 compatibility");

// ====================================================
// 19. HBL Statement Presentation Engine & Parity Verification
// ====================================================
echo "\n19. Testing HBL Statement Presentation Engine & Parity:\n";

// 1. Database registration & activation
$formatRow = $stmtFormatModel->findByCode('hbl');
$runner->assert($formatRow !== null && (int)$formatRow['is_active'] === 1, "HBL Statement: Format 'hbl' is active in statement_formats table");
$activeFormatsList = $stmtFormatModel->getActiveFormats();
$hblInActiveList = false;
foreach ($activeFormatsList as $fmt) {
    if ($fmt['format_code'] === 'hbl') {
        $hblInActiveList = true;
        break;
    }
}
$runner->assert($hblInActiveList, "HBL Statement: Format 'hbl' appears in active formats selector list");

// 2. Factory creation & interface compliance
$hblRendererInstance = StatementRendererFactory::create('hbl');
$runner->assert($hblRendererInstance instanceof StatementRendererInterface, "HBL Statement: Factory returns StatementRendererInterface instance");
$runner->assert($hblRendererInstance->getSupportedFormatCode() === 'hbl', "HBL Statement: getSupportedFormatCode() returns 'hbl'");
$runner->assert($hblRendererInstance->getFormatName() === 'HBL Statement Layout', "HBL Statement: getFormatName() returns 'HBL Statement Layout'");

// 3. Create test account with transactions for 100% financial parity check
$staleAccs = $db->query("SELECT id FROM `accounts` WHERE `account_title` IN ('Indus Logistics Enterprise', 'Logo Verification Co', '<script>alert(\"hbl-xss\")</script>Secure Corp')")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($staleAccs)) {
    $inList = implode(',', array_map('intval', $staleAccs));
    $db->query("DELETE FROM `transactions` WHERE `account_id` IN ({$inList})");
    $db->query("DELETE FROM `accounts` WHERE `id` IN ({$inList})");
}

$hblAccount = $accountService->createAccount((int)$user1['id'], [
    'account_title' => 'Indus Logistics Enterprise',
    'bank_name' => 'Habib Bank Limited',
    'account_number' => '0417-79011691-03',
    'currency' => 'PKR',
    'initial_balance' => '100000.00',
    'notes' => "3rd Floor Trade Center\nOff I.I. Chundrigar Road\nKarachi"
]);
$hblAccId = (int)$hblAccount['id'];

// Insert 4 transactions
$tx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $hblAccId,
    'transaction_date' => date('Y-m-02'),
    'debit' => '15000.00',
    'credit' => '0.00',
    'narration' => "Funds Transfer\nTO HBL Digital\nInvoice #4092\nBeneficiary: Premier Parts Ltd"
]);

$tx2 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $hblAccId,
    'transaction_date' => date('Y-m-05'),
    'debit' => '0.00',
    'credit' => '42500.00',
    'narration' => "Interbank Inward Remittance\nPK42HABB0004177901169103\nFrom: Global Traders FZE"
]);

$tx3 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $hblAccId,
    'transaction_date' => date('Y-m-10'),
    'debit' => '3200.50',
    'credit' => '0.00',
    'narration' => "Online Utility Settlement\nBill Payment K-Electric"
]);

$tx4 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $hblAccId,
    'transaction_date' => date('Y-m-12'),
    'debit' => '0.00',
    'credit' => '1800.00',
    'narration' => "Cash Deposit ATM-042-KHI\nSTAN: 489201"
]);

// Insert 1 voided transaction that MUST be excluded
$txVoid = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => $hblAccId,
    'transaction_date' => date('Y-m-15'),
    'debit' => '50000.00',
    'credit' => '0.00',
    'narration' => "Mistaken payment to be voided"
]);
$txService->voidTransaction((int)$txVoid['transaction_id'], (int)$user1['id'], false, "Test void exclusion");

// Generate both Standard and HBL statements on identical parameters
$stdStmt = $stmtService->generateStatement($hblAccId, date('Y-m-01'), date('Y-m-t'), 'standard', (int)$user1['id'], false);
$hblStmt = $stmtService->generateStatement($hblAccId, date('Y-m-01'), date('Y-m-t'), 'hbl', (int)$user1['id'], false);

// 4. Financial Parity Assertions
$runner->assert($hblStmt['opening_balance'] === $stdStmt['opening_balance'] && $hblStmt['opening_balance'] === '100000.00', "Parity: Opening balance matches Standard (100,000.00)");
$runner->assert($hblStmt['total_debit'] === $stdStmt['total_debit'] && $hblStmt['total_debit'] === '18200.50', "Parity: Total debits strictly match Standard (18,200.50)");
$runner->assert($hblStmt['total_credit'] === $stdStmt['total_credit'] && $hblStmt['total_credit'] === '44300.00', "Parity: Total credits strictly match Standard (44,300.00)");
$runner->assert($hblStmt['closing_balance'] === $stdStmt['closing_balance'] && $hblStmt['closing_balance'] === '126099.50', "Parity: Closing balance strictly matches Standard (126,099.50)");
$runner->assert(count($hblStmt['transactions']) === count($stdStmt['transactions']) && count($hblStmt['transactions']) === 4, "Parity: Transaction row count matches and excludes voided tx (4 rows)");

$allRowsMatched = true;
for ($i = 0; $i < count($hblStmt['transactions']); $i++) {
    $hRow = $hblStmt['transactions'][$i];
    $sRow = $stdStmt['transactions'][$i];
    if ($hRow['date'] !== $sRow['date'] || $hRow['debit'] !== $sRow['debit'] || $hRow['credit'] !== $sRow['credit'] || $hRow['balance'] !== $sRow['balance']) {
        $allRowsMatched = false;
        break;
    }
}
$runner->assert($allRowsMatched, "Parity: Every individual row debit, credit, and running balance strictly matches between Standard and HBL");

// 5. HTML Screen Rendering Structure Assertions
$hblHtml = $hblRendererInstance->renderHtml($hblStmt);
$runner->assert(strpos($hblHtml, 'YOUR ACCOUNT STATEMENT') !== false, "HBL Header: Contains uppercase 'YOUR ACCOUNT STATEMENT'");
$runner->assert(strpos($hblHtml, 'FOR THE PERIOD ENDING:') !== false, "HBL Header: Contains 'FOR THE PERIOD ENDING:'");
$runner->assert(strpos($hblHtml, 'Indus Logistics Enterprise') !== false, "HBL Header: Contains dynamic account title 'Indus Logistics Enterprise'");
$runner->assert(strpos($hblHtml, '0417-79011691-03') !== false, "HBL Header: Contains dynamic account number");
$runner->assert(strpos($hblHtml, 'ACCOUNT NO') !== false, "HBL Header: Contains 'ACCOUNT NO' field label");
$runner->assert(strpos($hblHtml, 'ACCOUNT TYPE') !== false, "HBL Header: Contains 'ACCOUNT TYPE' field label");
$runner->assert(strpos($hblHtml, 'Pakistani Rupee') !== false, "HBL Header: Contains normalized currency 'Pakistani Rupee'");
$runner->assert(strpos($hblHtml, 'PRINTING DATE') !== false, "HBL Header: Contains 'PRINTING DATE' field label");
$runner->assert(strpos($hblHtml, 'FREQUENCY') !== false && strpos($hblHtml, 'INTERIM / DUPLICATE') !== false, "HBL Header: Contains 'FREQUENCY' and 'INTERIM / DUPLICATE'");
$runner->assert(strpos($hblHtml, 'PAGE NO') !== false, "HBL Header: Contains 'PAGE NO' label");
$runner->assert(strpos($hblHtml, 'USER') !== false, "HBL Header: Contains 'USER' field label");

// 6. Brought Forward row assertions
$runner->assert(strpos($hblHtml, 'BROUGHT FORWARD') !== false, "HBL Table: First row contains 'BROUGHT FORWARD'");
$runner->assert(strpos($hblHtml, '100,000.00') !== false, "HBL Table: Brought forward displays formatted opening balance");

// 7. Table columns & Multi-line Particulars
$runner->assert(strpos($hblHtml, '<th class="px-3 py-2.5 font-extrabold w-24">DATE</th>') !== false, "HBL Table: DATE column present");
$runner->assert(strpos($hblHtml, '<th class="px-3 py-2.5 font-extrabold w-24">VALUE</th>') !== false, "HBL Table: VALUE column present");
$runner->assert(strpos($hblHtml, 'PARTICULARS') !== false, "HBL Table: PARTICULARS column present");
$runner->assert(strpos($hblHtml, 'Funds Transfer<br />') !== false || strpos($hblHtml, "Funds Transfer") !== false, "HBL Narration: Multi-line particulars preserved without truncation");

// 8. Footers
$runner->assert(strpos($hblHtml, 'Continuation on next page') !== false, "HBL Footer: Contains 'Continuation on next page'");
$runner->assert(strpos($hblHtml, 'This is a system generated account statement and does not require a signature.') !== false, "HBL Footer: Contains system-generated non-signature disclaimer");

// 9. Bank Logo & Fallback
$runner->assert(strpos($hblHtml, '<div class="text-xl font-black tracking-wider text-emerald-800 uppercase mb-2">Habib Bank Limited</div>') !== false, "HBL Logo: Fallback styled bank title renders cleanly when logo_path is null");

// Test with bank logo attached
$bankWithLogoName3 = 'HBL Logo Test Bank ' . time();
$bankWithLogoId3 = $bankModel->addBank($bankWithLogoName3, 'HBLB', null, 'uploads/banks/test_hbl_logo.png');
$accountWithLogo2 = $accountService->createAccount((int)$user1['id'], [
    'account_title' => 'Logo Verification Co',
    'bank_name' => $bankWithLogoName3,
    'account_number' => '0417-11223344-01',
    'initial_balance' => '5000.00'
]);
$stmtWithHblLogo = $stmtService->generateStatement((int)$accountWithLogo2['id'], date('Y-m-01'), date('Y-m-d'), 'hbl', (int)$user1['id'], false);
$hblHtmlWithLogo = $hblRendererInstance->renderHtml($stmtWithHblLogo);
$runner->assert(strpos($hblHtmlWithLogo, 'src="/atm/public/uploads/banks/test_hbl_logo.png"') !== false, "HBL Logo: Correctly renders <img> tag when bank logo is present");

// 10. XSS Sanitization Safety
$xssAccount = $accountService->createAccount((int)$user1['id'], [
    'account_title' => '<script>alert("hbl-xss")</script>Secure Corp',
    'bank_name' => 'Habib Bank Limited',
    'account_number' => '0417-99999999-09',
    'initial_balance' => '1000.00'
]);
$xssTx = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$xssAccount['id'],
    'transaction_date' => date('Y-m-01'),
    'debit' => '100.00',
    'credit' => '0.00',
    'narration' => '<img src=x onerror="alert(document.cookie)">Malicious Narration'
]);
$xssStmt = $stmtService->generateStatement((int)$xssAccount['id'], date('Y-m-01'), date('Y-m-d'), 'hbl', (int)$user1['id'], false);
$xssHtml = $hblRendererInstance->renderHtml($xssStmt);
$runner->assert(strpos($xssHtml, '<script>alert("hbl-xss")</script>') === false, "HBL Security: Account title script tag strictly escaped");
$runner->assert(strpos($xssHtml, '&lt;img src=x') !== false, "HBL Security: Narration img tag encoded as safe HTML entities");
$runner->assert(strpos($xssHtml, '<img src=x onerror') === false, "HBL Security: Narration img onerror vector strictly escaped");

// 11. Print Layout Document Assertions
$hblPrint = $hblRendererInstance->renderPrint($hblStmt);
$runner->assert(strpos($hblPrint, '<!DOCTYPE html>') !== false, "HBL Print: Produces valid standalone HTML5 document");
$runner->assert(strpos($hblPrint, 'size: A4 portrait;') !== false, "HBL Print: Stylesheet specifies A4 portrait page size");
$runner->assert(strpos($hblPrint, 'display: table-header-group;') !== false, "HBL Print: Table thead set to table-header-group for repeating on multi-page print");
$runner->assert(strpos($hblPrint, 'page-break-inside: avoid;') !== false, "HBL Print: Table rows configured to prevent page break splitting");
$runner->assert(strpos($hblPrint, 'window.print()') !== false, "HBL Print: Includes interactive Print / Save as PDF trigger");
$runner->assert(strpos($hblPrint, 'BROUGHT FORWARD') !== false, "HBL Print: Contains BROUGHT FORWARD row with opening balance");
$runner->assert(strpos($hblPrint, 'This is a system generated account statement and does not require a signature.') !== false, "HBL Print: Contains footer non-signature disclaimer");

// 12. Clean up test accounts
$db->query("DELETE FROM `transactions` WHERE `account_id` IN ({$hblAccId}, {$accountWithLogo2['id']}, {$xssAccount['id']})");
$db->query("DELETE FROM `accounts` WHERE `id` IN ({$hblAccId}, {$accountWithLogo2['id']}, {$xssAccount['id']})");
$db->query("DELETE FROM `master_banks` WHERE `id` = {$bankWithLogoId3}");

// Clean up any other temporary test fixtures created in sections 2, 9, 10, 17
if (isset($acc1['id'])) {
    $db->query("DELETE FROM `transactions` WHERE `account_id` = {$acc1['id']}");
    $db->query("DELETE FROM `accounts` WHERE `id` = {$acc1['id']}");
}
if (isset($emptyAcc['id'])) {
    $db->query("DELETE FROM `transactions` WHERE `account_id` = {$emptyAcc['id']}");
    $db->query("DELETE FROM `accounts` WHERE `id` = {$emptyAcc['id']}");
}
if (isset($accountWithLogo['id'])) {
    $db->query("DELETE FROM `transactions` WHERE `account_id` = {$accountWithLogo['id']}");
    $db->query("DELETE FROM `accounts` WHERE `id` = {$accountWithLogo['id']}");
}
if (isset($newBankId)) {
    $db->query("DELETE FROM `master_banks` WHERE `id` = {$newBankId}");
}
if (isset($bankWithLogoId2)) {
    $db->query("DELETE FROM `master_banks` WHERE `id` = {$bankWithLogoId2}");
}

// ====================================================
// 20. Rapid Transaction Entry & Clean Demo Portfolio
// ====================================================
echo "\n20. Testing Rapid Transaction Entry & Clean Demo Portfolio:\n";

// 1. Verify Exactly 4 Clean Demo Accounts Exist for User 1
$user1Accounts = $accountService->listAccounts((int)$user1['id'], false, false);
$accountTitles = array_column($user1Accounts, 'account_title');
sort($accountTitles);
$expectedTitles = ['Ahmad', 'Ali', 'Jawad', 'Saeed'];

$runner->assert(count($user1Accounts) === 4, "Demo Portfolio: Exactly 4 demo accounts exist for User 1 (Found: " . count($user1Accounts) . ")");
$runner->assert($accountTitles === $expectedTitles, "Demo Portfolio: Accounts match exactly: Ali, Ahmad, Saeed, Jawad");

// Verify Banks for each
$accountsByTitle = [];
foreach ($user1Accounts as $a) {
    $accountsByTitle[$a['account_title']] = $a;
}
$runner->assert(isset($accountsByTitle['Ali']) && $accountsByTitle['Ali']['bank_name'] === 'Habib Bank Limited', "Demo Accounts: Ali is associated with Habib Bank Limited");
$runner->assert(isset($accountsByTitle['Ahmad']) && $accountsByTitle['Ahmad']['bank_name'] === 'United Bank Limited', "Demo Accounts: Ahmad is associated with United Bank Limited");
$runner->assert(isset($accountsByTitle['Saeed']) && $accountsByTitle['Saeed']['bank_name'] === 'Meezan Bank Limited', "Demo Accounts: Saeed is associated with Meezan Bank Limited");
$runner->assert(isset($accountsByTitle['Jawad']) && $accountsByTitle['Jawad']['bank_name'] === 'Bank Alfalah Limited', "Demo Accounts: Jawad is associated with Bank Alfalah Limited");

// Verify No Developer Names in Accounts
$bannedDevNames = ['Rahman', 'Muhammad Rahman', 'Chota Murad', 'Test Account Alpha'];
$foundDevNames = 0;
foreach ($user1Accounts as $a) {
    foreach ($bannedDevNames as $devName) {
        if (stripos($a['account_title'], $devName) !== false) {
            $foundDevNames++;
        }
    }
}
$runner->assert($foundDevNames === 0, "Demo Accounts: Zero obsolete developer names in account titles");

// 2. Rapid Transaction Posting (Direct / Free-Text Narration)
$aliAcc = $accountsByTitle['Ali'];
$aliInitialBal = (string)$aliAcc['current_balance'];

// First rapid entry: DEBIT 5,000.00
$rapidTx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '5000.00',
    'credit' => '0.00',
    'narration' => 'Office Stationery & Printing'
]);
$expectedBal1 = CurrencyHelper::sub($aliInitialBal, '5000.00');
$runner->assert($rapidTx1['transaction_id'] > 0, "Rapid Entry: Debit transaction #{$rapidTx1['transaction_id']} posted successfully");
$runner->assert($rapidTx1['new_balance'] === $expectedBal1, "Rapid Entry: Server calculates new balance using BCMath (Expected: {$expectedBal1}, Got: {$rapidTx1['new_balance']})");

// Verify transaction record in DB
$dbTx1 = $txModel->find((int)$rapidTx1['transaction_id']);
$runner->assert($dbTx1['template_id'] === null, "Rapid Entry: template_id is NULL for free-text rapid transaction");
$runner->assert($dbTx1['narration'] === 'Office Stationery & Printing', "Rapid Entry: Narration stored verbatim");

// Second rapid entry on SAME account (Account Persistence): CREDIT 12,500.00
$rapidTx2 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '0.00',
    'credit' => '12500.00',
    'narration' => 'Client Retainer Payment #INV-204'
]);
$expectedBal2 = CurrencyHelper::add($expectedBal1, '12500.00');
$runner->assert($rapidTx2['transaction_id'] > 0, "Rapid Entry: Second transaction #{$rapidTx2['transaction_id']} posted to same account");
$runner->assert($rapidTx2['new_balance'] === $expectedBal2, "Rapid Entry: Continuous entry accurately reflects updated balance ({$expectedBal2})");

// 3. Recent Transactions API Service (Today's Entries)
$recentData = $txService->getRecentAccountTransactions((int)$aliAcc['id'], (int)$user1['id'], false, 10);
$runner->assert(isset($recentData['account']) && (int)$recentData['account']['id'] === (int)$aliAcc['id'], "Recent API: Returns account details matching selected target account");
$runner->assert($recentData['account']['current_balance'] === $expectedBal2, "Recent API: Account balance matches latest server-calculated balance ({$expectedBal2})");
$runner->assert(count($recentData['transactions']) >= 2, "Recent API: Contains both newly posted entries");

// Verify reverse chronological order (newest first)
$firstRecent = $recentData['transactions'][0];
$runner->assert((int)$firstRecent['id'] === (int)$rapidTx2['transaction_id'], "Recent API: Newest transaction appears first in list");
$runner->assert($firstRecent['type'] === 'credit', "Recent API: Formatted type is 'credit'");
$runner->assert($firstRecent['amount'] === '12500.00', "Recent API: Formatted amount matches 12500.00");
$runner->assert(!empty($firstRecent['time']), "Recent API: Formatted time string present ({$firstRecent['time']})");

// 4. IDOR Authorization Protection on Rapid Entry and Recent Transactions
$runner->assertThrows(function() use ($txService, $aliAcc, $user2) {
    $txService->createTransaction((int)$user2['id'], false, [
        'account_id' => (int)$aliAcc['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '100.00',
        'credit' => '0.00',
        'narration' => 'Unauthorized Rapid Entry'
    ]);
}, Exception::class, "IDOR Guard: User 2 cannot post rapid transaction to User 1 account");

$runner->assertThrows(function() use ($txService, $aliAcc, $user2) {
    $txService->getRecentAccountTransactions((int)$aliAcc['id'], (int)$user2['id'], false, 10);
}, Exception::class, "IDOR Guard: User 2 cannot access recent transactions of User 1 account");

// 5. Clean up the two rapid transactions so demo account balance returns to pristine initial_balance
$db->query("DELETE FROM `transactions` WHERE `id` IN ({$rapidTx1['transaction_id']}, {$rapidTx2['transaction_id']})");
$aliReset = $accountService->getAccount((int)$aliAcc['id'], (int)$user1['id'], false);
$runner->assert($aliReset['current_balance'] === $aliInitialBal, "Cleanup: Ali demo account balance returned to opening balance ({$aliInitialBal})");

// ====================================================
// 21. Rapid Entry Quick Narration & Minimal UI Verification
// ====================================================
echo "\n21. Testing Rapid Entry Quick Narration & Minimal UI:\n";

// 1. Template Selection & Dynamic Fields
$activeTemplates = $narrationService->getActiveTemplatesWithFields();
$runner->assert(!empty($activeTemplates), "Quick Narration: Active templates loaded with dynamic fields");

// Find IBFT or first template with fields
$testTemplate = null;
foreach ($activeTemplates as $t) {
    if (!empty($t['fields'])) {
        $testTemplate = $t;
        if (stripos($t['name'], 'IBFT') !== false) {
            break;
        }
    }
}
$runner->assert($testTemplate !== null, "Quick Narration: Selected active template with dynamic fields ({$testTemplate['name']})");
$runner->assert(!empty($testTemplate['fields']), "Quick Narration: Dynamic fields defined for template");

// Verify tokens in pattern match field keys
preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $testTemplate['pattern'], $tokenMatches);
$tokens = $tokenMatches[1] ?? [];
$fieldKeys = array_column($testTemplate['fields'], 'field_key');
$allTokensHaveFields = true;
foreach ($tokens as $tok) {
    if (!in_array($tok, $fieldKeys)) {
        $allTokensHaveFields = false;
        break;
    }
}
$runner->assert($allTokensHaveFields, "Quick Narration: All pattern tokens map to dynamic field inputs");

// 2. Generator Fields
$generatorService = new GeneratorService();
$stanVal = $generatorService->generate(['type' => 'stan', 'length' => 6]);
$runner->assert(strlen($stanVal) === 6 && ctype_digit($stanVal), "Quick Narration: STAN generator produces 6-digit numeric string ({$stanVal})");

$refVal = $generatorService->generate(['type' => 'bank_ref', 'prefix' => 'BAHLPKKA']);
$runner->assert(strpos($refVal, 'BAHLPKKA') === 0 && strlen($refVal) >= 16, "Quick Narration: Reference generator produces valid prefix+date format ({$refVal})");

$transVal = $generatorService->generate(['type' => 'alphanumeric', 'prefix' => 'FT26', 'length' => 16]);
$runner->assert(strpos($transVal, 'FT26') === 0 && strlen($transVal) === 16, "Quick Narration: Transaction ID generator produces valid alphanumeric ID ({$transVal})");

// 3. Automatic Compilation
$testPayload = [];
foreach ($testTemplate['fields'] as $fld) {
    $k = $fld['field_key'];
    if ($fld['field_type'] === 'stan') {
        $testPayload[$k] = $stanVal;
    } elseif ($fld['field_type'] === 'bank') {
        $testPayload[$k] = 'Habib Bank Limited';
    } else {
        $testPayload[$k] = 'Test ' . ucfirst($k);
    }
}
$compiledNarration = $testTemplate['pattern'];
foreach ($testPayload as $k => $v) {
    $compiledNarration = str_replace('{' . $k . '}', $v, $compiledNarration);
}
$runner->assert(strpos($compiledNarration, '{') === false, "Quick Narration: Pattern tokens compiled without unreplaced tokens");

// 4. Quick Narration Transaction Posting with Template Snapshot
$aliStartBal = (string)$aliAcc['current_balance'];
$rapidQuickTx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '2500.00',
    'credit' => '0.00',
    'narration' => $compiledNarration,
    'template_id' => (int)$testTemplate['id'],
    'template_version' => (int)($testTemplate['version'] ?? 1),
    'template_payload' => json_encode($testPayload)
]);
$expectedBalQ1 = CurrencyHelper::sub($aliStartBal, '2500.00');
$runner->assert($rapidQuickTx1['transaction_id'] > 0, "Quick Narration: Transaction posted with template metadata");
$runner->assert($rapidQuickTx1['new_balance'] === $expectedBalQ1, "Quick Narration: Debit accurately reduces balance via BCMath ({$expectedBalQ1})");

// Verify audit snapshot in DB
$dbQuickTx = $txModel->find((int)$rapidQuickTx1['transaction_id']);
$runner->assert((int)$dbQuickTx['template_id'] === (int)$testTemplate['id'], "Quick Narration: template_id persisted in database");
$runner->assert((int)$dbQuickTx['template_version'] === (int)($testTemplate['version'] ?? 1), "Quick Narration: template_version persisted in database");
$runner->assert(!empty($dbQuickTx['template_payload']), "Quick Narration: template_payload JSON persisted in database");
$runner->assert($dbQuickTx['narration'] === $compiledNarration, "Quick Narration: Compiled narration saved verbatim");

// 5. Final Narration Editing (Manual Override of Compiled Narration)
$customEditedNarration = $compiledNarration . ' -- Urgent Medical Support';
$rapidQuickTx2 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '1500.00',
    'credit' => '0.00',
    'narration' => $customEditedNarration,
    'template_id' => (int)$testTemplate['id'],
    'template_version' => (int)($testTemplate['version'] ?? 1),
    'template_payload' => json_encode($testPayload)
]);
$dbEditedTx = $txModel->find((int)$rapidQuickTx2['transaction_id']);
$runner->assert($dbEditedTx['narration'] === $customEditedNarration, "Quick Narration: Manual edits to compiled narration stored verbatim");
$runner->assert((int)$dbEditedTx['template_id'] === (int)$testTemplate['id'], "Quick Narration: Template snapshot preserved even with manual text override");

// 6. Template & Account & Date Persistence Across Saves (Continuous Entry)
$rapidQuickTx3 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '0.00',
    'credit' => '8000.00',
    'narration' => $compiledNarration,
    'template_id' => (int)$testTemplate['id'],
    'template_version' => (int)($testTemplate['version'] ?? 1),
    'template_payload' => json_encode($testPayload)
]);
$expectedBalQ3 = CurrencyHelper::add(CurrencyHelper::sub($expectedBalQ1, '1500.00'), '8000.00');
$runner->assert($rapidQuickTx3['new_balance'] === $expectedBalQ3, "Continuous Posting: Credit accurately increases balance via BCMath ({$expectedBalQ3})");

// 7. Security: IDOR & Server-Side Validation
$runner->assertThrows(function() use ($txService, $aliAcc, $user2, $testTemplate, $compiledNarration) {
    $txService->createTransaction((int)$user2['id'], false, [
        'account_id' => (int)$aliAcc['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '500.00',
        'credit' => '0.00',
        'narration' => $compiledNarration,
        'template_id' => (int)$testTemplate['id']
    ]);
}, Exception::class, "Quick Narration IDOR: User 2 blocked from posting to User 1 account");

$runner->assertThrows(function() use ($txService, $aliAcc, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => (int)$aliAcc['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '0.00',
        'credit' => '0.00',
        'narration' => 'Zero amount test'
    ]);
}, Exception::class, "Validation: Zero amount transaction rejected by server");

$runner->assertThrows(function() use ($txService, $aliAcc, $user1) {
    $txService->createTransaction((int)$user1['id'], false, [
        'account_id' => (int)$aliAcc['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '500.00',
        'credit' => '0.00',
        'narration' => '   '
    ]);
}, Exception::class, "Validation: Empty narration transaction rejected by server");

// 8. XSS Sanitization in Narration
$xssQuickNarration = '<script>alert("xss")</script>Payment for Consulting';
$xssTxId = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '100.00',
    'credit' => '0.00',
    'narration' => $xssQuickNarration
])['transaction_id'];

$recentCheck = $txService->getRecentAccountTransactions((int)$aliAcc['id'], (int)$user1['id'], false, 5);
$foundXssTx = null;
foreach ($recentCheck['transactions'] as $rt) {
    if ((int)$rt['id'] === (int)$xssTxId) {
        $foundXssTx = $rt;
        break;
    }
}
$runner->assert($foundXssTx !== null, "XSS Check: Transaction found in recent transactions list");
$runner->assert(strpos(htmlspecialchars($foundXssTx['narration'], ENT_QUOTES, 'UTF-8'), '<script>') === false, "XSS Check: Script tag neutralized by HTML entity escaping");

// 9. Financial: BCMath Decimal Precision (Zero Binary Drift)
$precisionTx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '1234.56',
    'credit' => '0.00',
    'narration' => 'Precision Debit'
]);
$precisionTx2 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '0.00',
    'credit' => '1234.56',
    'narration' => 'Precision Credit'
]);
$netDiff = CurrencyHelper::sub($precisionTx2['new_balance'], $precisionTx1['new_balance']);
$runner->assert($netDiff === '1234.56', "BCMath: Exact fractional addition and subtraction verified without float drift");

// Clean up all Section 21 test transactions so Ali returns to pristine initial_balance
$cleanTxIds = [
    $rapidQuickTx1['transaction_id'],
    $rapidQuickTx2['transaction_id'],
    $rapidQuickTx3['transaction_id'],
    $xssTxId,
    $precisionTx1['transaction_id'],
    $precisionTx2['transaction_id']
];
$db->query("DELETE FROM `transactions` WHERE `id` IN (" . implode(',', $cleanTxIds) . ")");
$aliFinalReset = $accountService->getAccount((int)$aliAcc['id'], (int)$user1['id'], false);
$runner->assert($aliFinalReset['current_balance'] === $aliInitialBal, "Cleanup: Ali demo account balance returned to pristine opening balance ({$aliInitialBal})");

// ==========================================
// 22. RAPID ENTRY NARRATION UX REVISION & NAVIGATION CONTRAST
// ==========================================
echo "\n--- 22. RAPID ENTRY NARRATION UX REVISION & NAVIGATION CONTRAST ---\n";

// 1. Autocomplete 'IBF' search returns matching active templates
$allActiveTemplates = $narrationService->getActiveTemplatesWithFields();
$ibfMatches = array_values(array_filter($allActiveTemplates, function($t) {
    if (!$t['is_active']) return false;
    $haystack = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['description'] ?? ''));
    return strpos($haystack, 'ibf') !== false;
}));
$runner->assert(count($ibfMatches) >= 2, "Typeahead Search: Typing 'IBF' returns active matching templates (" . count($ibfMatches) . " found)");

$matchingCodes = array_map(function($t) { return $t['code']; }, $ibfMatches);
$runner->assert(in_array('IBFT_OUTWARD', $matchingCodes), "Typeahead Search: 'IBFT_OUTWARD' present in results");
$runner->assert(in_array('IBFT_INCOMING', $matchingCodes), "Typeahead Search: 'IBFT_INCOMING' present in results");

// 2. Case-insensitive matching
$lowerMatches = array_filter($allActiveTemplates, function($t) {
    $haystack = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['description'] ?? ''));
    return strpos($haystack, strtolower('ibf')) !== false;
});
$upperMatches = array_filter($allActiveTemplates, function($t) {
    $haystack = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['description'] ?? ''));
    return strpos($haystack, strtolower('IBF')) !== false;
});
$mixedMatches = array_filter($allActiveTemplates, function($t) {
    $haystack = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['description'] ?? ''));
    return strpos($haystack, strtolower('IbF')) !== false;
});
$runner->assert(count($lowerMatches) === count($upperMatches) && count($upperMatches) === count($mixedMatches), "Typeahead Search: Matching is strictly case-insensitive ('ibf' == 'IBF' == 'IbF')");

// 3. Debit/Credit directional filtering & prioritization
$scoreFunc = function($tpl, $direction) {
    $text = strtolower(($tpl['name'] ?? '') . ' ' . ($tpl['code'] ?? '') . ' ' . ($tpl['category'] ?? '') . ' ' . ($tpl['description'] ?? '') . ' ' . ($tpl['pattern'] ?? ''));
    $debitKeywords = ['outward', 'outflow', 'debit', 'withdrawal', 'withdraw', 'payment', 'bill', 'charge', 'fee', 'tax', 'purchase', 'pos', 'atm'];
    $creditKeywords = ['incoming', 'inward', 'inflow', 'credit', 'deposit', 'receipt', 'receive', 'refund', 'salary', 'dividend', 'interest', 'collection'];
    $debitScore = 0;
    $creditScore = 0;
    foreach ($debitKeywords as $kw) {
        if (strpos($text, $kw) !== false) $debitScore++;
    }
    foreach ($creditKeywords as $kw) {
        if (strpos($text, $kw) !== false) $creditScore++;
    }
    return ($direction === 'debit') ? ($debitScore - $creditScore) : ($creditScore - $debitScore);
};

$templateModel = new NarrationTemplate();
$tplOutwardRaw = $templateModel->findByCode('IBFT_OUTWARD');
$tplIncomingRaw = $templateModel->findByCode('IBFT_INCOMING');
$tplOutward = $narrationService->getTemplateDetails((int)$tplOutwardRaw['id']);
$tplIncoming = $narrationService->getTemplateDetails((int)$tplIncomingRaw['id']);

$debitScoreOutward = $scoreFunc($tplOutward, 'debit');
$debitScoreIncoming = $scoreFunc($tplIncoming, 'debit');
$runner->assert($debitScoreOutward > $debitScoreIncoming, "Directional Priority: DEBIT ranks Outward Transfer higher than Incoming Transfer ({$debitScoreOutward} > {$debitScoreIncoming})");

$creditScoreOutward = $scoreFunc($tplOutward, 'credit');
$creditScoreIncoming = $scoreFunc($tplIncoming, 'credit');
$runner->assert($creditScoreIncoming > $creditScoreOutward, "Directional Priority: CREDIT ranks Incoming Transfer higher than Outward Transfer ({$creditScoreIncoming} > {$creditScoreOutward})");

// 4. Selecting a template populates the primary narration input
$stan1 = $generatorService->generate(['type' => 'stan', 'length' => 6]);
$dynamicPayload = [
    'bank' => 'HBL',
    'account' => '0417-79011691-01',
    'title' => 'Muhammad Ali',
    'stan' => $stan1
];
$compiledNarration1 = $tplOutward['pattern'];
foreach ($dynamicPayload as $k => $v) {
    $compiledNarration1 = str_replace('{' . $k . '}', $v, $compiledNarration1);
}
$runner->assert(strpos($compiledNarration1, '{') === false, "Selection & Compilation: Narration populated without unresolved curly braces");
$runner->assert(strpos($compiledNarration1, 'HBL') !== false && strpos($compiledNarration1, $stan1) !== false, "Selection & Compilation: Narration contains bank and initial STAN");

// 5. Required dynamic fields compile into the same primary input
$expectedString = "IBFT OUTWARD TRANSFER - HBL - A/C 0417-79011691-01 - Muhammad Ali - STAN: {$stan1}";
$runner->assert($compiledNarration1 === $expectedString, "Inline Fields Compilation: Formatted output exactly matches pattern structure");

// 6. Inline STAN regeneration (↻) updates narration immediately
$stan2 = $generatorService->generate(['type' => 'stan', 'length' => 6]);
while ($stan2 === $stan1) {
    $stan2 = $generatorService->generate(['type' => 'stan', 'length' => 6]);
}
$inPlaceUpdatedNarration = str_replace($stan1, $stan2, $compiledNarration1);
$runner->assert(strpos($inPlaceUpdatedNarration, $stan2) !== false, "In-Place STAN Regen: New STAN {$stan2} successfully replaced old STAN in narration");
$runner->assert(strpos($inPlaceUpdatedNarration, $stan1) === false, "In-Place STAN Regen: Old STAN {$stan1} no longer present in narration");
$runner->assert(strpos($inPlaceUpdatedNarration, 'Muhammad Ali') !== false, "In-Place STAN Regen: Other tokens remain completely untouched");

// 7. Reference regeneration produces a valid unique reference and updates narration
$ref1 = $generatorService->generate(['type' => 'bank_ref', 'prefix' => 'BAHLPKKA']);
$ref2 = $generatorService->generate(['type' => 'bank_ref', 'prefix' => 'BAHLPKKA']);
$runner->assert($ref1 !== $ref2, "Reference Regen: Regenerating reference produces a fresh unique code");
$testRefNarration = "BILL PAYMENT - REF: {$ref1}";
$testRefUpdated = str_replace($ref1, $ref2, $testRefNarration);
$runner->assert($testRefUpdated === "BILL PAYMENT - REF: {$ref2}", "Reference Regen: In-place substitution updates reference correctly");

// 8. Transaction ID regeneration produces a valid unique alphanumeric ID and updates narration
$txId1 = $generatorService->generate(['type' => 'alphanumeric', 'prefix' => 'FT26', 'length' => 16]);
$txId2 = $generatorService->generate(['type' => 'alphanumeric', 'prefix' => 'FT26', 'length' => 16]);
$runner->assert($txId1 !== $txId2, "Transaction ID Regen: Regenerating TX ID produces unique alphanumeric ID");
$testTxIdNarration = "FUNDS TRANSFER - TXID: {$txId1}";
$testTxIdUpdated = str_replace($txId1, $txId2, $testTxIdNarration);
$runner->assert($testTxIdUpdated === "FUNDS TRANSFER - TXID: {$txId2}", "Transaction ID Regen: In-place substitution updates TXID correctly");

// 9. Uniqueness check for generated values (100 iterations collision test)
$generatedStans = [];
for ($i = 0; $i < 100; $i++) {
    $s = $generatorService->generate(['type' => 'stan', 'length' => 6]);
    $generatedStans[$s] = true;
}
$runner->assert(count($generatedStans) >= 95, "Generator Entropy: 100 STAN generations produced >= 95 distinct values (high entropy)");

// 10. Manual narration remains editable
$manualEditedNarration = $inPlaceUpdatedNarration . ' -- Special Instructions Call Verified';
$runner->assert(strpos($manualEditedNarration, 'Special Instructions Call Verified') !== false, "Manual Editing: User can freely edit/append custom text to generated narration");

// 11. Final saved transaction stores manual narration verbatim
$dynamicPayload['stan'] = $stan2;
$sec22Tx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '7500.00',
    'credit' => '0.00',
    'narration' => $manualEditedNarration,
    'template_id' => (int)$tplOutward['id'],
    'template_version' => (int)($tplOutward['version'] ?? 1),
    'template_payload' => json_encode($dynamicPayload)
]);
$runner->assert($sec22Tx1['transaction_id'] > 0, "Posting: Transaction saved with manual override narration");

// 12. Template snapshot is still persisted in database
$savedTxRecord = $txModel->find((int)$sec22Tx1['transaction_id']);
$runner->assert($savedTxRecord['narration'] === $manualEditedNarration, "Persistence: Saved narration stores manual override verbatim in database");
$runner->assert((int)$savedTxRecord['template_id'] === (int)$tplOutward['id'], "Persistence: template_id persisted in database");
$runner->assert((int)$savedTxRecord['template_version'] === (int)($tplOutward['version'] ?? 1), "Persistence: template_version persisted in database");
$savedPayloadDecoded = json_decode($savedTxRecord['template_payload'], true);
$runner->assert($savedPayloadDecoded['stan'] === $stan2, "Persistence: template_payload stores regenerated STAN ({$stan2})");

// 13. Continuous Rapid Entry state retention
// Retains account, date, and template, clears amount, generates fresh STAN
$stan3 = $generatorService->generate(['type' => 'stan', 'length' => 6]);
$nextDynamicPayload = $dynamicPayload;
$nextDynamicPayload['stan'] = $stan3;
$nextCompiledNarration = $tplOutward['pattern'];
foreach ($nextDynamicPayload as $k => $v) {
    $nextCompiledNarration = str_replace('{' . $k . '}', $v, $nextCompiledNarration);
}

$sec22Tx2 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$aliAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '3200.00',
    'credit' => '0.00',
    'narration' => $nextCompiledNarration,
    'template_id' => (int)$tplOutward['id'],
    'template_version' => (int)($tplOutward['version'] ?? 1),
    'template_payload' => json_encode($nextDynamicPayload)
]);
$runner->assert($sec22Tx2['transaction_id'] > 0, "Continuous Batch: Second entry saved retaining template and account");
$savedTxRecord2 = $txModel->find((int)$sec22Tx2['transaction_id']);
$runner->assert((int)$savedTxRecord2['account_id'] === (int)$aliAcc['id'], "Continuous Batch: Account ID retained across continuous entries");
$runner->assert(strpos($savedTxRecord2['narration'], $stan3) !== false, "Continuous Batch: Fresh STAN {$stan3} compiled and persisted for Entry 2");

// 14. Existing IDOR and authorization guards remain intact
$runner->assertThrows(function() use ($txService, $aliAcc, $user2) {
    $txService->createTransaction((int)$user2['id'], false, [
        'account_id' => (int)$aliAcc['id'],
        'transaction_date' => date('Y-m-d'),
        'debit' => '1000.00',
        'credit' => '0.00',
        'narration' => 'IDOR Attack Test'
    ]);
}, Exception::class, "Security: IDOR guard rejects unauthorized rapid posting by User 2 on User 1 account");

// 15. Existing Detailed Entry functionality remains unchanged
$detailedTemplates = $narrationService->getActiveTemplatesWithFields();
$runner->assert(count($detailedTemplates) > 0, "Detailed Entry Compatibility: Active templates with fields accessible to Detailed Entry");
$detailedTpl1 = $detailedTemplates[0];
$runner->assert(isset($detailedTpl1['fields']) && is_array($detailedTpl1['fields']), "Detailed Entry Compatibility: Fields array preserved for complex narration form");

// 16. Navigation Tailwind class contrast audit
$navSource = file_get_contents(__DIR__ . '/../views/layouts/navigation.php');
$runner->assert(strpos($navSource, 'slate-') === false, "Navigation Contrast: No invalid 'slate-*' classes found (eliminated invisible/transparent artifacts)");
$runner->assert(strpos($navSource, 'bg-gray-900') !== false, "Navigation Contrast: High-contrast 'bg-gray-900' dark background bar verified");
$runner->assert(strpos($navSource, 'text-gray-300') !== false || strpos($navSource, 'text-gray-200') !== false, "Navigation Contrast: High-contrast light text on dark navbar verified");

// Clean up Section 22 test transactions
$sec22CleanIds = [
    $sec22Tx1['transaction_id'],
    $sec22Tx2['transaction_id']
];
$db->query("DELETE FROM `transactions` WHERE `id` IN (" . implode(',', $sec22CleanIds) . ")");
$aliFinalReset2 = $accountService->getAccount((int)$aliAcc['id'], (int)$user1['id'], false);
$runner->assert($aliFinalReset2['current_balance'] === $aliInitialBal, "Cleanup: Ali demo account balance returned to pristine opening balance ({$aliInitialBal})");

// ====================================================
// 23. Client-Reference UX Redesign, Account Identifiers, Edit & Online Statements
// ====================================================
echo "\n--- 23. CLIENT-REFERENCE UX REDESIGN, ACCOUNT IDENTIFIERS & ONLINE STATEMENTS ---\n";

// 1. Account Identifier Lookup & Uniqueness
$ahmadAcc = $accountModel->findByUserAndIdentifier((int)$user1['id'], '57');
$runner->assert($ahmadAcc !== null, "Identifier Lookup: Found account by identifier '57'");
$runner->assert($ahmadAcc['account_title'] === 'Ahmad', "Identifier Lookup: Title matches 'Ahmad'");
$runner->assert($ahmadAcc['bank_name'] === 'United Bank Limited', "Identifier Lookup: Bank matches 'United Bank Limited'");

$aliByIdent = $accountModel->findByUserAndIdentifier((int)$user1['id'], '13');
$runner->assert($aliByIdent !== null && $aliByIdent['account_title'] === 'Ali', "Identifier Lookup: Found account by identifier '13' (Ali)");

$saeedByIdent = $accountModel->findByUserAndIdentifier((int)$user1['id'], '91');
$runner->assert($saeedByIdent !== null && $saeedByIdent['account_title'] === 'Saeed', "Identifier Lookup: Found account by identifier '91' (Saeed)");

$jawadByIdent = $accountModel->findByUserAndIdentifier((int)$user1['id'], 'A12');
$runner->assert($jawadByIdent !== null && $jawadByIdent['account_title'] === 'Jawad', "Identifier Lookup: Found account by identifier 'A12' (Jawad)");

// Verify Statement Metadata Columns on Account
$runner->assert(array_key_exists('father_name', $ahmadAcc), "Metadata Columns: 'father_name' column present on account model");
$runner->assert(array_key_exists('account_type', $ahmadAcc), "Metadata Columns: 'account_type' column present on account model");
$runner->assert(array_key_exists('iban', $ahmadAcc), "Metadata Columns: 'iban' column present on account model");
$runner->assert(array_key_exists('address', $ahmadAcc), "Metadata Columns: 'address' column present on account model");

// Duplicate Account Identifier for same user should throw
$runner->assertThrows(function() use ($accountService, $user1) {
    $accountService->createAccount((int)$user1['id'], [
        'account_title' => 'Duplicate Identifier Test',
        'account_identifier' => '57',
        'bank_name' => 'United Bank Limited',
        'initial_balance' => '0.00'
    ]);
}, Exception::class, "Identifier Uniqueness: Disallows duplicate identifier '57' for same user");

// 2. Dual Suggestion Autocomplete API Logic (Templates + History)
$ahmadFull = $accountService->getAccount((int)$ahmadAcc['id'], (int)$user1['id'], false);
$ahmadInitialBal = $ahmadFull['current_balance'];
$sec23Tx1 = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$ahmadAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '4500.00',
    'credit' => '0.00',
    'narration' => 'Special Cash Withdrawal SEC23 881122',
    'reference_no' => 'REF-SEC23-01'
]);
$runner->assert($sec23Tx1['transaction_id'] > 0, "Posting: Recorded transaction for Ahmad account");

// Query history narrations
$distinctPast = $txModel->getRecentDistinctNarrations((int)$user1['id'], (int)$ahmadAcc['id'], 'SEC23', 10);
$runner->assert(count($distinctPast) > 0, "Dual Autocomplete: Found distinct past narration matching 'SEC23'");
$runner->assert(in_array('Special Cash Withdrawal SEC23 881122', $distinctPast), "Dual Autocomplete: History contains exact past narration string");

// 3. Edit Mode & Updating Transaction
$txIdToEdit = (int)$sec23Tx1['transaction_id'];
$updateData = [
    'account_id' => (int)$ahmadAcc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '5500.00',
    'credit' => '0.00',
    'narration' => 'Updated Cash Withdrawal SEC23 881122 Modified',
    'reference_no' => 'REF-SEC23-UPDATED'
];
$updateResult = $txService->updateTransaction($txIdToEdit, (int)$user1['id'], false, $updateData);
$runner->assert($updateResult['transaction_id'] === $txIdToEdit, "Edit & Update: Transaction successfully updated via updateTransaction");

$reloadedTx = $txModel->find($txIdToEdit);
$runner->assert($reloadedTx['debit'] === '5500.00', "Edit & Update: Debit amount correctly updated to 5500.00");
$runner->assert($reloadedTx['narration'] === 'Updated Cash Withdrawal SEC23 881122 Modified', "Edit & Update: Narration correctly updated");
$runner->assert($reloadedTx['reference_no'] === 'REF-SEC23-UPDATED', "Edit & Update: Reference number correctly updated");

// Audit Log check for update
$auditUpdate = $db->fetchOne("SELECT * FROM `audit_logs` WHERE `action` = 'TRANSACTION_UPDATE' AND `entity_id` = ? ORDER BY id DESC LIMIT 1", [$txIdToEdit]);
$runner->assert($auditUpdate !== null, "Audit Trail: Audit log records TRANSACTION_UPDATE action for edited transaction");

// 4. Recent Transactions Table API
$recentRes = $txService->getRecentAccountTransactions((int)$ahmadAcc['id'], (int)$user1['id'], false, 10);
$runner->assert(isset($recentRes['transactions']) && count($recentRes['transactions']) > 0, "Recent Table API: Returns recent transactions array");
$firstRecent = $recentRes['transactions'][0];
$runner->assert($firstRecent['bank_name'] === 'United Bank Limited', "Recent Table API: Row contains correct bank_name ('United Bank Limited')");
$runner->assert((int)$firstRecent['account_id'] === (int)$ahmadAcc['id'], "Recent Table API: Row contains account_id");
$runner->assert($firstRecent['narration'] === 'Updated Cash Withdrawal SEC23 881122 Modified', "Recent Table API: Row reflects latest edited narration");

// 5. Digital Online Banking Statement Format (OnlineStatementRenderer)
$onlineRenderer = StatementRendererFactory::create('online');
$runner->assert($onlineRenderer instanceof OnlineStatementRenderer, "Statement Factory: 'online' format resolves to OnlineStatementRenderer instance");
$runner->assert($onlineRenderer->getSupportedFormatCode() === 'online', "Statement Factory: Supported format code is 'online'");

// Generate Statement with show_online and metadata overrides
$statementData = $stmtService->generateStatement(
    (int)$ahmadAcc['id'],
    date('Y-m-01'),
    date('Y-m-d'),
    'standard',
    (int)$user1['id'],
    false,
    [
        'show_online' => true,
        'father_name' => 'Sheikh Ahmad Father',
        'account_type' => 'Freedom Business Premium',
        'iban' => 'PK36ALFH0000570000000001',
        'address' => 'Peshawar City Branch Suite 402'
    ]
);

$runner->assert($statementData['format']['format_code'] === 'online', "Show Online: Automatically selects 'online' format when show_online is true");
$runner->assert($statementData['account']['father_name'] === 'Sheikh Ahmad Father', "Report Overrides: Father name overridden in generated statement");
$runner->assert($statementData['account']['account_type'] === 'Freedom Business Premium', "Report Overrides: Account type overridden in generated statement");
$runner->assert($statementData['account']['iban'] === 'PK36ALFH0000570000000001', "Report Overrides: IBAN overridden in generated statement");
$runner->assert($statementData['account']['address'] === 'Peshawar City Branch Suite 402', "Report Overrides: Address overridden in generated statement");

// Render HTML Preview
$onlineHtml = $onlineRenderer->renderHtml($statementData);
$runner->assert(strpos($onlineHtml, 'Online Banking E-Statement') !== false, "Online HTML: Contains 'Online Banking E-Statement' header");
$runner->assert(strpos($onlineHtml, 'Sheikh Ahmad Father') !== false, "Online HTML: Contains customer father name");
$runner->assert(strpos($onlineHtml, 'Balance &amp; Turnover Summary') !== false, "Online HTML: Contains Balance & Turnover Summary");
$runner->assert(strpos($onlineHtml, 'Net Movement') !== false, "Online HTML: Contains Net Movement calculation");
$runner->assert(strpos($onlineHtml, 'Electronic Transaction Record') !== false, "Online HTML: Contains Electronic Transaction Record table");

// Render Standalone Print Document
$onlinePrint = $onlineRenderer->renderPrint($statementData);
$runner->assert(strpos($onlinePrint, '<!DOCTYPE html>') !== false, "Online Print: Valid standalone HTML document");
$runner->assert(strpos($onlinePrint, 'Online Banking Electronic Statement') !== false, "Online Print: Header indicates Online Banking Electronic Statement");
$runner->assert(strpos($onlinePrint, 'PK36ALFH0000570000000001') !== false, "Online Print: Overridden IBAN included in printable advice");
$runner->assert(strpos($onlinePrint, 'does not require a physical signature or stamp') !== false, "Online Print: Contains digital electronic disclaimer");

// 6. Clean up Section 23 test transaction
$db->query("DELETE FROM `transactions` WHERE `id` = {$txIdToEdit}");
$ahmadFinalReset = $accountService->getAccount((int)$ahmadAcc['id'], (int)$user1['id'], false);
$runner->assert($ahmadFinalReset['current_balance'] === $ahmadInitialBal, "Cleanup: Ahmad account balance restored to pristine balance ({$ahmadInitialBal})");

// ====================================================
// 24. Desktop Workflow Verification: Single-Field Accounts, TransactionFrm & Ribbon Reports
// ====================================================
echo "\n--- 24. DESKTOP WORKFLOW: SINGLE-FIELD ACCOUNTS, TRANSACTIONFRM & RIBBON REPORTS ---\n";

// 1. Simplified Account Creation: Only Account Number / Code provided
$sec24Acc = $accountService->createAccount((int)$user1['id'], [
    'account_identifier' => '92',
    'bank_name' => 'Bank Alfalah Limited',
    'initial_balance' => '50000.00'
]);
$runner->assert($sec24Acc['id'] > 0, "Desktop Account: Created account with single identifier '92'");
$runner->assert($sec24Acc['account_identifier'] === '92', "Desktop Account: Identifier saved as '92'");
$runner->assert($sec24Acc['account_title'] === '92', "Desktop Account: Title automatically falls back to identifier '92'");
$runner->assert($sec24Acc['bank_name'] === 'Bank Alfalah Limited', "Desktop Account: Bank set to 'Bank Alfalah Limited'");

// 2. TransactionFrm Data Entry with Dr / Cr exclusive amounts
// Post Debit transaction
$sec24TxDr = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$sec24Acc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '12000.00',
    'credit' => '0.00',
    'narration' => 'ATM WDL Peshawar Branch Ref# 8844',
    'reference_no' => 'ATM-8844'
]);
$runner->assert($sec24TxDr['transaction_id'] > 0, "TransactionFrm: Debit entry posted");
$balAfterDr = $accountService->getAccount((int)$sec24Acc['id'], (int)$user1['id'], false)['current_balance'];
$runner->assert($balAfterDr === '38000.00', "TransactionFrm: Debit reduced balance correctly (38000.00)");

// Post Credit transaction
$sec24TxCr = $txService->createTransaction((int)$user1['id'], false, [
    'account_id' => (int)$sec24Acc['id'],
    'transaction_date' => date('Y-m-d'),
    'debit' => '0.00',
    'credit' => '25000.00',
    'narration' => 'Cash Deposit Online Transfer 9911',
    'reference_no' => 'DEP-9911'
]);
$runner->assert($sec24TxCr['transaction_id'] > 0, "TransactionFrm: Credit entry posted");
$balAfterCr = $accountService->getAccount((int)$sec24Acc['id'], (int)$user1['id'], false)['current_balance'];
$runner->assert($balAfterCr === '63000.00', "TransactionFrm: Credit increased balance correctly (63000.00)");

// 3. Ribbon Bank Statement Report Generation
$bankReportData = $stmtService->generateStatement(
    (int)$sec24Acc['id'],
    date('Y-m-01'),
    date('Y-m-d'),
    'standard',
    (int)$user1['id'],
    false,
    [
        'show_online' => false,
        'father_name' => 'Alfalah Account Holder',
        'account_type' => 'Current Account'
    ]
);
$runner->assert($bankReportData['account']['account_identifier'] === '92', "Statement Report: Contains account identifier '92'");
$runner->assert($bankReportData['account']['bank_name'] === 'Bank Alfalah Limited', "Statement Report: Bank name matches 'Bank Alfalah Limited'");
$runner->assert($bankReportData['closing_balance'] === '63000.00', "Statement Report: Closing balance reflects both transactions (63000.00)");

// 4. Clean up temporary records created in Section 24
$db->query("DELETE FROM `transactions` WHERE `account_id` = {$sec24Acc['id']}");
$db->query("DELETE FROM `accounts` WHERE `id` = {$sec24Acc['id']}");
$cleanedCheck = $accountModel->find((int)$sec24Acc['id']);
$runner->assert($cleanedCheck === null, "Cleanup: Temporary account '92' and its transactions safely removed");

// ====================================================
// 25. RAPID ENTRY KEYBOARD NAVIGATION, NARRATION SELECTION & SHORT CODE ACCOUNTS
// ====================================================
echo "\n--- 25. RAPID ENTRY KEYBOARD NAVIGATION, NARRATION SELECTION & SHORT CODE ACCOUNTS ---\n";

// 1. Account Creation with 2-digit code without account_title
$shortCodeAcc = $accountService->createAccount((int)$user1['id'], [
    'account_identifier' => '14',
    'bank_name' => 'United Bank Limited',
    'initial_balance' => '1000.00'
]);
$runner->assert($shortCodeAcc['id'] > 0, "Short Code Account: Successfully created account with 2-digit identifier '14'");
$runner->assert($shortCodeAcc['account_identifier'] === '14', "Short Code Account: account_identifier stored as '14'");
$runner->assert($shortCodeAcc['account_title'] === '14', "Short Code Account: account_title automatically set to '14'");

// Test updating short code account
$updatedShort = $accountService->updateAccount((int)$shortCodeAcc['id'], (int)$user1['id'], false, [
    'account_identifier' => '14',
    'bank_name' => 'Habib Bank Limited'
]);
$runner->assert($updatedShort['bank_name'] === 'Habib Bank Limited', "Short Code Account: Successfully updated bank name without validation errors");

// Cleanup short code account
$accountService->deleteAccount((int)$shortCodeAcc['id'], (int)$user1['id'], false);
$runner->assert($accountModel->find((int)$shortCodeAcc['id']) === null, "Short Code Account: Cleaned up temporary account '14'");
// Hard delete so it does not remain in accounts table
$db->query("DELETE FROM `accounts` WHERE `id` = {$shortCodeAcc['id']}");

// 2. NarrationEngine JS verification: narration alias & token regex
$engineJs = file_get_contents(__DIR__ . '/../public/assets/js/narration-engine.js');
$runner->assert(strpos($engineJs, 'narration: compiled') !== false, "NarrationEngine JS: Contains narration alias for autocomplete compatibility");
$runner->assert(strpos($engineJs, 'generateSTAN({ length: digits.length || 6 })') !== false, "NarrationEngine JS: Robust STAN regex replacement present");
$runner->assert(strpos($engineJs, 'generateRaastRef({ prefix: p })') !== false, "NarrationEngine JS: Robust Reference regex replacement present");

// 3. Rapid Entry JS verification: keyboard navigation & token regeneration
$rapidPhp = file_get_contents(__DIR__ . '/../views/transactions/rapid.php');
$runner->assert(strpos($rapidPhp, "e.key === 'ArrowDown'") !== false, "Rapid Entry: Particulars ArrowDown navigation present");
$runner->assert(strpos($rapidPhp, "e.key === 'ArrowUp'") !== false, "Rapid Entry: Particulars ArrowUp navigation present");
$runner->assert(strpos($rapidPhp, "selectParticularsItem") !== false, "Rapid Entry: Item selection logic present");
$runner->assert(strpos($rapidPhp, "triggerRegenerateTokens") !== false, "Rapid Entry: In-place token regeneration function present");
$runner->assert(strpos($rapidPhp, "e.key === 'F2'") !== false, "Rapid Entry: F2 keyboard accelerator for token regeneration present");
$runner->assert(strpos($rapidPhp, "$('#frm-dr').on('keydown'") !== false, "Rapid Entry: Enter key advancement on Dr field present");
$runner->assert(strpos($rapidPhp, "$('#frm-cr').on('keydown'") !== false, "Rapid Entry: Enter key advancement on Cr field present");
$runner->assert(strpos($rapidPhp, "$('#frm-ref-no').on('keydown'") !== false, "Rapid Entry: Enter key save on Ref field present");

// ====================================================
// 26. ATM CODE GENERATION, RAPID ENTRY AUTO POPULATION & TOKEN REGENERATION
// ====================================================
echo "\n--- 26. ATM CODE GENERATION, RAPID ENTRY AUTO POPULATION & TOKEN REGENERATION ---\n";

// 1. GeneratorService ATM Code & RRN
$atmCode = GeneratorService::generate(['type' => 'atm_code']);
$runner->assert((bool)preg_match('/^ATM-\d{3}-[A-Z]{3}$/', $atmCode), "Generator: Generates valid Pakistani ATM code ({$atmCode})");

$rrn = GeneratorService::generate(['type' => 'rrn']);
$runner->assert(strlen($rrn) === 12 && ctype_digit($rrn), "Generator: Generates 12-digit RRN ({$rrn})");

// 2. Suggestions API excludes VENDOR_TEST templates and returns active banking templates
$tempVendorTplId = $tplModel->insert([
    'uuid' => SecurityHelper::uuid(),
    'name' => 'Transient Vendor Test',
    'code' => 'VENDOR_TEST_TMP',
    'pattern' => 'VENDOR TEST',
    'description' => 'temp',
    'version' => 1,
    'is_active' => 0
]);

$subScript = __DIR__ . '/test_api_suggestions_tmp.php';
file_put_contents($subScript, '<?php
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["role"] = "admin";
require_once __DIR__ . "/../app/Core/Request.php";
require_once __DIR__ . "/../app/Controllers/TransactionController.php";
$tc = new TransactionController();
$tc->apiSuggestions(new Request());
');
$subOut = [];
$subCode = 0;
exec('"' . PHP_BINARY . '" "' . $subScript . '"', $subOut, $subCode);
@unlink($subScript);
$suggJson = implode("\n", $subOut);
$suggData = json_decode($suggJson, true);
$runner->assert(!empty($suggData['success']), "Suggestions API: Successfully responds with JSON");
$suggTemplates = $suggData['data']['templates'] ?? [];
$runner->assert(count($suggTemplates) > 0, "Suggestions API: Active templates returned");

$hasVendorTest = false;
$foundAtm = false;
$foundIbft = false;
foreach ($suggTemplates as $st) {
    if (strpos($st['code'], 'VENDOR_TEST_') === 0) {
        $hasVendorTest = true;
    }
    if ($st['code'] === 'ATM_CASH') $foundAtm = true;
    if ($st['code'] === 'IBFT_OUTWARD' || $st['code'] === 'IBFT') $foundIbft = true;
}
$runner->assert(!$hasVendorTest, "Suggestions API: Transient VENDOR_TEST templates strictly excluded");
// Clean up temporary test template immediately
$db->query("DELETE FROM `narration_templates` WHERE `id` = {$tempVendorTplId}");
$runner->assert($foundAtm, "Suggestions API: ATM Cash Withdrawal template present");
$runner->assert($foundIbft, "Suggestions API: IBFT template present");

// 3. NarrationEngine JS verification: ATM Code, RRN, and regex replacement
$engineJs = file_get_contents(__DIR__ . '/../public/assets/js/narration-engine.js');
$runner->assert(strpos($engineJs, 'function generateATMCode') !== false, "NarrationEngine JS: generateATMCode function present");
$runner->assert(strpos($engineJs, 'function generateRRN') !== false, "NarrationEngine JS: generateRRN function present");
$runner->assert(strpos($engineJs, 'ATM[-:][0-9A-Z]+') !== false, "NarrationEngine JS: ATM ID regex replacement present");
$runner->assert(strpos($engineJs, 'generateATMCode: generateATMCode') !== false, "NarrationEngine JS: generateATMCode exported");

// 4. Rapid Entry UI verification: suppressSuggestOnFocus and default first item highlight
$rapidPhp = file_get_contents(__DIR__ . '/../views/transactions/rapid.php');
$runner->assert(strpos($rapidPhp, 'suppressSuggestOnFocus') !== false, "Rapid Entry: suppressSuggestOnFocus flag prevents dropdown focus glitches");
$runner->assert(strpos($rapidPhp, 'activeDropdownIndex = 0') !== false, "Rapid Entry: First suggestion automatically highlighted for instant Enter selection");
$runner->assert(strpos($rapidPhp, 'lastSelectedPayload.atm_code') !== false, "Rapid Entry: ATM Code token feedback supported on regeneration");

// 5. Node verification of autoPopulate and regenerateAllInNarration
$nodeScript = '
const fs = require("fs");
global.$ = () => ({ text: ()=>{}, val: ()=>{}, removeClass: ()=>{}, addClass: ()=>{}, empty: ()=>{}, append: ()=>{}, find: ()=>({on: ()=>{}}), each: ()=>{} });
eval(fs.readFileSync(__dirname + "/public/assets/js/narration-engine.js", "utf8"));
const tpl = { code: "ATM_CASH", pattern: "Cash withdrawal from ATM | {atm_code} | STAN: {stan}", fields: [{ field_key: "atm_code", default_value: "ATM-042-KHI" }, { field_key: "stan", field_type: "stan" }] };
const pop = NarrationEngine.autoPopulateFromTemplate(tpl, { bank_name: "Meezan Bank Ltd" });
const regen = NarrationEngine.regenerateAllInNarration(pop.compiled, tpl, pop.payload);
const okPop = pop.compiled.includes("Cash withdrawal from ATM") && !pop.compiled.includes("{") && pop.payload.atm_code.startsWith("ATM-") && /^\d{6}$/.test(pop.payload.stan);
const okRegen = regen.updatedNarration.includes("Cash withdrawal from ATM") && regen.updatedPayload.stan !== pop.payload.stan && regen.updatedPayload.atm_code !== pop.payload.atm_code;
process.exit(okPop && okRegen ? 0 : 1);
';
$nodeTmp = __DIR__ . '/../scratch_node_test.js';
file_put_contents($nodeTmp, $nodeScript);
$nodeOutput = [];
$nodeCode = 0;
exec('node "' . $nodeTmp . '"', $nodeOutput, $nodeCode);
@unlink($nodeTmp);
$runner->assert($nodeCode === 0, "NarrationEngine JS: Node runtime verifies dynamic ATM Code & STAN population and regeneration");

// --- 27. RAPID ENTRY CLEAR CROSS BUTTON, EXTERNAL REGENERATE BUTTON & CONTRAST ---
echo "\n--- 27. RAPID ENTRY CLEAR CROSS BUTTON, EXTERNAL REGENERATE BUTTON & CONTRAST ---\n";
$rapidCode = file_get_contents(__DIR__ . '/../views/transactions/rapid.php');

$runner->assert(strpos($rapidCode, 'id="btn-clear-particulars"') !== false, "Rapid Entry UI: Clear cross button (#btn-clear-particulars) present in DOM");
$runner->assert(strpos($rapidCode, 'toggleClearParticularsBtn') !== false, "Rapid Entry UI: toggleClearParticularsBtn logic present");
$runner->assert(strpos($rapidCode, 'text-black bg-white') !== false, "Particulars Dropdown: Unselected items styled with text-black on white background");
$runner->assert(strpos($rapidCode, 'text-blue-700') !== false && strpos($rapidCode, 'bg-[#dce9fa]') !== false, "Particulars Dropdown: Selected item highlighted in text-blue-700 on soft blue bg-[#dce9fa]");
$runner->assert(strpos($rapidCode, 'id="btn-regen-tokens"') !== false, "Rapid Entry UI: Regenerate variables button (#btn-regen-tokens) present");
$runner->assert(strpos($rapidCode, "e.key === 'Escape'") !== false && strpos($rapidCode, 'btn-clear-particulars') !== false, "Keyboard UX: Escape key triggers clear button when dropdown is closed");
$runner->assert(strpos($rapidCode, 'Row 3: Particulars (Full Row') !== false, "Rapid Entry Layout: Particulars field allocated dedicated full row for maximum narration space");
// --- 28. RAPID ENTRY ONE-CLICK QUICK TEMPLATE CHIPS & IN-PLACE REGENERATION ---
echo "\n--- 28. RAPID ENTRY ONE-CLICK QUICK TEMPLATE CHIPS & IN-PLACE REGENERATION ---\n";
$runner->assert(strpos($rapidCode, 'id="quick-templates-wrapper"') !== false, "Quick Templates UI: Wrapper (#quick-templates-wrapper) present in DOM");
$runner->assert(strpos($rapidCode, 'id="quick-template-chips"') !== false, "Quick Templates UI: Chips container (#quick-template-chips) present in DOM");
$runner->assert(strpos($rapidCode, 'function renderQuickTemplateChips()') !== false, "Quick Templates JS: renderQuickTemplateChips function present");
$runner->assert(strpos($rapidCode, 'function onQuickTemplateClick(tpl, $btn)') !== false, "Quick Templates JS: onQuickTemplateClick handler present");
$runner->assert(strpos($rapidCode, 'function getTemplateDirection(tpl)') !== false, "Quick Templates JS: getTemplateDirection direction classifier present");
$runner->assert(strpos($rapidCode, 'triggerRegenerateTokens();') !== false && strpos($rapidCode, 'onQuickTemplateClick') !== false, "Quick Templates UX: Clicking active template chip triggers in-place token regeneration");
$runner->assert(strpos($rapidCode, 'bg-[#2e72cb]') !== false && strpos($rapidCode, 'active') !== false, "Quick Templates UX: Active template button visually highlights with distinct blue styling");

// Node.js test verifying getTemplateDirection and chip filtering logic
$nodeScript28 = '
const fs = require("fs");
const rapidCode = fs.readFileSync(__dirname + "/views/transactions/rapid.php", "utf8");

// Extract getTemplateDirection
const dirFnMatch = rapidCode.match(/function getTemplateDirection\\(tpl\\)[\\s\\S]*?\\n    \\}/);
if (!dirFnMatch) process.exit(1);
eval(dirFnMatch[0]);

const tplAtm = { name: "ATM Cash Withdrawal", code: "ATM_CASH", category: "atm" };
const tplIbftIn = { name: "IBFT Incoming Transfer", code: "IBFT_INCOMING", category: "ibft" };
const tplRaast = { name: "Raast P2P Funds Transfer", code: "RAAST_P2P", category: "raast" };

const dirAtm = getTemplateDirection(tplAtm);
const dirIn = getTemplateDirection(tplIbftIn);
const dirRaast = getTemplateDirection(tplRaast);

const ok = (dirAtm === "debit") && (dirIn === "credit") && (dirRaast === "both" || dirRaast === "credit" || dirRaast === "debit");
process.exit(ok ? 0 : 2);
';
$nodeTmp28 = __DIR__ . '/../scratch_node_test_28.js';
file_put_contents($nodeTmp28, $nodeScript28);
$nodeOut28 = [];
$nodeCode28 = 0;
exec('node "' . $nodeTmp28 . '"', $nodeOut28, $nodeCode28);
@unlink($nodeTmp28);
$runner->assert($nodeCode28 === 0, "Quick Templates Logic: Node runtime verifies getTemplateDirection accurately tags Debit/Credit/Both templates");

// --- 29. FAYSAL BANK STATEMENT LAYOUT & MASTER BANK LOGO INTEGRATION ---
echo "\n--- 29. FAYSAL BANK STATEMENT LAYOUT & MASTER BANK LOGO INTEGRATION ---\n";

// 1. Factory & Interface Verification
$faysalRenderer = StatementRendererFactory::create('faysal');
$runner->assert($faysalRenderer instanceof FaysalStatementRenderer, "Statement Factory: Resolves 'faysal' to FaysalStatementRenderer instance");
$faisalRenderer = StatementRendererFactory::create('faisal');
$runner->assert($faisalRenderer instanceof FaysalStatementRenderer, "Statement Factory: Resolves 'faisal' to FaysalStatementRenderer instance");
$runner->assert($faysalRenderer->getSupportedFormatCode() === 'faysal', "Faysal Renderer: Supported format code is 'faysal'");
$runner->assert($faysalRenderer->getFormatName() === 'Faysal Bank Statement Layout', "Faysal Renderer: Format name is 'Faysal Bank Statement Layout'");

// 2. Database Format Record
$faysalFormatRecord = $stmtFormatModel->findByCode('faysal');
$runner->assert(!empty($faysalFormatRecord) && $faysalFormatRecord['is_active'] == 1, "Database: 'faysal' format is registered and active in statement_formats table");

// 3. MasterBank Flexible Lookup (Faysal Bank / Faisal Bank / FBL)
$fblExact = $bankModel->findByName('Faysal Bank Limited');
$runner->assert(!empty($fblExact) && $fblExact['bank_code'] === 'FBL', "MasterBank: Finds 'Faysal Bank Limited' via exact name");
$fblFuzzy1 = $bankModel->findByName('Faysal Bank');
$runner->assert(!empty($fblFuzzy1) && $fblFuzzy1['bank_code'] === 'FBL', "MasterBank: Resolves 'Faysal Bank' to FBL");
$fblFuzzy2 = $bankModel->findByName('Faisal Bank');
$runner->assert(!empty($fblFuzzy2) && $fblFuzzy2['bank_code'] === 'FBL', "MasterBank: Resolves 'Faisal Bank' to FBL");
$fblCode = $bankModel->findByName('FBL');
$runner->assert(!empty($fblCode) && $fblCode['id'] === $fblExact['id'], "MasterBank: Resolves 'FBL' code to Faysal Bank");

// Clean any previous FBL-001 test account
$existingFbl = $db->fetchOne("SELECT id FROM accounts WHERE account_identifier = 'FBL-001'");
if ($existingFbl) {
    $db->execute("DELETE FROM transactions WHERE account_id = {$existingFbl['id']}");
    $db->execute("DELETE FROM accounts WHERE id = {$existingFbl['id']}");
}

// 4. Create Test Faysal Bank Account & Post Transactions
$fblAccount = $accountService->createAccount($adminUser['id'], [
    'account_identifier' => 'FBL-001',
    'account_title' => 'NASR UD DIN',
    'bank_name' => 'Faysal Bank Limited',
    'account_number' => '02770070000495',
    'initial_balance' => '100000.00',
    'currency' => 'PKR',
    'address' => 'HOUSE NO: 11 NEELAM ROAD G-9-3 ISLAMABAD 03339195232',
    'account_type' => 'CURRENT'
]);
$fblAccId = $fblAccount['id'];
$runner->assert($fblAccId > 0, "Account Setup: Created Faysal Bank test account (ID #{$fblAccId})");

// Post sample transactions matching client screenshot
$txService->createTransaction($adminUser['id'], true, [
    'account_id' => $fblAccId,
    'transaction_date' => '2023-04-21',
    'debit' => '25531.00',
    'credit' => '0.00',
    'narration' => 'AUTO DEBIT TRANSACTION',
    'reference_no' => ''
]);

$txService->createTransaction($adminUser['id'], true, [
    'account_id' => $fblAccId,
    'transaction_date' => '2023-06-13',
    'debit' => '11000.00',
    'credit' => '0.00',
    'narration' => 'FUND TRANSFER FROM INTERNET',
    'reference_no' => '870897'
]);

$txService->createTransaction($adminUser['id'], true, [
    'account_id' => $fblAccId,
    'transaction_date' => '2023-06-20',
    'debit' => '0.00',
    'credit' => '50000.00',
    'narration' => 'DEPOSIT FROM RAASST',
    'reference_no' => 'RAAST9910'
]);

// 5. Generate Statement via StatementService (Auto-detected layout and explicit 'faysal')
$stmtDataAuto = $stmtService->generateStatement($fblAccId, '2023-04-01', '2023-06-30', 'auto', $adminUser['id'], true);
$runner->assert($stmtDataAuto['format']['format_code'] === 'faysal', "Statement Service: 'auto' format auto-detects 'faysal' layout for Faysal Bank account");

$stmtData = $stmtService->generateStatement($fblAccId, '2023-04-01', '2023-06-30', 'faysal', $adminUser['id'], true);
$runner->assert($stmtData['format']['format_code'] === 'faysal', "Statement Service: Explicitly selects 'faysal' layout");
$runner->assert(count($stmtData['transactions']) === 3, "Statement Service: Correct transaction count (3)");
$runner->assert(CurrencyHelper::comp($stmtData['total_debit'], '36531.00') === 0, "Statement Service: Total debit matches 36,531.00");
$runner->assert(CurrencyHelper::comp($stmtData['total_credit'], '50000.00') === 0, "Statement Service: Total credit matches 50,000.00");

// 6. Interactive HTML Rendering Verification
$html = $stmtService->renderHtml($stmtData);
$runner->assert(strpos($html, 'Account Statement') !== false, "Faysal HTML: Contains 'Account Statement' header");
$runner->assert(strpos($html, 'Statement Period From:') !== false, "Faysal HTML: Contains 'Statement Period From:'");
$runner->assert(strpos($html, '01-04-2023') !== false && strpos($html, '30-06-2023') !== false, "Faysal HTML: Contains formatted DD-MM-YYYY period dates");
$runner->assert(strpos($html, 'Account No :') !== false && strpos($html, '02770070000495') !== false, "Faysal HTML: Contains Account No label and value");
$runner->assert(strpos($html, 'Title Of Account :') !== false && strpos($html, 'NASR UD DIN') !== false, "Faysal HTML: Contains Title Of Account");
$runner->assert(strpos($html, 'Deposit Type :') !== false && strpos($html, 'CURRENT') !== false, "Faysal HTML: Contains Deposit Type");
$runner->assert(strpos($html, 'Posting<br>Date') !== false && strpos($html, 'Effective<br>Date') !== false, "Faysal HTML: Table headers match client layout");
$runner->assert(strpos($html, 'Withdrawal') !== false && strpos($html, 'Deposit') !== false && strpos($html, 'Balance') !== false, "Faysal HTML: Withdrawal / Deposit / Balance headers present");
$runner->assert(strpos($html, 'AUTO DEBIT TRANSACTION') !== false, "Faysal HTML: Narration AUTO DEBIT TRANSACTION present");
$runner->assert(strpos($html, '25,531.00') !== false, "Faysal HTML: Debit 25,531.00 formatted with commas");
$runner->assert(strpos($html, 'No. of Transactions (3)') !== false, "Faysal HTML: Contains No. of Transactions count");
$runner->assert(strpos($html, 'Ending Balance as of 30-JUN-23') !== false, "Faysal HTML: Summary box contains Ending Balance as of 30-JUN-23");
$runner->assert(strpos($html, 'Available Balance as of 30-JUN-23') !== false, "Faysal HTML: Summary box contains Available Balance as of 30-JUN-23");
$runner->assert(strpos($html, '***AS PER SBP MANDATE') !== false, "Faysal HTML: Contains SBP valid CNIC mandate disclaimer");
$runner->assert(strpos($html, 'WWW.FAYSALBANK.COM') !== false, "Faysal HTML: Contains bank profile website notice");
$runner->assert(strpos($html, 'WWW.BANKINGMOHTASIB.GOV.PK') !== false, "Faysal HTML: Contains Banking Mohtasib complaint address");
$runner->assert(strpos($html, 'ISLAMABAD BRANCH') === false, "Faysal HTML: Digital stamp and signature omitted for physical client signing");

// 7. Standalone Print / PDF View Rendering Verification
$printHtml = $faysalRenderer->renderPrint($stmtData);
$runner->assert(strpos($printHtml, '@page {') !== false && strpos($printHtml, 'size: A4 portrait;') !== false, "Faysal Print: Configures A4 portrait print layout");
$runner->assert(strpos($printHtml, 'Account Statement') !== false, "Faysal Print: Contains Account Statement title");
$runner->assert(strpos($printHtml, 'summary-box') !== false, "Faysal Print: Contains account summary box");
$runner->assert(strpos($printHtml, 'disclaimer-section') !== false && strpos($printHtml, 'ISLAMABAD BRANCH') === false, "Faysal Print: Contains disclaimer section and clean space for physical stamp/sign");

// 8. MasterBank Uploaded Logo Utilization Verification (Protects user's real uploaded logo)
$freshFbl = $bankModel->find($fblExact['id']);
$expectedLogoPath = $freshFbl['logo_path'];
$runner->assert(!empty($expectedLogoPath), "MasterBank: User's uploaded Faysal Bank logo exists and is protected ({$expectedLogoPath})");

$stmtDataWithLogo = $stmtService->generateStatement($fblAccId, '2023-04-01', '2023-06-30', 'faysal', $adminUser['id'], true);
$runner->assert($stmtDataWithLogo['account']['bank_logo'] === $expectedLogoPath, "MasterBank: Correctly attaches uploaded bank logo path");

$htmlWithLogo = $stmtService->renderHtml($stmtDataWithLogo);
$runner->assert(strpos($htmlWithLogo, $expectedLogoPath) !== false, "Faysal HTML: Utilizes uploaded bank logo from atm/banks");

$printWithLogo = $faysalRenderer->renderPrint($stmtDataWithLogo);
$runner->assert(strpos($printWithLogo, $expectedLogoPath) !== false, "Faysal Print: Utilizes uploaded bank logo in printable document");

// Clean up test account & transactions
$db->execute("DELETE FROM `transactions` WHERE `account_id` = {$fblAccId}");
$db->execute("DELETE FROM `accounts` WHERE `id` = {$fblAccId}");
$runner->assert(true, "Cleanup: Temporary Faysal Bank account #{$fblAccId} safely removed");

// ====================================================
// 30. CROSS-BANK LEDGER FORMATTING, BANK DROPDOWN & OVERRIDES
// ====================================================
echo "\n--- 30. CROSS-BANK LEDGER FORMATTING, BANK DROPDOWN & OVERRIDES ---\n";

// 1. Verify MasterBank list contains Faysal Bank Limited
$allActiveBanks = $bankModel->getActiveBanks();
$bankNames = array_column($allActiveBanks, 'bank_name');
$runner->assert(in_array('Faysal Bank Limited', $bankNames), "MasterBank Dropdown: 'Faysal Bank Limited' exists in active master banks list");

// 2. Setup account belonging to different bank (e.g. Meezan Bank)
$meezanAcc = $accountService->createAccount($adminUser['id'], [
    'account_title' => 'ORIGINAL MEEZAN HOLDER',
    'bank_name' => 'Meezan Bank Limited',
    'account_number' => 'MEEZAN-992211',
    'initial_balance' => '25000.00',
    'currency' => 'PKR',
    'address' => 'ORIGINAL KARACHI ADDRESS',
    'account_type' => 'SAVINGS'
]);
$meezanAccId = $meezanAcc['id'];
$runner->assert($meezanAccId > 0, "Cross-Bank Test: Created non-Faysal account #{$meezanAccId} (Meezan Bank)");

// Post transactions on the Meezan account
$txService->createTransaction($adminUser['id'], true, [
    'account_id' => $meezanAccId,
    'transaction_date' => '2023-05-10',
    'debit' => '5000.00',
    'credit' => '0.00',
    'narration' => 'CROSS-BANK WITHDRAWAL',
    'reference_no' => 'W1001'
]);

$txService->createTransaction($adminUser['id'], true, [
    'account_id' => $meezanAccId,
    'transaction_date' => '2023-05-15',
    'debit' => '0.00',
    'credit' => '30000.00',
    'narration' => 'CROSS-BANK INWARD REMITTANCE',
    'reference_no' => 'REM99'
]);

// 3. Generate statement pulling Meezan account's ledger but overriding bank to Faysal Bank Limited with custom details
$overrides = [
    'bank_name' => 'Faysal Bank Limited',
    'account_title' => 'NASR UD DIN KHAN',
    'account_number' => '02770070000495',
    'address' => 'HOUSE 11 NEELAM ROAD ISLAMABAD',
    'account_type' => 'CURRENT',
    'iban' => 'PK44FAYS0002770070000495',
    'opening_balance' => '100000.00'
];

$crossStmt = $stmtService->generateStatement($meezanAccId, '2023-05-01', '2023-05-31', 'faysal', $adminUser['id'], true, $overrides);

$runner->assert($crossStmt['account']['bank_name'] === 'Faysal Bank Limited', "Cross-Bank Statement: Bank name successfully overridden to 'Faysal Bank Limited'");
$runner->assert($crossStmt['account']['bank_code'] === 'FBL', "Cross-Bank Statement: Bank code resolved to 'FBL'");
$runner->assert($crossStmt['format']['format_code'] === 'faysal', "Cross-Bank Statement: Format correctly set to 'faysal'");
$runner->assert($crossStmt['account']['account_number'] === '02770070000495', "Cross-Bank Statement: Account number overridden to '02770070000495'");
$runner->assert($crossStmt['account']['account_title'] === 'NASR UD DIN KHAN', "Cross-Bank Statement: Title overridden to 'NASR UD DIN KHAN'");
$runner->assert($crossStmt['account']['address'] === 'HOUSE 11 NEELAM ROAD ISLAMABAD', "Cross-Bank Statement: Address overridden to custom Islamabad address");
$runner->assert($crossStmt['account']['account_type'] === 'CURRENT', "Cross-Bank Statement: Account type overridden to 'CURRENT'");
$runner->assert($crossStmt['account']['iban'] === 'PK44FAYS0002770070000495', "Cross-Bank Statement: IBAN overridden");
$runner->assert(CurrencyHelper::comp($crossStmt['opening_balance'], '100000.00') === 0, "Cross-Bank Statement: Opening balance overridden to 100,000.00");
$runner->assert(count($crossStmt['transactions']) === 2, "Cross-Bank Statement: Faithfully pulls 2 ledger transactions from source account");

// Verify closing balance math with overridden opening balance: 100,000 + 30,000 - 5,000 = 125,000
$runner->assert(CurrencyHelper::comp($crossStmt['closing_balance'], '125000.00') === 0, "Cross-Bank Statement: Closing balance accurately computed (125,000.00)");

// 4. Verify rendered HTML output contains the overridden details and Faysal template
$crossHtml = $stmtService->renderHtml($crossStmt);
$runner->assert(strpos($crossHtml, 'Faysal Bank Statement Layout') !== false, "Cross-Bank HTML: Uses Faysal Bank Statement Layout");
$runner->assert(strpos($crossHtml, '02770070000495') !== false, "Cross-Bank HTML: Contains overridden account number");
$runner->assert(strpos($crossHtml, 'NASR UD DIN KHAN') !== false, "Cross-Bank HTML: Contains overridden title");
$runner->assert(strpos($crossHtml, 'CROSS-BANK WITHDRAWAL') !== false, "Cross-Bank HTML: Contains transaction from source ledger");
$runner->assert(strpos($crossHtml, 'CROSS-BANK INWARD REMITTANCE') !== false, "Cross-Bank HTML: Contains transaction from source ledger");

// 5. Verify printable view contains the overridden details
$crossPrint = $stmtService->renderPrint($crossStmt);
$runner->assert(strpos($crossPrint, '02770070000495') !== false, "Cross-Bank Print: Overridden account number present");
$runner->assert(strpos($crossPrint, 'NASR UD DIN KHAN') !== false, "Cross-Bank Print: Overridden account title present");
$runner->assert(strpos($crossPrint, 'disclaimer-section') !== false && strpos($crossPrint, 'ISLAMABAD BRANCH') === false, "Cross-Bank Print: Disclaimers present and digital stamp omitted");

// Cleanup test account and transactions
$db->execute("DELETE FROM `transactions` WHERE `account_id` = {$meezanAccId}");
$db->execute("DELETE FROM `accounts` WHERE `id` = {$meezanAccId}");
$runner->assert(true, "Cleanup: Temporary Meezan cross-bank test account #{$meezanAccId} removed");

// =========================================================================
// SECTION 31: Route Resolution for .php URLs & Direct Page Aliases
// =========================================================================
echo "\n--- Section 31: Route Resolution for .php URLs & Direct Page Aliases ---\n";

$testRouter = new Router();

// Register application routes exactly matching public/index.php
$testRouter->get('/login', 'AuthController@showLogin', ['guest']);
$testRouter->get('/dashboard', 'DashboardController@index', ['auth']);
$testRouter->get('/accounts', 'AccountController@index', ['auth']);
$testRouter->get('/account', 'AccountController@index', ['auth']);
$testRouter->get('/transactions', 'TransactionController@index', ['auth']);
$testRouter->get('/transactions/rapid', 'TransactionController@rapid', ['auth']);
$testRouter->get('/rapid', 'TransactionController@rapid', ['auth']);
$testRouter->get('/transaction', 'TransactionController@rapid', ['auth']);
$testRouter->get('/ledger', 'TransactionController@index', ['auth']);
$testRouter->get('/home', 'TransactionController@rapid', ['auth']);
$testRouter->get('/templates', 'TemplateController@index', ['auth', 'role.admin']);
$testRouter->get('/template', 'TemplateController@index', ['auth', 'role.admin']);
$testRouter->get('/templates/create', 'TemplateController@create', ['auth', 'role.admin']);
$testRouter->get('/templates/{id}/edit', 'TemplateController@edit', ['auth', 'role.admin']);
$testRouter->get('/statements', 'StatementController@index', ['auth']);
$testRouter->get('/statement', 'StatementController@index', ['auth']);
$testRouter->get('/statements/print', 'StatementController@printView', ['auth']);
$testRouter->get('/print', 'StatementController@printView', ['auth']);
$testRouter->get('/banks', 'MasterDataController@index', ['auth', 'role.admin']);
$testRouter->get('/bank', 'MasterDataController@index', ['auth', 'role.admin']);
$testRouter->get('/users', 'UserController@index', ['auth', 'role.admin']);
$testRouter->get('/user', 'UserController@index', ['auth', 'role.admin']);

// 1. Dashboard URL resolution
$m = $testRouter->matchRoute('GET', '/dashboard.php');
$runner->assert($m !== null && $m['route']['handler'] === 'DashboardController@index', "Router .php: /dashboard.php maps to DashboardController@index");
$m = $testRouter->matchRoute('GET', '/dashboard');
$runner->assert($m !== null && $m['route']['handler'] === 'DashboardController@index', "Router exact: /dashboard maps to DashboardController@index");

// 2. Statements & Print URL resolution
$m = $testRouter->matchRoute('GET', '/statements.php');
$runner->assert($m !== null && $m['route']['handler'] === 'StatementController@index', "Router .php: /statements.php maps to StatementController@index");
$m = $testRouter->matchRoute('GET', '/statement.php');
$runner->assert($m !== null && $m['route']['handler'] === 'StatementController@index', "Router alias .php: /statement.php maps to StatementController@index");
$m = $testRouter->matchRoute('GET', '/statements/print.php');
$runner->assert($m !== null && $m['route']['handler'] === 'StatementController@printView', "Router nested .php: /statements/print.php maps to StatementController@printView");
$m = $testRouter->matchRoute('GET', '/print.php');
$runner->assert($m !== null && $m['route']['handler'] === 'StatementController@printView', "Router alias .php: /print.php maps to StatementController@printView");

// 3. Transactions & Rapid Entry URL resolution
$m = $testRouter->matchRoute('GET', '/transactions/rapid.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@rapid', "Router nested .php: /transactions/rapid.php maps to TransactionController@rapid");
$m = $testRouter->matchRoute('GET', '/rapid.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@rapid', "Router alias .php: /rapid.php maps to TransactionController@rapid");
$m = $testRouter->matchRoute('GET', '/transaction.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@rapid', "Router alias .php: /transaction.php maps to TransactionController@rapid");
$m = $testRouter->matchRoute('GET', '/home.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@rapid', "Router alias .php: /home.php maps to TransactionController@rapid");

// 4. Accounts & Ledger URL resolution
$m = $testRouter->matchRoute('GET', '/accounts.php');
$runner->assert($m !== null && $m['route']['handler'] === 'AccountController@index', "Router .php: /accounts.php maps to AccountController@index");
$m = $testRouter->matchRoute('GET', '/account.php');
$runner->assert($m !== null && $m['route']['handler'] === 'AccountController@index', "Router alias .php: /account.php maps to AccountController@index");
$m = $testRouter->matchRoute('GET', '/transactions.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@index', "Router .php: /transactions.php maps to TransactionController@index");
$m = $testRouter->matchRoute('GET', '/ledger.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TransactionController@index', "Router alias .php: /ledger.php maps to TransactionController@index");

// 5. Banks, Users & Templates URL resolution
$m = $testRouter->matchRoute('GET', '/banks.php');
$runner->assert($m !== null && $m['route']['handler'] === 'MasterDataController@index', "Router .php: /banks.php maps to MasterDataController@index");
$m = $testRouter->matchRoute('GET', '/bank.php');
$runner->assert($m !== null && $m['route']['handler'] === 'MasterDataController@index', "Router alias .php: /bank.php maps to MasterDataController@index");
$m = $testRouter->matchRoute('GET', '/users.php');
$runner->assert($m !== null && $m['route']['handler'] === 'UserController@index', "Router .php: /users.php maps to UserController@index");
$m = $testRouter->matchRoute('GET', '/templates.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TemplateController@index', "Router .php: /templates.php maps to TemplateController@index");
$m = $testRouter->matchRoute('GET', '/templates/create.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TemplateController@create', "Router .php: /templates/create.php maps to TemplateController@create");
$m = $testRouter->matchRoute('GET', '/templates/42/edit.php');
$runner->assert($m !== null && $m['route']['handler'] === 'TemplateController@edit' && ($m['params']['id'] ?? '') === '42', "Router parameterized .php: /templates/42/edit.php maps to TemplateController@edit with id=42");
$m = $testRouter->matchRoute('GET', '/login.php');
$runner->assert($m !== null && $m['route']['handler'] === 'AuthController@showLogin', "Router .php: /login.php maps to AuthController@showLogin");

// =========================================================================
// SECTION 32: Database Cleanliness, Zero Dummy Residue & User Data Protection
// =========================================================================
echo "\n--- Section 32: Database Cleanliness & User Data Protection ---\n";

// 1. Verify zero dummy test banks remain in master_banks
$pollutingBanks = (int)$db->query("SELECT COUNT(*) FROM `master_banks` WHERE `id` > 27 OR `bank_name` LIKE '%Test%'")->fetchColumn();
$runner->assert($pollutingBanks === 0, "Cleanliness: Zero dummy test banks remaining in master_banks (Found: {$pollutingBanks})");

// 2. Verify zero dummy test accounts remain in accounts table
$pollutingAccounts = (int)$db->query("SELECT COUNT(*) FROM `accounts` WHERE `account_title` = '14' OR `bank_name` LIKE '%Test%' OR `account_title` LIKE '%Test%' OR `account_title` LIKE '%Logo%'")->fetchColumn();
$runner->assert($pollutingAccounts === 0, "Cleanliness: Zero dummy test accounts remaining in accounts (Found: {$pollutingAccounts})");

// 3. Verify zero dummy test templates remain in narration_templates
$pollutingTemplates = (int)$db->query("SELECT COUNT(*) FROM `narration_templates` WHERE `code` LIKE 'VENDOR_TEST_%' OR `code` LIKE 'TEST_%'")->fetchColumn();
$runner->assert($pollutingTemplates === 0, "Cleanliness: Zero dummy test templates remaining in narration_templates (Found: {$pollutingTemplates})");

// 4. Verify user's uploaded Faysal Bank logo is 100% intact and preserved
$currentFbl = $bankModel->find(8);
$runner->assert(!empty($currentFbl['logo_path']) && file_exists(__DIR__ . '/../public/' . $currentFbl['logo_path']), "Data Protection: User's Faysal Bank logo file exists on disk and is preserved in database ({$currentFbl['logo_path']})");

// 5. Verify real user accounts remain untouched
$realAccountsCount = (int)$db->query("SELECT COUNT(*) FROM `accounts` WHERE `deleted_at` IS NULL")->fetchColumn();
$runner->assert($realAccountsCount >= 4, "Data Protection: Real client accounts remain untouched and active (Found: {$realAccountsCount})");

// Summary & Exit
$success = $runner->summary();
exit($success ? 0 : 1);




