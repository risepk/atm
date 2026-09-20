<?php
// app/Models/User.php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected string $table = 'users';

    public function findByUsername(string $username): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `username` = ? AND `deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [trim($username)]);
    }

    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `email` = ? AND `deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [trim($email)]);
    }

    public function findByUsernameOrEmail(string $login): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE (`username` = ? OR `email` = ?) AND `deleted_at` IS NULL LIMIT 1";
        return $this->db->fetchOne($sql, [trim($login), trim($login)]);
    }

    public function getAllUsersWithStats(): array {
        $sql = "SELECT u.id, u.uuid, u.username, u.email, u.full_name, u.role, u.status, u.last_login_at, u.created_at,
                (SELECT COUNT(*) FROM `accounts` a WHERE a.user_id = u.id AND a.deleted_at IS NULL) AS account_count,
                (SELECT COUNT(*) FROM `transactions` t WHERE t.user_id = u.id AND t.deleted_at IS NULL AND t.status = 'posted') AS tx_count
                FROM `users` u
                WHERE u.deleted_at IS NULL 
                ORDER BY u.id ASC";
        return $this->db->fetchAll($sql);
    }

    public function countUsers(): int {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `{$this->table}` WHERE `deleted_at` IS NULL");
    }
}
