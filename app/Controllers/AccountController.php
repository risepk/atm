<?php
// app/Controllers/AccountController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/AccountService.php';
require_once __DIR__ . '/../Models/MasterBank.php';
require_once __DIR__ . '/../Core/Validator.php';

class AccountController extends BaseController {
    private AccountService $accountService;
    private MasterBank $bankModel;

    public function __construct() {
        parent::__construct();
        $this->accountService = new AccountService();
        $this->bankModel = new MasterBank();
    }

    public function index(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), true);
        $banks = $this->bankModel->getActiveBanks();
        $this->render('accounts/index', [
            'accounts' => $accounts,
            'banks' => $banks
        ]);
    }

    public function apiList(Request $request): void {
        $accounts = $this->accountService->listAccounts($this->userId(), $this->isAdmin(), false);
        $this->jsonSuccess($accounts);
    }

    public function apiSave(Request $request): void {
        $data = $request->all();

        // Support single-field account code / identifier workflow (e.g. 12, 13, 14, 57, 91)
        if (empty($data['account_title']) && !empty($data['account_identifier'])) {
            $data['account_title'] = trim((string)$data['account_identifier']);
        }
        if (empty($data['account_identifier']) && !empty($data['account_title'])) {
            $data['account_identifier'] = trim((string)$data['account_title']);
        }

        $validator = Validator::make($data);

        if (!$validator->validate([
            'account_title' => 'required'
        ])) {
            $this->jsonError('Validation error', $validator->getFirstErrors(), 422);
        }

        try {
            if (!empty($data['id'])) {
                $account = $this->accountService->updateAccount((int)$data['id'], $this->userId(), $this->isAdmin(), $data);
                $this->jsonSuccess($account, 'Account updated successfully.');
            } else {
                $account = $this->accountService->createAccount($this->userId(), $data);
                $this->jsonSuccess($account, 'Account created successfully.');
            }
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function apiDelete(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        try {
            $this->accountService->softDeleteAccount($id, $this->userId(), $this->isAdmin());
            $this->jsonSuccess(null, 'Account deleted successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
}
