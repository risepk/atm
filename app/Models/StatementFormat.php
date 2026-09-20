<?php
// app/Models/StatementFormat.php

require_once __DIR__ . '/BaseModel.php';

class StatementFormat extends BaseModel {
    protected string $table = 'statement_formats';

    public function getActiveFormats(): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `is_active` = 1 ORDER BY `id` ASC";
        return $this->db->fetchAll($sql);
    }

    public function findByCode(string $code): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `format_code` = ? AND `is_active` = 1 LIMIT 1";
        return $this->db->fetchOne($sql, [$code]);
    }
}
