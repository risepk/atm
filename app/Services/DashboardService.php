<?php
// app/Services/DashboardService.php

require_once __DIR__ . '/../Models/Account.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class DashboardService {
    private Account $accountModel;
    private Transaction $txModel;
    private User $userModel;
    private AuditLog $auditModel;

    public function __construct() {
        $this->accountModel = new Account();
        $this->txModel = new Transaction();
        $this->userModel = new User();
        $this->auditModel = new AuditLog();
    }

    public function getUserMetrics(int $userId): array {
        $accounts = $this->accountModel->getUserAccounts($userId);
        $totals = $this->txModel->getTotals($userId);

        $totalBalance = 0.00;
        foreach ($accounts as $acc) {
            $totalBalance += (float)$acc['current_balance'];
        }

        $recentTx = $this->txModel->getFilteredTransactions(['user_id' => $userId], 1, 10);

        return [
            'account_count' => count($accounts),
            'total_balance' => $totalBalance,
            'total_debit' => (float)$totals['total_debit'],
            'total_credit' => (float)$totals['total_credit'],
            'total_transactions' => (int)$totals['total_count'],
            'accounts' => $accounts,
            'recent_transactions' => $recentTx['data']
        ];
    }

    public function getAdminMetrics(): array {
        $userCount = $this->userModel->countUsers();
        $accountCount = $this->accountModel->countAccounts();
        $totals = $this->txModel->getTotals(null);
        $recentLogs = $this->auditModel->getRecentLogs(10);
        $recentTx = $this->txModel->getFilteredTransactions([], 1, 10);
        $allAccounts = $this->accountModel->getAllAccounts();

        $systemBalance = 0.00;
        foreach ($allAccounts as $acc) {
            $systemBalance += (float)$acc['current_balance'];
        }

        return [
            'user_count' => $userCount,
            'account_count' => $accountCount,
            'total_transactions' => (int)$totals['total_count'],
            'total_debit' => (float)$totals['total_debit'],
            'total_credit' => (float)$totals['total_credit'],
            'system_balance' => $systemBalance,
            'recent_audits' => $recentLogs,
            'recent_transactions' => $recentTx['data'],
            'accounts' => $allAccounts
        ];
    }
}
