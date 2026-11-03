# Reusable Blade Components — UI Foundation

## Objective

Move repeated visual structure and interaction patterns into Blade components under `resources/views/components`. Each shared pattern should have one implementation so visual fixes and accessibility improvements can be applied consistently.

This is an incremental refactor. Do not rewrite every page in one batch and do not change business logic, authorization, routes, validation, Livewire state, or request payloads as part of component extraction.

## Component layers

### 1. UI primitives (`x-ui.*`)

Generic components that can be used by admin, employee, and authentication pages.

| Component | Responsibility | Initial contract |
|---|---|---|
| `x-ui.page` | Standard page width and vertical rhythm | Optional `maxWidth` preset (`xl`, `2xl`–`7xl`, `full`; default `7xl`), optional `gap` preset (`3`–`6`; default `4`), slot, and mergeable attributes |
| `x-ui.page-header` | Responsive page title, description, and action slot | `title`, optional `description`, optional `headingSize`, action slot |
| `x-ui.section` | Standard vertical grouping for page sections | Slot + mergeable attributes; default vertical gap 3 |
| `x-ui.flash-message` | Session status and validation feedback | Reads Laravel session status and error bag |
| `x-ui.empty-state` | Empty collection state | Optional `message`, optional table `colspan`, slot, and mergeable attributes |
| `x-ui.card` | Standard content surface or linked card | Slot, optional `href`, `padding`, and mergeable attributes; semantic anchor when `href` is supplied |
| `x-ui.button` | Standard action affordance | Slot, `variant` (default `primary`), optional `color`, `size` (default `sm`), `type`, optional `href`, `icon`, `iconTrailing`, `square`, `disabled`; primary defaults to SanMal blue, outline receives a dark-theme contrast treatment, and Livewire/Alpine/HTML attributes are forwarded |
| `x-ui.field` | Shared field layout for labels, descriptions, and validation errors | Optional `name`, `id`, `label`, `description`, and `showError`; slot for the actual control |
| `x-ui.input` | Standard text/date/file and other native input types | Optional `name`, `id`, `label`, `description`, `value`, `type`, and `showError`; forwards remaining HTML, Livewire, and Alpine attributes to `flux:input` |
| `x-ui.select` | Standard select field with shared label, description, and validation layout | Optional `name`, `id`, `label`, `description`, `placeholder`, and `showError`; option markup remains in the slot and remaining attributes are forwarded to `flux:select` |
| `x-ui.textarea` | Standard multiline text field with shared label, description, and validation layout | Optional `name`, `id`, `label`, `description`, `rows` (default 4), `resize` (default vertical), and `showError`; content is supplied through the slot and remaining attributes are forwarded to `flux:textarea` |
| `x-ui.checkbox` | Standard single checkbox with shared description and validation feedback | Optional `name`, `id`, `label`, `description`, `checked`, and `showError`; label remains adjacent to the checkbox as rendered by `flux:checkbox`, and remaining attributes are forwarded to the interactive control |
| `x-ui.badge` | Consistent status and compact indicator presentation | Slot content, optional Flux badge `color` (default `zinc`), and forwarded presentation attributes |
| `x-ui.metadata-item` | Semantic label/value pair inside a description list | Required `label`, slot for the value, optional `labelClass` and `valueClass`, and mergeable wrapper attributes |\n| `x-ui.notification-dropdown` | Reusable notification preview popover | Required `notifications` collection (latest items, limited by caller) and optional `unreadCount`; renders unread indicators, per-item read navigation, empty state, and the existing all-notifications route. Data retrieval remains in the controller/view model, not the component |

Metadata and status component rules:
- Use `x-ui.metadata-item` only as a direct item inside a semantic `<dl>`; the component supplies the wrapper plus `<dt>` and `<dd>`, not the parent grid or surface.
- Use `label-class` and `value-class` when a page needs a denser metadata hierarchy; keep the actual value in the slot so links, badges, and conditional content remain page-owned.
- Use `x-ui.badge` for compact status/type indicators where the page needs the shared Flux badge contract.\n- Use `x-ui.notification-dropdown` when a page header needs the shared notification preview. The caller supplies a user-scoped latest-notification collection and unread count; do not add queries or authorization decisions to the component. Pass the existing enum color and label through; do not move status mapping into the component.
- Keep semantic page wrappers such as `<article>` when the content is a record. `x-ui.card` renders a `div` or an anchor, so it is not a replacement when an article/list-item semantic is required.
- Do not create a dedicated employee-card or generic table component from one page's markup. Reuse the existing primitives first; extract a larger pattern only after multiple pages share the same structure and interaction contract.

