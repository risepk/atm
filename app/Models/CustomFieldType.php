<?php
// app/Models/CustomFieldType.php

require_once __DIR__ . '/BaseModel.php';

class CustomFieldType extends BaseModel {
    protected string $table = 'narration_custom_types';
    protected bool $hasSoftDelete = false;

    public function delete(int $id): int {
        $sql = "DELETE FROM `{$this->table}` WHERE `id` = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function getActiveTypes(): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `is_active` = 1 ORDER BY `type_name` ASC, `id` ASC";
        return $this->db->fetchAll($sql);
    }

    public function findByCode(string $code): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `type_code` = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$code]);
    }

    public function saveType(array $data): int {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int)$data['id'] : 0;

        $record = [
            'type_code' => trim($data['type_code']),
            'type_name' => trim($data['type_name']),
            'pattern' => trim($data['pattern']),
            'description' => trim($data['description'] ?? ''),
            'is_regeneratable' => !empty($data['is_regeneratable']) ? 1 : 0,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'updated_at' => $now
        ];

        if ($id <= 0) {
            $existing = $this->findByCode(trim($data['type_code']));
            if ($existing) {
                $id = (int)$existing['id'];
            }
        }

        if ($id > 0) {
            $this->update($id, $record);
            return $id;
        }

        $record['created_at'] = $now;
        return $this->insert($record);
    }
}
