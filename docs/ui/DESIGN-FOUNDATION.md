# SanMal Web Design Foundation

> **Status:** design and implementation guideline v1, reconciled with the source of `feature/ui-production-readiness` on 8 November 2026.  
> **Scope:** internal PT SanMal attendance and employee administration web application.  
> **Audience:** AI coding agents, frontend/backend developers, and junior developers.  
> **Implementation direction:** apply and validate design decisions directly in the existing Laravel source code. Figma is not the active design deliverable or source of truth for the next phase.  
> **Evidence rule:** a statement here is either a decision, a fact read from source, or an observation from a browser. Observations are recorded in [Visual QA Log](progress/VISUAL-QA-LOG.md). Nothing in this document claims that tests, build, or browser checks have passed unless the log says so.

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

The sidebar takes about 256 px from `lg` upward, so the content area at 1024 px is only about 768 px wide. Choose column counts from the **content** width, not the viewport: three-column card grids start at `xl`, not `lg`.

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

The primary action is theme-aware: blue in light theme and yellow in dark theme. Yellow is reserved for the dark-theme primary action and one brand accent line on authentication pages; it is not used for secondary actions, selected states, or calendar markers (D2). Always check foreground/background contrast.

### Live surface, text, and border tokens

These values are defined in `resources/css/app.css` and were read from that file on the branch. If this table and the stylesheet ever differ, the stylesheet wins: update this document. Contrast ratios in earlier handoffs were calculated from hex values (WCAG 2.x), not measured in a browser.

| Token | Light | Dark | Typical use |
|---|---|---|---|
| `surface/page` | `#EEF2F7` | `#0C1520` | Application main background |
| `surface/card` | `#FFFFFF` | `#131F2E` | Panels, list items, modals |
| `surface/primary` | `#F8FCFF` | `#131F2E` | Mobile header, banner strip; same as card in dark |
| `surface/sidebar` | `#CAD6E2` | `#0A111B` (token `surface-sidebar-dark`) | Sidebar. The dark token exists but the sidebar still uses the page surface until F2 (D3) |
| `surface/inset`, `surface/secondary` | `#E3EBF3`, `#EAF0F6` | `#1A293B` | Inputs, chips, table headers, row hover |
| `surface/elevated` | — | `#223449` | Dropdowns and popovers (dark), linked-card hover (dark). Light theme uses `surface/card` plus a border and shadow. Never a modal background |
| `border` | `#D3DDEA` | `#2B3D52` | Panel and divider lines |
| `control-border` | `#6F8399` | `#5A7391` | Edges of input, select, and textarea (at least 3:1) |
| `text/primary` | `#172033` | `#E6EDF5` | Main content |
| `text/secondary` | `#526579` | `#B3C1D1` | Supporting content |
| `text/muted` | `#5B6B80` | `#8B9DB1` | Metadata and helper text (at least 4.5:1 on page, card, and secondary) |
| `text/sidebar` | `#334155` | `#B3C1D1` | Sidebar item text |
| `text/sidebar-muted` | `#475569` | `#8B9DB1` | Sidebar group headings |
| `accent` | `#0E3B6B` | `#8AB4E8` | Links, active sidebar item, focus ring, unread and selected markers, active pill |
| `success-text` / `warning-text` / `danger-text` / `info-text` | `#1D6B32` / `#8A4B08` / `#991B1B` / `#10496D` | `#7FD6A0` / `#F0C065` / `#F59A9A` / `#8EC5F0` | Colored text outside badges |

Dark badges: success `#12301F` on `#7FD6A0`, warning `#33280F` on `#F0C065`, danger `#3A1A1E` on `#F59A9A`, info `#142C44` on `#8EC5F0`, and neutral (`sanmal-badge-neutral`) `#1F2D3D` on `#B3C1D1`. Dark danger button: `#DC2626` with white text.

Text tokens are available as Tailwind classes (`text-sanmal-text-primary`, `text-sanmal-text-muted`, `text-sanmal-warning-text`, and so on) through `@theme inline` (D7). Prefer these classes over `text-[var(--sanmal-text-muted)]` in new code.

### Semantic colors

| Token | HEX | Meaning |
|---|---|---|
| `semantic/success` | `#258640` | Successful/completed |
| `semantic/warning` | `#B2640A` | Warning/needs attention |
| `semantic/danger` | `#BD1F1F` | Error/rejected/destructive |
| `semantic/info` | `#145985` | Informational |

