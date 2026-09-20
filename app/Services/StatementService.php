<?php
// app/Services/StatementService.php

require_once __DIR__ . '/../Models/Account.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/StatementFormat.php';
require_once __DIR__ . '/../Models/MasterBank.php';
require_once __DIR__ . '/../Helpers/CurrencyHelper.php';
require_once __DIR__ . '/AccountService.php';
require_once __DIR__ . '/Statement/StatementRendererFactory.php';

class StatementService {
    private AccountService $accountService;
    private Transaction $txModel;
    private StatementFormat $formatModel;
    private MasterBank $bankModel;

    public function __construct() {
        $this->accountService = new AccountService();
        $this->txModel = new Transaction();
        $this->formatModel = new StatementFormat();
        $this->bankModel = new MasterBank();
    }

    public function generateStatement(int $accountId, ?string $startDate, ?string $endDate, string $formatCode, int $userId, bool $isAdmin, array $overrides = []): array {
        // Enforce ownership
        $account = $this->accountService->verifyOwnership($accountId, $userId, $isAdmin);

        // Apply metadata overrides from report dialog if provided
        if (!empty($overrides['bank_name'])) {
            $account['bank_name'] = trim((string)$overrides['bank_name']);
        }
        if (!empty($overrides['account_title'])) {
            $account['account_title'] = trim((string)$overrides['account_title']);
        }
        if (!empty($overrides['father_name'])) {
            $account['father_name'] = trim((string)$overrides['father_name']);
        }
        if (!empty($overrides['account_number'])) {
            $account['account_number'] = trim((string)$overrides['account_number']);
        }
        if (!empty($overrides['address'])) {
            $account['address'] = trim((string)$overrides['address']);
        }
        if (!empty($overrides['account_type'])) {
            $account['account_type'] = trim((string)$overrides['account_type']);
        }
        if (!empty($overrides['iban'])) {
            $account['iban'] = trim((string)$overrides['iban']);
        }

        // Show Online toggle overrides format to 'online'
        $showOnline = !empty($overrides['show_online']) && ($overrides['show_online'] === '1' || $overrides['show_online'] === 1 || $overrides['show_online'] === true || $overrides['show_online'] === 'true');
        if ($showOnline) {
            $formatCode = 'online';
        } elseif (empty($formatCode) || $formatCode === 'auto') {
            $lowerBank = strtolower($account['bank_name'] ?? '');
            if (strpos($lowerBank, 'faysal') !== false || strpos($lowerBank, 'faisal') !== false || strpos($lowerBank, 'fbl') !== false) {
                $formatCode = 'faysal';
            } elseif (strpos($lowerBank, 'hbl') !== false || strpos($lowerBank, 'habib bank') !== false) {
                $formatCode = 'hbl';
            } else {
                $formatCode = 'standard';
            }
        }

        // Resolve Bank Logo and Master Bank metadata
        if (!empty($account['bank_name'])) {
            $bank = $this->bankModel->findByName($account['bank_name']);
            $account['bank_logo'] = $bank['logo_path'] ?? null;
            $account['bank_code'] = $bank['bank_code'] ?? null;
        } else {
            $account['bank_logo'] = null;
            $account['bank_code'] = null;
        }

        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-d');

        // Opening Balance calculation (String Decimal with strict BCMath)
        $initialBalance = CurrencyHelper::normalizeDecimal($account['initial_balance'] ?? '0.00');
        $openingBalance = $this->txModel->getOpeningBalanceBefore($accountId, $startDate, $initialBalance);

        if (isset($overrides['opening_balance']) && is_numeric(str_replace(',', '', (string)$overrides['opening_balance']))) {
            $openingBalance = CurrencyHelper::normalizeDecimal(str_replace(',', '', (string)$overrides['opening_balance']));
        }

        // Fetch transactions in date range strictly with status = 'posted' AND deleted_at IS NULL
        $rawTransactions = $this->txModel->getAccountLedger($accountId, $startDate, $endDate);

        $runningBalance = $openingBalance;
        $processedRows = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($rawTransactions as $tx) {
            $debit = CurrencyHelper::normalizeDecimal($tx['debit']);
            $credit = CurrencyHelper::normalizeDecimal($tx['credit']);

            // Running balance: add credit, subtract debit using BCMath
            $runningBalance = CurrencyHelper::add($runningBalance, $credit);
            $runningBalance = CurrencyHelper::sub($runningBalance, $debit);

            $totalDebit = CurrencyHelper::add($totalDebit, $debit);
            $totalCredit = CurrencyHelper::add($totalCredit, $credit);

            $processedRows[] = [
                'id' => $tx['id'],
                'date' => $tx['transaction_date'],
                'reference_no' => $tx['reference_no'] ?? '-',
                'narration' => $tx['narration'],
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance
            ];
        }

        $renderer = StatementRendererFactory::create($formatCode);
        $format = $this->formatModel->findByCode($formatCode) ?: [
            'format_code' => $renderer->getSupportedFormatCode(),
            'format_name' => $renderer->getFormatName()
        ];

        return [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'transactions' => $processedRows,
            'format' => $format,
            'is_deferred' => StatementRendererFactory::isDeferred($formatCode),
            'deferred_note' => StatementRendererFactory::getDeferredReason($formatCode)
        ];
    }

    public function renderHtml(array $statementData): string {
        $formatCode = $statementData['format']['format_code'] ?? 'standard';
        $renderer = StatementRendererFactory::create($formatCode);
        return $renderer->renderHtml($statementData);
    }

    public function renderPrint(array $statementData): string {
        $formatCode = $statementData['format']['format_code'] ?? 'standard';
        $renderer = StatementRendererFactory::create($formatCode);
        return $renderer->renderPrint($statementData);
    }
}
