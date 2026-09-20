# ATM Ledger — Permanent UX Rules

## Rule 1: High-Volume Transaction Entry Priority
> **Rapid Entry is optimized for high-volume posting. Keep it minimal and do not move advanced narration-engine controls into the primary Rapid Entry interface.**

### Principles:
1. **Speed First**:
   - A user entering 50 consecutive entries for one account must be able to do so with minimal keystrokes and zero unnecessary mouse interactions.
   - The primary fields remain: **Amount**, **Type (Debit/Credit)**, **Date**, and **Narration**.
   - Amount field must always be auto-focused on initial page load and re-focused automatically after every save.
   - `Ctrl+Enter` (and `Cmd+Enter`) must always trigger submission from anywhere within the form.

2. **Typeahead Autocomplete & Inline Dynamic Controls (No Drawers)**:
   - The primary `#narration-input` is the direct entry point for template selection and narration.
   - Operators should never have to open a separate "Quick Narration Drawer" or modal to pick templates.
   - Typing in `#narration-input` triggers instant, case-insensitive typeahead suggestions filtered/prioritized by Debit/Credit flow.
   - When a template is selected, it immediately populates `#narration-input` and renders compact inline dynamic inputs directly below.
   - Generated tokens (STAN, Reference, Transaction ID) feature an inline `↻` button that regenerates the value and performs in-place token replacement in `#narration-input`.
   - Manual narration remains 100% editable at all times; manual text overrides are preserved and saved verbatim alongside template snapshot metadata (`template_id`, `template_version`, `template_payload`).

3. **Context Retention Across Batches**:
   - Consecutive `Save & Next` (`Ctrl+Enter`) submissions must preserve:
     - Target Account (locked)
     - Transaction Date
     - Transaction Flow / Type (Debit / Credit)
     - Active Template
   - Amounts and references are cleared, and generators automatically supply fresh unique codes for the subsequent entry.
   - Focus is automatically returned to Amount for uninterrupted data entry.

4. **Separation of Concerns**:
   - **Rapid Entry (`/transactions/rapid`)**: Optimized for speed, batch entry, high volume, and day-to-day operations with inline typeahead templates.
   - **Detailed Entry (`/transactions/detailed`)**: Full template configuration, dynamic field inspection, counterparty preset management, live preview tabs, and advanced transaction configuration.
