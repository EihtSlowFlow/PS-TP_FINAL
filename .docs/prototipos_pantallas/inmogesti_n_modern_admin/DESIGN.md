---
name: InmoGestión Wood & Melamine
colors:
  surface: '#FFFDF9'
  surface-dim: '#E4D9C9'
  surface-bright: '#FFFDF9'
  surface-container-lowest: '#FFFFFF'
  surface-container-low: '#FBF6EE'
  surface-container: '#F8F1E7'
  surface-container-high: '#F4ECDF'
  surface-container-highest: '#EDE2D2'
  on-surface: '#2B1D14'
  on-surface-variant: '#6B5E52'
  inverse-surface: '#3B2A20'
  inverse-on-surface: '#F3E9DC'
  outline: '#8C7B6B'
  outline-variant: '#E4D9C9'
  surface-tint: '#7A4B2A'
  primary: '#7A4B2A'
  on-primary: '#FFFFFF'
  primary-container: '#F0E2CF'
  on-primary-container: '#5F3A20'
  inverse-primary: '#D9A566'
  secondary: '#3B2A20'
  on-secondary: '#F3E9DC'
  secondary-container: '#D9A566'
  on-secondary-container: '#2B1D14'
  tertiary: '#D9A566'
  on-tertiary: '#2B1D14'
  tertiary-container: '#F0E2CF'
  on-tertiary-container: '#5F3A20'
  error: '#B91C1C'
  on-error: '#FFFFFF'
  error-container: '#FEF2F2'
  on-error-container: '#B91C1C'
  primary-fixed: '#F0E2CF'
  primary-fixed-dim: '#D9A566'
  on-primary-fixed: '#2B1D14'
  on-primary-fixed-variant: '#5F3A20'
  secondary-fixed: '#E4D9C9'
  secondary-fixed-dim: '#D2C1AE'
  on-secondary-fixed: '#241811'
  on-secondary-fixed-variant: '#4A372B'
  tertiary-fixed: '#F0E2CF'
  tertiary-fixed-dim: '#D9A566'
  on-tertiary-fixed: '#2B1D14'
  on-tertiary-fixed-variant: '#5F3A20'
  background: '#F5EFE6'
  on-background: '#3A2E26'
  surface-variant: '#F8F1E7'
  primary-hover: '#5F3A20'
  primary-soft: '#F0E2CF'
  accent: '#D9A566'
  heading: '#2B1D14'
  text-muted: '#6B5E52'
  sidebar-bg: '#3B2A20'
  sidebar-text: '#F3E9DC'
  bg-app: '#F5EFE6'
  surface-muted: '#F8F1E7'
  row-hover: '#F4ECDF'
  border: '#E4D9C9'
  border-input: '#8C7B6B'
  edge: '#D3C3AE'
  success: '#10B981'
  success-soft: '#ECFDF5'
  success-text: '#065F46'
  danger: '#EF4444'
  danger-hover: '#991B1B'
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
    fontFamily: Fraunces
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-lg-mobile:
    fontFamily: Fraunces
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 26px
  headline-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
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
    fontWeight: '600'
    lineHeight: 18px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
  table-header:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 18px
    letterSpacing: 0.04em
  metric-display:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
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

> Source of truth for every value: `public/css/tokens.css`. The Spanish document `../DESIGN.md` describes the same identity in full and lists the CSS class vocabulary; this file is the Stitch-format copy of it and must stay aligned with both.

The design system embodies a warm, disciplined administrative aesthetic for real estate portfolio tracking, lease accounting and tenant-owner lifecycle management, with its own identity: **wood and melamine**, like melamine furniture in walnut and beige. It prioritizes clarity, financial accountability and operational efficiency over marketing flair.

- **Tone:** methodical, structured and trustworthy, but warm and approachable.
- **Visual style:** solid-colour melamine surfaces on a flat linen canvas. A café-brown sidebar carries a very subtle wood-grain texture. Content (tables, forms, cards) always sits on smooth surfaces, never on texture.
- **Melamine reference:** of the four melamine finishes (solid colour, wood-grain, material imitation, patterns) only **solid colour** (content) and a decorative **wood-grain** (sidebar and login brand panel) are used.
- **Role differentiation:** the layout adapts to three operational roles: Administrator/Staff, Property Owner and Tenant.

## Colors

- **Primary — walnut (`#7A4B2A`):** primary controls, links and focus. White on it is 7.32:1. Hover is `#5F3A20`. The soft variant is light oak `#F0E2CF` with `#5F3A20` text (7.80:1).
- **Accent — honey (`#D9A566`):** the active sidebar item and small brand details only. Text on it is `#2B1D14` (7.39:1).
- **Sidebar and brand panel — café (`#3B2A20`):** text `#F3E9DC` (11.38:1).
- **Canvas — linen (`#F5EFE6`):** flat, no gradient. Cards, tables, forms and the top bar are white melamine `#FFFDF9`, bounded by a 1px hairline `#E4D9C9` and a 2px darker bottom "edge" `#D3C3AE`, like the edge of a melamine board.
- **Neutrals:** `#2B1D14` for titles, `#3A2E26` for standard reading (11.49:1 on linen) and `#6B5E52` for secondary text (5.49:1 on linen). Input borders use `#8C7B6B` (4.01:1, minimum 3:1 for controls).
- **Semantic status architecture (unchanged):** business states keep their colours so they stay recognizable across properties, contracts, installments and payments, each with a soft background and a dark text tone:
  - **Success (`#10B981`, soft `#ECFDF5`, text `#065F46`):** active contracts, approved payments, available units, completed payouts.
  - **Warning (`#F59E0B`, soft `#FFFBEB`, text `#92400E`):** pending reviews, payments awaiting validation, maintenance.
  - **Danger (`#EF4444`, soft `#FEF2F2`, text `#B91C1C`):** overdue installments, rescinded leases, rejected payments, destructive prompts.
  - **Info (`#2563EB`, soft `#DBEAFE`, text `#1E40AF`):** leased units and installments under review.

