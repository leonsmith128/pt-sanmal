# SanMal UI/UX Visual QA Log

> This log records observed browser/device results. Do not use it as a plan-only checklist or infer results from source inspection.

## 1. How to record a QA run

Create one entry per page/workflow and meaningful browser/viewport combination.

Required fields:

- Date and tester/agent.
- Branch and commit SHA.
- Page/workflow and role.
- Browser/version and device or emulation details.
- Viewport width × height.
- Theme (light/dark), when supported.
- Scenario and state tested.
- Result: Pass / Fail / Blocked / Not run.
- Observed issue and reproduction steps.
- Screenshot/video or issue reference, if available.
- Follow-up commit or resolution reference.

## 2. Viewport checkpoints

| Target | Width | Minimum review focus |
|---|---:|---|
| Small mobile | 360–390 px | Primary actions, field width, text wrapping, overflow |
| Large mobile | 414–430 px | Long labels, card density, action wrapping |
| Tablet portrait | 768 px | Filter layout, navigation, table/list composition |
| Tablet landscape | 1024 px | Dense data, sidebar, multi-column layout |
| Desktop | 1280–1440 px | Admin information hierarchy, table readability |

These are review checkpoints, not mandatory CSS breakpoints. Record actual viewport dimensions.

## 3. Shared state checklist

Check states relevant to the component/page:

- Default and populated.
- Hover where pointer interaction exists.
- Keyboard focus-visible and focus order.
- Active/pressed.
- Disabled and loading/busy.
- Validation error and server error.
- Empty state.
- Long labels, long names, long addresses, and large data values.
- Modal/dialog scrolling and action visibility.
- Light/dark theme if supported.

## 4. Device-specific QA

For attendance camera/GPS changes, record actual supported device/browser checks. Include:

- HTTPS origin and permission state.
- Camera allowed/denied/unavailable.
- GPS ready/denied/timeout/low accuracy.
- Portrait/landscape and safe-area behavior.
- Slow/interrupted network and upload progress/failure.
- Modal scrolling, keyboard overlap, and lifecycle when navigating away.
- Evidence preview and confirmation flow.

Viewport emulation alone does not satisfy device QA.

## 5. Run log

