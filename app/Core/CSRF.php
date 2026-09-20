<?php
// app/Core/CSRF.php

require_once __DIR__ . '/Session.php';

class CSRF {
    private const TOKEN_KEY = '_csrf_token';

    public static function generateToken(): string {
        Session::start();
        if (!Session::has(self::TOKEN_KEY)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::TOKEN_KEY, $token);
        }
        return Session::get(self::TOKEN_KEY);
    }

    public static function getToken(): string {
        return self::generateToken();
    }

    public static function validate(?string $token): bool {
        Session::start();
        $stored = Session::get(self::TOKEN_KEY);
        if (!$stored || !$token) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function getField(): string {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
