<?php
// app/Controllers/UserController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/AuditService.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Core/Validator.php';

class UserController extends BaseController {
    private User $userModel;
    private AuditService $auditService;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->auditService = new AuditService();
    }

    public function index(Request $request): void {
        $users = $this->userModel->getAllUsersWithStats();
        $this->render('users/index', ['users' => $users]);
    }

    public function apiCreate(Request $request): void {
        $data = $request->all();
        $validator = Validator::make($data);

        if (!$validator->validate([
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'full_name' => 'required|min:3|max:100',
            'role' => 'required|in:admin,user'
        ])) {
            $this->jsonError('Validation error', $validator->getFirstErrors(), 422);
        }

        $username = trim($data['username']);
        $email = trim($data['email']);

        if ($this->userModel->findByUsername($username)) {
            $this->jsonError("Username '{$username}' is already taken.", null, 422);
        }
        if ($this->userModel->findByEmail($email)) {
            $this->jsonError("Email '{$email}' is already registered.", null, 422);
        }

        $passwordRaw = (string)$data['password']; // Verbatim, not trimmed
        $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $userData = [
            'uuid' => SecurityHelper::uuid(),
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'full_name' => SecurityHelper::clean($data['full_name']),
            'role' => $data['role'],
            'status' => in_array($data['status'] ?? 'active', ['active', 'inactive', 'suspended']) ? $data['status'] : 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $id = $this->userModel->insert($userData);
            $this->auditService->log('USER_CREATE', 'users', $id, null, [
                'username' => $username,
                'email' => $email,
                'role' => $data['role'],
                'status' => $userData['status']
            ]);
            $db->commit();
            $this->jsonSuccess(['id' => $id], 'User account created successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function apiUpdate(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError("User #{$id} not found.", null, 404);
        }

        $data = $request->all();
        $email = trim($data['email'] ?? $user['email']);

        // Check if email changed and is unique
        if ($email !== $user['email']) {
            $existing = $this->userModel->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $id) {
                $this->jsonError("Email '{$email}' is already in use.", null, 422);
            }
        }

        $updateData = [
            'full_name' => SecurityHelper::clean($data['full_name'] ?? $user['full_name']),
            'email' => $email,
            'role' => in_array($data['role'] ?? '', ['admin', 'user']) ? $data['role'] : $user['role'],
            'status' => in_array($data['status'] ?? '', ['active', 'inactive', 'suspended']) ? $data['status'] : $user['status'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $this->userModel->update($id, $updateData);
            $this->auditService->log('USER_UPDATE', 'users', $id, [
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $user['status']
            ], $updateData);
            $db->commit();
            $this->jsonSuccess(null, 'User profile updated successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function apiResetPassword(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError("User #{$id} not found.", null, 404);
        }

        $newPassword = (string)$request->input('password', '');
        if (strlen($newPassword) < 6) {
            $this->jsonError('Password must be at least 6 characters.', null, 422);
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $this->userModel->update($id, ['password_hash' => $hash, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->auditService->log('USER_PASSWORD_RESET', 'users', $id, null, ['status' => 'password_reset']);
            $db->commit();
            $this->jsonSuccess(null, 'Password has been reset successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
}
