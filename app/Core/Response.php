<?php
// app/Core/Response.php

class Response {
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        self::json([
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $statusCode);
    }

    public static function error(string $message, $errors = null, int $statusCode = 400): void {
        self::json([
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $statusCode);
    }

    public static function redirect(string $url): void {
        $config = require __DIR__ . '/../Config/app.php';
        $base = $config['base_url'] ?? '';
        
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = rtrim($base, '/') . '/' . ltrim($url, '/');
        }
        header("Location: $url");
        exit;
    }

    public static function render(string $viewPath, array $data = []): void {
        extract($data);
        $fullPath = __DIR__ . '/../../views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($fullPath)) {
            throw new Exception("View template [{$viewPath}] not found at {$fullPath}");
        }
        require $fullPath;
        exit;
    }
}
