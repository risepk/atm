<?php
// app/Models/BaseModel.php

require_once __DIR__ . '/../Core/Database.php';

abstract class BaseModel {
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $hasCreatedAt = true;
    protected bool $hasUpdatedAt = true;
    protected bool $hasSoftDelete = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $whereDeleted = $this->hasSoftDelete ? " AND `deleted_at` IS NULL" : "";
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?{$whereDeleted} LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function findWithTrashed(int $id): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function findByUuid(string $uuid): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `uuid` = ? AND `deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [$uuid]);
    }

    public function insert(array $data): int {
        if ($this->hasCreatedAt && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if ($this->hasUpdatedAt && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($data);
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO `{$this->table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->query($sql, array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): int {
        if ($this->hasUpdatedAt && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            $fields[] = "`{$k}` = ?";
            $values[] = $v;
        }
        $values[] = $id;

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE `{$this->primaryKey}` = ?";
        return $this->db->execute($sql, $values);
    }

    public function softDelete(int $id, int $userId): int {
        $sql = "UPDATE `{$this->table}` SET `deleted_at` = ?, `deleted_by` = ? WHERE `{$this->primaryKey}` = ?";
        return $this->db->execute($sql, [date('Y-m-d H:i:s'), $userId, $id]);
    }
}
