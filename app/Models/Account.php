<?php
// app/Models/Account.php

require_once __DIR__ . '/BaseModel.php';

class Account extends BaseModel {
    protected string $table = 'accounts';

    public function getUserAccounts(int $userId, bool $includeInactive = false): array {
        $statusClause = $includeInactive ? "" : "AND a.status = 'active'";
        $sql = "SELECT a.*, 
                COALESCE(SUM(t.credit - t.debit), 0) + a.initial_balance AS current_balance,
                COUNT(t.id) AS posted_tx_count,
                MAX(t.transaction_date) AS max_tx_date,
                MIN(t.transaction_date) AS min_tx_date
                FROM `accounts` a
                LEFT JOIN `transactions` t ON t.account_id = a.id AND t.deleted_at IS NULL AND t.status = 'posted'
                WHERE a.user_id = ? AND a.deleted_at IS NULL {$statusClause}
                GROUP BY a.id
                ORDER BY a.id ASC";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getAllAccounts(bool $includeInactive = false): array {
        $statusClause = $includeInactive ? "" : "AND a.status = 'active'";
        $sql = "SELECT a.*, u.username, u.full_name AS owner_name,
                COALESCE(SUM(t.credit - t.debit), 0) + a.initial_balance AS current_balance,
                COUNT(t.id) AS posted_tx_count,
                MAX(t.transaction_date) AS max_tx_date,
                MIN(t.transaction_date) AS min_tx_date
                FROM `accounts` a
                INNER JOIN `users` u ON u.id = a.user_id
                LEFT JOIN `transactions` t ON t.account_id = a.id AND t.deleted_at IS NULL AND t.status = 'posted'
                WHERE a.deleted_at IS NULL {$statusClause}
                GROUP BY a.id
                ORDER BY a.id ASC";
        return $this->db->fetchAll($sql);
    }

    public function getAccountWithBalance(int $id): ?array {
        $sql = "SELECT a.*, u.username, u.full_name AS owner_name,
                COALESCE(SUM(t.credit - t.debit), 0) + a.initial_balance AS current_balance,
                COUNT(t.id) AS posted_tx_count,
                MAX(t.transaction_date) AS max_tx_date,
                MIN(t.transaction_date) AS min_tx_date
                FROM `accounts` a
                INNER JOIN `users` u ON u.id = a.user_id
                LEFT JOIN `transactions` t ON t.account_id = a.id AND t.deleted_at IS NULL AND t.status = 'posted'
                WHERE a.id = ? AND a.deleted_at IS NULL
                GROUP BY a.id
                LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function hasPostedTransactions(int $accountId): bool {
        $sql = "SELECT COUNT(*) FROM `transactions` WHERE `account_id` = ? AND `status` = 'posted' AND `deleted_at` IS NULL";
        return ((int)$this->db->fetchColumn($sql, [$accountId])) > 0;
    }

    public function findByBankAndAccountNumber(int $userId, string $bankName, string $accountNumber, ?int $excludeAccountId = null): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = ? AND `bank_name` = ? AND `account_number` = ? AND `deleted_at` IS NULL";
        $params = [$userId, $bankName, $accountNumber];
        if ($excludeAccountId !== null) {
            $sql .= " AND `id` != ?";
            $params[] = $excludeAccountId;
        }
        $sql .= " LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }

    public function findByUserAndIdentifier(int $userId, string $identifier, ?int $excludeAccountId = null): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = ? AND (`account_identifier` = ? OR `id` = ?) AND `deleted_at` IS NULL";
        $params = [$userId, $identifier, is_numeric($identifier) ? (int)$identifier : 0];
        if ($excludeAccountId !== null) {
            $sql .= " AND `id` != ?";
            $params[] = $excludeAccountId;
        }
        $sql .= " LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }

    public function countAccounts(?int $userId = null): int {
        if ($userId !== null) {
            return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `accounts` WHERE `user_id` = ? AND `deleted_at` IS NULL", [$userId]);
        }
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `accounts` WHERE `deleted_at` IS NULL");
    }
}