Form control rules:
- Keep Flux as the renderer; `x-ui.*` supplies a consistent SanMal contract rather than reimplementing native controls.
- The interactive control receives forwarded `wire:*`, `x-*`, accessibility, and native HTML attributes.
- A field label is associated with the control ID. If no explicit ID is supplied, the field name is used.
- A field description and validation error remain adjacent to the relevant control.
- Use `x-ui.field` directly when a custom control needs the shared label/description/error layout.
- Do not put a border or card surface around every field; field grouping follows the Seamless-First design principle.

Button and action hierarchy:
- `primary`: the single most important action in the current page section or decision context. `x-ui.button` applies SanMal brand blue by default. Avoid presenting several competing primary actions side by side.
- `outline`: a neutral, labeled secondary action such as Back, Reset, Edit, or opening supporting details.
- `ghost`: a low-emphasis utility action that should not compete with the main task. Do not use it for an action whose consequence needs stronger emphasis.
- `danger`: the semantic destructive variant supported by Flux 2.13; use it for Delete, Remove, or destructive cancellation. Preserve the workflow's existing confirmation. Explicit `color="red"` remains appropriate when a non-danger variant needs a red semantic tint.
- Keep the supported Flux variant set; do not invent a separate `secondary` variant. In SanMal's action hierarchy, secondary is the design role implemented with Flux `outline`.
- Keep the visual hierarchy aligned with the action's actual consequence. Color alone is not a substitute for a clear label or confirmation step.
- Prefer one primary action per local context. If two actions are equally important, reconsider their grouping and make one secondary unless the workflow genuinely has two distinct primary tasks.
- Use `href` for navigation so the rendered element remains a link. Use `type="button"` for in-page actions and `type="submit"` only for form submission. The component defaults to `type="button"` to avoid accidental form submission.
- Icon-only actions must receive an accessible name through the supported Flux/API attributes; an icon is not a replacement for a label when the action is not self-evident.
- Keep compact actions visually subordinate; do not increase button size merely to fill space.
- Use `x-ui.button` for shared navigation and action controls, including Flux-backed controls inside Livewire components. Retain a native `<button>` only when the element is a specialized control whose state/content is tightly coupled to custom interaction markup (for example, selectable calendar-day tiles or photo-preview triggers); document the reason rather than keeping one-off button styling by default.
- The wrapper forwards supported Flux button props and arbitrary HTML/Livewire/Alpine attributes. Its only design default is SanMal blue for `primary`; other variants retain Flux semantics unless an explicit color is supplied.

A dynamically assigned Alpine URL must use an anchor rendered on the server (for example, provide a harmless initial `href` and bind the final URL with `x-bind:href`). A plain button cannot become a semantic link merely by adding an Alpine `href` attribute.

### 2. Domain/layout components (`x-admin.*`, `x-attendance.*`, etc.)

Components that encode a repeated page pattern or domain-specific presentation:
- page header;
- filter panel;
- flash/callout message;
- empty state;
- status badge;
- attendance evidence and record metadata when repeated across pages.

Keep these separate from generic primitives when they contain domain terminology or business-specific presentation.

### 3. Page composition

Page views should compose primitives and domain components, while retaining:
- Livewire component state and actions;
- query construction and filtering;
- authorization checks;
- routes and HTTP methods;
- form validation and confirmation flow;
- data-specific markup that is not genuinely repeated.

## Extraction rules

1. Extract only when a pattern repeats or has a clear cross-page standardization benefit.
2. Keep component APIs small; avoid one component with many booleans for unrelated layouts.
3. Use `$attributes->class([...])` or `$attributes->merge([...])` so callers can add page-specific classes and attributes.
4. Forward `wire:*`, `x-*`, accessibility, and native HTML attributes to the actual interactive element.
5. Preserve semantic HTML: links navigate, buttons perform actions, forms submit.
6. Keep labels, accessible names, focus states, disabled/loading states, and destructive confirmations.
7. Do not wrap every Flux component automatically. Add a wrapper only when it provides a stable shared contract or prevents recurring mistakes.
8. Prefer named variants over arbitrary class strings for shared visual decisions.
9. Do not put business logic, database access, authorization decisions, or route construction inside presentational components.
10. Migrate a small page group at a time and add/update focused tests for rendered contracts.

## Current standardization status

The shared reusable component foundation is implemented. The current `x-ui.*` inventory contains 14 primitives:

- Layout: `page`, `page-header`, `section`.
- Action: `button`.
- Surface: `card`.
- Form: `field`, `input`, `select`, `textarea`, `checkbox`.
- Feedback: `flash-message`, `empty-state`.
- Status/metadata: `badge`, `metadata-item`.

The primitives are used across selected Admin, Employee, Shared, and Authentication pages. Adoption is intentionally based on semantic fit; it does not require converting every page wrapper, native control, table, or one-off markup into a generic component.

