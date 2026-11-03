# SanMal Web Design Foundation

> **Status:** design and implementation guideline v1.  
> **Scope:** internal PT SanMal attendance and employee administration web application.  
> **Audience:** AI coding agents, frontend/backend developers, and junior developers.  
> **Implementation direction:** apply and validate design decisions directly in the existing Laravel source code. Figma is not the active design deliverable or source of truth for the next phase.

## 1. Product context

SanMal is a calibration and training services company. Its internal application supports attendance, employee administration, work locations/projects, requests and approvals, attendance history, monitoring, and data exports.

The product must feel professional, trustworthy, precise, and operationally efficient. The interface is not a marketing website. Do not place company service catalogs, promotional content, or long corporate descriptions into operational dashboards unless a real workflow requires them.

### Core experience principle: Seamless-first

All roles share one visual language: brand colors, typography, spacing rhythm, control behavior, status semantics, and feedback patterns should feel coherent.

**Seamless does not mean identical layouts.** Employee and Admin workflows have different device priorities and information needs. Use the same design system with role-aware responsive composition.

## 2. Two responsive strategies

### 2.1 Employee — mobile-first

Employee pages are primarily used on phones. Design and verify the narrow viewport first, then enhance for larger screens.

Priorities:
1. Make the main task obvious and reachable, especially Clock In and Clock Out.
2. Support comfortable one-handed interaction and adequate touch targets.
3. Keep forms short, labels visible, and field order natural.
4. Present attendance status, camera/GPS permission, location/project selection, upload progress, and errors clearly.
5. Use a simple single-column flow by default.
6. Keep history and request lists scannable; use cards/list patterns where comparing many columns is not essential.
7. Avoid requiring precision taps, hover, or horizontal scrolling for primary actions.

Critical constraint: attendance camera, GPS, evidence, upload, Livewire lifecycle, and clock-in/out behavior are business-critical. A visual task must not change these contracts unless explicitly requested and separately tested.

### 2.2 Admin — desktop/tablet information-first, mobile-friendly

Admin pages are used for operational monitoring and data management. Desktop and tablet should prioritize information readability and efficient scanning; mobile remains functional for quick checks and essential actions.

Priorities:
1. Preserve readable table headers, row relationships, status, timestamps, and action context.
2. Use available desktop width for filters, comparison columns, and related information without excessive blank space.
3. On tablet, reflow panels and filters deliberately; do not simply scale a desktop page down.
4. On mobile, stack filters and actions, preserve key information, and choose a card/list alternative only when it retains the data relationships.
5. If a table must scroll horizontally, scope scrolling to the table region, keep its purpose clear, and avoid making the whole page scroll sideways.
6. Never hide important status, evidence, warnings, or required actions just to make the layout compact.

### 2.3 Breakpoints

Use the existing Tailwind responsive conventions and choose breakpoints based on content fit, not by habit. Initial design checkpoints:

| Viewport | Review target | Expected behavior |
|---|---:|---|
| Small mobile | 360–390 px | Employee primary flows; Admin stacked layout |
| Large mobile | 414–430 px | Wrapping, field widths, card density |
| Tablet portrait | 768 px | Admin filter and table strategy |
| Tablet landscape | 1024 px | Dense information and navigation |
| Desktop | 1280–1440 px | Admin tables, filters, page hierarchy |

These are QA checkpoints, not a mandate to introduce new custom breakpoints. Inspect actual layout constraints and existing Tailwind breakpoints before changing them.

## 3. Brand identity and color tokens

The original SanMal logo supplied for this project was sampled to identify representative brand colors. Logo rasterization, compression, and antialiasing can affect sampled pixels; use the following as the agreed working brand palette unless the company provides official vector/source brand specifications.

