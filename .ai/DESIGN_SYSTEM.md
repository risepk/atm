# ATM Ledger — Minimal Financial UI Design System

## Philosophy
ATM Ledger visual language is built around **calmness, clarity, and speed**.
It is a professional financial ledger, not a marketing website.

## 1. Color Palette
- **Surfaces & Backgrounds**:
  - Main App Background: `#f9fafb` (`bg-gray-50`)
  - Container / Card Surface: `#ffffff` (`bg-white`)
  - Subdued / Input Background: `#f9fafb` (`bg-gray-50`)
- **Borders & Dividers**:
  - Subtle Outer Borders: `#e5e7eb` (`border-gray-200`)
  - Active / Focus Borders: `#6366f1` (`border-indigo-500`)
  - Input Inactive Borders: `#d1d5db` (`border-gray-300`)
- **Primary Accent**:
  - Primary: `#4f46e5` (`bg-indigo-600`, `text-indigo-600`, `hover:bg-indigo-700`)
  - Focus Ring: `ring-2 ring-indigo-400`
- **Semantic Accents (Restrained)**:
  - **Credit / Inflow / Success**:
    - Text: `#047857` (`text-emerald-700`)
    - Badge: `#d1fae5` (`bg-emerald-100 text-emerald-800 border-emerald-300`)
    - Active Button: `#059669` (`bg-emerald-600 hover:bg-emerald-700 text-white`)
  - **Debit / Outflow / Danger**:
    - Text: `#b91c1c` (`text-red-700`)
    - Badge: `#fee2e2` (`bg-red-100 text-red-800 border-red-300`)
    - Active Button: `#dc2626` (`bg-red-600 hover:bg-red-700 text-white`)

## 2. Elevation & Shadows
- **No Heavy Shadows**: Eliminate `shadow-2xl`, `shadow-xl`, and `shadow-md` on regular cards.
- **Default Elevation**: `shadow-sm` on white cards with a crisp `border border-gray-200`.
- **Modals / Dropdowns**: Maximum elevation is `shadow-lg border border-gray-200`.

## 3. Radii & Geometry
- **Cards & Modals**: `rounded-xl` (12px)
- **Buttons & Inputs**: `rounded-lg` (8px)
- **Badges**: `rounded-md` (6px) or `rounded-full` for small indicators
- **Avoid**: `rounded-3xl` or exaggerated bubble styling.

## 4. Typography & Contrast
- **Headings**: `font-bold text-gray-900`
- **Body & Labels**: `text-sm font-semibold text-gray-700`
- **Monospace Financials**: `font-mono font-bold` (amounts, account numbers, reference codes)
- **Secondary & Captions**: `text-xs font-medium text-gray-500` (strictly maintaining WCAG AA 4.5:1 minimum contrast against white/gray surfaces; no washed out `text-gray-300` on light backgrounds).

## 5. Navigation & Layout
- Desktop Header: Clean flat dark neutral (`bg-gray-900 border-b border-gray-800`) with high-contrast light text (`text-gray-200`, `text-gray-300`, `text-white`).
- Strict Contrast Rule: Dark navigation background → light text; Light page background → dark text. Never use `text-white` on light surfaces or washed out text on dark surfaces.
- Note: Tailwind CSS v2.2.19 palette does not include `slate-*`, `zinc-*`, or `neutral-*`. Always use `gray-*` classes for neutral backgrounds and text to prevent transparent/invisible rendering artifacts.
- No glowing drop-shadows or multi-color gradients.
- Mobile friendly: strict viewport responsiveness without horizontal scrolling down to 320px width.