These raw values are for fills, dots, and rails. For text, use the matching `*-text` token (the raw values fail 4.5:1 as small text). A status color must not be the only way to communicate meaning; include readable text or an accessible label.

**Unread, new, and selected states use the `accent` token**, not a semantic color and not a Tailwind palette color (`sky`, `purple`, `amber-*`, `red-*`, `emerald-*`). Semantic colors are reserved for real status.

### Surface usage rules

- Use `x-ui.card` for standard content cards so light/dark backgrounds, text color, radius, and border remain centralized.
- Cards and panels carry a 1px border (`border-sanmal-border` / `border-sanmal-border-dark`) in both themes. Shadow is only for floating elements: modal, dropdown, popover, toast. A linked card changes its surface and its border color to the accent on hover, with no extra shadow. Do not nest a card inside a card (D1, applied in F1; `UiCardComponentTest` asserts it).
- Borders also apply to controls, dividers, tables, modal frames, and inset/secondary groups when they communicate structure.
- Use the approved `surface/secondary` or `surface/inset` tokens for nested groups, chips, and recessed content; always provide the matching dark token.
- Do not introduce one-off `white`, `zinc`, or `gray` backgrounds/borders inside standardized cards.
- Use the shared text tokens for neutral text hierarchy. Reserve semantic colors for actual status and feedback.
- A component may have a different layout by role or page, but its surface palette must remain consistent with the shared design tokens.

## 4. Typography

The application typeface is Instrument Sans, configured in `vite.config.js` through `laravel-vite-plugin/fonts` (`bunny('Instrument Sans', { weights: [400, 500, 600] })`). The earlier Inter proposal is withdrawn. Use only the loaded weights (400, 500, 600); do not use `font-bold`. The 8 November local build emitted the font files under `public/build/assets`; whether the deployed page makes requests to other domains is still to be confirmed (P0-4).

Typography principles:
- Use a clear hierarchy rather than many unrelated font sizes.
- Keep body text and table data comfortably readable.
- Use medium/semibold weights for labels and headings; avoid bolding every item.
- Use tabular numerals (`tabular-nums`) for timestamps, quantities, IDs, and numeric columns.
- Do not reduce essential text size merely to make a dense page fit. **The floor is 12 px (`text-xs`).** Do not use arbitrary sizes such as `text-[10px]` or `text-[11px]`.

Suggested hierarchy (adapt to the actual app typography and Tailwind scale):

| Role | Suggested size | Usage |
|---|---:|---|
| Page title | 24–32 px | Main page heading |
| Section heading | 18–22 px | Content section |
| Body | 14–16 px | Main content and descriptions |
| Label | 12–14 px | Form labels and metadata labels |
| Dense data | 13–14 px | Tables, timestamps, compact metadata |

### Radius, date, and time format

Amended by D5 and D6; applied during F1 to F5.

| Element | Radius |
|---|---|
| Panel and card | `rounded-xl` |
| Control (input, select, textarea, button) | `rounded-lg` |
| Modal | `rounded-xl` |
| Popover and dropdown panel | `rounded-xl` |
| Authentication card | `rounded-xl` |
| Badge and pill | `rounded-full` |

| Context | Format |
|---|---|
| Date | `translatedFormat('d M Y')` |
| Date and time | `translatedFormat('d M Y, H:i')` |
| Range | `d M Y – d M Y` (en dash) |
| Time zone | WITA; show the `WITA` suffix only on attendance evidence times and link validity times |
| Element | Always `<time datetime="...">`; do not use `font-mono` for dates |

These formats require `APP_LOCALE=id` on the target server (P0-2). An 8 November local run showed English weekday and month names, so the local `.env` does not have it yet. Stored values and the application time zone do not change.

## 5. Spacing, layout, and density

Use the existing Tailwind spacing scale and established project baseline. The values below are practical defaults, not a reason to rewrite every page.

| Element | Baseline | Notes |
|---|---|---|
| Admin list/monitoring container | `w-full max-w-7xl` | Use narrower width for reading/form pages when justified |
| Main vertical section rhythm | `gap-4` (16 px) | `gap-5` or `gap-6` only when a real section distinction needs it |
| Filter panel | `p-3` (12 px) | Compact, controls usually `gap-2` or `gap-3` |
| Monitoring card/list | `p-3` or `p-4` | Enough space for clear grouping and touch interaction |
| Dense table cell | `px-3 py-2` | Preserve readable line height and target size |
| Label/value pair | `gap-1` | Keep related information visually connected |
| Form/detail surface | Context-dependent | Do not compact at the expense of entry or reading comfort |

