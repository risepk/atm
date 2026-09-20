# Automated Testing Suite & QA Architecture

## Test Runner Design
The automated test runner is located in `tests/run_tests.php`. It is executed from the CLI:
```bash
php tests/run_tests.php
```

## Coverage Sections
1. **Authentication, Passwords & Session Security**: Login, logout, role verification, whitespace preservation, password rejection.
2. **Account Ownership & IDOR Protection**: Create, read, update, delete, IDOR rejection on unauthorized cross-user access, duplicate account rejection.
3. **Opening Balance Immutability & Account Status**: Modifying initial balance before transactions, strict rejection once transactions exist, status transitions (active &rarr; inactive &rarr; closed).
4. **Financial Math & BCMath Decimal Engine**: Decimal validation, normalization, comma formatting, zero float drift verification, large number precision.
5. **Transaction Mutations, Editing & Voiding**: Double-entry exclusion, debit posting, editing balances, IDOR protection on edit/void, void balance restoration, audit logging.
6. **Narration Template Engine & Server-Side Validation**: Token syntax validation, duplicate key rejection, required field enforcement, strict placeholder substitution, template version snapshots.
7. **Generators & Collision Resistance**: 6-digit STAN generator, date-prefixed bank reference, alphanumeric unique generator.
8. **Narration Presets & User Isolation**: Personal preset creation, admin global preset creation, user boundary isolation on presets.
9. **Pakistan Bank Master & Dynamic Admin Addition**: Seed catalogue inspection, dynamic bank addition by admin, case-insensitive duplicate prevention.
10. **Statement Presentation Engine**: Standard format rendering, opening/closing balance accuracy, chronological running balance verification, voided transaction exclusion, empty account statements.
11. **XSS Injection & Sanitization Safety**: HTML entity conversion, script tag neutralization, onerror and SVG attribute neutralization.
12. **User Administration Module**: User listing with portfolio statistics, creation, updating role/status, password reset verification.
13. **Bank Master Management & Deactivation**: Master bank CRUD, `getAllBanks`, update bank with uniqueness checks, `toggleStatus` soft deactivation and activation.
14. **Personal/Demo Name Elimination**: Asserts zero occurrences of real or personal development names across `users`, `accounts`, and `narration_presets`.
15. **Select2 Assets & Auto-Field Pattern Extraction**: Verifies presence of local Select2 bundles and adapter stylesheet, checks token extraction regex.
16. **Bank Logo Architecture, Storage & Removal**: Verifies logo file creation, database persistence of `logo_path`, replacement cleanup of old files, and logo removal setting path to null and deleting files from disk.
17. **Statement Presentation Architecture & Renderers**: Validates `StatementRendererInterface` factory creation, active registration of `hbl`, deferred format fallback behavior for unprovided bank layouts (Meezan), bank logo resolution, and HTML/Print renderers.
18. **UI Contrast & Responsive Navigation**: Asserts presence of profile dropdown, hamburger drawer, POST + CSRF logout, docs link removal from navbar, and elimination of unstyled emerald classes.
19. **HBL Statement Presentation Engine & Parity**:
    - Format activation in `statement_formats` and presence in active selector list.
    - Factory instantiation of `HBLStatementRenderer` implementing `StatementRendererInterface`.
    - 100% financial parity with Standard format: opening balance, debits, credits, running balances, closing totals, and row count match identically.
    - Exclusion of voided transactions from statement.
    - Header assertions: `YOUR ACCOUNT STATEMENT`, period ending, customer account title, `ACCOUNT NO`, `ACCOUNT TYPE`, `CURRENCY` (Pakistani Rupee), `PRINTING DATE`, `FREQUENCY` (INTERIM / DUPLICATE), `PAGE NO`, `USER`.
    - First row `BROUGHT FORWARD` with opening balance.
    - Table column assertions (`DATE`, `VALUE`, `PARTICULARS`, `DEBIT`, `CREDIT`, `BALANCE`).
    - Multi-line particulars wrapping without truncation.
    - Bank logo rendering and neutral styled typography fallback when logo is null.
    - XSS escaping across all dynamic fields.
    - Print layout verification: standalone HTML5, A4 portrait CSS, repeating `thead` (`table-header-group`), page-break avoidance (`page-break-inside: avoid`), interactive print trigger, and system-generated non-signature disclaimer.

## Exit Status
- `exit(0)` on 100% pass rate.
- `exit(1)` on any assertion failure or unhandled exception.