## Typography

- **Inter** for data, tables, forms and menus. **Fraunces** (a serif, weights 600 and 700) only for page titles, the "InmoGestión" brand mark, the login title and empty-state titles.
- **Tabular figures:** financial figures and ledger values use tabular numerals so decimals align across rows.
- **Table headers:** uppercase, 12px weight 600, letter spacing `0.04em`.
- **Scale behavior:** page titles contract from 28px on desktop to 22px on handheld screens.

## Layout & Spacing

- **Desktop (1024px+):** fixed left navigation rail of 260px. The workspace is a centered container of up to 1200px with 32px outer margins and 24px gutters.
- **Top bar:** 64px high, breadcrumb on the left and the role badge on the right.
- **Mobile (≤900px):** the sidebar becomes an off-canvas drawer opened by a 44px hamburger button in the top bar, with a dark overlay. It closes with the overlay or Escape and keeps focus inside while open. Multi-column forms reflow into one column and primary buttons stretch to full width. Tables stay tables and scroll horizontally inside their card; the page itself never scrolls sideways.
- **Rhythm:** card padding defaults to 28px 32px; compact grouping follows a 4px base increment.

## Elevation & Depth

- **Surfaces:** white melamine cards with a hairline border and a 2px darker bottom edge, lifted by a wide soft warm shadow (`0 10px 40px -10px`, café at 14%).
- **Modals and drawers:** a stronger layered shadow over a café backdrop at 55% opacity.
- **Texture:** the wood grain is a lightweight inline SVG (two `feTurbulence` layers, no images or libraries), declared once in `tokens.css`, at 22% opacity, allowed only on the sidebar and the login brand panel, never behind content.
- **Print optimization:** in `@media print` textures, shadows and gradients are removed and the sidebar and top bar are hidden, so settlement receipts and reports stay clean.

## Shapes

- **Interactive controls (inputs, buttons):** 8px radius.
- **Content cards and data tables:** 12px radius.
- **Large containers:** 16px radius.
- **Badges and avatars:** fully rounded (`999px`).
- **Iconography:** 20px outlined stroke icons, always paired with text.

## Components

### Buttons
- **Primary:** solid walnut, white text, 8px radius, height 42px. Hover `#5F3A20`.
- **Secondary:** white surface, `#3A2E26` text, 1px border `#8C7B6B`.
- **Destructive:** solid `#B91C1C` with white text; hover `#991B1B`. Used for rescissions and deactivations.
- **Success:** solid `#065F46` with white text. Reserved for payment approval.
- **Warning:** solid `#F59E0B` with dark text `#2B1D14`. Used for edit and close actions.
- **Focus (all interactive elements):** a solid 3px outline with 2px offset (walnut on light surfaces, honey on the sidebar). The outline is never removed.
- **Mobile behavior:** primary action buttons stretch to 100% width below 600px, with a minimum 44px tap target.

### Form Inputs & Fields
- **Container:** white melamine surface, 1px border `#8C7B6B`, height 42px, 8px radius.
- **States:** focus shows a walnut border, a soft halo and the 3px outline. Error shifts the border to `#EF4444` with an error message under the field.
- **Layout rule:** dedicated full-page workflows for creating and editing entities; forms never open inside modals.

### Status Badges & Chips
- **Geometry:** full pill radius, small vertical padding.
- **Pairing rule:** never rely on colour alone. Every state shows an icon, the text and the colour. The icon is generated by CSS (check for success, exclamation for warning, cross for danger, "i" for info, dash for finished).
- **Values:**
  - *Available / In force / Paid / Approved / Transferred:* `#ECFDF5` background, `#065F46` label.
  - *Maintenance / Pending / Pending validation / Pending transfer:* `#FFFBEB` background, `#92400E` label.
  - *Rented / Under review:* `#DBEAFE` background, `#1E40AF` label.
  - *Finished:* neutral background `#F8F1E7`, `#6B5E52` label.
  - *Overdue / Rescinded / Rejected:* `#FEF2F2` background, `#B91C1C` label.

### Data Tables
- **Header:** background `#F8F1E7`, hairline bottom border, uppercase `table-header` text.
- **Rows:** hover `#F4ECDF`; tabular figures for amounts (`$ 420.000`).
- **Footer:** pinned totals row and pagination controls.

### Flash Alerts & Banners
- Placed above the content, with a 4px accent bar on the left in the semantic tone (success, danger, warning or info), a light tint fill and a lead icon.

### Metric / KPI Cards
- White rounded cards with a 2px bottom edge: a soft circular icon, an uppercase label, a large value and a short caption.

### Empty State
- Serif title, short text and an optional button inside a 2px dashed `#D3C3AE` border.