Spacing scale reference: 4, 8, 12, 16, 20, 24, 32, 40, and 48 px. Prefer consistent utility classes over arbitrary one-off values. Do not introduce a new token layer unless it can be mapped cleanly to the current Tailwind/theme setup.

Density means removing decorative whitespace, not shrinking everything. Do not hide critical information or reduce hit targets to fit more content.

### Application shell and content surface

Keep the application shell visually continuous without making the sidebar and page indistinguishable: the `body` and sidebar use the sidebar surface (`bg-sanmal-surface-sidebar dark:bg-sanmal-surface-page-dark`). In light theme both are `#CAD6E2`; the full-width main content uses the separate page surface, which is `#EEF2F7`. The light-theme sidebar has no separating border. **Target (D3, F2):** in dark theme the sidebar uses its own `surface/sidebar` token (`#0A111B`) and a 1px `border-e` (`border-sanmal-border-dark`), because the colour difference from the page is only 1.03:1. Until F2 lands, the dark sidebar still shares the page surface.

The main content uses `<flux:main>` without Flux's `inset` prop. Standard and linked `x-ui.card` components keep the dedicated card surface, intentionally distinct from the main background. Keep the sidebar, optional header, and main as direct siblings. Let the document scroll normally at every viewport; do not turn the main region into a nested scroll container.

The permanent `class="dark"` was removed from the `<html>` element of the four layouts (D4, F1), so the theme follows the user's appearance choice through `@fluxAppearance`. On 8 November the owner saw a saved Dark choice honoured; empty localStorage, OS light/dark with System selected, and first-paint flash are still untested (P0-3).

**Page gutter (D9, resolved).** The horizontal and vertical gutter of the main region comes from Flux itself: `<flux:main>` carries `p-6 lg:p-8` (24 px, then 32 px from `lg`). Our authored `p-0` on the same element is overridden by stylesheet order (`.p-0` is emitted before `.p-6`), so it has no effect; the QA log recorded `padding: 32px` on all four sides at desktop width. A 360 px screenshot of `/admin/rekap-harian` (8 November) shows the page title about 24 px from the screen edge, which matches `p-6`; that value was read from a screenshot, not measured in DevTools. **Do not add a second gutter wrapper.** Page content must not add blanket horizontal padding either. When a page needs inner padding, apply it to the relevant section or card. The ineffective `p-0` should be removed when `layouts/app.blade.php` is next edited, so the source stops implying a rule that does not hold.

Do not add `overflow-y-auto`, `overscroll-contain`, fixed heights, or per-page overflow wrappers to the main region. `x-ui.page` provides width and vertical flow. Pages set width and gap through its props (`maxWidth`, `gap`), not through `!max-w-*` or `!gap-*` overrides (D10).

Preserve the employee mobile header and drawer toggle. Do not add a fixed bottom navigation bar.

### Authentication pages

Use the shared `layouts.auth.simple` layout for active authentication routes: a compact `max-w-md` form surface on the SanMal page surface, the shared brand logo, clear left-aligned form hierarchy, and visible focus states. Keep decoration restrained to the brand accent; do not add promotional copy, random quotes, ambient gradient effects, or a second authentication shell. Keep inputs and primary actions full-width on narrow screens, with recovery links placed separately from field labels. Authentication behavior, route, CSRF, and validation contracts remain owned by Fortify.

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
| Ghost | Flux `ghost`; minor or infrequent action, or an icon control that does not need a filled surface (for example the status correction action on each daily recap card) |
| Danger | Flux `danger`; destructive or high-risk actions such as Delete, Remove, or Cancel when the action is destructive |

Do not make every button primary. When a list repeats the same secondary action on every item, prefer ghost over outline so the list does not become a column of heavy buttons. Destructive actions must retain the workflow's existing confirmation; styling does not replace confirmation.

### 6.2 Compact sizing

The agreed design direction is **compact by default, comfortable when needed**. Previous Figma button padding looked too wide for operational screens.

