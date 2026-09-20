# System Architecture & Design Specification

## Overview
**ATM Ledger** is an institutional-grade Account & Transaction Management platform built with pure Core PHP 8.2 and MariaDB 10.4 without framework dependencies.

## Request Lifecycle
1. **Entry Point**: Web server routes requests through `.htaccess` to `public/index.php`.
2. **Routing**: `app/Core/Router.php` dispatches URI and HTTP method to Controller classes.
3. **Middleware Pipeline**:
   - `auth`: Verifies active authenticated session in `Session::get('user_id')`.
   - `guest`: Ensures authenticated users are redirected away from login.
   - `role.admin`: Restricts routes (such as `/templates`, `/banks`, `/users`) to admin users.
   - `csrf`: Verifies `_csrf_token` on all state-mutating requests (`POST`, `PUT`, `DELETE`).
4. **Controllers**: Light routing handlers that validate requests via `app/Core/Validator.php`, delegate business logic to Services, and return JSON responses or render PHP views.
5. **Services**: Domain logic layer (`AuthService`, `AccountService`, `TransactionService`, `NarrationService`, `PresetService`, `StatementService`, `AuditService`, `GeneratorService`).
6. **Models**: PDO database interaction via `BaseModel` and specialized models (`User`, `Account`, `Transaction`, `NarrationTemplate`, `NarrationPreset`, `MasterBank`, `AuditLog`, `StatementFormat`).
7. **Views**: Pure PHP templates with clean Tailwind CSS styling and Select2 searchable comboboxes.

## Key Architectural Invariants
- **No Float Math**: Strict `DECIMAL(18,2)` database types and BCMath PHP functions. Floating point casting is banned.
- **Account Ownership & IDOR Protection**: Accounts belong to a `user_id`. Non-admin users can never access or mutate other users' accounts or transactions.
- **Permanent Account Identifiers**: Accounts use immutable numeric IDs. Account titles can be renamed at will without altering historical transactions.
- **Audit Trails**: Critical actions (user changes, bank modifications, transaction edits, voiding) are immutably logged to `audit_logs`.
- **Extensible Statement Presentation Engine**: Strict separation of financial calculation engine (`StatementService` using pure BCMath) from presentation layer (`StatementRendererInterface`, `StandardStatementRenderer`, `HBLStatementRenderer`, `StatementRendererFactory`).
  - **Standard Format (`standard`)**: Clean tabular presentation with account summary and transaction breakdown.
  - **HBL Format (`hbl`)**: Dense institutional statement layout modeled after client-supplied Habib Bank Limited sample. Features:
    - Uppercase header: `YOUR ACCOUNT STATEMENT` with formatted period range.
    - Two-column header layout: Customer info on left, dense account metadata on right (`ACCOUNT NO`, `ACCOUNT TYPE`, `CURRENCY`, `PRINTING DATE`, `FREQUENCY`, `PAGE NO`, `USER`).
    - Dedicated `BROUGHT FORWARD` row displaying opening balance.
    - Standard columns: `DATE`, `VALUE`, `PARTICULARS`, `DEBIT`, `CREDIT`, `BALANCE`.
    - Multi-line natural particulars wrapping without clipping.
    - Dedicated A4 print stylesheet (`@page { size: A4 portrait; }`, `thead { display: table-header-group; }`, `tr { page-break-inside: avoid; }`).
    - 100% financial parity with Standard format (opening, debits, credits, running balances, closing balance).
  - **Future Formats (Meezan, UBL, Alfalah, MCB)**: Must follow the exact same adapter pattern: implement `StatementRendererInterface`, consume pre-calculated financial data from `StatementService` (never recalculate balances), and register via `StatementRendererFactory`.
- **Bank Branding System**: Master bank catalog stores `logo_path` (`public/uploads/banks/`), validated via `finfo` for MIME types (`image/png`, `image/jpeg`, `image/webp`) with a 2MB limit and automatic old file cleanup. Neutral initials badge fallback is provided when logos are unattached.
- **Responsive Navigation Architecture**: Desktop navigation features an accessible profile dropdown with user metadata and strict POST + CSRF logout; mobile navigation features a responsive hamburger drawer (320px–768px+) with complete role-based link protection.

