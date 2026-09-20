<?php
// app/Helpers/SecurityHelper.php

class SecurityHelper {
    /**
     * Escape HTML output against XSS
     */
    public static function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate cryptographically secure UUID v4
     */
    public static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Sanitize string input
     */
    public static function clean(?string $value): string {
        return trim(strip_tags($value ?? ''));
    }
}

// Global shortcut function for escaping
if (!function_exists('e')) {
    function e(?string $value): string {
        return SecurityHelper::e($value);
    }
}
