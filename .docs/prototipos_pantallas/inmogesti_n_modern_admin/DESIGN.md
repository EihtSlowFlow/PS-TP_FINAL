---
name: InmoGestión Modern Admin
colors:
  surface: '#FFFFFF'
  surface-dim: '#d0dbed'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dee9fc'
  surface-container-highest: '#d9e3f6'
  on-surface: '#121c2a'
  on-surface-variant: '#464555'
  inverse-surface: '#27313f'
  inverse-on-surface: '#eaf1ff'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4d44e3'
  primary: '#3525cd'
  on-primary: '#ffffff'
  primary-container: '#4f46e5'
  on-primary-container: '#dad7ff'
  inverse-primary: '#c3c0ff'
  secondary: '#5c5f62'
  on-secondary: '#ffffff'
  secondary-container: '#dee0e4'
  on-secondary-container: '#606366'
  tertiary: '#7e3000'
  on-tertiary: '#ffffff'
  tertiary-container: '#a44100'
  on-tertiary-container: '#ffd2be'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3323cc'
  secondary-fixed: '#e0e2e6'
  secondary-fixed-dim: '#c4c7ca'
  on-secondary-fixed: '#191c1f'
  on-secondary-fixed-variant: '#44474a'
  tertiary-fixed: '#ffdbcc'
  tertiary-fixed-dim: '#ffb695'
  on-tertiary-fixed: '#351000'
  on-tertiary-fixed-variant: '#7b2f00'
  background: '#f8f9ff'
  on-background: '#121c2a'
  surface-variant: '#d9e3f6'
  primary-hover: '#4338CA'
  primary-soft: '#E0E7FF'
  heading: '#111827'
  text-muted: '#6B7280'
  background-start: '#F0F4F8'
  background-end: '#D9E2EC'
  surface-muted: '#F9FAFB'
  row-hover: '#F3F4F6'
  border: '#E5E7EB'
  border-input: '#D1D5DB'
  success: '#10B981'
  success-soft: '#ECFDF5'
  success-text: '#065F46'
  danger: '#EF4444'
  danger-hover: '#DC2626'
  danger-soft: '#FEF2F2'
  danger-text: '#B91C1C'
  warning: '#F59E0B'
  warning-soft: '#FFFBEB'
  warning-text: '#92400E'
  info: '#2563EB'
  info-soft: '#DBEAFE'
  info-text: '#1E40AF'
typography:
  headline-lg:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '500'
    lineHeight: 26px
  headline-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '500'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
  table-header:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
    letterSpacing: 0.05em
  metric-display:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 38px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

The design system embodies a modern, disciplined, corporate administrative aesthetic tailored specifically for real estate portfolio tracking, lease accounting, and tenant-owner lifecycle management. It prioritizes clarity, financial accountability, and operational efficiency over marketing flair.

The UI leverages crisp, high-density dashboard layouts balanced by calm, light surfaces. The dominant personality is trustworthy, precise, and understated:
- **Tone:** Methodical, structured, and institutional yet fresh and approachable.
- **Visual Style:** Corporate Modern with refined atmospheric canvas depth—pairing a cool-slate gradient backdrop (`#F0F4F8` to `#D9E2EC`) with crisp white card containers, restrained 1px structural dividers, and purposeful semantic accents.
- **Role Differentiation:** Clear spatial organization adapts gracefully across three discrete operational roles: Administrator/Staff, Property Owner, and Tenant.

## Colors

The palette establishes an authoritative visual hierarchy designed for heavy transactional interfaces:

- **Primary (`#4F46E5`):** Reserved for primary interactive controls, main focus rings, active navigation indicators, and navigational badges.
- **Canvas Backdrop:** A linear vertical gradient running from `#F0F4F8` at the top to `#D9E2EC` at the bottom, which provides visual grounding while setting off pure `#FFFFFF` data containers and cards.
- **Neutrals:** Text hierarchy relies on stark contrast: `#111827` for page titles and high-priority metrics, `#1F2937` for standard reading and data entry, and `#6B7280` for secondary metadata and table headers. Structural hairline borders utilize `#E5E7EB`.
- **Semantic Status Architecture:** Business states are paired with low-saturation backgrounds and high-contrast foreground text to preserve WCAG AA legibility:
  - **Success (`#10B981`, soft `#ECFDF5`, text `#065F46`):** Active contracts, validated payments, available units, completed payouts.
  - **Warning (`#F59E0B`, soft `#FFFBEB`, text `#92400E`):** Pending reviews, payment verifications awaiting admin action, scheduled maintenance.
  - **Danger (`#EF4444`, soft `#FEF2F2`, text `#B91C1C`):** Overdue installments, terminated leases, rejected payment slips, destructive prompts.
  - **Info (`#2563EB`, soft `#DBEAFE`, text `#1E40AF`):** Leased units, active audit logs, in-review balances.

## Typography

The typography uses Inter across all levels to maintain crisp geometry and high legibility at micro sizes.

- **Tabular Figures:** All financial figures, contract amounts, and ledger values must enforce `font-feature-settings: "tnum" 1` to guarantee vertical decimal alignment across multi-row data tables.
- **Table Headers:** Rendered uppercase with letter spacing of `0.05em` at 13px weight 500 to cleanly separate structural metadata from cell contents.
- **Scale Behavior:** Page titles gracefully contract from 28px on desktop to 22px on handheld screens, preventing awkward wrapping on localized strings and Argentine Peso currency statements.

## Layout & Spacing

