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
        if (isset($overrides['city'])) {
            $account['city'] = trim((string)$overrides['city']);
        }
        if (isset($overrides['postal_code'])) {
            $account['postal_code'] = trim((string)$overrides['postal_code']);
        }
        if (isset($overrides['province_country'])) {
            $account['province_country'] = trim((string)$overrides['province_country']);
        }
        if (isset($overrides['phone1'])) {
            $account['phone1'] = trim((string)$overrides['phone1']);
        }
        if (isset($overrides['phone2'])) {
            $account['phone2'] = trim((string)$overrides['phone2']);
        }
        if (isset($overrides['branch_name'])) {
            $account['branch_name'] = trim((string)$overrides['branch_name']);
        }
        if (isset($overrides['branch_address'])) {
            $account['branch_address'] = trim((string)$overrides['branch_address']);
        }
        if (isset($overrides['old_account_no'])) {
            $account['old_account_no'] = trim((string)$overrides['old_account_no']);
        }
        if (isset($overrides['product'])) {
            $account['product'] = trim((string)$overrides['product']);
        }
        if (isset($overrides['generated_by'])) {
            $account['generated_by'] = trim((string)$overrides['generated_by']);
        }
        if (isset($overrides['print_date'])) {
            $account['print_date'] = trim((string)$overrides['print_date']);
        }
        if (isset($overrides['currency'])) {
            $account['currency'] = trim((string)$overrides['currency']);
        }
        if (isset($overrides['account_status'])) {
            $account['account_status'] = trim((string)$overrides['account_status']);
            $account['account_type'] = $account['account_status'];
        } elseif (isset($overrides['account_type'])) {
            $account['account_type'] = trim((string)$overrides['account_type']);
            $account['account_status'] = $account['account_type'];
        }
        if (isset($overrides['iban'])) {
            $account['iban'] = trim((string)$overrides['iban']);
        }
        if (isset($overrides['frequency'])) {
            $account['frequency'] = trim((string)$overrides['frequency']);
        }
        if (isset($overrides['printing_date'])) {
            $account['printing_date'] = trim((string)$overrides['printing_date']);
        }
        if (isset($overrides['user_code'])) {
            $account['user_code'] = trim((string)$overrides['user_code']);
        }
        if (isset($overrides['printed_on'])) {
            $account['printed_on'] = trim((string)$overrides['printed_on']);
        }
        if (isset($overrides['issuing_branch'])) {
            $account['issuing_branch'] = trim((string)$overrides['issuing_branch']);
        }
        if (isset($overrides['issue_date'])) {
            $account['issue_date'] = trim((string)$overrides['issue_date']);
        }
        if (isset($overrides['statement_title'])) {
            $account['statement_title'] = trim((string)$overrides['statement_title']);
        }
        if (isset($overrides['branch_code'])) {
            $account['branch_code'] = trim((string)$overrides['branch_code']);
        }
        if (isset($overrides['product_type'])) {
            $account['product_type'] = trim((string)$overrides['product_type']);
            $account['product'] = $account['product_type'];
        } elseif (isset($overrides['product'])) {
            $account['product'] = trim((string)$overrides['product']);
            $account['product_type'] = $account['product'];
        }
        if (isset($overrides['reg_cell_no'])) {
            $account['reg_cell_no'] = trim((string)$overrides['reg_cell_no']);
        }
        if (isset($overrides['cif_no'])) {
            $account['cif_no'] = trim((string)$overrides['cif_no']);
        }

        // Show Online toggle overrides format to 'online'
        $showOnline = !empty($overrides['show_online']) && ($overrides['show_online'] === '1' || $overrides['show_online'] === 1 || $overrides['show_online'] === true || $overrides['show_online'] === 'true');
        if ($showOnline) {
            $formatCode = 'online';
        } elseif (empty($formatCode) || $formatCode === 'auto') {
            $lowerBank = strtolower($account['bank_name'] ?? '');
            if (strpos($lowerBank, 'faysal') !== false || strpos($lowerBank, 'faisal') !== false || strpos($lowerBank, 'fbl') !== false) {
                $formatCode = 'faysal';
            } elseif (strpos($lowerBank, 'allied') !== false || strpos($lowerBank, 'abl') !== false) {
                $formatCode = 'allied';
            } elseif (strpos($lowerBank, 'meezan') !== false || strpos($lowerBank, 'mezn') !== false) {
                $formatCode = 'meezan';
            } elseif (strpos($lowerBank, 'metro') !== false || strpos($lowerBank, 'hmb') !== false) {
                $formatCode = 'habibmetro';
            } elseif (strpos($lowerBank, 'hbl') !== false || strpos($lowerBank, 'habib bank') !== false) {
                $formatCode = 'hbl';
            } elseif (strpos($lowerBank, 'islami') !== false || strpos($lowerBank, 'bipl') !== false) {
                $formatCode = 'bankislami';
            } elseif (strpos($lowerBank, 'ubl') !== false || strpos($lowerBank, 'united') !== false || strpos($lowerBank, 'ameen') !== false) {
                $formatCode = 'ubl';
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

        if (!empty($startDate)) {
            $sTs = strtotime(str_replace('/', '-', trim((string)$startDate)));
            $startDate = ($sTs !== false) ? date('Y-m-d', $sTs) : trim((string)$startDate);
        } else {
            $minTx = $this->txModel->getMinDateForAccount($accountId);
            $startDate = $minTx ?: date('Y-m-01');
        }

        if (!empty($endDate)) {
            $eTs = strtotime(str_replace('/', '-', trim((string)$endDate)));
            $endDate = ($eTs !== false) ? date('Y-m-d', $eTs) : trim((string)$endDate);
        } else {
            $maxTx = $this->txModel->getMaxDateForAccount($accountId);
            $endDate = ($maxTx && $maxTx > date('Y-m-d')) ? $maxTx : date('Y-m-d');
        }

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
