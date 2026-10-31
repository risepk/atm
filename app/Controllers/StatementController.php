<?php
// app/Controllers/StatementController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/StatementService.php';
require_once __DIR__ . '/../Services/AccountService.php';
require_once __DIR__ . '/../Models/StatementFormat.php';
require_once __DIR__ . '/../Models/MasterBank.php';

class StatementController extends BaseController {
    private StatementService $statementService;
    private AccountService $accountService;
    private StatementFormat $formatModel;
    private MasterBank $bankModel;

    public function __construct() {
        parent::__construct();
        $this->statementService = new StatementService();
        $this->accountService = new AccountService();
        $this->formatModel = new StatementFormat();
        $this->bankModel = new MasterBank();
    }

    public function index(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedBank = $request->get('bank', '');
        $selectedAccountId = $request->get('account_id');

        // If bank passed without account_id, find first matching account
        if (!$selectedAccountId && $selectedBank) {
            foreach ($accounts as $a) {
                if (strcasecmp($a['bank_name'] ?? '', $selectedBank) === 0 || stripos($a['bank_name'] ?? '', $selectedBank) !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $defaultEndDate = date('Y-m-d');
        if ($selectedAccountId) {
            foreach ($accounts as $a) {
                if ($a['id'] == $selectedAccountId && !empty($a['max_tx_date']) && $a['max_tx_date'] > $defaultEndDate) {
                    $defaultEndDate = $a['max_tx_date'];
                    break;
                }
            }
        }

        $this->render('statements/view', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => $selectedBank,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', $defaultEndDate),
            'formatCode' => $request->get('format', 'standard')
        ]);
    }

    public function apiGenerate(Request $request): void {
        $accountId = (int)$request->get('account_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $formatCode = $request->get('format', 'standard');

        if (!$accountId) {
            $this->jsonError('Please select an account.', null, 422);
        }

        $overrides = [
            'bank_name' => $request->get('bank_name'),
            'account_title' => $request->get('account_title'),
            'father_name' => $request->get('father_name'),
            'account_number' => $request->get('account_number'),
            'address' => $request->get('address'),
            'city' => $request->get('city'),
            'postal_code' => $request->get('postal_code'),
            'province_country' => $request->get('province_country'),
            'phone1' => $request->get('phone1'),
            'phone2' => $request->get('phone2'),
            'branch_name' => $request->get('branch_name'),
            'branch_address' => $request->get('branch_address'),
            'old_account_no' => $request->get('old_account_no'),
            'product' => $request->get('product'),
            'generated_by' => $request->get('generated_by'),
            'print_date' => $request->get('print_date'),
            'account_status' => $request->get('account_status'),
            'account_type' => $request->get('account_type'),
            'currency' => $request->get('currency'),
            'iban' => $request->get('iban'),
            'opening_balance' => $request->get('opening_balance'),
            'show_online' => $request->get('show_online'),
            'frequency' => $request->get('frequency'),
            'printing_date' => $request->get('printing_date'),
            'user_code' => $request->get('user_code'),
            'printed_on' => $request->get('printed_on') ?: $request->get('print_date'),
            'issuing_branch' => $request->get('issuing_branch'),
            'issue_date' => $request->get('issue_date'),
            'statement_title' => $request->get('statement_title'),
            'branch_code' => $request->get('branch_code'),
            'product_type' => $request->get('product_type') ?: $request->get('product'),
            'reg_cell_no' => $request->get('reg_cell_no') ?: $request->get('phone1'),
            'cif_no' => $request->get('cif_no')
        ];

        try {
            $statement = $this->statementService->generateStatement($accountId, $startDate, $endDate, $formatCode, $this->userId(), $this->isAdmin(), $overrides);
            $statement['rendered_html'] = $this->statementService->renderHtml($statement);
            $this->jsonSuccess($statement);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function printView(Request $request): void {
        $accountId = (int)$request->get('account_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $formatCode = $request->get('format', 'standard');

        $overrides = [
            'bank_name' => $request->get('bank_name'),
            'account_title' => $request->get('account_title'),
            'father_name' => $request->get('father_name'),
            'account_number' => $request->get('account_number'),
            'address' => $request->get('address'),
            'city' => $request->get('city'),
            'postal_code' => $request->get('postal_code'),
            'province_country' => $request->get('province_country'),
            'phone1' => $request->get('phone1'),
            'phone2' => $request->get('phone2'),
            'branch_name' => $request->get('branch_name'),
            'branch_address' => $request->get('branch_address'),
            'old_account_no' => $request->get('old_account_no'),
            'product' => $request->get('product'),
            'generated_by' => $request->get('generated_by'),
            'print_date' => $request->get('print_date'),
            'account_status' => $request->get('account_status'),
            'account_type' => $request->get('account_type'),
            'currency' => $request->get('currency'),
            'iban' => $request->get('iban'),
            'opening_balance' => $request->get('opening_balance'),
            'show_online' => $request->get('show_online'),
            'frequency' => $request->get('frequency'),
            'printing_date' => $request->get('printing_date'),
            'user_code' => $request->get('user_code'),
            'printed_on' => $request->get('printed_on') ?: $request->get('print_date'),
            'issuing_branch' => $request->get('issuing_branch'),
            'issue_date' => $request->get('issue_date'),
            'statement_title' => $request->get('statement_title'),
            'branch_code' => $request->get('branch_code'),
            'product_type' => $request->get('product_type') ?: $request->get('product'),
            'reg_cell_no' => $request->get('reg_cell_no') ?: $request->get('phone1'),
            'cif_no' => $request->get('cif_no')
        ];

        try {
            $statement = $this->statementService->generateStatement($accountId, $startDate, $endDate, $formatCode, $this->userId(), $this->isAdmin(), $overrides);
            Response::render('statements/print', ['statement' => $statement]);
        } catch (Exception $e) {
            Response::render('errors/403', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Dedicated separate page for Meezan Bank Statement Generator
     */
    public function meezan(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedAccountId = $request->get('account_id');
        if (!$selectedAccountId) {
            foreach ($accounts as $a) {
                if (stripos($a['bank_name'] ?? '', 'meezan') !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $this->render('statements/meezan', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => 'Meezan Bank Limited',
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d')),
            'formatCode' => 'meezan'
        ]);
    }

    /**
     * Dedicated separate page for Habib Bank Limited (HBL) Statement Generator
     */
    public function hbl(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedAccountId = $request->get('account_id');
        if (!$selectedAccountId) {
            foreach ($accounts as $a) {
                if (stripos($a['bank_name'] ?? '', 'hbl') !== false || stripos($a['bank_name'] ?? '', 'habib bank') !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $this->render('statements/hbl', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => 'Habib Bank Limited',
            'startDate' => $request->get('start_date', date('Y-07-01')),
            'endDate' => $request->get('end_date', date('Y-06-30')),
            'formatCode' => 'hbl'
        ]);
    }

    /**
     * Dedicated separate page for Habib Metropolitan Bank (HMB) Statement Generator
     */
    public function habibmetro(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedAccountId = $request->get('account_id');
        if (!$selectedAccountId) {
            foreach ($accounts as $a) {
                if (stripos($a['bank_name'] ?? '', 'metro') !== false || stripos($a['bank_name'] ?? '', 'hmb') !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $this->render('statements/habibmetro', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => 'Habib Metropolitan Bank',
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d')),
            'formatCode' => 'habibmetro'
        ]);
    }

    /**
     * Dedicated separate page for BankIslami Pakistan Statement Generator
     */
    public function bankislami(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedAccountId = $request->get('account_id');
        if (!$selectedAccountId) {
            foreach ($accounts as $a) {
                if (stripos($a['bank_name'] ?? '', 'islami') !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $defaultEndDate = date('Y-m-d');
        if ($selectedAccountId) {
            foreach ($accounts as $a) {
                if ($a['id'] == $selectedAccountId && !empty($a['max_tx_date']) && $a['max_tx_date'] > $defaultEndDate) {
                    $defaultEndDate = $a['max_tx_date'];
                    break;
                }
            }
        }

        $this->render('statements/bankislami', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => 'BankIslami Pakistan Limited',
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', $defaultEndDate),
            'formatCode' => 'bankislami'
        ]);
    }

    /**
     * Dedicated separate page for UBL / UBL Ameen Islamic Banking Statement Generator
     */
    public function ubl(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $formats = $this->formatModel->getActiveFormats();
        $masterBanks = $this->bankModel->getActiveBanks();

        $selectedAccountId = $request->get('account_id');
        if (!$selectedAccountId) {
            foreach ($accounts as $a) {
                if (stripos($a['bank_name'] ?? '', 'ubl') !== false || stripos($a['bank_name'] ?? '', 'united') !== false || stripos($a['bank_name'] ?? '', 'ameen') !== false) {
                    $selectedAccountId = $a['id'];
                    break;
                }
            }
        }

        $defaultEndDate = date('Y-m-d');
        if ($selectedAccountId) {
            foreach ($accounts as $a) {
                if ($a['id'] == $selectedAccountId && !empty($a['max_tx_date']) && $a['max_tx_date'] > $defaultEndDate) {
                    $defaultEndDate = $a['max_tx_date'];
                    break;
                }
            }
        }

        $this->render('statements/ubl', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => 'United Bank Limited',
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', $defaultEndDate),
            'formatCode' => 'ubl'
        ]);
    }
}

