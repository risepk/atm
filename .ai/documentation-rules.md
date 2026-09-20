# Permanent Documentation & System Maintenance Rules

## Invariant Rule: Unified Synchronization
Whenever any feature, endpoint, database column, UI component, or security policy is modified in the ATM Ledger application, the developer or AI assistant **MUST** update all corresponding documentation and test suites before completing the task:

1. **User Documentation**: Update `public/docs/user-manual.html` if user-facing behavior, forms, views, or workflows are affected.
2. **Developer Documentation**: Update `public/docs/developer-manual.html` if APIs, database schemas, MVC patterns, or architecture change.
3. **AI Documentation Suite**: Update the relevant markdown document(s) in `.ai/`:
   - `.ai/architecture.md`
   - `.ai/database.md`
   - `.ai/narration-engine.md`
   - `.ai/transactions.md`
   - `.ai/security.md`
   - `.ai/testing.md`
   - `.ai/ui-conventions.md`
4. **Automated Test Suite**:
   - Add new test assertions in `tests/run_tests.php` covering the change.
   - Run `php tests/run_tests.php` and verify that all assertions pass with a 100% success rate.
5. **No Personal or Development Names**:
   - Never insert real personal names into demo records, placeholders, seeders, or documentation. Always use neutral fictional business entities (e.g. `Ahmed Khan`, `Main HBL Account`, `Apex Trading`, `Crescent Logistics`).
6. **No Corrupted Characters**:
   - Never use literal non-ASCII glyphs or question marks in place of icons. Always use inline SVG.
