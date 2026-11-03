# Employee Dashboard V1 — Layout Specification

> Status: layout direction approved; implementation not yet complete.
> Visual baseline: the SanMal Employee Dashboard mockup approved in the project conversation on 3 November 2026.
> Data source: [Employee Dashboard Data Contract](EMPLOYEE-DASHBOARD-DATA-CONTRACT.md).

## 1. Objective

Replace the employee's minimal welcome panel with an operational dashboard that makes today's attendance state immediately clear, summarizes the current month's attendance, and provides direct access to common employee workflows.

This is a presentation specification. It does not authorize new attendance, request, approval, notification, or profile workflows.

## 2. Page composition

Use a single-column page flow with a responsive two-column lower content area on wide screens. Preserve the existing application shell and role-aware navigation; do not recreate the sidebar or global navigation inside the page.

### A. Employee greeting/header

- Header order from left to right: employee profile image/avatar, greeting and employee identity, notification icon aligned at the far right.
- Display the greeting (for example, “Selamat datang,”) on the first line in regular-weight text. Display the authenticated employee name on the second line in bold, visually prominent text. Put the current date below these lines.
- The notification control must link to the existing `notifications.index` route; do not add notification behavior or duplicate notification data.
- Avatar is optional. Use the existing profile image or the established initials fallback; do not invent a profile image or verification badge.

### B. Today's attendance hero

This is the dominant section and must appear before monthly statistics.

- Show today's resolved attendance status with text and semantic badge, not color alone.
- Show the effective Clock In time when available.
- Show current work duration. An open session is explicitly marked as running/provisional.
- Show effective Clock Out time when available.
- Primary action links to the existing `attendance` route. The dashboard itself must not start/stop attendance or invoke camera/GPS.
- Empty, incomplete, leave/sick/permission, needs-review, and no-record states must remain distinguishable. In particular, no-record must not be labelled absent.
- On narrow screens, stack status, time/duration, and action vertically; the action remains easy to reach.

### C. Four monthly statistic cards

Render four equal-priority cards beneath the hero:

1. **Hari Hadir** — count of resolved `present` dates.
2. **Total Jam Kerja** — net non-overtime duration; open-session contribution is provisional.
3. **Belum Lengkap** — count of resolved `incomplete` dates.
4. **Total Lembur** — approved overtime duration only.

Current-month attribution is evaluated independently for each attendance session using the timezone snapshot established by that session's Clock In anchor. Sessions belonging to different local months at the same absolute instant remain attributed to their own Clock In month; a cross-midnight or cross-month Clock Out never moves the session. Every calendar date in each applicable session-local month is included in the period/context; weekends and holidays are not excluded. Do not add a lateness metric or an absence count.

At desktop width, use a four-column row. At tablet, allow two columns. At mobile, use a compact two-column grid if labels and values remain readable; otherwise stack.

### D. Lower content grid

On wide screens, use a narrower left column and a wider right column. The left column contains **Pengajuan Saya** followed by **Akses Cepat**; the right column contains **Aktivitas Terbaru**. On mobile, stack in this order: Pengajuan Saya, Akses Cepat, Aktivitas Terbaru.

**Pengajuan Saya**
- One combined card, not separate cards per request category.
- Show total pending count and breakdown for attendance correction, overtime, and leave/permission/sick requests.
- Link to the existing request center route.
- Approved, rejected, and cancelled records are not counted.

**Aktivitas Terbaru**
- Show 3–5 most recent attendance events only.
- Each item identifies event type and effective local timestamp.
- Do not merge notifications, request decisions, or audit-log entries into this list.
- Link to attendance history where appropriate.
- Use a clear empty state when no events exist.

### E. Akses Cepat

Place this card directly below Pengajuan Saya in the left column. Provide compact links to existing employee destinations:
- Riwayat Absensi
- Pusat Pengajuan
- Lembur
- Profil Pegawai

Avoid duplicating global navigation if the application shell already exposes the same destinations prominently. On mobile, use a compact two-column or wrapped layout.

### F. Optional encouragement panel

The mockup includes a decorative encouragement card. This is lowest priority. Include it only if it uses a concise static message and does not displace operational information on mobile. Do not add promotional copy, fabricated attendance achievements, or dynamic claims.

## 3. Visual hierarchy

- Light theme uses the approved light neutral surfaces; dark theme uses the matching SanMal dark surface tokens. Both themes must style every dashboard card consistently.
- Reuse existing `x-ui.page`, `x-ui.page-header`, `x-ui.card`, `x-ui.badge`, and `x-ui.button` where their contracts fit.
- All Employee Dashboard cards use the shared shadow-only `x-ui.card` surface: `sanmal-surface-primary` in light theme and `sanmal-surface-primary-dark` in dark theme. Do not leave individual cards on generic white/zinc backgrounds or add one-off borders.
- Use semantic sections and headings; avoid card-in-card nesting.
- Keep four statistic cards visually consistent; semantic accent colors may distinguish categories but text labels remain mandatory.
- Use compact, consistent spacing and restrained borders/shadows. Do not copy the mockup's sample numbers, sample employee name, dates, notification count, or fabricated activity.
- Respect light and dark themes supported by the application.

## 4. Responsive acceptance criteria

- **360–390 px:** no horizontal page overflow; hero content and CTA stack cleanly; statistic cards remain readable; lower sections stack; no tiny tap targets.
- **414–430 px:** no clipping of long employee names, status labels, duration values, or request breakdowns.
- **768 px:** statistics may form two columns; lower sections may remain stacked or move to a balanced grid only when content fits.
- **1280–1440 px:** hero remains the dominant first section; four statistics fit in one row; lower content uses a left request/quick-access column and a wider right activity column.
- All layouts preserve keyboard focus visibility and readable contrast in light/dark themes.

## 5. Data and behavior constraints

- Follow the aggregation rules in [Employee Dashboard Data Contract](EMPLOYEE-DASHBOARD-DATA-CONTRACT.md) exactly.
- Resolve employee identity server-side from the authenticated user. Never accept an employee ID from client input.
- Do not calculate business metrics in Blade.
- Keep existing route names, authorization, attendance event recording, camera/GPS/evidence, request submission, approval transitions, and profile behavior unchanged.
- If the existing source cannot provide a value without violating the contract, show a truthful unavailable/empty state and report the gap; do not fabricate a fallback value.

## 6. Implementation sequence

1. Audit the current Dashboard controller, employee relationship, attendance/session/overtime/request models, and tests.
2. Implement a shared aggregation service or focused query layer with employee scoping and the contract's session/overtime semantics.
3. Add focused service/feature tests before wiring the metrics into the view.
4. Update the Dashboard controller to branch by role without loading employee data for unrelated roles or exposing admin-wide data to employees.
5. Implement the Employee Dashboard view using existing UI primitives.
6. Run focused tests, full relevant test suite, Pint, and asset build as applicable.
7. Perform browser QA at the responsive checkpoints above and record evidence in the Visual QA Log.

## 7. Out of scope

- Redesigning the Admin Dashboard in this batch.
- Reworking the Employee Attendance page or its calendar/camera/GPS interactions.
- Adding notifications, new quick-link destinations, attendance policies, lateness rules, or new request types.
- Changing domain calculations or workflow beyond the approved data contract.
