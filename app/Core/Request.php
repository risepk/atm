<?php
// app/Core/Request.php

class Request {
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $bodyParams;
    private array $headers;

    public function __construct() {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Extract URI path without query string and strip base path
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        $config = require __DIR__ . '/../Config/app.php';
        $base = $config['base_url'] ?? '';
        if ($base && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        $this->uri = $path === '' ? '/' : $path;

        $this->queryParams = $_GET;

        // Parse JSON body or form post
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $this->bodyParams = json_decode($raw, true) ?: [];
        } else {
            $this->bodyParams = $_POST;
        }

        $this->headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function get(string $key, $default = null) {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, $default = null) {
        return $this->bodyParams[$key] ?? $default;
    }

    public function all(): array {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    public function input(string $key, $default = null) {
        return $this->bodyParams[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function file(string $key): ?array {
        return $_FILES[$key] ?? null;
    }

    public function hasFile(string $key): bool {
        return isset($_FILES[$key]) && !empty($_FILES[$key]['tmp_name']) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function isAjax(): bool {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false;
    }

    public function getHeader(string $name): ?string {
        foreach ($this->headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) {
                return $v;
            }
        }
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$serverKey] ?? null;
    }

    public function getCsrfToken(): ?string {
        return $this->post('_csrf_token') ?? $this->getHeader('X-CSRF-TOKEN');
    }

    public function getIp(): string {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
