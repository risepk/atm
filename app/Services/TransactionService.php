<?php
// app/Services/TransactionService.php

require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/Account.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/AccountService.php';
require_once __DIR__ . '/NarrationService.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/../Core/Database.php';

class TransactionService {
    private Transaction $txModel;
    private AccountService $accountService;
    private NarrationService $narrationService;
    private AuditService $auditService;
    private Database $db;

    public function __construct() {
        $this->txModel = new Transaction();
        $this->accountService = new AccountService();
        $this->narrationService = new NarrationService();
        $this->auditService = new AuditService();
        $this->db = Database::getInstance();
    }

    public function createTransaction(int $userId, bool $isAdmin, array $data): array {
        $accountId = (int)($data['account_id'] ?? 0);
        
        // 1. Authorize Account Ownership (Critical IDOR Guard)
        $account = $this->accountService->verifyOwnership($accountId, $userId, $isAdmin);

        // 2. Account Status Validation: Inactive / Closed accounts cannot receive transactions!
        if ($account['status'] !== 'active') {
            throw new Exception("Cannot post transaction to Account #{$accountId} because its status is '{$account['status']}'.");
        }

        // 3. Strict Decimal Financial Validation (No PHP Floats!)
        $debitRaw = trim((string)($data['debit'] ?? '0.00'));
        $creditRaw = trim((string)($data['credit'] ?? '0.00'));

        if (!CurrencyHelper::isValidDecimal($debitRaw) || !CurrencyHelper::isValidDecimal($creditRaw)) {
            throw new Exception("Invalid monetary amount. Amounts must be numeric with up to 2 decimal places.");
        }

        $debit = CurrencyHelper::normalizeDecimal($debitRaw);
        $credit = CurrencyHelper::normalizeDecimal($creditRaw);

        $hasDebit = CurrencyHelper::comp($debit, '0.00') > 0;
        $hasCredit = CurrencyHelper::comp($credit, '0.00') > 0;

        if ($hasDebit && $hasCredit) {
            throw new Exception("A transaction cannot contain both Debit and Credit amounts.");
        }

        if (!$hasDebit && !$hasCredit) {
            throw new Exception("Please enter either a Debit or Credit amount greater than zero.");
        }

        // 4. Final Narration Validation
        $narration = trim((string)($data['narration'] ?? ''));
        if ($narration === '') {
            throw new Exception("Narration / Description is required.");
        }

        // 5. Date Validation (supports back dates and future dates, normalizes format)
        $txDate = trim((string)($data['transaction_date'] ?? date('Y-m-d')));
        $d = DateTime::createFromFormat('Y-m-d', $txDate);
        if (!$d || $d->format('Y-m-d') !== $txDate) {
            $parsedTs = strtotime(str_replace('/', '-', $txDate));
            if ($parsedTs !== false) {
                $txDate = date('Y-m-d', $parsedTs);
            } else {
                throw new Exception("Invalid transaction date format. Expected YYYY-MM-DD.");
            }
        }

        // 6. Server-Side Template & Payload Validation
        $templateId = !empty($data['template_id']) ? (int)$data['template_id'] : null;
        $templateVersion = !empty($data['template_version']) ? (int)$data['template_version'] : null;
        $templatePayloadJson = null;

        if ($templateId) {
            $rawPayload = is_array($data['template_payload'] ?? null) 
                ? $data['template_payload'] 
                : json_decode($data['template_payload'] ?? '{}', true);

            $validatedTpl = $this->narrationService->validateTransactionPayload($templateId, $templateVersion, $rawPayload ?: []);
            $templateVersion = (int)$validatedTpl['template']['version'];
            $templatePayloadJson = json_encode($validatedTpl['sanitized_payload']);
        }

        $referenceNo = !empty($data['reference_no']) ? SecurityHelper::clean($data['reference_no']) : null;

        $txData = [
            'uuid' => SecurityHelper::uuid(),
            'account_id' => $accountId,
            'user_id' => (int)$account['user_id'], // Account owner identity
            'template_id' => $templateId,
            'template_version' => $templateVersion,
            'transaction_date' => $txDate,
            'debit' => $debit,
            'credit' => $credit,
            'narration' => $narration,
            'template_payload' => $templatePayloadJson,
            'reference_no' => $referenceNo,
            'status' => 'posted',
            'created_by' => $userId
        ];

        // 7. Atomic Transaction + Audit (PDO Transaction Boundary)
        $this->db->beginTransaction();
        try {
            $txId = $this->txModel->insert($txData);
            $this->auditService->log('TRANSACTION_POST', 'transactions', $txId, null, $txData);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        // Fetch updated account balance
        $updatedAccount = $this->accountService->getAccount($accountId, $userId, $isAdmin);

        return [
            'transaction_id' => $txId,
            'account_id' => $accountId,
            'new_balance' => $updatedAccount['current_balance']
        ];
    }

    public function updateTransaction(int $txId, int $userId, bool $isAdmin, array $data): array {
        $existing = $this->txModel->find($txId);
        if (!$existing) {
            throw new Exception("Transaction #{$txId} not found.");
        }

        // Check if transaction is voided: Cannot edit voided transaction!
        if ($existing['status'] === 'voided') {
            throw new Exception("Voided transactions cannot be edited.");
        }

        // IDOR Verification on existing transaction
        if (!$isAdmin && (int)$existing['user_id'] !== $userId) {
            throw new Exception("Unauthorized access: You do not own Transaction #{$txId}.");
        }

        $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : (int)$existing['account_id'];
        $account = $this->accountService->verifyOwnership($accountId, $userId, $isAdmin);

        if ($account['status'] !== 'active') {
            throw new Exception("Target Account #{$accountId} is '{$account['status']}' and cannot accept transactions.");
        }

        // Financial Validation (Strict Decimal Strings)
        $debitRaw = trim((string)($data['debit'] ?? $existing['debit']));
        $creditRaw = trim((string)($data['credit'] ?? $existing['credit']));

        if (!CurrencyHelper::isValidDecimal($debitRaw) || !CurrencyHelper::isValidDecimal($creditRaw)) {
            throw new Exception("Invalid monetary amount. Amounts must be numeric with up to 2 decimal places.");
        }

        $debit = CurrencyHelper::normalizeDecimal($debitRaw);
        $credit = CurrencyHelper::normalizeDecimal($creditRaw);

        $hasDebit = CurrencyHelper::comp($debit, '0.00') > 0;
        $hasCredit = CurrencyHelper::comp($credit, '0.00') > 0;

        if ($hasDebit && $hasCredit) {
            throw new Exception("A transaction cannot contain both Debit and Credit amounts.");
        }

        if (!$hasDebit && !$hasCredit) {
            throw new Exception("Please enter either a Debit or Credit amount greater than zero.");
        }

        $narration = trim((string)($data['narration'] ?? $existing['narration']));
        if ($narration === '') {
            throw new Exception("Narration / Description is required.");
        }

        $txDate = trim((string)($data['transaction_date'] ?? $existing['transaction_date']));
        $d = DateTime::createFromFormat('Y-m-d', $txDate);
        if (!$d || $d->format('Y-m-d') !== $txDate) {
            $parsedTs = strtotime(str_replace('/', '-', $txDate));
            if ($parsedTs !== false) {
                $txDate = date('Y-m-d', $parsedTs);
            } else {
                throw new Exception("Invalid transaction date format. Expected YYYY-MM-DD.");
            }
        }

        $templateId = !empty($data['template_id']) ? (int)$data['template_id'] : null;
        $templateVersion = !empty($data['template_version']) ? (int)$data['template_version'] : null;
        $templatePayloadJson = null;

        if ($templateId) {
            $rawPayload = is_array($data['template_payload'] ?? null) 
                ? $data['template_payload'] 
                : json_decode($data['template_payload'] ?? '{}', true);

            $validatedTpl = $this->narrationService->validateTransactionPayload($templateId, $templateVersion, $rawPayload ?: []);
            $templateVersion = (int)$validatedTpl['template']['version'];
            $templatePayloadJson = json_encode($validatedTpl['sanitized_payload']);
        }

        $referenceNo = isset($data['reference_no']) ? SecurityHelper::clean($data['reference_no']) : $existing['reference_no'];

        $updateData = [
            'account_id' => $accountId,
            'template_id' => $templateId,
            'template_version' => $templateVersion,
            'transaction_date' => $txDate,
            'debit' => $debit,
            'credit' => $credit,
            'narration' => $narration,
            'template_payload' => $templatePayloadJson,
            'reference_no' => $referenceNo,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Atomic update + audit
        $this->db->beginTransaction();
        try {
            $this->txModel->update($txId, $updateData);
            $this->auditService->log('TRANSACTION_UPDATE', 'transactions', $txId, $existing, $updateData);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        $updatedAccount = $this->accountService->getAccount($accountId, $userId, $isAdmin);

        return [
            'transaction_id' => $txId,
            'account_id' => $accountId,
            'new_balance' => $updatedAccount['current_balance']
        ];
    }

    public function voidTransaction(int $txId, int $userId, bool $isAdmin, string $reason = ''): bool {
        $tx = $this->txModel->find($txId);
        if (!$tx) {
            throw new Exception("Transaction #{$txId} not found.");
        }

        if ($tx['status'] === 'voided') {
            throw new Exception("Transaction #{$txId} is already voided.");
        }

        // Verify Account Ownership
        $this->accountService->verifyOwnership((int)$tx['account_id'], $userId, $isAdmin);

        $now = date('Y-m-d H:i:s');
        $voidData = [
            'status' => 'voided',
            'voided_at' => $now,
            'voided_by' => $userId,
            'void_reason' => SecurityHelper::clean($reason ?: 'Voided by user'),
            'deleted_at' => $now,
            'deleted_by' => $userId,
            'updated_at' => $now
        ];

        // Atomic void + audit
        $this->db->beginTransaction();
        try {
            $this->txModel->update($txId, $voidData);
            $this->auditService->log('TRANSACTION_VOID', 'transactions', $txId, $tx, $voidData);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    public function getTransaction(int $txId, int $userId, bool $isAdmin): array {
        $tx = $this->txModel->find($txId);
        if (!$tx) {
            throw new Exception("Transaction #{$txId} not found.");
        }
        if (!$isAdmin && (int)$tx['user_id'] !== $userId) {
            throw new Exception("Unauthorized access to Transaction #{$txId}.");
        }
        return $tx;
    }

    public function getFilteredTransactions(array $filters, int $userId, bool $isAdmin, int $page = 1, int $limit = 20): array {
        if (!$isAdmin) {
            $filters['user_id'] = $userId;
        }
        if (!empty($filters['account_id'])) {
            $this->accountService->verifyOwnership((int)$filters['account_id'], $userId, $isAdmin);
        }

        return $this->txModel->getFilteredTransactions($filters, $page, $limit);
    }

    public function getRecentAccountTransactions(int $accountId, int $userId, bool $isAdmin, int $limit = 0): array {
        $account = $this->accountService->getAccount($accountId, $userId, $isAdmin);

        $limitSql = ($limit > 0) ? "LIMIT {$limit}" : "";
        $sql = "SELECT t.*, u.username, u.full_name AS user_name
                FROM `transactions` t
                INNER JOIN `users` u ON u.id = t.user_id
                WHERE t.account_id = ? 
                  AND t.deleted_at IS NULL 
                ORDER BY t.transaction_date DESC, t.id DESC
                {$limitSql}";
        $rows = $this->db->fetchAll($sql, [$accountId]);

        $formatted = [];
        foreach ($rows as $r) {
            $hasDebit = CurrencyHelper::comp((string)$r['debit'], '0.00') > 0;
            $formatted[] = [
                'id' => (int)$r['id'],
                'uuid' => $r['uuid'] ?? '',
                'transaction_date' => $r['transaction_date'],
                'created_at' => $r['created_at'],
                'time' => date('H:i:s', strtotime($r['created_at'])),
                'type' => $hasDebit ? 'debit' : 'credit',
                'amount' => $hasDebit ? $r['debit'] : $r['credit'],
                'debit' => $r['debit'],
                'credit' => $r['credit'],
                'narration' => $r['narration'],
                'reference_no' => $r['reference_no'] ?? '',
                'bank_name' => $account['bank_name'] ?? '',
                'account_id' => (int)$account['id'],
                'status' => $r['status']
            ];
        }

        return [
            'account' => [
                'id' => (int)$account['id'],
                'account_title' => $account['account_title'],
                'bank_name' => $account['bank_name'],
                'account_number' => $account['account_number'],
                'initial_balance' => $account['initial_balance'],
                'current_balance' => $account['current_balance'],
                'formatted_balance' => CurrencyHelper::format($account['current_balance']),
                'status' => $account['status']
            ],
            'transactions' => $formatted
        ];
    }
}