| Token | HEX | Intended use |
|---|---|---|
| `brand/blue` | `#0E3B6B` | Primary brand blue and light-theme primary actions |
| `brand/blue-dark` | `#0C315B` | Light-theme primary hover/pressed emphasis |
| `brand/blue-light` | `#B4C8DC` | Subtle blue surface and dark-theme focus/accent |
| `brand/yellow` | `#E7A007` | Brand accent and light-theme highlight |
| `brand/yellow-dark` | `#D9A72E` | Dark-theme primary action |
| `brand/yellow-dark-hover` | `#C49320` | Dark-theme primary hover |

The primary action is theme-aware: blue in light theme and yellow in dark theme. Yellow is also used as a restrained secondary-action accent in dark theme; it must not visually compete with the filled primary action. Always check foreground/background contrast.

### Surface and text palette

| Token | Light | Dark | Typical use |
|---|---|---|---|
| `surface/page` | `#E5EBF1` | `#0B1623` | Outer application shell and body background |
| `surface/primary` | `#F8FCFF` | `#122235` | Main content panel |
| `surface/card` | `#F8FCFF` | `#192B40` | Standard and linked cards; distinct from the dark main panel |\n| `surface/sidebar` | `#CAD6E2` | `#0B1623` | Sidebar navigation surface; dark theme stays integrated with the page shell |
| `surface/secondary` | `#EAF0F6` | `#122235` | Secondary grouping and light-theme hover surfaces |
| `surface/inset` | `#E3EBF3` | `#20354B` | Inset controls and recessed areas |
| `surface/elevated` | — | `#263D55` | Elevated dark-theme controls and linked-card hover state |
| `border` | `#D3DDEA` | `#344B63` | Dividers and control borders |
| `text/primary` | `#172033` | `#F1F5F9` | Main content |
| `text/secondary` | `#526579` | `#CBD5E1` | Supporting content |
| `text/muted` | `#64748B` | `#94A3B8` | Metadata and helper text |

### Semantic colors

| Token | HEX | Meaning |
|---|---|---|
| `semantic/success` | `#258640` | Successful/completed |
| `semantic/warning` | `#B2640A` | Warning/needs attention |
| `semantic/danger` | `#BD1F1F` | Error/rejected/destructive |
| `semantic/info` | `#145985` | Informational |

Use semantic colors consistently. A status color must not be the only way to communicate meaning; include readable text or an accessible label.

## 4. Typography

Inter is the proposed working typeface for the design foundation. **Do not assume it is already loaded or configured in the Laravel application.** Before implementation, inspect the existing font assets, CSS imports, and layout. Prefer the font already shipped by the application unless a deliberate font change is approved.

Typography principles:
- Use a clear hierarchy rather than many unrelated font sizes.
- Keep body text and table data comfortably readable.
- Use medium/semibold weights for labels and headings; avoid bolding every item.
- Use tabular numerals for timestamps, quantities, IDs, and numeric columns when supported by the existing font/CSS.
- Do not reduce essential text size merely to make a dense page fit.

Suggested hierarchy (adapt to the actual app typography and Tailwind scale):

| Role | Suggested size | Usage |
|---|---:|---|
| Page title | 24–32 px | Main page heading |
| Section heading | 18–22 px | Content section |
| Body | 14–16 px | Main content and descriptions |
| Label | 12–14 px | Form labels and metadata labels |
| Dense data | 13–14 px | Tables, timestamps, compact metadata |

## 5. Spacing, layout, and density

Use the existing Tailwind spacing scale and established project baseline. The values below are practical defaults, not a reason to rewrite every page.

| Element | Baseline | Notes |
|---|---|---|
| Admin list/monitoring container | `w-full max-w-7xl` | Use narrower width for reading/form pages when justified |
| Main vertical section rhythm | `gap-4` (16 px) | `gap-5` only when a real section distinction needs it |
| Filter panel | `p-3` (12 px) | Compact, controls usually `gap-2` or `gap-3` |
| Monitoring card/list | `p-3` or `p-4` | Enough space for clear grouping and touch interaction |
| Dense table cell | `px-3 py-2` | Preserve readable line height and target size |
| Label/value pair | `gap-1` | Keep related information visually connected |
| Form/detail surface | Context-dependent | Do not compact at the expense of entry or reading comfort |

