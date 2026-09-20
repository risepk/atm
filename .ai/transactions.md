# Transactions & Ledger Mutation Specification

## Financial Ledger Invariants
1. **Double-Entry Exclusion**:
   - `debit > 0` AND `credit == 0` (Debit / Outflow)
   - `credit > 0` AND `debit == 0` (Credit / Inflow)
   - Both debit and credit greater than 0 is strictly rejected with HTTP 422.
   - Both debit and credit equal to 0 is strictly rejected with HTTP 422.
   - Negative amounts are rejected.
2. **Precision & Scale**:
   - All amounts validated via `/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/`.
   - Money stored as `DECIMAL(18,2)`.
   - Calculations use `CurrencyHelper::add()` and `CurrencyHelper::sub()` via BCMath.

## Transaction Lifecycle
- **Creation**: Posted to `transactions` table with status `posted`. Current account balance immediately reflects the new transaction.
- **Editing**: Users can edit date, reference, debit/credit, and narration text on active transactions.
  - When financial amounts change, the portfolio balance dynamically re-adjusts.
  - Edited transactions trigger an audit log with old and new values.
- **Voiding**:
  - Direct deletion is prohibited.
  - Voiding sets `status = 'voided'`, sets `deleted_at = NOW()`, `voided_at = NOW()`, `voided_by = user_id`, and records a required `void_reason`.
  - Voided transactions are automatically excluded from account balance computations and statements.
  - Once voided, a transaction cannot be edited.
