<?php
// app/Models/NarrationPreset.php

require_once __DIR__ . '/BaseModel.php';

class NarrationPreset extends BaseModel {
    protected string $table = 'narration_presets';

    public function getPresetsForTemplate(int $templateId, ?int $userId = null): array {
        $where = ["template_id = ?", "deleted_at IS NULL", "is_active = 1"];
        $params = [$templateId];

        if ($userId !== null) {
            $where[] = "(user_id IS NULL OR user_id = ?)";
            $params[] = $userId;
        } else {
            $where[] = "user_id IS NULL";
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE {$whereSql} 
                ORDER BY user_id ASC, sort_order ASC, preset_name ASC";
        return $this->db->fetchAll($sql, $params);
    }
}
