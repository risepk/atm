# ATM Ledger ? Account & Transaction Management System
## System Architecture & Technical Manual

---

## 1. Project Vision & Purpose
ATM Ledger is an enterprise-grade financial recordkeeping and transaction management platform designed for speed, security, and extensibility. The central innovation is a database-driven **Dynamic Narration Template Engine** that removes repetitive typing from transaction processing by transforming complex banking descriptions (IBFT, RAAST, ATM withdrawals, Utility Bills, Bank Charges) into dynamic form fields with automated value generation, reusable counterparty presets, real-time preview, and manual override capabilities.

---

## 2. Technology Stack
* **Runtime**: Core PHP 8.2+ (No frameworks like Laravel or Symfony)
* **Database**: MySQL / MariaDB (InnoDB, `utf8mb4_unicode_ci`)
* **Access Layer**: PHP Data Objects (PDO) with strict parameterized queries
* **Frontend**: HTML5, Tailwind CSS, JavaScript, jQuery 3.7.1, and AJAX
* **Server**: Apache 2.4 (compatible with Nginx / PHP-FPM)

---

## 3. Database Standards
* **Primary Keys**: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` across all tables.
* **Storage Engine**: `InnoDB`.
* **Character Set & Collation**: `utf8mb4` with `utf8mb4_unicode_ci`.
* **Zero Database Foreign Keys**: No `CONSTRAINT FOREIGN KEY` clauses. All relational integrity, cascading rules, and isolation are enforced in the application PHP Service layer within PDO transactions.
* **Monetary Precision**: Stored as `DECIMAL(18, 2)`. Floating-point types (`FLOAT`, `DOUBLE`) are strictly forbidden.
* **Synchronization / API**: `CHAR(36)` UUID v4 on all business entities.
* **Soft Deletes**: `deleted_at DATETIME NULL` and `deleted_by BIGINT UNSIGNED NULL`.
* **Audit Trail**: Every create, update, and soft-delete logs actor, action, timestamp, IP, and JSON snapshots.

---

## 4. User Roles & Permission Model
* **Admin Role**:
  * Oversees system accounts and cross-user transactions.
  * Executive dashboard with system-wide liquidity and audit trails.
  * Full CRUD on narration templates, dynamic field builders, and global presets.
  * Statement generator across any account.
* **Normal User Role**:
  * Owns accounts and records transactions.
  * Strict ownership isolation: Cannot view, edit, or delete another user's accounts or transactions (enforced server-side against IDOR).
  * Uses system templates and creates private counterparty presets.
  * Personal dashboard with account balances and transaction history.

---

## 5. Account Ownership Architecture
* Accounts possess an immutable permanent numeric ID (`1`, `2`, `3`, `4`).
* User-friendly account labels (`Main HBL Account` -> `Operations HBL Account`) can be changed at any time.
* Transactions record the immutable `account_id`; reports join `accounts` on `account_id` to display current titles without ledger corruption.
* **Standard Demonstration Accounts**:
  * `Ali`: Habib Bank Limited (Account `0417-79011691-01`, PKR 100,000.00)
  * `Ahmad`: United Bank Limited (Account `1102-88239102-02`, PKR 150,000.00)
  * `Saeed`: Meezan Bank Limited (Account `0104-55192038-03`, PKR 80,000.00)
  * `Jawad`: Bank Alfalah Limited (Account `0229-33819204-04`, PKR 120,000.00)

---

## 6. Financial Ledger & Transaction Workflows
1. **Debit vs Credit Mutual Exclusion**:
   * Exactly one field must be `> 0.00`.
   * The opposing field must be `0.00`.
   * Both `debit > 0 AND credit > 0` is rejected.
   * Both `debit == 0 AND credit == 0` is rejected.
   * Negative amounts are rejected.
2. **Account Balances**:
   * Stored dynamically: `Initial Balance + SUM(Credit) - SUM(Debit)`.
   * Voided/soft-deleted transactions are immediately excluded from balance computations.
3. **Two Distinct Transaction Workflows**:
   * **Rapid Transaction Entry (`/transactions/rapid`)**: Primary high-speed batch posting mode. Account is locked across submissions, date is retained, flow is toggled via large DEBIT/CREDIT buttons (`Alt+D`/`Alt+C`), amount is auto-cleared and auto-focused after save (`Ctrl+Enter`), with instant live BCMath balance feedback and live "Today's Entries" list.
   * **Detailed Transaction Entry (`/transactions/detailed`)**: Advanced template-driven entry with dynamic fields, counterparty presets, one-click value generators, live preview, and manual override.
   * **Transaction Editing (`/transactions/{id}/edit`)**: Uses detailed template interface for correcting previously recorded transactions without ledger corruption.

---

## 7. Dynamic Narration & Template Engine
* Templates define pattern strings with bracketed tokens: `{sender_name}`, `{sender_bank}`, `{stan}`, `{transaction_id}`.
* Dynamic form fields are generated on-the-fly according to field types:
  * `text`, `number`, `amount`, `date`, `time`, `account`, `bank` (searchable select from master banks), `iban`, `reference_no`, `transaction_id`, `stan`, `card_number`, `dropdown`, `textarea`.
* **Value Generator Rules**:
  * `stan`: 6-digit random/sequential numeric.
  * `bank_ref`: Prefix + Date (YYMMDD) + random digits.
  * `alphanumeric`: e.g. `FT26` + Julian Day + random alphanumeric string.
  * Generated values appear with a refresh icon (↻) and remain fully editable.
* **Presets Engine**:
  * Stores JSON key-value snapshots for repetitive counterparties (e.g. `Apex Trading`, `Crescent Logistics`, `Prime Enterprises`).
  * Selecting a preset instantaneously fills matching inputs.
* **Authoritative Final Narration**:
  * The final textarea reflects compiled output in real time.
  * Users can freely customize or append custom text.
  * The final textarea value is persisted into `transactions.narration` and is immutable historically.

---

## 8. Statement Presentation Engine
* Financial data is cleanly separated from presentation layouts.
* Statement adapters:
  * **Standard Statement**: Clean running balance ledger.
  * **HBL Format**: Bank headers with value dates and reference codes.
  * **Meezan Format**: Shariah-compliant presentation.
* Output formats: On-screen preview, print layout, and PDF.

---

## 9. Security Architecture
* **PDO Prepared Statements**: 100% parameter binding. Zero SQL concatenation.
* **IDOR Protection**: Every request validates ownership against `$_SESSION['user_id']`.
* **CSRF Defense**: Cryptographic token issued per session, passed via `X-CSRF-TOKEN` AJAX header or `_csrf_token` POST field.
* **XSS Prevention**: Output escaping using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` through `e()` helper.
* **Session Security**:
  * `session_regenerate_id(true)` upon login.
  * `HttpOnly`, `SameSite=Lax`, and `Secure` cookie flags.
* **Passwords**: Hashed with `PASSWORD_DEFAULT` (BCrypt / Argon2ID).

---

## 10. Automated Testing
Run the zero-dependency test runner via CLI:
```bash
php tests/run_tests.php
```
Test suite verifies:
* Authentication & password security
* IDOR ownership barriers
* Account CRUD & title renaming
* Debit vs Credit mutual exclusion & financial constraints
* Narration pattern compilation & value generators
* Soft-delete mechanics & balance preservation
* Statement running balance calculations

---

## 11. Default System Logins
* **Admin**: `admin` / `Admin@123456`
* **Normal User 1**: `user1` / `User@123456`
* **Normal User 2**: `user2` / `User@123456`