| Design size | Target height | Horizontal padding target | Use |
|---|---:|---:|---|
| Small | 32 px | 12 px | Dense table actions and compact toolbar; Flux `sm` |
| Medium | 40 px | 16 px | Standard Admin action; Flux `base` |
| Touch target | 44 px minimum | Context-dependent | Any control a finger must hit on a phone: use `min-h-11` on small screens and drop it from `sm` where the dense size is fine |
| Large touch action | 48 px minimum | Context-dependent | Employee's most important mobile action |

Flux 2.13 button sizes are `xs`, `sm`, and `base`; do not pass unsupported values such as `md` or `lg`. The shared component defaults to `sm` for compact operational density. Icon-only controls need an adequate hit area even if the visible icon is small: 44 px on mobile.

### 6.3 Theme contrast and button states

- Light theme primary: SanMal brand blue (`#0E3B6B`) with white text; hover uses brand dark blue (`#0C315B`).
- Dark theme primary: warm SanMal yellow (`#D9A72E`) with dark text (`#172033`); hover uses `#C49320`.
- Light theme secondary: neutral primary surface, subtle border, and brand-blue text.
- Dark theme secondary (D2, applied in F1): transparent background, `control-border-dark` border (`#5A7391`), and `text-primary-dark` text; hover uses `surface-inset-dark`. Yellow and brown are not used for any button other than the primary action.
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
- The shared `x-ui.card` uses the card surface with a 1px border and no shadow. Light cards use `sanmal-surface-card`; dark cards use `sanmal-surface-card-dark`. Linked cards change the surface to secondary (light) or elevated (dark) and the border to the accent on hover.
- Controls use the `control-border` token (at least 3:1); panels and dividers use `border`.
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

**Per-person summary cards (daily recap).** Give every card the same structure whether or not data exists: header (avatar, name, ID, status badge), then fixed time slots (Masuk, Pulang), then the action row. An empty slot shows a dashed placeholder with an en dash, and the sentence "Tidak ada catatan clock in/out." stays available to screen readers. This keeps empty and filled cards at the same height so a grid of them stays aligned and scannable. Times use `<time datetime>` and `tabular-nums`. The left rail is a scan aid only; the badge text carries the meaning. Grid: one column below `md`, two from `md`, three from `xl`.

### Navigation

- Use Flux sidebar and header primitives for the shared application shell; do not build a parallel navigation system.
- Use `<flux:main>` without `inset`. Keep sidebar, header, and main as direct layout siblings; use `lg:ms-0` on the main region (add `lg:mt-0` only if a desktop-visible header is present). The main region's padding is Flux's own (see Page gutter in section 5).
- Let `x-ui.page` own the page-specific content width; do not add Flux `container` at the main level as well as another page max-width unless the double constraint is intentional. Use narrower page widths for forms/details and wider widths for operational data pages.
- Employee navigation is one ungrouped list: Beranda, Absensi, Riwayat, Permohonan, and Lembur. On phones, use the top header with a drawer toggle; do not use a fixed bottom navigation bar.
- Admin Dashboard is a standalone first item, not a section/group heading. It remains the default landing page and represents company-wide monitoring.
- Admin/Superadmin accounts with a linked employee profile also receive an `Aktivitas Saya` group, beginning with `Dashboard Pribadi`, followed by Absensi, Riwayat, Permohonan Saya, and Lembur Saya. The personal dashboard reuses employee-scoped aggregation and must never show company-wide data.
- Follow the Admin navigation order: standalone Dashboard; Aktivitas Saya (only when an employee profile exists); Operasional; Persetujuan; Data Master; Sistem. Keep concise labels and do not add a group heading around the single Dashboard item.
- Group headings must not disappear and collapse the vertical rhythm when the desktop sidebar is collapsed. Use `x-ui.sidebar-section`: the expanded state shows the heading, while the collapsed state replaces it with a subtle separator in the same fixed-height row. Employee navigation remains ungrouped and does not render separators.
- Sidebar navigation icons use a consistent 20px square size and do not change size between expanded and collapsed states. Preserve the icon's aspect ratio, alignment, and non-shrinking behavior.
- Every authenticated role uses the same collapsible sidebar on desktop and the drawer toggle on phones. Use viewport-relative `dvh` sizing for the shell. Do not create separate role-specific navigation implementations.
- **Open gap (F2):** the notification bell lives in the mobile header, which is hidden from `lg` upward (`lg:hidden`), and the desktop user menu has no notification entry. On desktop, notifications are reachable only through the `/notifications` page. A desktop entry point must be decided in F2. Note that the sidebar has `overflow-x-hidden`, so a popover launched from inside it must be positioned with `fixed`, not `absolute`.

