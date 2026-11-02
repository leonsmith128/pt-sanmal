# Reusable Blade Components — UI Foundation

## Objective

Move repeated visual structure and interaction patterns into Blade components under `resources/views/components`. Each shared pattern should have one implementation so visual fixes and accessibility improvements can be applied consistently.

This is an incremental refactor. Do not rewrite every page in one batch and do not change business logic, authorization, routes, validation, Livewire state, or request payloads as part of component extraction.

## Component layers

### 1. UI primitives (`x-ui.*`)

Generic components that can be used by admin, employee, and authentication pages.

| Component | Responsibility | Initial contract |
|---|---|---|
| `x-ui.page` | Standard page width and vertical rhythm | Slot + mergeable attributes; default `w-full max-w-7xl`, `gap-4` |
| `x-ui.page-header` | Responsive page title, description, and action slot | `title`, optional `description`, optional `headingSize`, action slot |
| `x-ui.section` | Standard vertical grouping for page sections | Slot + mergeable attributes; default vertical gap 3 |
| `x-ui.flash-message` | Session status and validation feedback | Reads Laravel session status and error bag |
| `x-ui.empty-state` | Empty collection state | Optional `message`, optional table `colspan`, slot, and mergeable attributes |
| `x-ui.card` | Standard content surface or linked card | Slot, optional `href`, `padding`, and mergeable attributes; semantic anchor when `href` is supplied |
| `x-ui.button` | Standard action affordance | Slot, `variant` (default `primary`), `size`, `type`, optional `href`, `icon`, `iconTrailing`, `square`, `disabled`; forwards Livewire/Alpine/HTML attributes |
| `x-ui.field` | Shared field layout for labels, descriptions, and validation errors | Optional `name`, `id`, `label`, `description`, and `showError`; slot for the actual control |
| `x-ui.input` | Standard text/date/file and other native input types | Optional `name`, `id`, `label`, `description`, `value`, `type`, and `showError`; forwards remaining HTML, Livewire, and Alpine attributes to `flux:input` |
| `x-ui.select` | Standard select field with shared label, description, and validation layout | Optional `name`, `id`, `label`, `description`, `placeholder`, and `showError`; option markup remains in the slot and remaining attributes are forwarded to `flux:select` |
| `x-ui.textarea` | Standard multiline text field with shared label, description, and validation layout | Optional `name`, `id`, `label`, `description`, `rows` (default 4), `resize` (default vertical), and `showError`; content is supplied through the slot and remaining attributes are forwarded to `flux:textarea` |
| `x-ui.checkbox` | Standard single checkbox with shared description and validation feedback | Optional `name`, `id`, `label`, `description`, `checked`, and `showError`; label remains adjacent to the checkbox as rendered by `flux:checkbox`, and remaining attributes are forwarded to the interactive control |
| `x-ui.badge` | Consistent status and compact indicator presentation | Slot content, optional Flux badge `color` (default `zinc`), and forwarded presentation attributes |
| `x-ui.metadata-item` | Semantic label/value pair inside a description list | Required `label`, slot for the value, optional `labelClass` and `valueClass`, and mergeable wrapper attributes |

Metadata and status component rules:
- Use `x-ui.metadata-item` only as a direct item inside a semantic `<dl>`; the component supplies the wrapper plus `<dt>` and `<dd>`, not the parent grid or surface.
- Use `label-class` and `value-class` when a page needs a denser metadata hierarchy; keep the actual value in the slot so links, badges, and conditional content remain page-owned.
- Use `x-ui.badge` for compact status/type indicators where the page needs the shared Flux badge contract. Pass the existing enum color and label through; do not move status mapping into the component.
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
- `primary`: the single most important action in the current page section or decision context. Avoid presenting several competing primary actions side by side.
- `outline`: a labeled secondary action such as Back, Reset, Edit, or opening supporting details.
- `ghost`: a low-emphasis utility action that should not compete with the main task. Do not use it for an action whose consequence needs stronger emphasis.
- `danger`: reserve the destructive/high-risk role for actions such as delete or irreversible cancellation. Confirm that the installed Flux version supports the chosen destructive styling before using it; the wrapper forwards the variant rather than defining its own visual implementation.
- Keep the visual hierarchy aligned with the action's actual consequence. Color alone is not a substitute for a clear label or confirmation step.
- Prefer one primary action per local context. If two actions are equally important, reconsider their grouping and make one secondary unless the workflow genuinely has two distinct primary tasks.
- Use `href` for navigation so the rendered element remains a link. Use `type="button"` for in-page actions and `type="submit"` only for form submission. The component defaults to `type="button"` to avoid accidental form submission.
- Icon-only actions must receive an accessible name through the supported Flux/API attributes; an icon is not a replacement for a label when the action is not self-evident.
- Keep compact actions visually subordinate; do not increase button size merely to fill space.

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

