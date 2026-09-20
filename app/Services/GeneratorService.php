<?php
// app/Services/GeneratorService.php

require_once __DIR__ . '/../Core/Database.php';

class GeneratorService {
    /**
     * Generate collision-checked unique value if field is a financial reference
     */
    public static function generate(array $rule, bool $checkUnique = false): string {
        $type = $rule['type'] ?? 'alphanumeric';
        $prefix = $rule['prefix'] ?? '';
        $suffix = $rule['suffix'] ?? '';
        $length = (int)($rule['length'] ?? 16);

        $attempts = 0;
        $maxAttempts = 5;

        do {
            $value = self::generateRaw($type, $prefix, $suffix, $length);
            $attempts++;

            if (!$checkUnique) {
                return $value;
            }

            // Check uniqueness against transactions reference_no
            $exists = self::checkReferenceExists($value);
            if (!$exists) {
                return $value;
            }
        } while ($attempts < $maxAttempts);

        // If collision persists, append higher-entropy suffix
        return $value . random_int(100, 999);
    }

    private static function generateRaw(string $type, string $prefix, string $suffix, int $length): string {
        switch ($type) {
            case 'stan':
                // 6-digit System Trace Audit Number (100000 to 999999)
                $len = $length > 0 ? $length : 6;
                $min = (int)pow(10, $len - 1);
                $max = (int)pow(10, $len) - 1;
                return $prefix . (string)random_int($min, $max) . $suffix;

            case 'bank_ref':
                // Bank Reference: Prefix + Date (YYMMDD) + 6-8 digit random sequence
                $p = $prefix ?: 'BAHLPKKA';
                $datePart = date('ymd');
                $numLength = max(4, $length - strlen($p) - strlen($datePart) - strlen($suffix));
                $randomDigits = '';
                for ($i = 0; $i < $numLength; $i++) {
                    $randomDigits .= (string)random_int(0, 9);
                }
                return $p . $datePart . $randomDigits . $suffix;

            case 'alphanumeric':
            case 'transaction_id':
                // Transaction ID: Prefix (e.g. FT) + 2-digit Year + Day-of-Year + Random Alphanumeric
                $p = $prefix ?: 'FT26';
                $dateCode = date('y') . sprintf('%03d', date('z') + 1);
                $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randLen = max(4, $length - strlen($p) - strlen($dateCode) - strlen($suffix));
                $randStr = '';
                $charLen = strlen($chars);
                for ($i = 0; $i < $randLen; $i++) {
                    $randStr .= $chars[random_int(0, $charLen - 1)];
                }
                return $p . $dateCode . $randStr . $suffix;

            case 'atm_code':
            case 'atm':
                // Realistic Pakistani ATM terminal code: ATM-3-digit-City
                $p = $prefix ?: 'ATM-';
                $cities = ['KHI', 'LHE', 'ISB', 'RWP', 'FSD', 'MUL', 'PEW'];
                $city = $suffix ?: $cities[array_rand($cities)];
                $digits = sprintf('%03d', random_int(10, 999));
                return $p . $digits . '-' . $city;

            case 'rrn':
                // 12-digit Retrieval Reference Number
                $rrn = '';
                for ($i = 0; $i < 12; $i++) {
                    $rrn .= (string)random_int(0, 9);
                }
                return $prefix . $rrn . $suffix;

            case 'numeric':
                $num = '';
                for ($i = 0; $i < $length; $i++) {
                    $num .= (string)random_int(0, 9);
                }
                return $prefix . $num . $suffix;

            default:
                $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $rand = '';
                for ($i = 0; $i < $length; $i++) {
                    $rand .= $chars[random_int(0, strlen($chars) - 1)];
                }
                return $prefix . $rand . $suffix;
        }
    }

    private static function checkReferenceExists(string $reference): bool {
        try {
            $db = Database::getInstance();
            $sql = "SELECT COUNT(*) FROM `transactions` WHERE `reference_no` = ? OR `narration` LIKE ?";
            $cnt = $db->fetchColumn($sql, [$reference, '%' . $reference . '%']);
            return ((int)$cnt) > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