## 9. Overlays, popovers, and motion

Added 8 November 2026. Applies to dropdowns, popovers, menus, and any panel that floats above the page. Modals follow `x-ui.modal`.

### 9.1 Dismissal contract

Every floating panel closes on:
1. A pointer press outside the panel and its trigger.
2. The Escape key, returning focus to the trigger.
3. Navigation (a link inside the panel, or a Livewire `wire:navigate` transition).

An item that submits a form or navigates closes the panel by leaving the page; do not rely on the panel staying open. Only one floating panel should be open at a time.

**Do not build floating panels from native `<details>`/`<summary>`.** That element has no outside-click dismissal, which is exactly the defect found in the first notification dropdown. Use Flux `dropdown`/`menu` for menu-like content, and an Alpine panel (`x-data`, `x-on:click.outside`, `x-on:keydown.escape.window`) when the content is mixed, such as items that are forms. Closing on focus loss is deliberately not part of the contract: Safari does not focus buttons on click, so a `focusout` handler can close the panel before a click inside it completes.

### 9.2 Surface and anchoring

- Surface: `surface/card` in light and `surface/elevated` in dark, a 1px `border`, `rounded-xl`, and `shadow-xl`. Shadow is allowed here because the panel floats.
- Layer: popovers use `z-[100]`.
- Below `lg`, anchor the panel under the header with a 0.5 rem gutter (`fixed right-2 top-16`, width `min(22rem, 100vw - 1rem)`). From `lg`, anchor it to the trigger (`absolute right-0 top-full mt-2`). Height is bounded with `dvh` units.
- Structure long panels in three parts: a fixed header, a scrolling list (`overflow-y-auto overscroll-contain`), and a fixed footer action. The header and footer must stay visible while the list scrolls.

### 9.3 Accessibility

- The trigger is a `<button type="button">` with a constant accessible name (for example `Buka notifikasi`), `aria-expanded`, `aria-haspopup`, and `aria-controls` pointing at the panel id. Open state is communicated by `aria-expanded`, not by changing the label.
- The panel has `role="dialog"` and an `aria-label`.
- The trigger is at least 44 × 44 px on touch screens. A count badge on the trigger is `aria-hidden`; the count is repeated in an `sr-only` string.
- Unread items carry a non-color cue: an accessible-name dot (`role="img"` with `aria-label`) in addition to the accent rail and tint.

### 9.4 Motion

- Motion is allowed only in response to a user action (opening, closing, expanding, confirming) and must show what changed. No entrance animation on page sections, no staggered reveals, and no hover lift on cards.
- Popover open: 150 ms ease-out, from `scale-95` and a 4 px upward offset to rest, with the origin at the trigger corner (`origin-top-right`). Close: 100 ms ease-in, fade and scale only.
- Colour and background transitions on hover and focus are limited to 150 ms.
- Every transition class is paired with `motion-reduce:transition-none`. Reduced motion removes the movement; the state change itself stays.

## 10. Single branch and single PR

- Untuk finalisasi UI/UX sebelum produksi, gunakan satu branch `feature/ui-production-readiness` dan satu PR ke `main`. PR #27 dan #29 sudah di-merge dan tidak lagi menjadi acuan.
- Jangan membuat branch atau PR UI lain selama PR ini terbuka. Commit per fase, kecil dan fokus.
- Jangan merge sebelum Definition of Done di `docs/ui/DEVELOPMENT-HANDOFF.md` terpenuhi dan bukti QA tercatat di `docs/ui/progress/VISUAL-QA-LOG.md`.
- Pertahankan deskripsi PR dan tasklist fase tetap mutakhir.

## 11. Implementation references

- Start with `docs/ui/DEVELOPMENT-HANDOFF.md`.
- Use `docs/ui/PAGE-INVENTORY.md` for page scope and audit references.
- Use `docs/ui/COMPONENT-CATALOG.md` and `docs/ui/REUSABLE-BLADE-COMPONENTS.md` for existing component contracts.
- Track progress in `docs/ui/progress/IMPLEMENTATION-STATUS.md` and evidence in `docs/ui/progress/VISUAL-QA-LOG.md`.

The actual source code, current tests, and rendered browser output remain the implementation truth. If a documented API or page status conflicts with source, inspect and correct the documentation rather than blindly following stale details.
