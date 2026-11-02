# SanMal UI/UX Development Handoff

> **Purpose:** operational entry point for AI coding agents and developers joining the SanMal V1 UI/UX program.
> **Current delivery:** one long-lived branch and one tracking PR; do not merge until V1 UI/UX is complete.

## 1. Start here

Read documents in this order:

1. [Repository README](../../README.md) — application purpose and technology overview.
2. [Project status](../PROJECT-STATUS.md) — repository milestones, known risks, and current project status.
3. [AI agent rules](../../AGENTS.md) — mandatory repository constraints.
4. [Architecture and development](../ARCHITECTURE-AND-DEVELOPMENT.md) — Laravel, Blade, Livewire, routes, domain, tests.
5. [Design foundation](DESIGN-FOUNDATION.md) — brand, design principles, responsive priorities, component standards.
6. [Page inventory](PAGE-INVENTORY.md) — in-scope pages, audit notes, and source areas to inspect.
7. [Component catalog](COMPONENT-CATALOG.md) — existing shared UI building blocks.
8. [Implementation status](progress/IMPLEMENTATION-STATUS.md) — current milestone state.
9. [Visual QA log](progress/VISUAL-QA-LOG.md) — evidence of actual browser/device checks.

Do not read every historical audit before starting. Use the page inventory to open only the relevant audit and source.

## 2. Product and role context

SanMal is an internal attendance and employee administration application for PT SanMal Calibration Laboratory. It is not a marketing website.

- **Employee:** mobile-first. Prioritize attendance, camera/GPS permissions and evidence, requests, history, and clear touch interactions.
- **Admin:** desktop/tablet information-first, but mobile-friendly. Prioritize readable operational data, filters, tables/lists, review details, and efficient administration.
- **Shared principle:** seamless-first. Roles share visual language and component behavior, not necessarily identical page composition.

See [Design Foundation](DESIGN-FOUNDATION.md) for the authoritative visual rules.

## 3. Scope and non-goals

### Included in V1 UI/UX

- Shared components and their real usage across pages.
- Employee and Admin page layout, hierarchy, density, and responsive behavior.
- Forms, feedback, statuses, tables/lists/cards, navigation, and critical interaction states.
- Accessibility, keyboard/touch behavior, visual regression, and responsive QA.
- Documentation needed to maintain the system.

### Not included without explicit approval

- New product features or workflow capabilities.
- Changes to domain rules, attendance calculations, timestamps, approval logic, persistence, authorization, or validation.
- Changes to camera/GPS/evidence capture behavior disguised as visual cleanup.
- Framework migrations or broad refactors solely for stylistic consistency.

If a visual improvement cannot be delivered without changing a protected behavior or contract, stop and explain the dependency before implementation.

## 4. Git workflow — mandatory