## Current migration status

- `x-ui.page`, `x-ui.card`, and `x-ui.button` primitives exist.
- The button primitive is applied to Admin Attendance, Leave Requests, Daily Attendance, Clients, Employees, Audit Logs, Attendance Corrections, Overtime Requests, Projects, Notifications, selected Employee list/history pages, the Employee dashboard's primary/secondary navigation actions, and the authentication login submit action. The latter two were standardized in the current V1 UI/UX branch; remaining direct Flux/native buttons still require page-by-page review rather than blanket replacement.
- The generic page header is now the implementation behind the existing `x-admin.page-header` API, preserving current callers while making the layout primitive reusable outside Admin.
- `x-ui.section` provides a standard vertical section wrapper.
- Generic flash-message and empty-state components now back the existing Admin component names, preserving their call sites.
- Dashboard statistic/action cards and Admin daily employee cards now compose `x-ui.card`. Attendance information cards retain their semantic section wrapper.
- `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, and `x-ui.checkbox` are now implemented.
- `x-ui.metadata-item` is implemented for semantic `<dt>`/`<dd>` pairs. It provides default label/value typography while allowing page-specific label/value classes and wrapper attributes. Admin Attendance Correction detail, Admin Leave Request detail, Admin Employee cards, and Admin Project summary/detail now use it for repeated label/value pairs; conditional fields and all displayed values remain controlled by the existing page data.
- `x-ui.badge` is implemented as a thin Flux badge wrapper. Admin employee account status, Admin overtime approval/state indicators, Admin Client location/office status, Admin Project type/status/site warning, Employee request status, and Employee attendance event indicators now use the shared primitive; the existing `x-admin.request-status-badge` API delegates to it. Admin Employee Create/Edit use `x-ui.input` for text, email, phone, employee number, and date fields, plus `x-ui.select` for job title and account status. Option markup and selected-state expressions remain in the page so existing request values are preserved. Employee leave requests, Admin leave decisions, and Admin overtime finalization/decision/photo-exception forms use `x-ui.textarea`; the authentication login page uses `x-ui.checkbox` for Remember me.
- `tests/Feature/UiFormControlsTest.php` covers input label/description/error rendering, Livewire/Alpine/native attribute forwarding, and custom field slots.
- `tests/Feature/UiSelectComponentTest.php` covers select label/description/error rendering, option slots, custom IDs, and Livewire/Alpine/native attribute forwarding.
- `tests/Feature/UiTextareaComponentTest.php` covers textarea label/description/error rendering, slot content, row/resize options, custom IDs, and Livewire/Alpine/native attribute forwarding.
- `tests/Feature/UiCheckboxComponentTest.php` covers checkbox label/description/error rendering, checked state, custom IDs, and Livewire/Alpine/native attribute forwarding.
- `tests/Feature/UiButtonComponentTest.php` covers button semantics, default type, Alpine-bound links, and forwarding of Livewire, accessibility, disabled, and presentation attributes. The expanded test has not yet been executed in the current environment.
- `tests/Feature/UiLayoutComponentsTest.php` covers the generic page header, page container, and section wrapper.
- `tests/Feature/UiCardComponentTest.php` covers content and linked-card semantics.
- `tests/Feature/UiFeedbackComponentsTest.php` covers generic and Admin-compatible empty states.
- `tests/Feature/UiBadgeComponentTest.php` covers default/explicit color, forwarded presentation attributes, and enum-provided status labels/colors.
- `tests/Feature/UiMetadataItemComponentTest.php` covers semantic description-list markup, default/custom classes, slot values, and forwarded wrapper attributes.
- Tests have been authored but not yet executed in this environment; CI status must be checked before considering this migration validated.

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

## Migration order

1. UI primitives: page, button, card.
2. Shared feedback and navigation patterns: flash message, empty state, page header, filter panel.
3. Form controls: field, input, select, textarea, checkbox.
4. Repeated data presentation: badges, table wrappers, list/card rows, metadata groups.
5. Apply components to Admin pages, then employee pages and auth.
6. Consolidate remaining one-off utility classes only after usage has been audited.

## Validation checklist

- Blade compiles and Livewire pages render.
- `wire:click`, `wire:model`, Alpine `x-on`/`x-bind`, and custom attributes reach the intended DOM element.
- Links remain links; actions remain buttons.
- Light/dark, mobile/desktop, focus, disabled, and loading states remain correct.
- Existing routes, authorization, filters, sorting, pagination, exports, and forms are unchanged.
- Run focused Pest tests before broader suite; report tests as unverified until actually executed.
