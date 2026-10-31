<?php
// app/Controllers/TransactionController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/TransactionService.php';
require_once __DIR__ . '/../Services/AccountService.php';
require_once __DIR__ . '/../Services/NarrationService.php';

class TransactionController extends BaseController {
    private TransactionService $txService;
    private AccountService $accountService;
    private NarrationService $narrationService;
    private Transaction $txModel;

    public function __construct() {
        parent::__construct();
        $this->txService = new TransactionService();
        $this->accountService = new AccountService();
        $this->narrationService = new NarrationService();
        $this->txModel = new Transaction();
    }

    public function index(Request $request): void {
        $filters = [
            'account_id' => $request->get('account_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'search' => $request->get('search')
        ];
        $page = max(1, (int)$request->get('page', 1));

        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $result = $this->txService->getFilteredTransactions($filters, $this->userId(), $this->isAdmin(), $page, 20);

        $this->render('transactions/index', [
            'transactions' => $result['data'],
            'pagination' => $result,
            'accounts' => $accounts,
            'filters' => $filters
        ]);
    }

    public function rapid(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $defaultAccountId = (int)$request->get('account_id', 0);
        if ($defaultAccountId <= 0 && !empty($accounts)) {
            $defaultAccountId = (int)$accounts[0]['id'];
        }

        $templates = $this->narrationService->getActiveTemplatesWithFields();
        $masterBankModel = new MasterBank();
        $masterBanks = $masterBankModel->getAllActive();

        require_once __DIR__ . '/../Models/CustomFieldType.php';
        $customTypeModel = new CustomFieldType();
        $customTypes = $customTypeModel->getActiveTypes();

        $this->render('transactions/rapid', [
            'accounts' => $accounts,
            'defaultAccountId' => $defaultAccountId,
            'templates' => $templates,
            'masterBanks' => $masterBanks,
            'customTypes' => $customTypes
        ]);
    }

    public function detailed(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $templates = $this->narrationService->getActiveTemplatesWithFields();
        require_once __DIR__ . '/../Models/CustomFieldType.php';
        $customTypeModel = new CustomFieldType();
        $customTypes = $customTypeModel->getActiveTypes();

        $this->render('transactions/create', [
            'accounts' => $accounts,
            'templates' => $templates,
            'customTypes' => $customTypes,
            'defaultAccountId' => $request->get('account_id')
        ]);
    }

    public function create(Request $request): void {
        $query = $request->get('account_id') ? '?account_id=' . (int)$request->get('account_id') : '';
        Response::redirect('/transactions/rapid' . $query);
    }

    public function apiRecent(Request $request, array $params): void {
        $accountId = (int)($params['id'] ?? 0);
        $limit = (int)$request->get('limit', 0); // 0 = all transactions
        try {
            $data = $this->txService->getRecentAccountTransactions($accountId, $this->userId(), $this->isAdmin(), $limit);
            $this->jsonSuccess($data);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }


    public function edit(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        try {
            $transaction = $this->txService->getTransaction($id, $this->userId(), $this->isAdmin());
            if ($transaction['status'] === 'voided') {
                Session::setFlash('error', 'Voided transactions cannot be edited.');
                Response::redirect('/transactions');
            }

            $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
            $templates = $this->narrationService->getActiveTemplatesWithFields();

            $this->render('transactions/edit', [
                'transaction' => $transaction,
                'accounts' => $accounts,
                'templates' => $templates
            ]);
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Response::redirect('/transactions');
        }
    }

    public function apiSave(Request $request): void {
        $data = $request->all();

        try {
            $result = $this->txService->createTransaction($this->userId(), $this->isAdmin(), $data);
            $this->jsonSuccess($result, 'Transaction recorded successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiUpdate(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $data = $request->all();

        try {
            $result = $this->txService->updateTransaction($id, $this->userId(), $this->isAdmin(), $data);
            $this->jsonSuccess($result, 'Transaction updated successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiVoid(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $reason = $request->input('reason', '');

        try {
            $this->txService->voidTransaction($id, $this->userId(), $this->isAdmin(), (string)$reason);
            $this->jsonSuccess(null, 'Transaction voided successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function apiSuggestions(Request $request): void {
        $q = trim($request->get('q', ''));
        $accountId = (int)$request->get('account_id', 0);
        $direction = strtolower(trim($request->get('direction', 'debit')));

        // 1. Get matching active templates
        $account = $accountId > 0 ? (new Account())->find($accountId) : null;
        $accBankName = strtolower(trim($account['bank_name'] ?? ''));

        $templates = $this->narrationService->getActiveTemplatesWithFields();
        $matchingTemplates = [];
        foreach ($templates as $t) {
            if (empty($t['is_active'])) continue;
            // Exclude transient test templates from UI suggestions
            if (strpos($t['code'], 'VENDOR_TEST_') === 0) continue;

            // Bank filtering: if template is bank-specific, only show for matching bank
            if (!empty($t['bank_id']) || !empty($t['bank_name'])) {
                if (!$accBankName) continue;
                $tBankName = strtolower(trim($t['bank_name'] ?? ''));
                if ($tBankName && strpos($accBankName, $tBankName) === false && strpos($tBankName, $accBankName) === false) {
                    continue;
                }
            }

            if ($q !== '') {
                $haystack = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['description'] ?? '') . ' ' . ($t['pattern'] ?? ''));
                if (strpos($haystack, strtolower($q)) === false) continue;
            }
            $matchingTemplates[] = $t;
        }

        // Score templates by direction
        $debitKeywords = ['outward', 'outflow', 'debit', 'withdrawal', 'withdraw', 'payment', 'bill', 'charge', 'fee', 'tax', 'purchase', 'pos', 'atm'];
        $creditKeywords = ['incoming', 'inward', 'inflow', 'credit', 'deposit', 'receipt', 'receive', 'refund', 'salary', 'dividend', 'interest', 'collection'];

        $tplItems = [];
        foreach ($matchingTemplates as $t) {
            $text = strtolower($t['name'] . ' ' . $t['code'] . ' ' . ($t['category'] ?? '') . ' ' . ($t['pattern'] ?? ''));
            $debitScore = 0;
            $creditScore = 0;
            foreach ($debitKeywords as $kw) {
                if (strpos($text, $kw) !== false) $debitScore++;
            }
            foreach ($creditKeywords as $kw) {
                if (strpos($text, $kw) !== false) $creditScore++;
            }
            $score = ($direction === 'debit') ? ($debitScore - $creditScore) : ($creditScore - $debitScore);

            $tplItems[] = [
                'type' => 'template',
                'id' => (int)$t['id'],
                'code' => $t['code'],
                'name' => $t['name'],
                'pattern' => $t['pattern'],
                'version' => (int)($t['version'] ?? 1),
                'category' => $t['category'] ?? 'general',
                'fields' => $t['fields'] ?? [],
                'score' => $score
            ];
        }

        usort($tplItems, function($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return strcmp($a['name'], $b['name']);
        });

        // 2. Get matching previously used narrations
        $pastNarrations = $this->txModel->getRecentDistinctNarrations($this->userId(), $accountId > 0 ? $accountId : null, $q, 20);
        $historyItems = [];
        foreach ($pastNarrations as $pn) {
            $historyItems[] = [
                'type' => 'history',
                'name' => $pn,
                'pattern' => $pn
            ];
        }

        $this->jsonSuccess([
            'templates' => array_values($tplItems),
            'history' => $historyItems
        ]);
    }
}
