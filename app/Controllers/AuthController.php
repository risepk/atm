<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Core/Validator.php';

class AuthController extends BaseController {
    public function showLogin(Request $request): void {
        $this->render('auth/login');
    }

    public function login(Request $request): void {
        $login = trim((string)$request->input('login', ''));
        // Point 21: Password is treated verbatim as entered, NOT trimmed!
        $password = (string)$request->input('password', '');

        if ($login === '' || $password === '') {
            if ($request->isAjax()) {
                $this->jsonError('Please enter both username/email and password.', null, 422);
            }
            Session::setFlash('error', 'Please enter both username/email and password.');
            Response::redirect('/login');
        }

        $result = $this->auth->attempt($login, $password);

        if (!$result['success']) {
            if ($request->isAjax()) {
                $this->jsonError($result['message'], null, 401);
            }
            Session::setFlash('error', $result['message']);
            Response::redirect('/login');
        }

        if ($request->isAjax()) {
            $this->jsonSuccess(['redirect' => '/atm/dashboard'], 'Logged in successfully.');
        }
        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void {
        $this->auth->logout();
        Response::redirect('/login');
    }
}
