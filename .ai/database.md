# Database Architecture & Data Integrity

## Engine & Charset
- **Engine**: InnoDB
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`

## Tables Schema Summary
1. `users`: System users with `uuid`, `username`, `email`, `password_hash`, `full_name`, `role` (`admin`, `user`), and `status` (`active`, `inactive`, `suspended`).
2. `accounts`: Bank/cash portfolios with `id` (BIGINT UNSIGNED), `uuid`, `user_id`, `account_title`, `account_number`, `bank_name`, `currency` (PKR), `initial_balance` (DECIMAL 18,2), and `status` (`active`, `inactive`, `closed`).
3. `transactions`: Double-entry transaction ledger with `id`, `uuid`, `account_id`, `user_id`, `transaction_date`, `debit` (DECIMAL 18,2), `credit` (DECIMAL 18,2), `narration` (TEXT), `reference_no`, `template_id`, `template_version`, `template_payload` (JSON), `status` (`posted`, `voided`), `void_reason`, `voided_at`, `voided_by`.
4. `narration_templates`: Patterns with `id`, `uuid`, `name`, `code`, `category`, `pattern` (TEXT), `version` (INT), `is_active`.
5. `narration_template_fields`: Dynamic fields per template with `field_key`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `generator_rule` (JSON), `options_source`, `sort_order`.
6. `narration_presets`: Counterparty presets with `id`, `uuid`, `template_id`, `user_id` (NULL for global presets), `preset_name`, `field_values` (JSON), `is_active`.
7. `master_banks`: Official bank directory with `id`, `bank_name`, `bank_code`, `swift_code`, `logo_path` (VARCHAR 255 NULL), `is_active`, `sort_order`.
8. `statement_formats`: Formats for presentation with `format_code`, `format_name`, `description`, `config` (JSON), `is_active`.
9. `audit_logs`: Immutable audit trail with `user_id`, `action`, `entity_type`, `entity_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`, `user_agent`, `created_at`.

## Financial Balance Derivation
Portfolio balance is never stored as a mutable static number that can desynchronize.
Instead, running and current balances are derived from:
```sql
SELECT 
    a.initial_balance + 
    COALESCE(SUM(CASE WHEN t.status != 'voided' AND t.deleted_at IS NULL THEN t.credit ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN t.status != 'voided' AND t.deleted_at IS NULL THEN t.debit ELSE 0 END), 0) AS current_balance
FROM accounts a
LEFT JOIN transactions t ON a.id = t.account_id
WHERE a.id = :account_id
GROUP BY a.id;
```
Opening balance is strictly immutable once any active transactions are recorded.
