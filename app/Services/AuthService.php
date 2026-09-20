<?php
// app/Services/AuthService.php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Services/AuditService.php';

class AuthService {
    private User $userModel;
    private AuditService $auditService;

    public function __construct() {
        $this->userModel = new User();
        $this->auditService = new AuditService();
    }

    public function attempt(string $login, string $password): array {
        $user = $this->userModel->findByUsernameOrEmail($login);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is ' . $user['status'] . '. Please contact administrator.'];
        }

        // Point 21: Password verification with exact supplied string
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Regenerate session for session fixation protection
        Session::regenerate();

        Session::set('user_id', (int)$user['id']);
        Session::set('username', $user['username']);
        Session::set('full_name', $user['full_name']);
        Session::set('email', $user['email']);
        Session::set('role', $user['role']);
        Session::set('logged_in_at', time());

        // Check if password hash needs rehash
        $updateData = ['last_login_at' => date('Y-m-d H:i:s')];
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $updateData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->userModel->update($user['id'], $updateData);

        $this->auditService->log('USER_LOGIN', 'users', $user['id'], null, ['username' => $user['username']]);

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
    }

    public function logout(): void {
        Session::start();
        $userId = Session::get('user_id');
        if ($userId) {
            $this->auditService->log('USER_LOGOUT', 'users', (int)$userId);
        }
        Session::destroy();
    }

    public function user(): ?array {
        Session::start();
        $userId = Session::get('user_id');
        if (!$userId) return null;
        return $this->userModel->find((int)$userId);
    }

    public function check(): bool {
        Session::start();
        return Session::has('user_id');
    }

    public function isAdmin(): bool {
        Session::start();
        return Session::get('role') === 'admin';
    }

    public function id(): ?int {
        Session::start();
        $id = Session::get('user_id');
        return $id ? (int)$id : null;
    }
}