Spacing scale reference: 4, 8, 12, 16, 20, 24, 32, 40, and 48 px. Prefer consistent utility classes over arbitrary one-off values. Do not introduce a new token layer unless it can be mapped cleanly to the current Tailwind/theme setup.

Density means removing decorative whitespace, not shrinking everything. Do not hide critical information or reduce hit targets to fit more content.

### Application shell and content surface

Keep the application shell visually continuous without making the sidebar and page indistinguishable: the `body` uses the page surface (`bg-sanmal-surface-page dark:bg-sanmal-surface-page-dark`), while the sidebar uses its dedicated surface (`bg-sanmal-surface-sidebar dark:bg-sanmal-surface-page-dark`). In light theme the sidebar is `#CAD6E2` and the page remains `#E5EBF1`; in dark theme both remain integrated with the dark page surface. The sidebar has no separating border.

The main content uses Flux's `<flux:main inset>` as a distinct primary-surface panel (`bg-sanmal-surface-primary dark:bg-sanmal-surface-primary-dark`) on the page shell. Standard and linked `x-ui.card` components keep the dedicated card surface (`bg-sanmal-surface-card dark:bg-sanmal-surface-card-dark`); in dark theme this is intentionally lighter than the main panel. Linked-card hover uses the elevated dark surface token. Keep the sidebar, optional header, and main as direct siblings. Flux owns the inset panel's rounded surface, desktop outer gutter, constrained viewport layout, and internal desktop scrolling; on smaller screens the panel becomes full width and the document scrolls normally. Do not recreate these behaviors with manual margin or overflow utilities.

Keep `p-0` on the main region to neutralize Flux's default inset padding because page content owns its internal spacing. Use `lg:ms-0` to remove the extra start offset beside the sidebar. Add `lg:mt-0` only when a desktop-visible Flux header is also present. Do not manually add `overflow-y-auto`, `overscroll-contain`, or duplicate desktop margin classes; these are already supplied by Flux inset.

Page content owns its own internal spacing. `x-ui.page` provides width, vertical flow, and section gap; do not add blanket horizontal padding to it or duplicate spacing already provided by a page-specific surface. When a page needs inner padding, apply it to the relevant page section/card, not the application shell.

Preserve the employee mobile header and drawer toggle. Do not add a fixed bottom navigation bar.

## 6. Button foundation

The source already contains `x-ui.button`, which wraps Flux UI 2.13's button component. Reuse it before adding another button implementation. Its current Blade API includes:
- `variant` (default `primary`; supported values include `primary`, `outline`, `ghost`, `subtle`, `filled`, and `danger`)
- `color` (optional; primary defaults to SanMal blue, explicit semantic colors remain available)
- `size` (default `sm`; supported sizes are `xs`, `sm`, and `base`)
- `type`
- `href`
- `icon` and `iconTrailing`
- `disabled`
- `square`
- forwarded attributes (including Livewire/Alpine attributes)

Inspect the component and Flux version in the target branch before changing its API or assuming exact rendered dimensions.

### 6.1 Variants and hierarchy

| Variant | Use |
|---|---|
| Primary | Flux `primary` with SanMal brand blue; one dominant action such as Save, Add, Apply, or final confirmation |
| Secondary | Flux `outline` with neutral styling; supporting actions such as Edit, Detail, Back, Reset, Filter, and Export |
| Ghost | Flux `ghost`; minor utility action or icon control that does not need a filled surface |
| Danger | Flux `danger`; destructive or high-risk actions such as Delete, Remove, or Cancel when the action is destructive |

Do not make every button primary. Destructive actions must retain the workflow's existing confirmation; styling does not replace confirmation.

### 6.2 Compact sizing

The agreed design direction is **compact by default, comfortable when needed**. Previous Figma button padding looked too wide for operational screens.

| Design size | Target height | Horizontal padding target | Use |
|---|---:|---:|---|
| Small | 32 px | 12 px | Dense table actions and compact toolbar; Flux `sm` |
| Medium | 40 px | 16 px | Standard Admin action; Flux `base` |
| Large touch action | 48 px minimum | Context-dependent | Employee's most important mobile action; use a deliberate minimum-height utility only where the workflow needs the larger target |

