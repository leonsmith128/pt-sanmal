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

**Automated coverage:** assertions were added or extended for Client and Site responsive grids, shared card surfaces, three-column Project cards, Employee card/table preservation, and the responsive Leave Request queue. CI run [#41981719951](https://github.com/leonsmith128/pt-sanmal/actions/runs/41981719951) passed on the latest source commit `f9bcc443d77370182fa0aa1dfe471b40d05337bb`.

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

**Automated coverage:** `OvertimeWorkflowTest` asserts the responsive wrappers, employee, reason, both status labels, and detail route. CI run [#41981719951](https://github.com/leonsmith128/pt-sanmal/actions/runs/41981719951) passed on the latest source commit `f9bcc443d77370182fa0aa1dfe471b40d05337bb`.

**Browser/device QA: Not run.** Check 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light/dark themes. Confirm cards remain scannable, desktop columns do not become cramped, the horizontal scroll stays inside the table region, status badges remain distinct, and pagination remains reachable.


## Employee workflow token alignment — source follow-up (5 November 2026)

Source commit: `f38a551befc48ef6626c7108dde1711d19a6d002`.

The employee Attendance supporting metadata, Employee Profile upload feedback, Employee Overtime history, Notifications, Attendance Correction form, Leave Request form, and Location creation context now use SanMal text, surface, and divider tokens. Camera/GPS behavior and request contracts were not changed. Employee Attendance History was intentionally left untouched as a completed V1 scope.

**Automated coverage:** existing feature tests remain the regression coverage for the affected workflows. CI run [#41981719951](https://github.com/leonsmith128/pt-sanmal/actions/runs/41981719951) passed on the latest source commit and covered Pint, static analysis, MariaDB, and SQLite.

**Browser/device QA: Not run.** Check the affected pages at 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light/dark themes. Pay particular attention to Attendance supporting panels and long evidence text, mobile form action separators, notification text wrapping, and Location context surfaces. Confirm camera/GPS interactions remain unchanged through manual smoke testing; source-only token changes do not establish device compatibility.


## Admin Leave Request and Overtime table scroll accessibility — source follow-up (5 November 2026)

Source commit: `f9bcc443d77370182fa0aa1dfe471b40d05337bb`.

The desktop/table presentations now expose the horizontal-scroll container as a named, keyboard-focusable region. The table content and review workflow are unchanged.

**Automated coverage:** feature assertions check the localized region labels. CI run [#41981719951](https://github.com/leonsmith128/pt-sanmal/actions/runs/41981719951) passed for this accessibility change, including Pint, static analysis, MariaDB, and SQLite.

**Browser/device QA: Not run.** Verify that keyboard focus can reach the region, arrow/trackpad scrolling behaves normally, screen-reader labels are meaningful, and the scroll container does not create document-level horizontal overflow at 768 px, 1024 px, and 1280–1440 px.


## Admin Overtime status filter pills — 5 November 2026

Source commit: `20d4e1bec22a55595c8efbc965a107a3f984190f`.

The Admin Overtime approval-status dropdown was replaced with the same pill-link pattern used by Employee Permohonan. Verify all four states (All, Pending, Approved, Rejected), active/inactive styling in light/dark themes, keyboard focus, query preservation, and immediate filtering at mobile and desktop widths.

**Automated coverage:** `OvertimeWorkflowTest` checks status link URLs and confirms the old select is absent. CI run #41984069098 is queued; check its result before merge.

**Browser/device QA: Not run.**


## F0 baseline — production readiness (`feature/ui-production-readiness`, PR #30) — 7 November 2026

Branch HEAD when written: `f3ea581f13f09e5efc6e91c3eb0e5a45111f6eeb`. Tester/agent: Claude, working through the GitHub connector (no browser, no device, no local build).

**No P0 check below has been run in a browser, on a device, or against the target server `.env`.** Every result is **Not run**. The *Source facts* column lists what was read in the repository at the commit above, so the owner knows what to look for. It is not a QA result and does not close any P0 item.

| ID | Check | Result | Source facts read (not QA evidence) | Still needed from the owner |
|---|---|---|---|---|
| P0-1 | Horizontal gutter at 360 px (`/dashboard`, `/admin/pegawai`, light and dark) | **Not run** | `layouts/app.blade.php` renders `<flux:main class="min-w-0 … p-0 … lg:ms-0">`. `components/ui/page.blade.php` renders `mx-auto flex w-full max-w-* flex-col gap-*` with no horizontal padding. No gutter source was found in these two files. Flux's own main/sidebar styles are in `vendor/` and were not read. | Measure the left distance from the screen edge to the H1 and first card. Result decides D9 (add a single gutter wrapper or document the existing source). |
| P0-2 | Locale and app name on the target server | **Not run** | `.env.example`: `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`, `APP_NAME=Laravel`, `APP_DEBUG=true`. `layouts/app/sidebar.blade.php` still has English `__()` keys: `Change appearance`, `Light`, `Dark`, `System`, `Log out`. The target `.env` was not seen. | Read the server `.env`. Confirm `APP_LOCALE=id`, `APP_DEBUG=false`, and that Carbon formats months in Indonesian. Result decides D6, 7.3, and section 10. |
| P0-3 | Theme with `class="dark"` removed (empty localStorage, OS light then dark, flash check) | **Not run** | `class="dark"` is hard-coded on `<html>` in four layouts: `layouts/app/sidebar`, `layouts/auth/simple`, `layouts/auth/split`, `layouts/auth/card`. `@fluxAppearance` in `partials/head` was not re-read in this run. | Remove the class locally, test the three conditions, and record any flash of the wrong theme. |
| P0-4 | `npm run build`, font files, third-party requests | **Not run** | `vite.config.js` loads `bunny('Instrument Sans', { weights: [400, 500, 600] })` through `laravel-vite-plugin/fonts`. `.gitignore` excludes `/public/build` and `/public/fonts-manifest.dev.json`, so the build output must be uploaded by hand. | Run the build, confirm `@font-face` files exist under `public/build/assets`, and check DevTools for requests to other domains. |
| P0-5 | Contrast of Flux parts not controlled by tokens (zinc badge, select dropdown, sidebar item, `danger` button) | **Not run** | None. Colours come from Flux's internal classes. | Measure in the browser in both themes. |
| P0-6 | Tests that assert CSS classes or markup | **Not run** (list below came from GitHub code search, so it may be incomplete) | `AdminEmployeePageTest`: `lg:grid-cols-2 xl:hidden`, `bg-sanmal-surface-card`, `overflow-x-auto overscroll-x-contain`, `role="region"`. `UiCardComponentTest`: `rounded-xl bg-sanmal-surface-card text-[var(--sanmal-text-primary)] shadow-sm`, `dark:bg-sanmal-surface-card-dark dark:shadow-black/20`, `shadow-sm transition-shadow`, `hover:shadow-md` (these will change with D1 in F1). `LeaveRequestWorkflowTest` and `OvertimeWorkflowTest`: `md:hidden`, `hidden overflow-hidden md:block`, plus the Indonesian region labels. Not searched: `RequestCenterTest`, `UiFeedbackComponentsTest`, `UiLayoutComponentsTest`, `DashboardTest`, `AdminAuditLog*`. | Run a local `grep` over `tests/` for class strings and finish the list before F1. |
| P0-7 | Session expiry on the attendance page (Livewire 419 dialog) | **Not run** | `.env.example`: `SESSION_LIFETIME=120`. The attendance SFC was not read. | Leave the page open past the limit, submit, and record the dialog text and language. |
| P0-8 | Camera and GPS on real Android and iOS over HTTPS | **Not run** | None. | Device test per section 4. |
| P0-9 | Baseline screenshots of the 12 priority pages at 360, 768, 1280, light and dark | **Not run** | None. | Capture and attach to the PR or keep outside the repo. |

**Other source observations (not QA results):**

- Repository code search for `color-sanmal-text` returned no matches, which agrees with D7 (the classes `text-sanmal-text-*` are used in views but not defined in `@theme`). Confirm with a local `grep -rn` before F1.
- `/AGENTS.md` is listed in `.gitignore` although it is tracked and edited in this PR. Edits to an already-tracked file still show up, but a fresh `git add` of a new copy would be ignored. Worth a decision from the owner.
- The `Attendance History` row in section 5 contains a literal `\n` where a table row break was intended, so the following `Admin workflows and application shell` row is on the same line. This existed before this run and was left as is.

**F0 status:** documentation amendments (D1 to D8, D10) are in this branch. F0 is **not complete**: P0-1 to P0-9 still need browser, device, or server evidence, and the P0-1 and P0-2 decisions cannot be recorded until then. F1 should not start before that.
