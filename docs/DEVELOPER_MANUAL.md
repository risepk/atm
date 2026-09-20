# ATM Ledger — Developer Manual

## Architecture & Narration Engine Integration

### Narration Flow Architecture
ATM Ledger strictly reuses a single authoritative narration engine across both Rapid Entry and Detailed Entry:

```
Rapid Entry (/transactions/rapid)
     ↓
Typeahead & Quick Chips (#narration-input)
     ↓
Existing Narration Engine (NarrationEngine.js & NarrationService.php)
     ↓
Inline Dynamic Fields & Generators (GeneratorService.php)
     ↓
Compiled Narration (#narration-input with manual override)
     ↓
Transaction API (/api/transactions/save)
     ↓
Database (transactions table with template_id, template_version, template_payload snapshot)
```

### Core Invariants & Rules

1. **Do NOT Duplicate Narration Logic**:
   - Rapid Entry does not have its own template engine or separate parser.
   - It reuses `NarrationEngine.compile(pattern, values)` on the client side and `NarrationService` / `GeneratorService` on the server side.
   - All generator rules (`stan`, `bank_ref`, `alphanumeric`, `numeric`) are identical to Detailed Entry.

2. **In-Place Token Replacement**:
   - When a user clicks the inline `↻` regenerator button beside a dynamic token (e.g. STAN), only that specific token is regenerated and substituted inside `#narration-input`.
   - If the operator added custom notes (e.g., ` - Call verified with Branch Manager`), the custom text is preserved verbatim while only the target token changes.

3. **Immutable Audit Snapshots**:
   - When a transaction is posted with an active template, the following audit fields are saved:
     - `template_id`: ID of the template used.
     - `template_version`: Version integer at the moment of recording.
     - `template_payload`: JSON string of all dynamic field key-value pairs.
     - `narration`: The final compiled (or manually overridden) string.
   - Historical transactions never change if a template is later edited or deleted.

4. **Continuous Batch State Retention**:
   - On successful `Save & Next`:
     - Keep: `account_id`, `transaction_date`, `transaction_type`, `currentTemplate`.
     - Reset: `amount`, `reference_no`.
     - Regenerate: Generator tokens (STAN, Ref, ID) get fresh values automatically.
     - Recompile: `#narration-input` updates with the fresh codes.
     - Auto-focus: `#amount-input` is focused immediately.

5. **Tailwind CSS v2 & High-Contrast Navigation**:
   - The compiled Tailwind asset is v2.2.19 (`tailwind.min.css`).
   - Tailwind v2 does **not** support `slate-*`, `zinc-*`, or `neutral-*` color palettes. Using those classes results in unstyled transparent backgrounds and severe contrast failures.
   - Always use standard `gray-*` classes (`bg-gray-900`, `bg-gray-800`, `text-gray-200`, `text-gray-300`, `text-white`).
   - Strict contrast rule: Dark surfaces (`bg-gray-900`) must always pair with high-contrast light text (`text-gray-200` or `text-white`). Light surfaces (`bg-gray-50`, `bg-white`) must pair with dark text (`text-gray-900`, `text-gray-700`).

6. **Financial Calculations**:
   - All balance updates must use the `BCMath` wrapper via `CurrencyHelper` (`add`, `sub`, `comp`) to prevent IEEE 754 binary floating-point rounding errors.
