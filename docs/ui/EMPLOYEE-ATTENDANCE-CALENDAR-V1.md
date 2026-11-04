# Employee Attendance Calendar V1

> Scope: weekly date selector and monthly date picker on the Employee Attendance page. The current source implementation is in `x-attendance.calendar` and the parent Employee Attendance Livewire SFC.
> The monthly-grid regression test passed in CI run [#1418](https://github.com/leonsmith128/pt-sanmal/actions/runs/41771398379); the overall workflow still has two other failing assertions. Browser/device QA remains pending. See [Implementation Status](progress/IMPLEMENTATION-STATUS.md) for the complete verification snapshot and [Employee Attendance Layout V1](EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) for page composition.

## 1. Purpose

Provide a compact weekly calendar for daily navigation and a monthly calendar for selecting dates farther away. Both views share the existing Livewire `selectedDate` state and the existing `Asia/Makassar` attendance-date context.

## 2. Weekly calendar

- Seven columns, Monday through Sunday.
- The selected date's month and year are displayed as a text button that opens the monthly picker. Style it as a readable, semibold heading with a subtle chevron, comfortable click target, hover surface, and visible keyboard-focus ring.
- Do not display a separate calendar icon/button or helper description beneath the month heading.
- Previous/next week controls and a **Hari ini** action remain available.
- Each date shows weekday, day number, and a small status dot when one of the three agreed indicator states applies.
- Selected date uses SanMal brand blue in light theme and the approved warm yellow primary accent in dark theme.
- Today receives an outline when it is not selected.
- No status legend is displayed.

## 3. Monthly calendar

- Opens as a centered responsive dialog.
- Shows a six-row, seven-column grid with Monday as the first weekday.
- Previous/next month controls and a close control are available.
- Adjacent-month dates are muted but remain selectable when visible in the grid.
- Selecting a date closes the dialog, updates `selectedDate`, and causes the weekly view and date-dependent attendance content to follow that date.
- Backdrop click and Escape close the dialog.

## 4. Status indicators

| Daily status service result | Calendar indicator | Color |
|---|---|---|
| `present` | Hadir | Green `#54B66F` |
| `needs_review` | Perlu ditinjau | Warning amber `#B2640A` |
| `no_record` | Tidak ada catatan | Subtle neutral gray |
| Other domain statuses | No dot in this three-state indicator set | Not reclassified |

No-record is not equivalent to absence. The calendar does not infer absence from a missing attendance event. Incomplete, leave, sick, permission, and explicit absence classifications remain distinct domain states and are not silently relabelled as one of the three chosen indicators.

The dot remains visible for a selected date. Its color is not replaced by the selected-date background.

## 5. Data and performance

- `DailyAttendanceStatusService::forEmployeesDateRange()` is the source of truth for the daily classification.
- The weekly view requests one seven-day range; the monthly view requests one 42-day visible-grid range.
- Each range is resolved in a batch, rather than querying once per day.
- The page does not create or mutate attendance records to decorate the calendar.
- Existing camera, GPS, evidence upload, attendance submission, overtime, and history workflows remain unchanged.

## 6. Responsive and accessibility rules

- Seven columns remain intact on mobile, tablet, and desktop.
- Date controls have a minimum height of approximately 44 px.
- Each date exposes an accessible label including its date and indicator meaning when present.
- Selected state is exposed with `aria-pressed`.
- Monthly picker uses dialog semantics, supports Escape and backdrop dismissal, and has explicit previous/next/close labels.
- Light and dark themes use the existing SanMal surface, text, focus, and brand conventions.
- No horizontal scrolling and no visible status legend.

## 7. Verification

- Feature coverage verifies monthly dialog state, six-row grid size, month navigation, date selection, and synchronization back to the selected date.
- CI and browser/device verification must be recorded against the current PR head before declaring the calendar fully verified.
- Browser QA checkpoints: 360–390 px, 414–430 px, 768 px, 1024 px, and 1280–1440 px in light and dark themes.
