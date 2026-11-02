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
| `brand/blue` | `#003A6C` | Primary brand blue, primary actions, selected navigation |
| `brand/blue-dark` | `#00294D` | Hover/pressed/active emphasis |
| `brand/blue-light` | `#EAF1F7` | Subtle blue surface and selected backgrounds |
| `brand/yellow` | `#FDBE01` | Brand accent, highlights, restrained emphasis |
| `brand/yellow-soft` | `#FFF6D6` | Soft accent surface |

Yellow is an accent, not the default primary button fill. Always check foreground/background contrast before using brand colors for text or controls.

### Neutral palette

| Token | HEX | Typical use |
|---|---|---|
| `neutral/white` | `#FFFFFF` | Main surfaces |
| `neutral/50` | `#F8FAFC` | Page background / subtle surface |
| `neutral/100` | `#F1F5F9` | Secondary surface |
| `neutral/200` | `#E2E8F0` | Borders/dividers |
| `neutral/300` | `#CBD5E1` | Stronger borders |
| `neutral/500` | `#64748B` | Secondary text |
| `neutral/700` | `#334155` | Strong secondary text |
| `neutral/900` | `#0F172A` | Main text |

### Semantic colors

| Token | HEX | Meaning |
|---|---|---|
| `semantic/success` | `#15803D` | Successful/completed |
| `semantic/warning` | `#B45309` | Warning/needs attention |
| `semantic/danger` | `#B91C1C` | Error/rejected/destructive |
| `semantic/info` | `#2563EB` | Informational |

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

- Light theme primary: SanMal brand blue (`#003A6C`) with white text; hover uses brand dark blue (`#00294D`).
- Dark theme primary: use SanMal blue (`#006DCC`) with white text so the dominant action remains distinct against dark surfaces; hover uses the deeper blue (`#005CB8`). Keyboard focus uses a lighter blue outline (`#BFDBFE`).
- Light theme secondary: keep the neutral Flux outline treatment.
- Dark theme secondary: the shared `x-ui.button` applies a lighter neutral surface (`#262626`), border (`#525252`), and readable text (`#E5E7EB`). Hover increases surface and border contrast.
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
- Admin: preserve the established desktop sidebar/navigation model; mobile must remain usable without overflow or hidden essential routes.
- Employee: prioritize simple mobile navigation and easy access to attendance, history, requests, and profile.
- Shared visual language does not require identical navigation structure for different roles.
- Do not change route names, middleware, role checks, active states, or navigation permissions as part of visual-only work.

## 9. Seamless implementation rules for AI agents and developers

Before changing a page:
1. Read `AGENTS.md`, this document, and the relevant page audit.
2. Inspect the actual target branch and current working tree. Documentation and previous conversations are not proof of current source state.
3. Locate existing components under `resources/views/components/ui` and `resources/views/components/admin`.
4. Read the page view/SFC, route, related component, and relevant tests.
5. Identify the role (Employee/Admin), task, primary viewport, data density, and important states.
6. Record the current behavior/contract before editing.

During implementation:
- Work directly in the Laravel source code; Figma is not required for routine component/page design in this phase.
- Treat source code and browser rendering as the implementation truth. This document is the design guideline; it does not override actual behavior or domain rules.
- Reuse existing Blade/Flux/Tailwind components when semantically appropriate.
- Prefer a small, focused change over a broad redesign.
- Keep presentation components free of business decisions.
- Do not change query, state, filter semantics, sorting, pagination, route, authorization, validation, persistence, attendance calculations, timestamps, evidence, camera/GPS, or workflow unless explicitly in scope.
- Do not create generic abstractions solely to reduce markup. Confirm repeated markup and matching interaction contracts first.
- Preserve light/dark readability and keyboard accessibility where supported by the application.

After implementation:
1. Run relevant component/feature tests.
2. Run Pint for changed PHP/Blade-related files as appropriate.
3. Build frontend assets when CSS/JS changes require it.
4. Inspect rendered browser output at the relevant viewport checkpoints.
5. Check overflow, wrapping, focus, loading, disabled, error, and empty states.
6. Report what was actually tested and what remains unverified. Source inspection is not browser QA.

## 10. Recommended component implementation order

Implement directly in source code, in small reviewable changes:

1. Button — finalize variants, sizing, states, accessibility, and existing `x-ui.button` API.
2. Form controls — input, select, textarea, checkbox, date controls, search, labels, and validation.
3. Badge and status — generic primitive plus domain-specific adapters.
4. Card and surface — only recurring semantic surface patterns.
5. Alert and feedback — flash, alert, empty, loading, confirmation.
6. Table and data display — table density, responsive list/card alternatives, pagination.
7. Navigation — Admin sidebar and Employee mobile navigation.

For every component, document:
- Purpose and when to use it
- Existing source component/API
- Variants and sizes
- States
- Responsive behavior for Employee and Admin
- Accessibility and keyboard/touch expectations
- Example usage from a real SanMal page
- Tests and visual verification performed

## 11. Definition of done for UI work

A UI change is complete only when:
- The right existing component/pattern is reused or a justified new one is added.
- Employee and Admin responsive priorities are respected.
- Visual hierarchy, density, and readability are consistent with this guideline.
- Existing business behavior and server-side protections remain unchanged unless explicitly in scope.
- Relevant automated tests pass, or unrun tests are clearly disclosed.
- Browser rendering has been checked at relevant viewport sizes, or the missing QA is explicitly documented.
- No unrelated formatting/refactor changes are included.
- Documentation is updated when a shared component contract or design decision changes.

## 12. V1 UI/UX scope and delivery workflow

The SanMal V1 UI/UX program covers the existing web application's Employee and Admin experiences, shared components, page-level consistency, responsive behavior, interaction states, accessibility, and visual QA. It is not permission to add product features or change business/domain behavior.

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
