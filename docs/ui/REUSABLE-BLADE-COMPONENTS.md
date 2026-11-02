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

Form control rules:
- Keep Flux as the renderer; `x-ui.*` supplies a consistent SanMal contract rather than reimplementing native controls.
- The interactive control receives forwarded `wire:*`, `x-*`, accessibility, and native HTML attributes.
- A field label is associated with the control ID. If no explicit ID is supplied, the field name is used.
- A field description and validation error remain adjacent to the relevant control.
- Use `x-ui.field` directly when a custom control needs the shared label/description/error layout.
- Do not put a border or card surface around every field; field grouping follows the Seamless-First design principle.

Button variant policy:
- `primary`: main action for the current context.
- `outline`: labeled secondary action.
- `danger`: destructive or high-risk action.
- `ghost`: icon-only or intentionally low-emphasis utility action.

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
- The button primitive is now applied to Admin Attendance, Leave Requests, Daily Attendance, Clients, Employees, Audit Logs, Attendance Corrections, Overtime Requests, Projects, Notifications, and selected Employee list/history pages.
- The generic page header is now the implementation behind the existing `x-admin.page-header` API, preserving current callers while making the layout primitive reusable outside Admin.
- `x-ui.section` provides a standard vertical section wrapper.
- Generic flash-message and empty-state components now back the existing Admin component names, preserving their call sites.
- Dashboard statistic/action cards and Admin daily employee cards now compose `x-ui.card`. Attendance information cards retain their semantic section wrapper.
- `x-ui.field` and `x-ui.input` are now implemented. Admin Employee Create/Edit use `x-ui.input` for text, email, phone, employee number, and date fields; selects remain on the existing Flux pattern pending `x-ui.select`.
- `tests/Feature/UiFormControlsTest.php` covers label/description/error rendering, Livewire/Alpine/native attribute forwarding, and custom field slots.
- `tests/Feature/UiButtonComponentTest.php` covers button semantics, default type, and Alpine-bound links.
- `tests/Feature/UiLayoutComponentsTest.php` covers the generic page header, page container, and section wrapper.
- `tests/Feature/UiCardComponentTest.php` covers content and linked-card semantics.
- `tests/Feature/UiFeedbackComponentsTest.php` covers generic and Admin-compatible empty states.
- Tests have been authored but not yet executed in this environment; CI status must be checked before considering this migration validated.

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
