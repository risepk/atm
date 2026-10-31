<?php
// app/Models/NarrationTemplate.php

require_once __DIR__ . '/BaseModel.php';

class NarrationTemplate extends BaseModel {
    protected string $table = 'narration_templates';

    public function getActiveTemplates(?int $bankId = null): array {
        $sql = "SELECT t.*, b.bank_name, b.bank_code 
                FROM `{$this->table}` t
                LEFT JOIN `master_banks` b ON b.id = t.bank_id
                WHERE t.`is_active` = 1 AND t.`deleted_at` IS NULL";
        $params = [];
        if ($bankId !== null) {
            $sql .= " AND (t.`bank_id` IS NULL OR t.`bank_id` = ?)";
            $params[] = $bankId;
        }
        $sql .= " ORDER BY t.`sort_order` ASC, t.`id` ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getAllTemplates(): array {
        $sql = "SELECT t.*, b.bank_name, b.bank_code, COUNT(f.id) AS fields_count 
                FROM `{$this->table}` t
                LEFT JOIN `master_banks` b ON b.id = t.bank_id
                LEFT JOIN `narration_template_fields` f ON f.template_id = t.id
                WHERE t.deleted_at IS NULL
                GROUP BY t.id
                ORDER BY t.sort_order ASC, t.id ASC";
        return $this->db->fetchAll($sql);
    }

    public function findWithBank(int $id): ?array {
        $sql = "SELECT t.*, b.bank_name, b.bank_code 
                FROM `{$this->table}` t
                LEFT JOIN `master_banks` b ON b.id = t.bank_id
                WHERE t.`id` = ? AND t.`deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function findByCode(string $code): ?array {
        $sql = "SELECT t.*, b.bank_name, b.bank_code 
                FROM `{$this->table}` t
                LEFT JOIN `master_banks` b ON b.id = t.bank_id
                WHERE t.`code` = ? AND t.`deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [$code]);
    }
}