| Date | Commit | Page/workflow | Browser/device | Viewport | Theme | Scenario | Result | Evidence / notes |
|---|---|---|---|---:|---|---|---|---|
| 2026-11-03 | `00d255c1d5384eb312e7842e85954c8ef7c9ca15` | Shared primary button | Not recorded in handoff | Multiple screen sizes; exact dimensions not recorded | Light and dark | Project owner confirmed actual visual review of primary button; appearance passed | **Pass (owner-confirmed)** | Browser/version, exact viewport list, screenshots, and individual interaction states were not included in the confirmation. CI job `ci` passed on this commit: [run 1004](https://github.com/leonsmith128/pt-sanmal/actions/runs/41543440506). |
| 2026-11-03 | `9827fa779f3279c844a5ae471c73d7936c57f709` (follow-up commits after this SHA contain final markup) | Employee notification dropdown and mobile app header | Browser/device not recorded | Not recorded | Light/dark not checked | Popover, unread/read appearance, empty state, mobile duplicate control, and notification navigation | **Not run** | Browser visual QA remains pending. Focused notification dropdown tests passed in CI run #1418; the overall workflow failed on two separate assertions. |
| 2026-11-04 | `91f58f27fca388163ee17007c2ca20e2ddc0433a` | Employee Attendance: calendar, time summary, status cards, form, photo preview | Owner's local browser; browser/version and viewport not recorded | Not recorded | Not checked | Owner reviewed the local page and confirmed the UI is good. This confirms page-level visual acceptance only; camera/GPS/device and detailed responsive checks are not included. | **Pass (owner-confirmed, limited)** | Owner confirmation received in project conversation. CI run #1460 passed; that is separate from browser QA. |
| 2026-11-04 | `7f887da6b44e2e43ea0c9e6ba1eb9764a4b36669` | Employee Dashboard V1 | Not run | Not recorded | Not checked | Hero, monthly metrics, pending requests, quick links, recent activity, and notification control | **Not run** | Source and automated coverage exist. No browser visual QA evidence has been recorded. |
| 2026-11-05 | `fd933654afe205cd845af0ba0885c1bfb3da8d2e` | Employee Profile: focused page width and photo picker target/size, avatar, upload, saved image, and preview dialog | Not run | Not recorded | Not checked | Source now uses a visually-hidden file input and an explicit compact `Pilih file` trigger (`xs`); browser/touch review must confirm scrolling elsewhere in the row does not open the picker, and that the picker button is shorter than `Simpan foto`. Also cover upload validation, persisted image response, avatar, modal, replace/remove, and responsive layout. | **Not run** | Regression assertions added for the hidden input and explicit trigger. CI for this follow-up commit has not yet been verified. No browser/device evidence is available. |
| 2026-11-04 | `091cedd8bd81b9bf9e956125320c456f541e7cd6` | Employee Attendance History | Not run | Not recorded | Not checked | Summary, collapsible date/sort filters (closed by default; draft retained while toggling), date-group pagination, status-only groups, event detail dialog, private photo, GPS/address/map link, export link, keyboard/focus, responsive and dark mode | **Not run** | Source review and CI run #1497 do not establish browser QA. Review at 360–430 px, 768/1024 px, and 1280–1440 px; inspect light/dark and keyboard dialog behavior. |\n| 2026-11-04 | `7f887da6b44e2e43ea0c9e6ba1eb9764a4b36669` | Admin workflows and application shell | Not run | Not recorded | Not checked | Navigation, filters, tables/lists, detail/review screens, and mobile reflow | **Not run** | Source review is not browser QA. No cross-page browser/device evidence has been recorded. |


## Admin Attendance reusable filters — source follow-up (5 November 2026)

The reusable filter UI and shared query/export filter contract were added to PR #27. Automated regression coverage targets combined Client/Location/Project filters, historical Location filtering after a Project Location change, inactive historical options, reversed date-range normalization, and complete daily recap export semantics.

**Browser/device QA: Not run.** Source review and CI do not establish visual or interaction QA. Verify filter wrapping and select interaction at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in supported light/dark themes. Also confirm independent dropdown selections remain visible when their combination returns an empty result.


## Admin Attendance filter-panel layout — source follow-up (5 November 2026)

The filter panel was tightened, redundant page descriptions removed, the reset action moved below the filter controls, and Export retained in the activity toolbar. The existing filter contract and reset semantics are unchanged.

**Browser/device QA: Not run.** Review at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in supported light/dark themes. Confirm the controls remain legible, the reset action stays easy to reach, and the Export dropdown is not clipped. The activity table still requires a separate responsive-layout pass; this change does not claim to resolve horizontal table overflow.


## Admin Attendance responsive table layout — source follow-up (5 November 2026)

The activity table source now has a fixed-width desktop layout and a stacked card-like row presentation below the large-screen breakpoint. All existing data groups and row actions remain represented.

**Browser/device QA: Not run.** Verify at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in supported themes. Specifically inspect whether the stacked rows eliminate horizontal overflow; whether long employee names, addresses, and project snapshots wrap correctly; and whether pagination, photo preview, map links, project context, and timestamp correction remain usable. Automated regression tests do not replace this review.

The mobile filter disclosure was added to the source. Browser QA must confirm that it starts collapsed on mobile, opens/closes by keyboard and touch, reflects the active-filter count, and remains expanded appropriately while users change Livewire-bound values. Desktop filter controls must remain visible without requiring a click.

## Admin Attendance layout refinement — 5 November 2026

Source adjustments: inset the table from the card edges; align the Export label and chevron horizontally; make the filter panel collapsible at all breakpoints while keeping the active-filter count in its always-visible header; use five desktop columns for the nine filter controls; keep Reset filter available outside the collapsed region.

**Browser QA: Pending.** Check the table inset against card edges, export trigger alignment, filter collapse/expand and active count, reset availability while collapsed, and whether the nine controls fit into exactly two rows at the actual desktop viewport width.

## Admin master data and review workflows — source follow-up (5 November 2026)

Source commit: `ffc5566bedfb97adfbec7eca44b6cb1da198067d`.

Scope: Client list/detail and Site sub-grid; Client/Location/Project/Employee forms; Project cards; Employee hybrid card/table; Leave Request quick status links and mobile cards/desktop table; Attendance Correction detail; Overtime queue/detail/finalization; Audit Log; daily attendance employee card tokens.

**Automated coverage:** assertions were added or extended for Client and Site responsive grids, shared card surfaces, three-column Project cards, Employee card/table preservation, and the responsive Leave Request queue. CI result for this commit is pending and must be checked before merge.

**Browser/device QA: Not run.** Review at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light and dark themes. Verify:
- Client cards and nested Site grids do not become cramped; long Client/Site names and addresses wrap without pushing actions off-screen.
- Project cards retain readable metadata and expandable details in one/two/three-column layouts.
- Create/edit forms remain readable, validation messages stay adjacent to their fields, and submit/cancel actions remain reachable on mobile.
- Employee mobile cards and the xl desktop table do not render simultaneously at the wrong breakpoint; the table's horizontal scroll remains scoped and keyboard accessible.
- Leave Request status links preserve their selected state, mobile cards show the same essential information as the desktop table, and pagination remains consistent.
- Correction/overtime detail and decision/finalization controls remain usable with long notes and validation errors.
- Audit Log horizontal scrolling remains inside its labeled, focusable region.
- Empty/loading/error states, focus-visible, touch target sizes, and layout stability remain acceptable in both themes.

Source review and automated tests do not count as browser/device visual QA. Record actual browser/device, viewport, theme, scenario, result, and evidence before marking this scope visually verified.


## Admin Overtime queue responsive layout — source follow-up (5 November 2026)

Source commit: `45b36286cc95b99185b114ff29f5a9e76c25d8c1`.

The queue now renders compact cards below `md` and a semantic table from `md` upward. Both views show approval status and operational state separately.

**Automated coverage:** `OvertimeWorkflowTest` asserts the responsive wrappers, employee, reason, both status labels, and detail route. CI result is pending.

**Browser/device QA: Not run.** Check 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light/dark themes. Confirm cards remain scannable, desktop columns do not become cramped, the horizontal scroll stays inside the table region, status badges remain distinct, and pagination remains reachable.


## Employee workflow token alignment — source follow-up (5 November 2026)

Source commit: `f38a551befc48ef6626c7108dde1711d19a6d002`.

The employee Attendance supporting metadata, Employee Profile upload feedback, Employee Overtime history, Notifications, Attendance Correction form, Leave Request form, and Location creation context now use SanMal text, surface, and divider tokens. Camera/GPS behavior and request contracts were not changed. Employee Attendance History was intentionally left untouched as a completed V1 scope.

**Automated coverage:** existing feature tests remain the regression coverage for the affected workflows. CI run #41981290455 passed on the preceding test-fix commit and covered Pint, static analysis, MariaDB, and SQLite. CI for this later token-only source commit is queued and must be checked before merge.

**Browser/device QA: Not run.** Check the affected pages at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light/dark themes. Pay particular attention to Attendance supporting panels and long evidence text, mobile form action separators, notification text wrapping, and Location context surfaces. Confirm camera/GPS interactions remain unchanged through manual smoke testing; source-only token changes do not establish device compatibility.


## Admin Leave Request and Overtime table scroll accessibility — source follow-up (5 November 2026)

Source commit: `f9bcc443d77370182fa0aa1dfe471b40d05337bb`.

The desktop/table presentations now expose the horizontal-scroll container as a named, keyboard-focusable region. The table content and review workflow are unchanged.

**Automated coverage:** feature assertions check the localized region labels. CI run #41981528715 passed for the preceding token-alignment commit; CI for this final accessibility change is pending.

**Browser/device QA: Not run.** Verify that keyboard focus can reach the region, arrow/trackpad scrolling behaves normally, screen-reader labels are meaningful, and the scroll container does not create document-level horizontal overflow at 768 px, 1024 px, and 1280–1440 px.
