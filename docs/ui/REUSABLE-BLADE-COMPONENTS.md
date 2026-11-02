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
| `x-ui.card` | Standard content surface | Slot + mergeable attributes; Flux card surface |
| `x-ui.button` | Standard action affordance | Slot, `variant`, `size`, `type`, optional `href`, `icon`, `iconTrailing`, `square`, `disabled`; forwards Livewire/Alpine/HTML attributes |

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
- `tests/Feature/UiButtonComponentTest.php` covers button semantics, default type, and Alpine-bound links.
- `tests/Feature/UiLayoutComponentsTest.php` covers the generic page header, page container, and section wrapper.
- These tests have been authored but not yet executed.

## Migration order

1. UI primitives: page, button, card.
2. Shared feedback and navigation patterns: flash message, empty state, page header, filter panel.
3. Repeated data presentation: badges, table wrappers, list/card rows, metadata groups.
4. Apply components to Admin pages, then employee pages and auth.
5. Consolidate remaining one-off utility classes only after usage has been audited.

## Validation checklist

- Blade compiles and Livewire pages render.
- `wire:click`, `wire:model`, Alpine `x-on`/`x-bind`, and custom attributes reach the intended DOM element.
- Links remain links; actions remain buttons.
- Light/dark, mobile/desktop, focus, disabled, and loading states remain correct.
- Existing routes, authorization, filters, sorting, pagination, exports, and forms are unchanged.
- Run focused Pest tests before broader suite; report tests as unverified until actually executed.