Focused component tests exist for the layout, button, card, feedback, form controls, badge, and metadata components. CI has passed on the latest responsive-fix commit `6da6b96ee1e8be19126cba43a62d6ec51aaf22ca` ([run 1065](https://github.com/leonsmith128/pt-sanmal/actions/runs/41566842563)). This is not a claim that all focused tests were run in one latest aggregate suite or that every page passed visual QA.

The latest targeted page fixes cover:
- Admin Attendance filter width and mobile-first control layout.
- Overtime detail's invalid image icon name.
- Employee request-center header action wrapping.

These changes passed CI, but the corresponding browser/viewport recheck remains pending owner confirmation. See [Implementation Status](progress/IMPLEMENTATION-STATUS.md) and [Visual QA Log](progress/VISUAL-QA-LOG.md).

### Standardization boundary

- Reuse the existing primitives where their semantics and API fit.
- Keep domain-specific status mapping and business decisions outside generic primitives.
- Do not extract one-off markup without a demonstrated repeated pattern and shared interaction contract.
- Do not treat reusable component standardization as completion of page design, layouting, responsive audit, accessibility audit, or final visual QA.

The next work phase is page-level design and layouting using the existing primitives. New component work should be driven by a concrete gap discovered during that phase, not by a goal of abstracting every remaining class or element.

## Responsive data presentation

Use the presentation pattern that matches the data's purpose; do not force every collection into a universal table component.

### Pattern selection

- **Responsive table with mobile cards:** use when desktop/tablet users benefit from comparing multiple attributes across records, while the same records need a readable stacked presentation on narrow screens.
- **Responsive list:** use when each row primarily acts as an entry point to inspect or process one record. Keep the identity, relevant date, and status visible without adding a table solely for consistency.
- **Data cards:** use when a record has contextual metadata, evidence, or a small number of independent actions that are easier to understand as a grouped unit.

### Responsive table contract

1. Keep the information hierarchy equivalent between mobile cards and the table. A field may be arranged differently, but do not silently omit useful information such as submission time, status, decision details, or location.
2. Use a single horizontal scroll container around the table. Give the scroll region an accessible name and keyboard focus when horizontal scrolling is required.
3. Set a content-driven minimum table width when columns would otherwise become too compressed. Choose the width per page and column density; do not apply one global width to all tables.
4. Keep intrinsically short values such as dates, times, and compact status indicators unwrapped where that improves scanning. Allow summaries, addresses, project names, and decision notes to wrap.
5. Use `scope="col"` on column headers. Preserve semantic links, buttons, and forms inside table cells.
6. Keep sorting affordances in their corresponding headers. Responsive presentation changes must not alter sorting parameters, filter query strings, pagination, export URLs, or action conditions.
7. Do not render a duplicate mobile and desktop record in the accessibility tree at the same viewport; use responsive visibility utilities so only the intended presentation is exposed.

### Current page-specific decisions

- Employee Leave Requests: mobile cards retain reference, submission date and time, period, summary, operational overtime status, decision metadata, status, and conditional cancellation. The tablet/desktop table has a content-driven minimum width and a named, keyboard-focusable horizontal scroll region.
- Employee Attendance History: mobile cards retain event time, activity, work type, client/project/location snapshots, photo preview, map access, and stored address. The table has a narrower content-driven minimum width than Leave Requests because it has fewer columns, but still needs room for evidence and location details.
- Admin Employees: use one-column cards below `lg`, two-column cards from `lg` through `xl`, and the comparison table from `xl`. These are CSS viewport-width decisions, not device-orientation detection: narrower tablet viewports retain one column, while wider tablet viewports can use two columns. Preserve the established desktop table breakpoint. Card and table fields stay equivalent: employee name/email, employee number, job title, account status, and edit action. The table has a 52rem content minimum, a named keyboard-focusable scroll region, and scoped column headers.
- Admin review queues such as Attendance Corrections and Overtime Requests remain responsive lists; a table is not required when the primary interaction is opening an individual request.

## Next phase

The reusable component standardization phase is complete. Do not repeat the primitive implementation sequence unless page-level work exposes a concrete missing reusable pattern. The next phase is page design and layouting, tracked in [Implementation Status](progress/IMPLEMENTATION-STATUS.md).

## Validation checklist

- Blade compiles and Livewire pages render.
- `wire:click`, `wire:model`, Alpine `x-on`/`x-bind`, and custom attributes reach the intended DOM element.
- Links remain links; actions remain buttons.
- Light/dark, mobile/desktop, focus, disabled, and loading states remain correct.
- Existing routes, authorization, filters, sorting, pagination, exports, and forms are unchanged.
- Run focused Pest tests before broader suite; report tests as unverified until actually executed.