Flux 2.13 button sizes are `xs`, `sm`, and `base`; do not pass unsupported values such as `md` or `lg`. The shared component defaults to `sm` for compact operational density. Use `base` for standard actions and add a 48 px minimum touch height only to the most important mobile action after checking its rendered alignment. Icon-only controls need an adequate hit area even if the visible icon is small.

### 6.3 Theme contrast and button states

- Light theme primary: SanMal brand blue (`#0E3B6B`) with white text; hover uses brand dark blue (`#0C315B`).
- Dark theme primary: warm SanMal yellow (`#D9A72E`) with dark text (`#172033`); hover uses `#C49320`.
- Light theme secondary: neutral primary surface, subtle border, and brand-blue text.
- Dark theme secondary: muted warm-yellow surface (`#49351F`), gold border (`#80602B`), and soft-yellow text (`#F2BB65`). Hover increases surface/border contrast without becoming a second filled primary action.
- Destructive remains semantic red in both themes.
- Theme-specific styles must preserve visible focus indication and disabled-state affordance.

Check and preserve:
- Default
- Hover (where a pointer exists)
- Pressed/active
- Keyboard focus-visible
- Disabled
- Loading/busy
- Icon-only and icon + label

Do not invent new loading behavior if the calling Livewire action already provides a supported loading state. Keep button text, icon meaning, route, method, authorization, and action semantics unchanged.

## 7. Form controls

Existing shared components:
- `x-ui.field`
- `x-ui.input`
- `x-ui.select`
- `x-ui.textarea`
- `x-ui.checkbox`

These wrap Flux UI and provide label/description/error integration. Inspect their current props and preserve the existing API. Do not create duplicate controls before checking these components and the specific page's contract.

### Form rules

1. A visible label is required for every field; placeholder is supplementary, never a replacement.
2. Use helper/description text only when it clarifies format, consequences, or expected input.
3. Keep validation errors close to their field and preserve the correct field name.
4. Preserve `name`, `id`, `required`, old/model values, selected state, error bag, `wire:model`, Alpine bindings, and form method.
5. Group related fields; on mobile default to one column. Use two columns on Admin/tablet/desktop only when fields are logically related and labels remain readable.
6. Keep controls wide enough for their content; prevent long values from causing overflow.
7. Preserve keyboard focus, disabled, readonly, invalid, and loading behavior.
8. Use native or existing Flux date/select controls where they already meet the workflow.

### Role-aware sizing

- Employee mobile: target approximately 48 px for primary touch controls where appropriate; keep form layout single-column and avoid cramped adjacent actions.
- Admin: 40 px controls are acceptable for dense filter bars and operational forms when rendered comfortably; do not force all forms into compact mode.
- These are visual targets. Verify actual Flux rendering before modifying global component defaults.

## 8. Badge, status, card, feedback, table, and navigation

### Badge and status
- Use `x-ui.badge` for generic semantic status only when its visual/semantic contract fits.
- Use domain adapters such as `x-admin.request-status-badge` where enum-to-label/color mapping belongs.
- Keep distinct domain dimensions distinct (for example, overtime approval status versus operational state).
- Never encode a business state only by color.

### Card and surface
- Reuse `x-ui.card` when the content is genuinely a card/surface.
- The shared card uses a shadow-only treatment without a border. Light cards use `sanmal-surface-primary`; dark cards use `sanmal-surface-primary-dark`. Linked cards may use the secondary surface on hover.
- Both linked and non-linked card variants must use the same theme surface tokens; do not leave one variant on generic white/zinc surfaces.
- Do not wrap every section in a card. Avoid card-in-card.
- Use semantic `article`, `section`, list, or table markup when it better represents the content.

### Feedback
- Reuse `x-ui.flash-message`, `x-admin.flash-message`, and existing empty-state components as appropriate.
- Feedback must explain what happened and, when useful, what the user can do next.
- Keep validation, permission errors, loading, empty, and success states distinguishable.

