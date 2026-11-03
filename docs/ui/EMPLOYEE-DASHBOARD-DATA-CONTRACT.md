# Employee Dashboard Data Contract

> Status: domain policy approved; the attendance write-path resolver is implemented but not yet verified. Dashboard aggregation and date-boundary behavior remain incomplete.

## Purpose and consistency

Employee Dashboard and Admin Dashboard must use the same domain definitions for daily attendance status, work duration, and approved overtime. Their scope differs only:

- Employee Dashboard: the authenticated employee only.
- Admin Dashboard: the employee set permitted by the existing admin dashboard and authorization rules.

Do not duplicate business calculations in Blade. Put shared aggregation in a dedicated service/query layer and expose presentation-ready values to both dashboards where practical. Reuse `DailyAttendanceStatusService` for daily classifications.

## Attendance timestamp and timezone policy

This policy is the approved domain decision for attendance recording and dashboard date interpretation.

### Authoritative time

- The server-generated timestamp is the authoritative event time. Persist it as an absolute UTC instant in `recorded_at`; never trust the device clock as the official attendance time.
- The timezone is used to derive local wall-clock display and `attendance_date`; it does not change the underlying instant.
- Preserve the timezone snapshot used for each attendance event. Historical attendance must not be reinterpreted using a later employee, browser, project, or location setting.
- Device-reported time and device timezone may be retained only as diagnostic evidence; they are never authoritative.

### Timezone selection

- **Office:** always use the company timezone `Asia/Makassar` (WITA). Ignore the office location's configurable timezone and the device timezone for attendance calculation.
- **Onsite / field duty:** resolve an IANA timezone from the submitted GPS coordinates using a server-trusted coordinate-to-timezone resolver. Do not use the browser/device timezone and do not assume the project's configured location timezone proves the employee's current timezone.
- Only coordinates that pass the existing GPS validity/accuracy policy may be used for timezone resolution. A coordinate rejected by that policy is not a verified location and must not produce a verified timezone.
- Validate every resolved identifier against PHP's supported IANA timezone identifiers before use. Never accept an arbitrary timezone string supplied by the client.
- If GPS is unavailable/invalid or the resolver cannot determine a timezone, attendance may continue under the existing GPS fallback policy: use `Asia/Makassar` as an explicitly unverified fallback, preserve available GPS accuracy/evidence, and flag the attendance for admin review. Do not label the fallback as GPS-verified.
- The coordinate-to-timezone implementation must not silently depend on the browser clock or leak exact GPS coordinates to a third-party service without an explicit product/privacy decision. Prefer a server-side resolver backed by maintained timezone-boundary data; document its data source and update process.

### Resolver implementation and data provenance

- SanMal uses `aldesrahim/laravel-timezone-indonesia` pinned by `composer.lock` to `v1.1.0`. It resolves coordinates locally from bundled Indonesian timezone polygons; attendance GPS coordinates are not sent to a timezone API.
- The package is accessed only through `App\Services\AttendanceTimezoneResolver`. Application actions must not call the package facade directly.
- The resolver returns only supported IANA identifiers. The current bundled boundary dataset reports upstream version `2026c`.
- Persist `timezone_source` on every new attendance event: `office_fixed`, `gps_resolved`, `fallback`, or `session_anchor`. Existing rows retain `legacy`.
- An onsite event using the `fallback` source uses `Asia/Makassar` only as an explicitly unverified fallback. The daily status is marked `needs_review`; the fallback must never be presented as GPS-verified.
- The package code is MIT licensed. Its bundled boundary data is ODbL 1.0 and derived from timezone-boundary-builder / OpenStreetMap. Preserve the attribution in [Third-party notices](../../THIRD-PARTY-NOTICES.md).
- The package has low public adoption, so keep it behind the SanMal resolver service and retain coordinate fixtures for all four zones, outside-Indonesia coordinates, invalid coordinates, and boundary-sensitive cases. Review package releases and boundary-data provenance before upgrades.
- Update through a reviewed Composer version change; do not download boundary data during attendance requests. The optional compile command is a deployment optimization and is not required for correctness.

### Session and reporting rules

- **Clock In is the sole anchor for a session's attendance date, calendar month, and timezone.** Clock Out and all other events attached to that session remain assigned to the Clock In anchor, even when they occur after midnight, cross a month boundary, or later GPS/device data reports another timezone.
- A new Clock In resolves its timezone independently. A person can therefore have sessions in different timezones over time; never use the latest session timezone to reinterpret an earlier session.
- Attribute cross-midnight sessions and their overtime to the Clock In anchor's local `attendance_date` / overtime `session_date`; do not split a session at UTC midnight, local midnight, or a later event's date.
- Evaluate whether a session belongs to the current month using the current calendar month in that session's stored Clock In timezone. A session in Jakarta may therefore belong to October while a separate session in Makassar belongs to November at the same absolute instant near a month boundary.
- The dashboard may use the latest session timezone to present its current-day header, but this display context must not change the date/month attribution of any session.
- Admin date filters and dashboard summaries must have an explicit company-date policy. Until that policy is implemented consistently, do not claim employee/admin parity for dates around local/UTC midnight.
- Approved timestamp corrections may change effective timestamps and effective local dates according to the correction workflow, but must not overwrite original UTC evidence or the stored timezone snapshot.

