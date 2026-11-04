# SanMal UI Component Catalog

> This is the navigation and acceptance index for shared UI components. The detailed source/API inventory remains in [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md). Always verify the current component source and tests before using or changing an API.

## 1. Source of truth

Priority when information differs:

1. Current component source under `resources/views/components/`.
2. Focused component tests under `tests/Feature/`.
3. [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md).
4. This catalog and [Design Foundation](DESIGN-FOUNDATION.md).

Do not copy a documented API into a page without checking the implementation on the working branch.

## 2. Shared primitive families

| Family | Existing components/patterns documented | Primary responsibility | Detailed reference |
|---|---|---|---|
| Page/layout | `x-ui.page`, `x-ui.page-header`, `x-ui.section` | Consistent page width, header, and section rhythm | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Navigation composition | `x-ui.sidebar-section` | Role-aware grouped sidebar section: heading when expanded, separator in the same row when collapsed | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Button/action | `x-ui.button` | Action semantics, variants, icon slots, forwarded attributes | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Form | `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox` | Label, description, validation, and native/Flux control integration | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Surface | `x-ui.card` | Reusable card surface/link when semantics fit | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Feedback | `x-ui.flash-message`, `x-ui.empty-state`, Admin adapters | Flash and empty-state presentation | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Status | `x-ui.badge`, `x-admin.request-status-badge` | Generic indicator vs domain-specific status mapping | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Metadata | `x-ui.metadata-item` | Semantic label/value pair inside a description list | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Identity utility | `x-ui.avatar` | Circular avatar with image/initials fallback | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Media preview | `x-ui.photo-preview` | Shared full-size photo dialog; accepts an avatar or thumbnail trigger and optional download link | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Notification utility | `x-ui.notification-dropdown` | User-scoped notification preview; data is supplied by the caller | [Reusable components](REUSABLE-BLADE-COMPONENTS.md) |
| Attendance composition | `x-attendance.calendar`, `x-attendance.calendar-indicator`, `x-attendance.time-summary`, `x-attendance.info-card`, `x-attendance.photo-preview`, `x-attendance.camera` | Attendance-specific presentation and browser capture. Parent SFC owns page state; camera and photo preview retain separate responsibilities | [Attendance Layout V1](EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) |
| Admin patterns | `x-admin.filter-panel`, `x-admin.page-header`, domain adapters | Repeated Admin composition and domain presentation | [Architecture guide](../ARCHITECTURE-AND-DEVELOPMENT.md) |
| Data presentation | Native table, Flux table, review list, article/card | Match semantics and interaction to the data, not a universal visual wrapper | [Design Foundation](DESIGN-FOUNDATION.md) |

## 3. Component adoption rules

- Reuse an existing component only when its semantics and contract fit the use case.
- Preserve forwarded `wire:*`, `x-*`, accessibility, and native HTML attributes.
- Keep links as links and actions as buttons.
- Keep domain enum-to-label/color mapping outside generic presentation primitives.
- Preserve field names, IDs, values, required/disabled/readonly state, errors, and model bindings.
- Do not create a generic table, employee card, or form abstraction from a single page.
- Do not force every section into a card; avoid card-in-card.
- A wrapper must not swallow attributes or change the actual interactive element's behavior.

## 4. Required component documentation

When a shared component is created or its public contract changes, update the detailed component reference with:

1. Purpose and intended use.
2. Source path.
3. Public props, slots, and forwarded attributes.
4. Variants and sizes.
5. Default, hover, focus-visible, active, disabled, loading, error, and empty states where relevant.
6. Employee mobile and Admin desktop/tablet behavior.
7. Accessibility and semantic HTML expectations.
8. Real SanMal usage example.
9. Focused tests and actual browser QA evidence.
10. Known limitations and non-use cases.

## 5. Current milestone boundary

The 14 core `x-ui.*` primitives have been implemented and adopted on selected pages. Two additional shared `x-ui.*` utilities (`x-ui.avatar`, `x-ui.photo-preview`, and `x-ui.notification-dropdown`) are also implemented; `x-ui.sidebar-section` is a shell-specific navigation composition helper, not a generic primitive. This closes the reusable-component standardization phase; it does not mean all pages are visually finalized or have passed responsive/accessibility QA.

For current evidence and the next page-level design/layouting phase, see [Implementation Status](progress/IMPLEMENTATION-STATUS.md). Do not add generic components without a demonstrated repeated pattern and stable interaction contract.

## 6. Completion criteria

A component milestone is complete only when:

- Its API is verified against source.
- Its focused tests pass, or the unrun status is disclosed.
- Its real usages are identified in [Page Inventory](PAGE-INVENTORY.md).
- Critical states and responsive behavior are checked.
- [Implementation Status](progress/IMPLEMENTATION-STATUS.md) and [Visual QA Log](progress/VISUAL-QA-LOG.md) are updated.
