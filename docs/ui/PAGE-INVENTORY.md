# SanMal V1 UI/UX Page Inventory

> **Purpose:** scope index for UI/UX implementation and review.
> **Source:** the existing [P1-D3 expanded page audit](P1-D3-expanded-page-audit.md) and [Admin pages audit](ADMIN-PAGES-AUDIT.md).
> **Important:** this inventory preserves the audit's page grouping and recommendations. It is not proof that every page has been re-inspected on the current branch. Before implementation, verify current routes and source paths.

## How to use this inventory

1. Select the page or shared shell being changed.
2. Open the linked audit and inspect the actual source on the working branch.
3. Confirm whether the audit recommendation is still relevant after later implementation.
4. Update [Implementation Status](progress/IMPLEMENTATION-STATUS.md) and [Visual QA Log](progress/VISUAL-QA-LOG.md) with evidence.

## A. Admin

| Page/workflow | Existing presentation recorded by audit | Review focus | Audit reference |
|---|---|---|---|
| Dashboard | Statistics, action cards, recent attendance activity | Density, section rhythm, wrapping on tablet/mobile | [P1-D3](P1-D3-expanded-page-audit.md) |
| Client list | Client cards, nested Site list, Office list | Card/list hierarchy, spacing, actions | [P1-D3](P1-D3-expanded-page-audit.md) |
| Client detail | Client summary and Site list | Width consistency, navigation, list density | [P1-D3](P1-D3-expanded-page-audit.md) |
| Client create/edit | Master-data forms | Form width, labels, errors, action hierarchy | [P1-D3](P1-D3-expanded-page-audit.md) |
| Location create/edit | Conditional Office/Site form | Conditional explanation, coordinate/timezone fields, mobile errors | [P1-D3](P1-D3-expanded-page-audit.md) |
| Project list | Expandable cards | Mobile/tablet grid, long names, metadata | [P1-D3](P1-D3-expanded-page-audit.md), [P1-D2](P1-D2-project-expandable-card.md) |
| Project create/edit | Shared form partial | Field order, locked Client on edit, Site selection | [P1-D3](P1-D3-expanded-page-audit.md) |
| Employee list | Five-column table in baseline audit | Hybrid table/card strategy, long names/emails, status and edit action | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Employee create/edit | Separate forms | Field grouping, account/password controls, validation | [P1-D3](P1-D3-expanded-page-audit.md) |
| Attendance correction queue | Review list/cards | Wrapping, correction type, timestamps, status, click target | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Attendance correction detail | Original/effective timestamps, evidence, decision form | Comparison clarity, attachments, warnings, decision form | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Leave request queue | Six-column table with status filter in baseline audit | Mobile alternative, filters, pagination, status and detail navigation | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Leave request detail | Request detail, decision form, confirmation modal | Reading order, modal on small screens, action access | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Overtime request queue | Review list/cards | Reason wrapping, schedule, approval status vs operational state | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Overtime request detail | Status sections, final time range, decision/finalization forms | Long-page grouping, datetime controls, schedule/actual/final distinction | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Audit log | Five-column chronological table | Scoped horizontal scroll, density, long object/action values | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Daily attendance recap | Employee cards with events, status, review and correction form | Dense event groups, embedded form, POST/CSRF/error behavior | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |
| Attendance monitoring | Dense seven-column table with filters, sorting, pagination, evidence, map, export | Information density and table usability; preserve every operational action | [P1-D3](P1-D3-expanded-page-audit.md), [Admin audit](ADMIN-PAGES-AUDIT.md) |

## B. Employee

| Page/workflow | Existing presentation recorded by audit | Review focus | Audit reference |
|---|---|---|---|
| Attendance / Clock In-Out | Complex Livewire page with camera, GPS, location/project selection | Highest mobile priority; permission, capture readiness, upload, error/fallback, confirmation | [P1-D3](P1-D3-expanded-page-audit.md) |
| Employee profile | Dedicated profile page | Information hierarchy, empty/long values, responsive sections | [P1-D3](P1-D3-expanded-page-audit.md) |
| Attendance history | History and evidence view | Filters, events, snapshots, original/effective timestamps, evidence, pagination/export | [P1-D3](P1-D3-expanded-page-audit.md) |
| Attendance correction submission | Correction request form | Record/timestamp selection, reason, attachment, validation on mobile | [P1-D3](P1-D3-expanded-page-audit.md) |
| Leave/request list | Employee request list | Status, period, summary, empty state, detail navigation | [P1-D3](P1-D3-expanded-page-audit.md) |
| Overtime list | Employee overtime requests | Schedule summary, status, modal/form actions | [P1-D3](P1-D3-expanded-page-audit.md) |

## C. Shared and supporting pages

| Page/workflow | Existing presentation recorded by audit | Review focus | Audit reference |
|---|---|---|---|
| Notifications | Notification article/list | Read/unread/resolved hierarchy, long messages, actions, empty state | [P1-D3](P1-D3-expanded-page-audit.md) |
| Account security | Security settings | Password/session forms, feedback, responsive form width | [P1-D3](P1-D3-expanded-page-audit.md) |
| Authentication | Login, forgot/reset password, email verification, password confirmation | Consistent auth states, field spacing/errors, mobile fallback | [P1-D3](P1-D3-expanded-page-audit.md) |
| Application shell/navigation | Header, Admin sidebar, Employee navigation | Active state, notification badge, overflow, mobile/desktop behavior | [P1-D3](P1-D3-expanded-page-audit.md) |

## D. Shared component families

Track these alongside pages; a component is not complete until its real usage contexts are checked.

- Button and action hierarchy.
- Form field, input, select, textarea, checkbox, date/search controls.
- Badge and domain status adapters.
- Page/layout/header/section/surface/card.
- Flash, alert, empty, loading, and confirmation feedback.
- Metadata and detail presentation.
- Tables, review lists, cards, filters, and pagination.
- Admin sidebar and Employee navigation.

See [Component Catalog](COMPONENT-CATALOG.md).

## E. Priority notes from the source audit

The original P1-D3 audit identifies the Employee attendance page as the highest-priority mobile UX workflow and Admin Attendance as a high-priority dense data page. Preserve these as audit-derived priorities, but re-evaluate against current source and project owner direction before changing scope.

The audit also records camera/GPS, attendance evidence, timestamp correction, and approval workflows as sensitive behavior. Visual work must not alter them without explicit approval and separate testing.

## F. Inventory maintenance

When a page is added, removed, or renamed:

1. Update this table and link the relevant source/audit.
2. Verify the route and view path in the current branch.
3. Update implementation status.
4. Record viewport and critical-state QA separately.
5. Keep historical audit notes intact unless a clearly labeled correction/superseding note is added.
