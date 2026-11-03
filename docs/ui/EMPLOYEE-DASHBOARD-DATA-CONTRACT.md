# Employee Dashboard Data Contract

> Status: specification approved for the Employee Dashboard layout phase. This document defines aggregation semantics; it does not claim that the aggregation service or UI has been implemented.

## Purpose and consistency

Employee Dashboard and Admin Dashboard must use the same domain definitions for daily attendance status, work duration, and approved overtime. Their scope differs only:

- Employee Dashboard: the authenticated employee only.
- Admin Dashboard: the employee set permitted by the existing admin dashboard and authorization rules.

Do not duplicate business calculations in Blade. Put shared aggregation in a dedicated service/query layer and expose presentation-ready values to both dashboards where practical. Reuse `DailyAttendanceStatusService` for daily classifications.

## Period and timezone

- Default period: current calendar month, from its first date through today (not future dates).
- Include every calendar date. Do not exclude weekends or holidays.
- Resolve the current date and all day boundaries using the employee's applicable attendance timezone; do not assume the server timezone.
- Attribute an attendance session and its overtime to the session's effective Clock In anchor date (`effectiveAttendanceDate()` / overtime `session_date`), not the date on which a cross-midnight Clock Out occurs.
- A session anchored in the month remains attributed to that month even when it ends after midnight. Do not split one session across two monthly totals.

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

This is a data contract for the Dashboard phase. It does not authorize changes to camera/GPS/evidence workflows, attendance recording, approval transitions, or admin finalization behavior. If an existing domain rule conflicts with this contract, stop and report the conflict before changing that domain rule.
