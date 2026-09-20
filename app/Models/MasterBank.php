<?php
// app/Models/MasterBank.php

require_once __DIR__ . '/BaseModel.php';

class MasterBank extends BaseModel {
    protected string $table = 'master_banks';
    protected bool $hasCreatedAt = false;
    protected bool $hasUpdatedAt = false;
    protected bool $hasSoftDelete = false;

    public function getActiveBanks(): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `bank_name` ASC";
        return $this->db->fetchAll($sql);
    }

    public function getAllBanks(): array {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `sort_order` ASC, `bank_name` ASC";
        return $this->db->fetchAll($sql);
    }

    public function getAllActive(): array {
        return $this->getActiveBanks();
    }

    public function findByName(string $name): ?array {
        $trimmed = trim($name);
        if (empty($trimmed)) return null;

        // 1. Exact name match
        $sql = "SELECT * FROM `{$this->table}` WHERE LOWER(`bank_name`) = LOWER(?) LIMIT 1";
        $bank = $this->db->fetchOne($sql, [$trimmed]);
        if ($bank) return $bank;

        // 2. Exact bank code match (e.g. 'FBL', 'HBL')
        $byCode = $this->findByCode($trimmed);
        if ($byCode) return $byCode;

        // 3. Match without or with 'Limited'
        $stripped = preg_replace('/\s+(ltd|limited)\.?$/i', '', $trimmed);
        if ($stripped !== $trimmed) {
            $bank = $this->db->fetchOne("SELECT * FROM `{$this->table}` WHERE LOWER(`bank_name`) = LOWER(?) LIMIT 1", [$stripped]);
            if ($bank) return $bank;
        }

        // 4. Prefix or substring match (e.g. 'Faysal Bank' -> 'Faysal Bank Limited')
        $bank = $this->db->fetchOne("SELECT * FROM `{$this->table}` WHERE LOWER(`bank_name`) LIKE LOWER(?) OR LOWER(`bank_name`) LIKE LOWER(?) LIMIT 1", [$trimmed . '%', '%' . $trimmed . '%']);
        if ($bank) return $bank;

        // 5. Common bank name aliases
        $lower = strtolower($trimmed);
        if (strpos($lower, 'faisal') !== false || strpos($lower, 'faysal') !== false) {
            return $this->findByCode('FBL');
        }
        if (strpos($lower, 'habib') !== false && strpos($lower, 'metro') === false && strpos($lower, 'bank al') === false) {
            return $this->findByCode('HBL');
        }

        return null;
    }

    public function findByNameExcludingId(string $name, int $id): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE LOWER(`bank_name`) = LOWER(?) AND `id` != ? LIMIT 1";
        return $this->db->fetchOne($sql, [trim($name), $id]);
    }

    public function findByCode(string $code): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE LOWER(`bank_code`) = LOWER(?) LIMIT 1";
        return $this->db->fetchOne($sql, [trim($code)]);
    }

    public function findByCodeExcludingId(string $code, int $id): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE LOWER(`bank_code`) = LOWER(?) AND `id` != ? LIMIT 1";
        return $this->db->fetchOne($sql, [trim($code), $id]);
    }

    public function addBank(string $bankName, ?string $bankCode = null, ?string $swiftCode = null, ?string $logoPath = null): int {
        $bankName = trim($bankName);
        if (mb_strlen($bankName) < 3 || mb_strlen($bankName) > 100) {
            throw new Exception("Bank name must be between 3 and 100 characters.");
        }

        $existing = $this->findByName($bankName);
        if ($existing) {
            throw new Exception("Bank '{$bankName}' already exists in the system.");
        }

        if (!$bankCode) {
            $words = explode(' ', $bankName);
            $code = '';
            foreach ($words as $w) {
                $code .= strtoupper(substr($w, 0, 1));
            }
            $bankCode = substr(preg_replace('/[^A-Z]/', '', $code) ?: 'BANK', 0, 10);
        } else {
            $bankCode = strtoupper(trim($bankCode));
        }

        $baseCode = $bankCode;
        while ($this->findByCode($bankCode)) {
            $bankCode = substr($baseCode, 0, 14) . random_int(100, 9999);
        }

        $data = [
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'swift_code' => $swiftCode ? trim($swiftCode) : null,
            'logo_path' => $logoPath ? trim($logoPath) : null,
            'is_active' => 1,
            'sort_order' => 999
        ];
        return $this->insert($data);
    }

    public function updateBank(int $id, string $bankName, string $bankCode, ?string $swiftCode = null, ?string $logoPath = null, bool $removeLogo = false): bool {
        $bankName = trim($bankName);
        $bankCode = strtoupper(trim($bankCode));

        if (mb_strlen($bankName) < 3 || mb_strlen($bankName) > 100) {
            throw new Exception("Bank name must be between 3 and 100 characters.");
        }
        if (empty($bankCode) || mb_strlen($bankCode) > 20) {
            throw new Exception("Bank code must be between 1 and 20 characters.");
        }

        $existingName = $this->findByNameExcludingId($bankName, $id);
        if ($existingName) {
            throw new Exception("Another bank with the name '{$bankName}' already exists.");
        }

        $existingCode = $this->findByCodeExcludingId($bankCode, $id);
        if ($existingCode) {
            throw new Exception("Another bank with the code '{$bankCode}' already exists.");
        }

        $currentBank = $this->find($id);
        $updateData = [
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'swift_code' => $swiftCode ? trim($swiftCode) : null
        ];

        if ($removeLogo) {
            if ($currentBank && !empty($currentBank['logo_path'])) {
                self::deleteLogoFile($currentBank['logo_path']);
            }
            $updateData['logo_path'] = null;
        } elseif ($logoPath !== null) {
            if ($currentBank && !empty($currentBank['logo_path']) && $currentBank['logo_path'] !== $logoPath) {
                self::deleteLogoFile($currentBank['logo_path']);
            }
            $updateData['logo_path'] = $logoPath;
        }

        return $this->update($id, $updateData);
    }

    public static function deleteLogoFile(?string $logoPath): void {
        if (!$logoPath) return;
        $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($logoPath, '/\\');
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function toggleStatus(int $id): int {
        $bank = $this->find($id);
        if (!$bank) {
            throw new Exception("Bank not found.");
        }
        $newStatus = ($bank['is_active'] == 1) ? 0 : 1;
        $this->update($id, ['is_active' => $newStatus]);
        return $newStatus;
    }
}
