# UI Conventions & Frontend Guidelines

## Palette & Styling
- **CSS Framework**: Tailwind CSS (bundled locally at `public/assets/css/tailwind.min.css`).
- **Primary Brand Theme**: Indigo (`indigo-900` for navigation bar, `indigo-600` for buttons and active links).
- **Admin Accent Theme**: Purple (`purple-900` for admin controls, `purple-700` for admin action buttons).
- **Financial Actions**:
  - Inflow / Credits: Emerald / Green (`emerald-600`, `green-600`, `bg-green-50`).
  - Outflow / Debits: Crimson / Red (`red-600`, `bg-red-50`).

## Icons
- Corrupted Unicode symbols and question marks (`?`) are strictly banned.
- All icons must render as crisp, inline SVG elements (Heroicons pattern):
  ```html
  <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="..." />
  </svg>
  ```

## Searchable Dropdowns (Select2)
- All searchable comboboxes use Select2 initialized through `public/assets/js/searchable-select.js`.
- Class `.searchable-select` applies custom styling via `select2-tailwind.css`.
- Modals set `dropdownParent: modalContainer` to ensure focus and typing work properly.
- All dynamic updates use:
  ```javascript
  $select.trigger('change.select2');
  ```

## Keyboard Shortcuts
- `Ctrl + Enter`: Submit and close active form.
- `Alt + N`: Save current transaction and immediately open fresh entry.
- `Escape`: Dismiss active modal dialog or close navigation menus.

## UI Contrast & Accessibility Guidelines
- **WCAG AA Compliance**: Form labels, captions, and secondary notes must use high-contrast text (`text-gray-700`, `text-gray-600`, or `#4b5563`). Pale `text-gray-400` on white backgrounds is strictly disallowed.
- **Input Placeholders**: Inputs and textareas enforce dark, legible placeholder color `#4b5563` (`gray-600`) via `select2-tailwind.css`.
- **Button Palette Compatibility**: Primary green action buttons (e.g. "Save & New") use `bg-green-600 hover:bg-green-700` with explicit CSS fallback definitions to prevent white-on-white text rendering.
- **Responsive Navigation**: Desktop features an interactive profile dropdown with avatar initials, role badge, and secure POST + CSRF logout. Mobile viewports (320px–768px+) use a responsive hamburger drawer with full role protection and zero horizontal overflow.

## Bank Statement Presentation & Print Guidelines
- **Format Decoupling**: All statement presentation templates (`StandardStatementRenderer`, `HBLStatementRenderer`) are pure visual adapters. They receive pre-calculated BCMath balances from `StatementService` and never re-calculate or mutate figures.
- **A4 Print Readiness**: Printable views enforce `@page { size: A4 portrait; margin: 12mm 10mm 15mm 10mm; }`.
- **Repeating Headers**: Statement tables enforce `thead { display: table-header-group; }` and `tr { page-break-inside: avoid; }` to ensure headers repeat seamlessly across multi-page statements without slicing transaction rows.
- **Multi-Line Particulars**: Transaction narration/description in banking statements must wrap naturally via `white-space: normal; word-break: break-word;` (no clipping or column expansion).
- **Dense Typography**: Banking statement rows utilize crisp tabular figures (`'Courier New', monospace` or Segoe UI tabular) with numbers formatted as `57,000.00`.
- **Brought Forward Rows**: Opening balance is clearly designated as `BROUGHT FORWARD` in the first row of the transaction ledger.

