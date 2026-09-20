<?php
// app/Controllers/BaseController.php

require_once __DIR__ . '/../Core/Request.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Services/AuthService.php';

abstract class BaseController {
    protected AuthService $auth;

    public function __construct() {
        $this->auth = new AuthService();
    }

    protected function render(string $view, array $data = []): void {
        $data['currentUser'] = $this->auth->user();
        $data['isAdmin'] = $this->auth->isAdmin();
        Response::render($view, $data);
    }

    protected function jsonSuccess($data = null, string $message = 'Success', int $code = 200): void {
        Response::success($data, $message, $code);
    }

    protected function jsonError(string $message, $errors = null, int $code = 400): void {
        Response::error($message, $errors, $code);
    }

    protected function userId(): int {
        $id = $this->auth->id();
        if (!$id) {
            throw new Exception("Unauthenticated");
        }
        return $id;
    }

    protected function isAdmin(): bool {
        return $this->auth->isAdmin();
    }
}
