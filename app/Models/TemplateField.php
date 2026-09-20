<?php
// app/Models/TemplateField.php

require_once __DIR__ . '/BaseModel.php';

class TemplateField extends BaseModel {
    protected string $table = 'narration_template_fields';

    public function getFieldsByTemplateId(int $templateId): array {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `template_id` = ? 
                ORDER BY `sort_order` ASC, `id` ASC";
        return $this->db->fetchAll($sql, [$templateId]);
    }

    public function deleteByTemplateId(int $templateId): int {
        return $this->db->execute("DELETE FROM `{$this->table}` WHERE `template_id` = ?", [$templateId]);
    }
}
