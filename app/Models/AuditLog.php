<?php
// app/Models/AuditLog.php

require_once __DIR__ . '/BaseModel.php';

class AuditLog extends BaseModel {
    protected string $table = 'audit_logs';
    protected bool $hasUpdatedAt = false;

    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? substr($userAgent, 0, 250) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->insert($data);
    }

    public function getRecentLogs(int $limit = 20): array {
        $sql = "SELECT l.*, u.username, u.full_name 
                FROM `audit_logs` l
                LEFT JOIN `users` u ON u.id = l.user_id
                ORDER BY l.id DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }
}