### Required timezone coverage

- Office ignores a location's non-WITA timezone and device timezone.
- Onsite resolves WIB, WITA, and WIT from valid coordinates, including locations near timezone boundaries.
- Invalid/missing GPS never yields a verified timezone; fallback is marked for review.
- Resolver unknown result, malformed identifier, and resolver failure are handled without blocking a valid photo-backed attendance event.
- Clock Out inherits the Clock In timezone even if its submitted GPS coordinates are in another timezone.
- A new session can use a different timezone from the previous session.
- Cross-midnight and month-boundary sessions, approved corrections, dashboard aggregation, admin date filtering, history, and exports remain consistent with the session timezone snapshot.

## Period and timezone

- Default period: current calendar month, from its first date through today (not future dates).
- Include every calendar date. Do not exclude weekends or holidays.
- Resolve each session's local date and its month attribution using that session's stored Clock In timezone snapshot. Do not infer one employee-wide timezone from the latest attendance record.
- Determine current-month membership independently for each session: compare the effective Clock In anchor date (`effectiveAttendanceDate()`) with the current month and current date in that anchor's timezone.
- Attribute an attendance session and its overtime to the effective Clock In anchor date / overtime `session_date`, not the date on which a cross-midnight Clock Out occurs.
- A session anchored in a month remains attributed to that month even when it ends after midnight or after the calendar month changes. Do not split one session across two monthly totals.

## Daily status counters

Use `DailyAttendanceStatusService::forEmployeesDateRange()` for the employee and month range. Do not infer attendance from raw Clock In counts.

- **Present days:** count dates whose resolved status is `present`. This includes an explicit administrative daily-status correction to `present`, even if that correction does not create attendance events.
- **Incomplete days:** count dates whose resolved status is `incomplete`.
- Keep `needs_review`, `no_record`, `leave`, `sick`, and `permission` as distinct statuses. They are not silently folded into present or incomplete.
- In particular, `no_record` is not equivalent to `absent`. Do not create an absence count from it.
- A date with an explicit daily-status correction follows that correction as the status source, consistent with the service.

## Work duration

The metric **Total Jam Kerja** means net non-overtime duration. Approved overtime is shown separately and must not be counted again as ordinary work duration.

For each unambiguous attendance session anchored in the selected month:

1. Start at the Clock In event's `effectiveRecordedAt()`.
2. For a closed session, end at its paired Clock Out event's `effectiveRecordedAt()`.
3. For an open session, end at the current time in the employee's attendance timezone. This is a provisional, continuously changing duration.
4. Subtract the overlap between that attendance interval and eligible approved-overtime intervals. Clamp the subtraction to the session interval and never allow a negative duration.
5. Sum valid session durations and present a live/open portion as provisional in the UI. Recalculate on each page refresh/request using current effective data; do not persist a cached monthly total as authoritative.

Safety and ambiguity rules:

- Pair events through `session_anchor_id` / session relationships, not merely by sorting all events for a calendar date.
- Preserve the existing cross-midnight session anchor behavior.
- Use effective timestamps after approved attendance corrections. Never mutate or replace the original `recorded_at` evidence.
- If the session is ambiguous, has duplicate/unpaired events, has an invalid/non-positive interval, or is flagged for review, do not invent a duration. Exclude that session's duration and retain its status/review indication.
- An incomplete session with a valid open Clock In is included provisionally through now. An incomplete date without a verifiable open session contributes no duration.
- A daily status correction alone changes the status counter; it does not fabricate a time interval.
- Avoid double counting when more than one overtime interval overlaps a session: normalize/merge eligible intervals before subtracting their union.

## Approved overtime duration

The metric **Total Lembur** includes only overtime that has `approval_status = approved` and has a verifiable actual start event. Pending, rejected, and cancelled requests do not contribute to the overtime total.

Duration source precedence:

1. **Finalized with `finalization_source = actual`:** calculate from the linked overtime-start and Clock Out events' current `effectiveRecordedAt()` values. The stored `final_start_at` / `final_end_at` are an audit snapshot of the finalization action, not the live source after a later approved timestamp correction. This preserves the agreed dynamic recalculation behavior.
2. **Finalized with `finalization_source = scheduled` or `manual`:** use the administrator-approved `final_start_at` and `final_end_at` as authoritative. These represent an explicit administrative decision and must not silently change when an underlying attendance event is corrected. A changed administrative decision requires a new explicit finalization.
3. **Not yet finalized but started:** use the linked overtime-start event's effective timestamp through the linked Clock Out event's effective timestamp when closed, or through now while the approved overtime is still running. The running amount is provisional.
4. **Approved but not started, or without a verifiable interval:** contribute zero duration until a valid interval exists.

Additional rules:

- Attribute overtime to `OvertimeRequest.session_date` (the work-session anchor date), including cross-midnight overtime.
- A pending or rejected overtime interval is not counted as overtime and is not subtracted from the attendance interval. Therefore, until approval, that interval remains part of net work duration; if later approved, both metrics are recalculated and the interval moves from ordinary work to overtime.
- A cancelled overtime request contributes zero, even if it has partial event data; preserve the event for review/history.
- Require end > start. Do not silently repair malformed intervals.
- Overtime is clipped to the associated attendance session for purposes of subtracting it from ordinary work duration. The overtime metric itself uses its authoritative interval; if it falls outside the attendance session, flag it for review rather than silently changing the administrator's finalized interval.
- Multiple eligible overtime intervals must be normalized/merged before calculating overlap, preventing duplicate subtraction.

## Pending request summary

Show one combined **Pengajuan Saya** card, scoped to the authenticated employee. Count only pending records from:

- `LeaveRequest.status = pending` (display leave/permission/sick types using the leave type data).
- `OvertimeRequest.approval_status = pending`.
- `AttendanceCorrection.status = pending`.

Show a total and a breakdown by request type. Approved, rejected, and cancelled records are excluded. Do not count notifications as requests and do not use admin-wide counts.

## Recent activity

For the first Dashboard iteration, show the 3–5 most recent attendance events belonging to the authenticated employee, using the effective timestamp for display/order where the event's correction is approved. Do not combine notifications, audit logs, and request records into a fabricated unified activity feed. The request card and request center remain the source for request status.

## Data isolation and performance

- Derive the employee from the authenticated user relationship on the server; never accept an employee ID from client input for this dashboard.
- Apply employee scoping to every attendance, overtime, request, and activity query.
- Load a monthly daily-status range in batches. Avoid one query per calendar date or one query per session.
- Eager-load approved corrections and session relations needed for effective timestamps.
- Share the same aggregation service between employee/admin views where possible so both dashboards cannot drift.
- Keep the existing Admin Dashboard definitions as the baseline where they already express the same metric. If its current values are raw counts rather than the semantics above, align the admin aggregation to this contract in the same implementation scope; do not present inconsistent labels for different calculations.

## Admin reporting calendar

Admin reports use the fixed company reporting timezone `Asia/Makassar` (WITA), independent of the application/server timezone, administrator device timezone, and employee session timezone.

- The Admin Dashboard's "today" counts and recent attendance activity use the current WITA calendar date.
- Admin daily attendance, attendance monitoring date filters, and Admin CSV exports use WITA calendar dates.
- A session is assigned to an Admin report date using the session anchor's effective timestamp converted to WITA. For an approved timestamp correction, the corrected effective timestamp is authoritative.
- The report-day window is the WITA calendar day converted to UTC bounds for database filtering. Event timestamps remain stored as UTC; no event timestamp or employee-local `attendance_date` is rewritten for reporting.
- Once a session is assigned to a WITA report date, all events linked to that session remain grouped with its Clock In anchor, including Clock Out and overtime-start events that occur after midnight.
- Approved leave requests and administrative daily-status corrections continue to use the selected WITA report date.
- Employee-facing history and dashboard calendars continue to use each session's stored timezone and `attendance_date`. A WITA report date can therefore differ from an onsite employee's local attendance date near a timezone boundary; this is intentional and must be clear in Admin reporting labels/help text.
- The Admin export's default starting date is derived from the earliest effective attendance timestamp converted to WITA, so no first-day records are omitted for WIT sessions.

This is a reporting view only. It does not change the timezone snapshot, local attendance date, or UTC evidence stored for an attendance event.

## Required automated coverage before UI implementation is considered complete

- Calendar-month boundaries, weekends, holidays, and current-month-to-today range.
- Present/incomplete/no-record/needs-review/leave/sick/permission and administrative status corrections.
- Closed session, open session with provisional duration, incomplete without a valid open session.
- Cross-midnight session attributed to Clock In date, including month boundary.
- Approved timestamp correction changing a closed and an open session's calculated duration.
- Ambiguous, duplicate, unanchored, or invalid sessions excluded from duration.
- Approved overtime in running and closed states; pending/rejected/cancelled excluded.
- `actual` finalization recalculated after approved event correction.
- `scheduled` and `manual` finalization remain authoritative after event correction.
- Pending overtime becoming approved moves its interval from ordinary work to overtime without double counting.
- Multiple/overlapping overtime intervals do not double-subtract.
- Pending request counts by type and employee isolation.
- Recent attendance activity is employee-scoped and uses effective timestamps.
- Employee and admin aggregates agree for the same employee and month.

## Implementation boundary

The timezone policy above is an approved domain decision and authorizes the narrowly scoped attendance-timezone implementation, including its resolver, persistence semantics, aggregation, tests, and documentation. It does not authorize unrelated changes to camera/GPS capture, photo evidence, approval transitions, or admin overtime finalization. Preserve the existing GPS accuracy/wait/photo rules. If the current schema or workflow cannot represent the policy safely, stop and report the specific migration or product decision required before proceeding.
