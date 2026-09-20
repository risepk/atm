# ATM Ledger — User Manual

## Overview
ATM Ledger is a double-entry financial ledger and transaction management system designed for speed, accuracy, and auditability. The system provides two primary modes for transaction recording: **Rapid Transaction Entry** and **Detailed Transaction Entry**.

---

## 1. Rapid Transaction Entry (`/atm/transactions/rapid`)

### Purpose
Designed for high-speed, high-volume batch posting where an operator needs to record dozens or hundreds of transactions for a single account without repetitive mouse clicking or page navigation.

### Key Workflow
1. **Target Account Selection**: Select the account once. The account remains locked at the top with a live available balance banner.
2. **Transaction Date**: Defaults to today's date and is remembered across entries.
3. **Transaction Flow (Debit vs Credit)**:
   - Click **DEBIT** (`Alt+D`) for money out (expenses, withdrawals, outward transfers, charges).
   - Click **CREDIT** (`Alt+C`) for money in (deposits, inward transfers, receipts).
4. **Amount**: Fast numeric input (`Ctrl+A` / auto-focused). Cleared and re-focused after every save.
5. **Narration**:
   - **Manual Free-Text**: Type any description directly.
   - **Typeahead Template Search**: Type a prefix (e.g., `IBF`, `ATM`, `CASH`). Active matching templates appear in an instant dropdown list. Press `ArrowDown` and `Enter` to select.
   - **Contextual Quick Chips**: Click any of the dynamic chips shown below the input for one-click template selection.
6. **Compact Inline Fields**:
   - If the selected template requires tokens (e.g. Bank, Account, STAN, Reference), compact inline controls appear directly below `#narration-input`.
   - Generated values (STAN, Bank Reference, Transaction ID) generate automatically.
   - **In-Place Regenerator (`↻`)**: Click the small `↻` button beside any generated field to generate a new unique value. The token in the narration updates in-place immediately without overwriting custom notes.
7. **Manual Override**: The narration input remains 100% editable. You can append or edit notes (e.g., ` - Verified via phone`) at any time.
8. **Save & Next (`Ctrl+Enter`)**:
   - Saves the transaction to the database with BCMath balance calculations.
   - The transaction appears immediately in **Today's Recorded Entries** table below.
   - Target Account, Date, Flow, and Template are preserved for the next entry.
   - Amount is cleared and auto-focused for uninterrupted continuous data entry.

---

## 2. Detailed Transaction Entry (`/atm/transactions/detailed`)

### Purpose
Designed for complex, individual transactions requiring advanced counterparty configurations, full template metadata inspection, preset management, and multi-line live previews.

### Features
- Full Template selector with categorized groups.
- Detailed form fields for senders, receivers, banks, branches, and custom parameters.
- Counterparty preset management (save and apply frequently used beneficiaries).
- Split-screen live preview showing compiled narration before posting.
- Extended audit parameters and manual reference overrides.

---

## 3. When to Use Which?

| Scenario | Recommended Mode |
| :--- | :--- |
| Entering a daily batch of bank statement lines | **Rapid Entry** |
| High-volume data entry (>10 entries in a session) | **Rapid Entry** |
| Quick withdrawal, deposit, or simple transfer | **Rapid Entry** |
| Complex corporate payment with multiple metadata fields | **Detailed Entry** |
| Saving a new reusable counterparty preset | **Detailed Entry** |
| Configuring advanced narration templates | **Detailed Entry** |

---

## 4. Keyboard Shortcuts Quick Reference

- `Ctrl + Enter` (or `Cmd + Enter`): **Save & Next Entry** from any field.
- `Alt + D`: Switch flow to **DEBIT (Money Out)**.
- `Alt + C`: Switch flow to **CREDIT (Money In)**.
- In Narration Input:
  - `ArrowDown` / `ArrowUp`: Navigate template suggestions.
  - `Enter`: Select highlighted template.
  - `Escape`: Dismiss template suggestions.
  - `Tab`: Jump to Reference field or Save button.
