<?php
// app/Helpers/CurrencyHelper.php

class CurrencyHelper {
    /**
     * Validate if value is a valid non-negative decimal string with max 2 decimals
     */
    public static function isValidDecimal($value): bool {
        if ($value === null || $value === '') {
            return false;
        }
        $str = trim((string)$value);
        // Allow optional commas for thousands, but strip them for regex
        $clean = str_replace(',', '', $str);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return false;
        }
        return true;
    }

    /**
     * Normalize a valid numeric/decimal string to exact 2-decimal string (e.g. '150' -> '150.00')
     * Does NOT use PHP float to avoid floating point precision loss
     */
    public static function normalizeDecimal($value): string {
        $str = trim((string)$value);
        $clean = str_replace(',', '', $str);

        $isNegative = false;
        if (strpos($clean, '-') === 0) {
            $isNegative = true;
            $clean = substr($clean, 1);
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return '0.00';
        }

        if (strpos($clean, '.') === false) {
            $normalized = $clean . '.00';
        } else {
            [$whole, $decimal] = explode('.', $clean, 2);
            if (strlen($decimal) === 0) {
                $decimal = '00';
            } elseif (strlen($decimal) === 1) {
                $decimal .= '0';
            } else {
                $decimal = substr($decimal, 0, 2);
            }
            $normalized = (ltrim($whole, '0') === '' ? '0' : ltrim($whole, '0')) . '.' . $decimal;
        }

        if ($isNegative && $normalized !== '0.00') {
            return '-' . $normalized;
        }
        return $normalized;
    }

    /**
     * Compare two decimal strings using bccomp
     * Returns:
     *  -1 if $a < $b
     *   0 if $a == $b
     *   1 if $a > $b
     */
    public static function comp(string $a, string $b): int {
        $normA = self::normalizeDecimal($a);
        $normB = self::normalizeDecimal($b);
        return bccomp($normA, $normB, 2);
    }

    /**
     * Add two decimal strings using bcadd
     */
    public static function add(string $a, string $b): string {
        $normA = self::normalizeDecimal($a);
        $normB = self::normalizeDecimal($b);
        return bcadd($normA, $normB, 2);
    }

    /**
     * Subtract two decimal strings ($a - $b) using bcsub
     */
    public static function sub(string $a, string $b): string {
        $normA = self::normalizeDecimal($a);
        $normB = self::normalizeDecimal($b);
        return bcsub($normA, $normB, 2);
    }

    /**
     * Format decimal monetary string for display with thousands commas
     */
    public static function format($amount, string $currency = 'PKR', bool $includeSymbol = false): string {
        $normalized = self::normalizeDecimal($amount);
        $isNegative = strpos($normalized, '-') === 0;
        $clean = $isNegative ? substr($normalized, 1) : $normalized;
        $parts = explode('.', $clean);
        $whole = $parts[0];
        $decimals = $parts[1] ?? '00';

        // Insert thousands commas into the whole integer string
        $formattedWhole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);
        $result = ($isNegative ? '-' : '') . $formattedWhole . '.' . $decimals;

        if ($includeSymbol) {
            return $currency . ' ' . $result;
        }
        return $result;
    }
}