### Table and data display
Choose based on data relationships:
- Native HTML table for chronological/comparative records that need column headers.
- Flux table for Livewire sorting/pagination or existing interactive contracts.
- Review list for queues where opening one record is the primary action.
- Card/article for a concise record summary.
- Mobile card/list is appropriate only when it preserves meaning and scan order; do not convert every table automatically.
- For unavoidable horizontal scrolling, scope it to the table container and preserve accessible headers.

### Navigation

- Use Flux sidebar and header primitives for the shared application shell; do not build a parallel navigation system.
- Apply `<flux:main inset>` for the rounded primary-surface content panel. Keep sidebar, header, and main as direct layout siblings; use `lg:ms-0` on the main region to avoid double offsets (add `lg:mt-0` only if a desktop-visible header is present). Keep the page surface visible around the panel.
- Treat the inset main as the full-width surface. Let `x-ui.page` own the page-specific content width; do not add Flux `container` at the main level as well as another page max-width unless the double constraint is intentional. Use narrower page widths for forms/details and wider widths for operational data pages.
- The inset main already establishes the shell's content spacing. Avoid adding another padding utility to the root `x-ui.page` (for example `p-6` or `px-4 py-4 sm:px-6 sm:py-6`); use spacing inside the page's own sections/cards instead. This prevents the inset gutter and page padding from accumulating.
- Employee navigation is one ungrouped list: Beranda, Absensi, Riwayat, Permohonan, and Lembur. On phones, use the top header with a drawer toggle; do not use a fixed bottom navigation bar.
- Admin Dashboard is a standalone first item, not a section/group heading. It remains the default landing page and represents company-wide monitoring.
- Admin/Superadmin accounts with a linked employee profile also receive an `Aktivitas Saya` group, beginning with `Dashboard Pribadi`, followed by Absensi, Riwayat, Permohonan Saya, and Lembur Saya. The personal dashboard reuses employee-scoped aggregation and must never show company-wide data.
- Follow the Admin navigation order: standalone Dashboard; Aktivitas Saya (only when an employee profile exists); Operasional; Persetujuan; Data Master; Sistem. Keep concise labels and do not add a group heading around the single Dashboard item.
- Group headings must not disappear and collapse the vertical rhythm when the desktop sidebar is collapsed. Use `x-ui.sidebar-section`: the expanded state shows the heading, while the collapsed state replaces it with a subtle separator in the same fixed-height row. Employee navigation remains ungrouped and does not render separators.
- Sidebar navigation icons use a consistent 20px square size and do not change size between expanded and collapsed states. Preserve the icon's aspect ratio, alignment, and non-shrinking behavior.
- Responsive behavior follows the available width: drawer on phone, collapsible sidebar where appropriate on tablet, and persistent sidebar on desktop. Use viewport-relative `dvh` sizing for the shell. Do not create separate role-specific navigation implementations.

### Single branch and single PR

- Work only on branch `docs/ui-v1-scope-and-pr-workflow`.
- Use only [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27), targeting `main`.
- Do not create another branch or PR for a component, page, milestone, test, or documentation task.
- Do not merge PR #27 early. Merge once the complete V1 UI/UX scope is finished, reviewed, and verified.
- Commit focused changes to the same branch and keep the PR description/checklist current.

### Implementation references

- Start with `docs/ui/DEVELOPMENT-HANDOFF.md`.
- Use `docs/ui/PAGE-INVENTORY.md` for page scope and audit references.
- Use `docs/ui/COMPONENT-CATALOG.md` and `docs/ui/REUSABLE-BLADE-COMPONENTS.md` for existing component contracts.
- Track progress in `docs/ui/progress/IMPLEMENTATION-STATUS.md` and evidence in `docs/ui/progress/VISUAL-QA-LOG.md`.

The actual source code, current tests, and rendered browser output remain the implementation truth. If a documented API or page status conflicts with source, inspect and correct the documentation rather than blindly following stale details.
