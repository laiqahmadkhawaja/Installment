# UI Review — Installment Management Software (اقساط پرو)

**Audited:** 2026-10-25  
**Baseline:** Abstract 6-Pillar Standards (Enterprise Installment & Credit POS System)  
**Target Environment:** Core PHP 8.2, Bootstrap 5, FontAwesome 6, Chart.js, Urdu RTL / English LTR  
**Screenshots / Static Analysis:** Code-derived inspection of 56 templates across 12 modules  

---

## Pillar Scores

| Pillar | Score | Key Finding |
|---|---|---|
| **1. Copywriting** | **3/4** | Rich domain-specific vocabulary and dual English/Urdu dictionaries; some sub-pages have untranslated English labels. |
| **2. Visuals** | **3.5/4** | High-density financial dashboard, distinct status pill badges, and authentic 80mm thermal receipt layouts. |
| **3. Color** | **3.5/4** | Disciplined 60/30/10 distribution with semantic financial color coding (Red: Receivables, Green: Cash Inflow, Blue: Financed). Minor dark mode badge contrast fixes needed. |
| **4. Typography** | **3.5/4** | Excellent typography pairing Plus Jakarta Sans and Noto Nastaliq Urdu; consistent PKR currency formatting. |
| **5. Spacing** | **4/4** | Consistent Bootstrap 5 rhythm (`g-3`, `g-4`, `p-3`, `p-4`), mobile responsive off-canvas sidebar drawer, zero arbitrary pixel hacks. |
| **6. Experience Design** | **3.5/4** | Fast counter workflow with auto-calculators and smart allocation. Cashier collection can be further enhanced with quick denomination chips. |

**Overall Score: 21 / 24 (Grade: A - Production Ready)**

---

## Top 3 Priority Recommendations

1. **Quick Cashier Denomination Chips on Collection Screen**  
   - **Impact**: Speeds up cashier counter throughput during high-traffic installment collection hours.
   - **Fix**: Add 1-click quick amount buttons on `collection/receive.php` (`[Exact Installment]`, `[Full Balance]`, `[+Rs. 1,000]`, `[+Rs. 5,000]`).

2. **Dark Mode Badge Contrast Polish**  
   - **Impact**: Elements using `bg-light text-dark` appear as harsh white blocks when dark mode is enabled.
   - **Fix**: Replace `bg-light text-dark border` with Bootstrap 5 semantic `bg-body-secondary text-body border` in table lists.

3. **Urdu Nastaliq Line-Height Spacing Adjustment**  
   - **Impact**: In RTL mode, Noto Nastaliq Urdu diacritics and dots can touch neighboring table row borders if line-height is default.
   - **Fix**: Add `line-height: 2.0` in `assets/css/rtl.css` for Urdu headings and table cells.

---

## Detailed Pillar Breakdown

### Pillar 1: Copywriting (Score: 3/4)
- **Strengths**:
  - `config/lang.php` contains comprehensive dual dictionaries with authentic Pakistani installment terminology:
    - *قسطوں کے منصوبے* (Installment Plans)
    - *واجب الادا اقساط* (Overdue Installments)
    - *بیع بالتقسیط اقرار نامہ* (Installment Sale Legal Contract)
    - *بنام / جمع* (Debit / Credit)
  - Clear empty states in tables: *"No overdue installments right now!"*, *"No customers found matching your criteria."*
- **Areas for Improvement**:
  - Some inner form section labels in `customers/create.php` and `contracts/create.php` use hardcoded English strings instead of dictionary keys.
  - Adding bilingual subtitles under every section header improves accessibility for non-English speaking counter staff.

### Pillar 2: Visuals (Score: 3.5/4)
- **Strengths**:
  - Distinctive KPI card hierarchy with soft background tint icons (`bg-primary bg-opacity-10 text-primary`).
  - Clear status pills (`.status-paid`, `.status-overdue`, `.status-partial`, `.status-draft`) with high-contrast text.
  - Authentic 80mm thermal receipt layout with monospace font, dashed cut lines, and scannable QR verification code.
  - Interactive Chart.js graphs visually separating Sales volume from physical Cash Inflows.
- **Areas for Improvement**:
  - In `customers/index.php`, avatars use single-letter initial badges; adding an `<img>` tag preview when a customer photo or CNIC scan is present will increase visual richness.
  - Add explicit `aria-label` attributes to icon-only action buttons.

### Pillar 3: Color Palette (Score: 3.5/4)
- **Strengths**:
  - 60/30/10 Rule followed:
    - 60% Dominant: Clean off-white background (`#f8fafc`) and crisp card white (`#ffffff`).
    - 30% Structural: Dark slate sidebar (`#0f172a`) and slate table typography (`#1e293b`).
    - 10% Intentional Accents: Royal Blue (`#1e3a8a`), Emerald (`#10b981`), Crimson (`#dc2626`).
  - Clear financial visual cues:
    - Green = Physical Cash Collected, Settled Installments.
    - Red = Market Receivables, Overdue Amounts, Severe Default.
    - Blue = Financed Volume, Active Contracts.
- **Areas for Improvement**:
  - Fix `bg-light text-dark` badges in dark mode by converting to `bg-body-secondary`.

### Pillar 4: Typography (Score: 3.5/4)
- **Strengths**:
  - Clean font stack: **Plus Jakarta Sans** for English/numbers and **Noto Nastaliq Urdu** for Urdu script.
  - Consistent currency formatting via `format_currency()` displaying `Rs. X,XXX.XX` (or `روپے X,XXX.XX`).
  - Font weights are disciplined (`400` body, `600` table headers, `700` KPI values).
- **Areas for Improvement**:
  - Increase Urdu text `line-height` slightly in `assets/css/rtl.css` to accommodate tall Nastaliq calligraphic ascenders and descenders.

### Pillar 5: Spacing & Rhythm (Score: 4/4)
- **Strengths**:
  - 100% adherence to Bootstrap 5 grid and spacing units (`mb-4`, `p-3`, `p-4`, `g-3`, `g-4`).
  - Off-canvas sidebar on viewports `< 992px` with a semi-transparent touch backdrop preventing accidental background clicks.
  - Form field grouping into structured cards with balanced 2-column and 3-column responsive breakpoints.
  - Clean table padding (`12px 14px`) providing breathing room for dense financial figures.

### Pillar 6: Experience Design & Flow (Score: 3.5/4)
- **Strengths**:
  - Counter cashier efficiency: Selecting a contract instantly populates current balance and monthly installment.
  - Smart oldest-due-first algorithm automatically calculates and settles installments without manual math.
  - Critical safety nets: Reversing a payment prompts a modal requiring a mandatory audit note before execution.
  - Direct WhatsApp trigger pre-populates Urdu overdue reminders and receipt notifications.
  - Clean thermal and A4 print media queries (`@media print`) hiding buttons and browser headers.
- **Areas for Improvement**:
  - Introduce quick amount chips (`[Rs. 1,000]`, `[Rs. 5,000]`, `[Full Installment]`) on payment forms to reduce typing.