The layout is structured around an administrative fixed-fluid hybrid grid:

- **Desktop Structure (1024px+):** Fixed left navigation rail at 260px width. The main workspace spans a max-width container of 1200px centered in the remaining viewport, flanked by 32px (`space-xl`) outer margins and 24px (`space-lg`) gutters across a 12-column grid.
- **Top Navigation Bar:** Persistent 64px height carrying dynamic route breadcrumbs on the left, and operational items (notification bell with red unread counter badge, profile trigger) pinned right.
- **Mobile Adaptation (<768px):** The left sidebar collapses into an off-canvas drawer controlled via a header hamburger toggle with a high-blur neutral overlay. Workspaces drop to 16px (`space-md`) outer margins. Multi-column forms reflow into single columns, and table rows convert into stacked card tiles.
- **Rhythm:** Standard card and container internal padding defaults to 24px (`space-lg`). Compact component grouping (inputs, badges, table cells) adheres strictly to the 4px base increment (`xs: 4px`, `sm: 8px`, `md: 16px`).

## Elevation & Depth

Visual hierarchy uses a refined blend of subtle surface separation and low-opacity ambient shadows:

- **Baseline Surfaces:** Canvas background carries the slate gradient (`#F0F4F8` to `#D9E2EC`). Workspaces, table wrappers, and sidebar panels sit on pristine `#FFFFFF` surfaces bounded by a crisp 1px hairline border (`#E5E7EB`).
- **Cards & Data Tables:** Elevated with a wide, soft ambient shadow: `box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08)`. This establishes clear separation without harsh lines or stark drops.
- **Modals & Drawers:** Elevated using an intentional layering shadow: `0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)`, underpinned by a semi-opaque neutral backdrop (`rgba(17, 24, 39, 0.5)`).
- **Print Optimization:** In print media stylesheets (`@media print`), all box shadows, background gradients, and decorative borders are stripped to preserve ink density and ensure clean hard copies of settlement receipts and lease contracts.

## Shapes

The interface embraces a balanced roundedness model to soften administrative density:

- **Interactive Controls (Inputs, Buttons):** Rounded at 8px (`rounded-sm`), delivering a disciplined, click-ready form appearance.
- **Content Cards & Data Tables:** Enclosed with 12px corners (`rounded-md`), softening the containment of complex data matrices.
- **Major Modals & Slide-overs:** Framed with 16px radius (`rounded-lg`) along exposed corners.
- **Badges & Avatars:** Fully rounded pill geometries (`999px`) to immediately distinguish categorical meta-tags from square interactive controls.
- **Iconography:** 20px outlined stroke icons paired consistently with explicit textual labels.

## Components

### Buttons
- **Primary:** Solid indigo (`#4F46E5`), text white, 8px border-radius, horizontal padding 24px, height 42px. Hover transitions to `#4338CA`. Active focus applies an outline offset with a 3px ring at 15% opacity (`rgba(79, 70, 229, 0.15)`).
- **Secondary:** Surface `#E5E7EB`, text `#374151`, hover `#D1D5DB`.
- **Destructive:** Solid `#EF4444`, text white; hover `#DC2626`. Used for contract rescissions and account deactivations.
- **Success:** Solid `#10B981`, text white. Strictly reserved for payment approval and disbursement execution.
- **Mobile Behavior:** Primary action buttons stretch to 100% container width on viewports under 480px, maintaining minimum 44px tap targets.

### Form Inputs & Fields
- **Container:** Pure `#FFFFFF` surface, 1px border `#D1D5DB`, height 44px, padding 10px 16px, 8px border radius.
- **States:** Focus replaces border with `#4F46E5` and a 3px matching halo. Error shifts border to `#EF4444` accompanied by a micro validation error message with icon directly below the field.
- **Layout Rule:** Dedicated full-page workflows for entity creation and edits (Inmuebles, Contratos, Usuarios); form flows never open inside cramped popups or nested modals.

### Status Badges & Chips
- **Geometry:** Height 24px, 4px vertical / 12px horizontal padding, full pill radius (`999px`).
- **Pairing Rule:** Never rely on color alone. Always display an accompanying icon (or status dot) alongside explicit text in Argentine Spanish.
- **Values:**
  - *Disponible / Vigente / Pagada / Aprobado / Transferido:* `#ECFDF5` background, `#065F46` label.
  - *En Mantenimiento / Pendiente / En Revisión / Por Transferir:* `#FFFBEB` background, `#92400E` label.
  - *Alquilado:* `#DBEAFE` background, `#1E40AF` label.
  - *Vencida / Rescindido / Rechazado:* `#FEF2F2` background, `#B91C1C` label.

### Data Tables
- **Header:** Background `#F9FAFB`, border-bottom 1px `#E5E7EB`, typography `table-header` with uppercase text.
- **Rows:** Alternating clean rows with responsive hover highlighting (`#F3F4F6`), 16px vertical padding. Monospaced or tabular figure rendering for amounts (`$ 420.000`).
- **Footer:** Pinned calculation totals row with bold metrics and lower pagination controls (records count, page selector).

### Flash Alerts & Banners
- Positioned directly above content containers, incorporating a 4px thick left accent bar matching the semantic tone (indigo, emerald, amber, or red), light tint fill, lead icon, and a dismissal button on the far right.

### Metric / KPI Cards
- White 12px rounded cards containing an upper icon encapsulated in a 40px soft-tinted circular badge, an uppercase 13px label, a large display value (`metric-display`), and a trend or contextual comparative caption underneath.