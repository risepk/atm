<?php
// app/Controllers/MasterDataController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/MasterBank.php';
require_once __DIR__ . '/../Services/GeneratorService.php';
require_once __DIR__ . '/../Services/AuditService.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';

class MasterDataController extends BaseController {
    private MasterBank $bankModel;
    private AuditService $auditService;

    public function __construct() {
        parent::__construct();
        $this->bankModel = new MasterBank();
        $this->auditService = new AuditService();
    }

    public function index(Request $request): void {
        if (!$this->isAdmin()) {
            $this->redirect('/atm/dashboard');
            return;
        }
        $banks = $this->bankModel->getAllBanks();
        $this->render('banks/index', [
            'banks' => $banks,
            'pageTitle' => 'Bank Master Management'
        ]);
    }

    public function banks(Request $request): void {
        $banks = $this->bankModel->getActiveBanks();
        $this->jsonSuccess($banks);
    }

    public function allBanks(Request $request): void {
        if (!$this->isAdmin()) {
            $this->jsonError('Forbidden: Only administrators can view all master banks.', null, 403);
            return;
        }
        $banks = $this->bankModel->getAllBanks();
        $this->jsonSuccess($banks);
    }

    public function addBank(Request $request): void {
        if (!$this->isAdmin()) {
            $this->jsonError('Forbidden: Only administrators can add master bank entries.', null, 403);
            return;
        }

        $bankName = trim((string)$request->input('bank_name', ''));
        $bankCode = strtoupper(trim((string)$request->input('bank_code', '')));

        if (mb_strlen($bankName) < 3 || mb_strlen($bankName) > 100) {
            $this->jsonError('Bank name must be between 3 and 100 characters.', null, 422);
            return;
        }

        // Duplicate prevention (case-insensitive)
        $existing = $this->bankModel->findByName($bankName);
        if ($existing) {
            $this->jsonError("Bank '{$bankName}' already exists in the system.", null, 422);
            return;
        }

        if (!$bankCode) {
            // Auto generate code from uppercase initials or letters
            $words = explode(' ', $bankName);
            $code = '';
            foreach ($words as $w) {
                $code .= strtoupper(substr($w, 0, 1));
            }
            $bankCode = substr(preg_replace('/[^A-Z]/', '', $code) ?: 'BANK', 0, 10);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            try {
                $logoPath = $this->handleLogoUpload($request->file('logo'));
            } catch (Exception $e) {
                $this->jsonError($e->getMessage(), null, 422);
                return;
            }
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $id = $this->bankModel->addBank($bankName, $bankCode, $request->input('swift_code', ''), $logoPath);
            $newBank = $this->bankModel->find($id);
            $this->auditService->log('BANK_CREATE', 'master_banks', $id, null, $newBank);
            $db->commit();

            $this->jsonSuccess($newBank, 'Bank added successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            if ($logoPath) {
                MasterBank::deleteLogoFile($logoPath);
            }
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function updateBank(Request $request, array $params): void {
        if (!$this->isAdmin()) {
            $this->jsonError('Forbidden: Only administrators can modify master banks.', null, 403);
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $bank = $this->bankModel->find($id);
        if (!$bank) {
            $this->jsonError("Bank #{$id} not found.", null, 404);
            return;
        }

        $bankName = trim((string)$request->input('bank_name', ''));
        $bankCode = strtoupper(trim((string)$request->input('bank_code', '')));
        $swiftCode = trim((string)$request->input('swift_code', ''));
        $removeLogo = (bool)$request->input('remove_logo', false);

        $newLogoPath = null;
        if ($request->hasFile('logo')) {
            try {
                $newLogoPath = $this->handleLogoUpload($request->file('logo'), $bank['logo_path'] ?? null);
            } catch (Exception $e) {
                $this->jsonError($e->getMessage(), null, 422);
                return;
            }
        }

        try {
            $this->bankModel->updateBank($id, $bankName, $bankCode, $swiftCode ?: null, $newLogoPath, $removeLogo);
            $updated = $this->bankModel->find($id);
            $this->auditService->log('BANK_UPDATE', 'master_banks', $id, $bank, $updated);
            $this->jsonSuccess($updated, "Bank '{$bankName}' updated successfully.");
        } catch (Exception $e) {
            if ($newLogoPath) {
                MasterBank::deleteLogoFile($newLogoPath);
            }
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function handleLogoUpload(?array $file, ?string $existingLogo = null): ?string {
        if (!$file || empty($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $file['error']);
        }

        // Max file size: 2MB
        $maxBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new Exception("Bank logo size exceeds maximum limit of 2MB.");
        }

        // Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMimes[$mime])) {
            throw new Exception("Invalid logo file format. Only PNG, JPG, and WEBP images are permitted.");
        }

        $extension = $allowedMimes[$mime];
        $safeHash = bin2hex(random_bytes(8));
        $filename = 'bank_' . time() . '_' . $safeHash . '.' . $extension;
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/banks/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to save uploaded logo to disk.");
        }

        // Delete old logo if replacing
        if ($existingLogo) {
            MasterBank::deleteLogoFile($existingLogo);
        }

        return 'uploads/banks/' . $filename;
    }

    public function toggleBankStatus(Request $request, array $params): void {
        if (!$this->isAdmin()) {
            $this->jsonError('Forbidden: Only administrators can modify master banks.', null, 403);
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $bank = $this->bankModel->find($id);
        if (!$bank) {
            $this->jsonError("Bank #{$id} not found.", null, 404);
            return;
        }

        try {
            $newStatus = $this->bankModel->toggleStatus($id);
            $this->auditService->log('BANK_STATUS_TOGGLE', 'master_banks', $id, ['is_active' => $bank['is_active']], ['is_active' => $newStatus]);
            $statusLabel = ($newStatus === 1) ? 'activated' : 'deactivated';
            $this->jsonSuccess(['id' => $id, 'is_active' => $newStatus], "Bank '{$bank['bank_name']}' {$statusLabel} successfully.");
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function generateValue(Request $request): void {
        $rule = $request->post('rule') ?: [];
        $val = GeneratorService::generate($rule, true);
        $this->jsonSuccess(['value' => $val]);
    }
}
