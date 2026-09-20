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

        $this->render('statements/view', [
            'accounts' => $accounts,
            'formats' => $formats,
            'masterBanks' => $masterBanks,
            'selectedAccountId' => $selectedAccountId,
            'selectedBank' => $selectedBank,
            'startDate' => $request->get('start_date', date('Y-m-01')),
            'endDate' => $request->get('end_date', date('Y-m-d')),
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
            'account_type' => $request->get('account_type'),
            'iban' => $request->get('iban'),
            'opening_balance' => $request->get('opening_balance'),
            'show_online' => $request->get('show_online')
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
            'account_type' => $request->get('account_type'),
            'iban' => $request->get('iban'),
            'opening_balance' => $request->get('opening_balance'),
            'show_online' => $request->get('show_online')
        ];

        try {
            $statement = $this->statementService->generateStatement($accountId, $startDate, $endDate, $formatCode, $this->userId(), $this->isAdmin(), $overrides);
            Response::render('statements/print', ['statement' => $statement]);
        } catch (Exception $e) {
            Response::render('errors/403', ['message' => $e->getMessage()]);
        }
    }
}