| Item | Required value |
|---|---|
| Repository | `leonsmith128/pt-sanmal` |
| Working branch | `docs/ui-v1-scope-and-pr-workflow` |
| Tracking PR | [#27](https://github.com/leonsmith128/pt-sanmal/pull/27) |
| PR target | `main` |
| Merge policy | One merge only, after the entire V1 UI/UX scope is complete |

Rules:

1. Never create another branch or PR for work within this UI/UX program.
2. Never commit UI/UX work directly to `main`.
3. Do not merge PR #27 early, even if an individual milestone is complete.
4. Use focused commits on the existing branch.
5. Before every write, inspect latest branch HEAD and current file SHA. Avoid stale writes.
6. Update PR #27 description/checklist when a milestone changes state.
7. If the branch has diverged or a newer commit appeared, fetch again and reconcile; do not force-push or rewrite history.

## 5. Required workflow for each task

### Before coding

- [ ] Confirm current branch and latest commit.
- [ ] Read the relevant entry in [Page Inventory](PAGE-INVENTORY.md).
- [ ] Read the linked audit and current component documentation.
- [ ] Inspect the actual Blade/SFC/Controller/route and related tests on the working branch.
- [ ] Record the existing user-visible behavior and contracts that must remain unchanged.
- [ ] Identify target roles, viewports, critical states, and acceptance criteria.

### During implementation

- [ ] Prefer existing primitives when their semantics and API fit.
- [ ] Keep presentation components free of business/domain decisions.
- [ ] Preserve route, HTTP method, authorization, validation, query/filter semantics, sorting, pagination, export behavior, and Livewire property/action/event contracts.
- [ ] Preserve Alpine/browser lifecycle, camera/GPS, evidence, upload, and attendance workflows unless separately approved.
- [ ] Avoid broad refactors and unrelated formatting changes.
- [ ] Keep labels, accessible names, semantic HTML, focus behavior, and touch targets usable.

### Before marking complete

- [ ] Run focused component/feature tests.
- [ ] Run relevant broader tests when the change has cross-page impact.
- [ ] Run Pint for changed PHP/Blade-related files as appropriate.
- [ ] Build frontend assets when CSS/JS changes require it.
- [ ] Inspect browser rendering at relevant mobile/tablet/desktop checkpoints.
- [ ] Check default, hover, focus-visible, active, disabled, loading, error, empty, and long-content states as relevant.
- [ ] Check light/dark themes if supported by the affected page.
- [ ] Record actual results and limitations in progress/QA documents.
- [ ] Commit to the existing branch and update PR #27.

A source inspection is not a browser test. A passing component test is not proof of a complete page workflow. A desktop screenshot is not proof of mobile behavior. Do not claim checks that were not actually performed.

## 6. Responsive QA checkpoints

Use content-driven breakpoints and the existing Tailwind conventions. These are review widths, not a requirement to introduce custom breakpoints.

| Target | Width | Typical focus |
|---|---:|---|
| Small mobile | 360–390 px | Employee primary flows, form controls, wrapping |
| Large mobile | 414–430 px | Long labels, cards, buttons, metadata |
| Tablet portrait | 768 px | Admin filters, navigation, table/list decisions |
| Tablet landscape | 1024 px | Dense admin content, sidebar and panels |
| Desktop | 1280–1440 px | Admin information hierarchy and tables |

For camera/GPS attendance, viewport simulation is insufficient. Device QA must cover real supported browsers, permission states, HTTPS, orientation, network conditions, and upload where relevant.

## 7. Page-level acceptance criteria

A page is ready only when all applicable points are true:

- The primary task and page hierarchy are obvious.
- Information needed to make a decision is visible and understandable.
- The layout follows the role's responsive strategy.
- No unintended horizontal page overflow or clipped controls exists.
- Forms retain labels, errors, values, and correct control semantics.
- Tables/lists preserve meaningful relationships, headings, sorting, pagination, and actions.
- Empty, loading, success, error, and disabled states are understandable.
- Keyboard focus and touch interaction remain usable.
- Existing route, authorization, validation, data, and workflow contracts are unchanged unless explicitly approved.
- Relevant automated and visual checks have evidence recorded.

## 8. Component-level acceptance criteria

For every shared component, document:

- Purpose and when to use it.
- Actual source path and public Blade API/attributes.
- Variants, sizes, and semantic constraints.
- Interaction states and accessibility expectations.
- Employee and Admin responsive behavior.
- At least one real SanMal usage example.
- Focused tests and browser verification evidence.
- Known limitations or cases where the component should not be used.

Do not invent a component API from this handoff. Verify the current implementation and tests.

## 9. Protected behavior checklist

Treat the following as high-risk contracts during UI work:

- Role-based route access and server-side authorization.
- Form field names, IDs, required state, validation errors, old/model values, and HTTP methods.
- Livewire public properties, actions, events, lifecycle hooks, and bindings.
- Query parameters, filters, sorting, pagination, export URLs, and download permissions.
- Attendance sessions, original evidence timestamps, effective corrected timestamps, and historical snapshots.
- Camera permission, stream lifecycle, GPS readiness/accuracy, selfie evidence, watermark, upload, and Clock In/Clock Out.
- Overtime approval status versus operational state.
- Leave/request and attendance correction submission/approval/cancellation transitions.

When in doubt, inspect the domain tests and implementation; do not infer behavior from labels or visual state.

## 10. Documentation maintenance

- Design decisions and visual rules → [Design Foundation](DESIGN-FOUNDATION.md).
- Page scope and audit pointers → [Page Inventory](PAGE-INVENTORY.md).
- Component usage/API pointers → [Component Catalog](COMPONENT-CATALOG.md) and [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md).
- Current milestone state → [Implementation Status](progress/IMPLEMENTATION-STATUS.md).
- Browser/device evidence → [Visual QA Log](progress/VISUAL-QA-LOG.md).
- Repository-wide project and production status → [Project Status](../PROJECT-STATUS.md).

Update the narrowest relevant document when a decision or implementation state changes. Avoid duplicating the same detailed guidance in multiple files; link to the authoritative source instead.

## 11. Definition of ready for V1 merge

Do not mark V1 complete until:

- All in-scope Employee, Admin, shared, and authentication pages have been reviewed.
- Shared components have been checked in real page contexts.
- Required responsive, interaction, accessibility, and visual QA has been completed or explicitly accepted as a known limitation by the project owner.
- Relevant automated tests, formatting, and asset builds have been run.
- Critical camera/GPS/device flows have been tested on supported real devices where affected.
- The PR checklist, implementation status, and QA log agree.
- No unresolved high-severity regression or undocumented scope gap remains.
- The final PR review confirms all changes are within the agreed V1 scope.
