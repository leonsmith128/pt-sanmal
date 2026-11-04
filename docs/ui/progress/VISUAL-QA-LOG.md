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
