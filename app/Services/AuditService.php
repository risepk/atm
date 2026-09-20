<?php
// app/Services/AuditService.php

require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Core/Session.php';

class AuditService {
    private AuditLog $auditLog;

    public function __construct() {
        $this->auditLog = new AuditLog();
    }

    public function log(string $action, string $entityType, int $entityId, ?array $oldValues = null, ?array $newValues = null): int {
        Session::start();
        $userId = Session::get('user_id');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        return $this->auditLog->log(
            $userId ? (int)$userId : null,
            $action,
            $entityType,
            $entityId,
            $oldValues,
            $newValues,
            $ip,
            $ua
        );
    }
}
