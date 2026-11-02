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
| — | — | No other run recorded | — | — | — | — | Not run | This is an empty log for other workflows, not a pass. |
