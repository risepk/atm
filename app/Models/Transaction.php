<?php
// app/Models/Transaction.php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/CurrencyHelper.php';

class Transaction extends BaseModel {
    protected string $table = 'transactions';

    public function getFilteredTransactions(array $filters, int $page = 1, int $limit = 20): array {
        $where = ["t.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "t.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['account_id'])) {
            $where[] = "t.account_id = ?";
            $params[] = (int)$filters['account_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "t.transaction_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "t.transaction_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(t.narration LIKE ? OR t.reference_no LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $limit;

        $countSql = "SELECT COUNT(*) FROM `transactions` t WHERE {$whereSql}";
        $total = (int)$this->db->fetchColumn($countSql, $params);

        $sql = "SELECT t.*, a.account_title, a.account_number, a.bank_name, u.username, u.full_name AS user_name
                FROM `transactions` t
                INNER JOIN `accounts` a ON a.id = t.account_id
                INNER JOIN `users` u ON u.id = t.user_id
                WHERE {$whereSql}
                ORDER BY t.transaction_date DESC, t.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    public function getAccountLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array {
        $where = ["t.account_id = ?", "t.deleted_at IS NULL", "t.status = 'posted'"];
        $params = [$accountId];

        if ($startDate) {
            $where[] = "t.transaction_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $where[] = "t.transaction_date <= ?";
            $params[] = $endDate;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT t.*, a.account_title, a.account_number, a.bank_name
                FROM `transactions` t
                INNER JOIN `accounts` a ON a.id = t.account_id
                WHERE {$whereSql}
                ORDER BY t.transaction_date ASC, t.id ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getOpeningBalanceBefore(int $accountId, string $startDate, string $initialBalance = '0.00'): string {
        $sql = "SELECT COALESCE(SUM(credit), 0) AS total_cr, COALESCE(SUM(debit), 0) AS total_dr
                FROM `transactions` 
                WHERE `account_id` = ? AND `deleted_at` IS NULL AND `status` = 'posted' AND `transaction_date` < ?";
        $row = $this->db->fetchOne($sql, [$accountId, $startDate]);
        $totalCr = (string)($row['total_cr'] ?? '0.00');
        $totalDr = (string)($row['total_dr'] ?? '0.00');
        
        $net = CurrencyHelper::sub($totalCr, $totalDr);
        return CurrencyHelper::add($initialBalance, $net);
    }

    public function getTotals(?int $userId = null): array {
        $where = "WHERE deleted_at IS NULL AND status = 'posted'";
        $params = [];
        if ($userId !== null) {
            $where .= " AND user_id = ?";
            $params[] = $userId;
        }
        $sql = "SELECT 
                COALESCE(SUM(debit), 0) AS total_debit, 
                COALESCE(SUM(credit), 0) AS total_credit,
                COUNT(*) AS total_count
                FROM `transactions` {$where}";
        return $this->db->fetchOne($sql, $params);
    }

    public function getRecentDistinctNarrations(int $userId, ?int $accountId = null, ?string $query = null, int $limit = 20): array {
        $where = ["t.user_id = ?", "t.deleted_at IS NULL", "t.status = 'posted'", "t.narration != ''"];
        $params = [$userId];
        if ($accountId !== null && $accountId > 0) {
            $where[] = "t.account_id = ?";
            $params[] = $accountId;
        }
        if ($query !== null && trim($query) !== '') {
            $where[] = "t.narration LIKE ?";
            $params[] = '%' . trim($query) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $limit = max(1, min(50, $limit));
        $sql = "SELECT t.narration, MAX(t.id) as max_id
                FROM `transactions` t
                WHERE {$whereSql}
                GROUP BY t.narration
                ORDER BY max_id DESC
                LIMIT {$limit}";
        $rows = $this->db->fetchAll($sql, $params);
        return array_column($rows, 'narration');
    }
}
