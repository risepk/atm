<?php
// app/Models/NarrationTemplate.php

require_once __DIR__ . '/BaseModel.php';

class NarrationTemplate extends BaseModel {
    protected string $table = 'narration_templates';

    public function getActiveTemplates(): array {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `is_active` = 1 AND `deleted_at` IS NULL 
                ORDER BY `sort_order` ASC, `id` ASC";
        return $this->db->fetchAll($sql);
    }

    public function getAllTemplates(): array {
        $sql = "SELECT t.*, COUNT(f.id) AS fields_count 
                FROM `{$this->table}` t
                LEFT JOIN `narration_template_fields` f ON f.template_id = t.id
                WHERE t.deleted_at IS NULL
                GROUP BY t.id
                ORDER BY t.sort_order ASC, t.id ASC";
        return $this->db->fetchAll($sql);
    }

    public function findByCode(string $code): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `code` = ? AND `deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [$code]);
    }
}
