<?php
// app/Services/AccountService.php

require_once __DIR__ . '/../Models/Account.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/AuditService.php';

class AccountService {
    private Account $accountModel;
    private AuditService $auditService;
    private Database $db;

    public function __construct() {
        $this->accountModel = new Account();
        $this->auditService = new AuditService();
        $this->db = Database::getInstance();
    }

    public function verifyOwnership(int $accountId, int $userId, bool $isAdmin): array {
        $account = $this->accountModel->find($accountId);
        if (!$account) {
            throw new Exception("Account #{$accountId} not found.");
        }
        if (!$isAdmin && (int)$account['user_id'] !== $userId) {
            throw new Exception("Unauthorized access to account #{$accountId}.");
        }
        return $account;
    }

    public function listAccounts(int $userId, bool $isAdmin, bool $includeInactive = false): array {
        if ($isAdmin) {
            return $this->accountModel->getAllAccounts($includeInactive);
        }
        return $this->accountModel->getUserAccounts($userId, $includeInactive);
    }

    public function getAccount(int $accountId, int $userId, bool $isAdmin): array {
        $this->verifyOwnership($accountId, $userId, $isAdmin);
        return $this->accountModel->getAccountWithBalance($accountId);
    }

    public function createAccount(int $userId, array $data): array {
        $status = $data['status'] ?? 'active';
        $initialBalance = '0.00';
        if (isset($data['initial_balance']) && CurrencyHelper::isValidDecimal($data['initial_balance'])) {
            $initialBalance = CurrencyHelper::normalizeDecimal($data['initial_balance']);
        }

        $bankName = SecurityHelper::clean($data['bank_name'] ?? '');
        $accountNumber = SecurityHelper::clean($data['account_number'] ?? '');
        $accountIdentifier = SecurityHelper::clean($data['account_identifier'] ?? '');
        $accountTitle = SecurityHelper::clean($data['account_title'] ?? '');

        if ($accountTitle === '' && $accountIdentifier !== '') {
            $accountTitle = $accountIdentifier;
        } elseif ($accountIdentifier === '' && $accountTitle !== '') {
            $accountIdentifier = $accountTitle;
        } elseif ($accountIdentifier === '' && $accountNumber !== '') {
            $accountIdentifier = $accountNumber;
            if ($accountTitle === '') $accountTitle = $accountNumber;
        }

        if ($accountTitle === '') {
            throw new Exception("Account Number / Code is required.");
        }

        if ($accountIdentifier !== '') {
            $existingIdent = $this->accountModel->findByUserAndIdentifier($userId, $accountIdentifier);
            if ($existingIdent) {
                throw new Exception("An account with Identifier '{$accountIdentifier}' already exists for this user.");
            }
        }

        if ($accountNumber !== '' && $bankName !== '') {
            $existing = $this->accountModel->findByBankAndAccountNumber($userId, $bankName, $accountNumber);
            if ($existing) {
                throw new Exception("An account with Bank '{$bankName}' and Account Number '{$accountNumber}' already exists for this user.");
            }
        }

        $accountData = [
            'uuid' => SecurityHelper::uuid(),
            'user_id' => $userId,
            'account_title' => $accountTitle,
            'account_identifier' => $accountIdentifier,
            'father_name' => SecurityHelper::clean($data['father_name'] ?? ''),
            'account_number' => $accountNumber,
            'account_type' => SecurityHelper::clean($data['account_type'] ?? 'Current'),
            'iban' => SecurityHelper::clean($data['iban'] ?? ''),
            'bank_name' => $bankName,
            'currency' => $data['currency'] ?? 'PKR',
            'initial_balance' => $initialBalance,
            'status' => in_array($status, ['active', 'inactive', 'closed'], true) ? $status : 'active',
            'notes' => SecurityHelper::clean($data['notes'] ?? ''),
            'address' => SecurityHelper::clean($data['address'] ?? '')
        ];

        $this->db->beginTransaction();
        try {
            $accountId = $this->accountModel->insert($accountData);
            $this->auditService->log('ACCOUNT_CREATE', 'accounts', $accountId, null, $accountData);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->getAccount($accountId, $userId, true);
    }

    public function updateAccount(int $accountId, int $userId, bool $isAdmin, array $data): array {
        $existing = $this->verifyOwnership($accountId, $userId, $isAdmin);

        // Check Opening Balance Integrity:
        // Once transactions are posted, initial_balance is strictly IMMUTABLE!
        $hasPosted = $this->accountModel->hasPostedTransactions($accountId);
        if ($hasPosted && isset($data['initial_balance'])) {
            $proposedBal = CurrencyHelper::normalizeDecimal($data['initial_balance']);
            $existingBal = CurrencyHelper::normalizeDecimal($existing['initial_balance']);
            if (CurrencyHelper::comp($proposedBal, $existingBal) !== 0) {
                throw new Exception("Initial balance cannot be modified because transactions have already been posted to Account #{$accountId}.");
            }
        }

        $newIdent = isset($data['account_identifier']) ? SecurityHelper::clean($data['account_identifier']) : ($existing['account_identifier'] ?? '');
        if ($newIdent !== '') {
            $existingIdent = $this->accountModel->findByUserAndIdentifier($userId, $newIdent, $accountId);
            if ($existingIdent) {
                throw new Exception("An account with Identifier '{$newIdent}' already exists for this user.");
            }
        }

        $status = $data['status'] ?? $existing['status'];
        $updateData = [
            'account_title' => SecurityHelper::clean($data['account_title'] ?? $existing['account_title']),
            'account_identifier' => $newIdent,
            'father_name' => isset($data['father_name']) ? SecurityHelper::clean($data['father_name']) : ($existing['father_name'] ?? ''),
            'account_number' => SecurityHelper::clean($data['account_number'] ?? $existing['account_number']),
            'account_type' => isset($data['account_type']) ? SecurityHelper::clean($data['account_type']) : ($existing['account_type'] ?? 'Current'),
            'iban' => isset($data['iban']) ? SecurityHelper::clean($data['iban']) : ($existing['iban'] ?? ''),
            'bank_name' => SecurityHelper::clean($data['bank_name'] ?? $existing['bank_name']),
            'status' => in_array($status, ['active', 'inactive', 'closed'], true) ? $status : $existing['status'],
            'notes' => SecurityHelper::clean($data['notes'] ?? $existing['notes']),
            'address' => isset($data['address']) ? SecurityHelper::clean($data['address']) : ($existing['address'] ?? '')
        ];

        // Only allow updating initial balance if NO transactions exist yet
        if (!$hasPosted && isset($data['initial_balance']) && CurrencyHelper::isValidDecimal($data['initial_balance'])) {
            $updateData['initial_balance'] = CurrencyHelper::normalizeDecimal($data['initial_balance']);
        }

        $this->db->beginTransaction();
        try {
            $this->accountModel->update($accountId, $updateData);
            $this->auditService->log('ACCOUNT_UPDATE', 'accounts', $accountId, $existing, $updateData);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->getAccount($accountId, $userId, $isAdmin);
    }

    public function softDeleteAccount(int $accountId, int $userId, bool $isAdmin): bool {
        $existing = $this->verifyOwnership($accountId, $userId, $isAdmin);
        $this->db->beginTransaction();
        try {
            $this->accountModel->softDelete($accountId, $userId);
            $this->auditService->log('ACCOUNT_DELETE', 'accounts', $accountId, $existing, ['deleted_at' => date('Y-m-d H:i:s')]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        return true;
    }

    public function deleteAccount(int $accountId, int $userId, bool $isAdmin): bool {
        return $this->softDeleteAccount($accountId, $userId, $isAdmin);
    }
}
