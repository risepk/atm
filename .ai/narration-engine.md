# Narration Engine & Dynamic Template Specification

## Core Concept
The Narration Engine automates the creation of standard, bank-compliant transaction narrations. It links pattern templates, dynamic field inputs, auto-generators, and reusable counterparty presets.

## Pattern Syntax & Tokens
Templates define pattern strings containing bracketed tokens:
```text
IBFT From {sender_name} - {sender_bank} - {sender_account} to {receiver_name} - {receiver_bank} - {receiver_account}
```

## Auto-Field Detection & Type Inference
In the template builder, the "Auto-detect fields from pattern" parser extracts all unique `{tokens}` and infers types:
- `*stan*` &rarr; `stan` (6-digit numeric generator)
- `*bank*` &rarr; `bank` (Searchable select populated from `master_banks`)
- `*iban*`, `*acc*`, `*account*` &rarr; `account`
- `*tid*`, `*trans_id*`, `*trx*` &rarr; `transaction_id` (Alphanumeric unique generator)
- `*ref*`, `*reference*`, `*rrn*` &rarr; `reference_no` (Prefix + YYMMDD + 8-digit generator)
- `*amount*`, `*fee*`, `*amt*` &rarr; `amount`
- `*card*` &rarr; `card_number`
- `*date*`, `*time*` &rarr; `text`
- Default &rarr; `text`

Existing user configurations (labels, custom options, required flags) are preserved during re-sync.

## Authoritative Final Narration
1. Dynamic fields compile live into `#narration-preview-text`.
2. Unless manually edited, the compiled string fills `#final-narration-textarea`.
3. If the user edits the textarea directly, `isManualOverride` flags it and displays "Custom Edits Applied".
4. The user may click "Re-sync from Fields" to revert to the generated text.
5. The string inside `#final-narration-textarea` is what gets saved to `transactions.narration` and is authoritative.

## Version Snapshot Integrity
When a template pattern is updated, its `version` increments. Historical transactions preserve their original `template_version` snapshot and stored narration; modifying a template never alters historical ledger records.
