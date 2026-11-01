<?php

namespace App\Http\Controllers;

use App\Actions\ApproveAttendanceCorrection;
use App\Enums\AttendanceType;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminAttendanceCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $corrections = AttendanceCorrection::query()
            ->with(['employee.user', 'reviewedBy'])
            ->when($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)->withQueryString();

        return view('pages::admin.attendance-corrections.index', compact('corrections'));
    }

    public function show(AttendanceCorrection $attendanceCorrection): View
    {
        $attendanceCorrection->load(['employee.user', 'attendanceRecord', 'reviewedBy']);

        return view('pages::admin.attendance-corrections.show', ['correction' => $attendanceCorrection]);
    }

    public function correctTimestamp(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'recorded_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        abort_unless(in_array($record->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true), 422);

        DB::transaction(function () use ($request, $record, $validated): void {
            $locked = AttendanceRecord::query()
                ->with(['employee.user', 'approvedCorrection'])
                ->whereKey($record->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($locked->employee?->user_id === $request->user()->id, 403, 'Anda tidak dapat mengoreksi timestamp absensi milik sendiri.');

            $correctedAt = Carbon::createFromFormat('!Y-m-d\\TH:i', $validated['recorded_at_local'], $locked->timezone);
            abort_if($correctedAt->isFuture(), 422, 'Timestamp koreksi tidak boleh berada di masa depan.');

            $previousEffectiveAt = $locked->effectiveRecordedAt();

            if ($locked->type === AttendanceType::ClockIn) {
                $nextClockOut = AttendanceRecord::query()
                    ->with('approvedCorrection')
                    ->where('employee_id', $locked->employee_id)
                    ->where('type', AttendanceType::ClockOut)
                    ->where('recorded_at', '>', $locked->recorded_at)
                    ->orderBy('recorded_at')
                    ->first();

                abort_if(
                    $nextClockOut && $correctedAt->gte($nextClockOut->effectiveRecordedAt()),
                    422,
                    'Waktu masuk harus lebih awal daripada waktu pulang pada sesi yang sama.'
                );
            } else {
                $previousClockIn = AttendanceRecord::query()
                    ->with('approvedCorrection')
                    ->where('employee_id', $locked->employee_id)
                    ->where('type', AttendanceType::ClockIn)
                    ->where('recorded_at', '<', $locked->recorded_at)
                    ->orderByDesc('recorded_at')
                    ->first();

                abort_if(
                    $previousClockIn && $correctedAt->lte($previousClockIn->effectiveRecordedAt()),
                    422,
                    'Waktu pulang harus lebih akhir daripada waktu masuk pada sesi yang sama.'
                );
            }

            $correctedAtUtc = $correctedAt->copy()->utc();
            $correction = AttendanceCorrection::create([
                'employee_id' => $locked->employee_id,
                'attendance_record_id' => $locked->id,
                'requested_type' => $locked->type->value,
                'requested_recorded_at' => $correctedAtUtc,
                'attendance_date' => $correctedAt->toDateString(),
                'reason' => $validated['reason'],
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => 'Koreksi timestamp langsung oleh administrator.',
            ]);

            AuditLog::record(
                auditable: $correction,
                action: 'attendance.timestamp_corrected',
                oldValues: [
                    'original_recorded_at' => $locked->recorded_at->toISOString(),
                    'previous_effective_recorded_at' => $previousEffectiveAt->toISOString(),
                    'attendance_date' => $locked->attendance_date->toDateString(),
                ],
                newValues: [
                    'effective_recorded_at' => $correctedAtUtc->toISOString(),
                    'attendance_date' => $correctedAt->toDateString(),
                    'reason' => $validated['reason'],
                    'reviewed_by' => $request->user()->id,
                ],
                userId: $request->user()->id,
            );
        });

        return back()->with('status', 'Timestamp efektif berhasil dikoreksi. Bukti waktu asli tetap tersimpan.');
    }

    public function attachment(AttendanceCorrection $attendanceCorrection)
    {
        abort_unless($attendanceCorrection->attachment_path && Storage::disk('local')->exists($attendanceCorrection->attachment_path), 404);

        return Storage::disk('local')->download($attendanceCorrection->attachment_path);
    }

    public function decide(Request $request, AttendanceCorrection $attendanceCorrection, ApproveAttendanceCorrection $approveCorrection): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $approveCorrection->handle(
            correction: $attendanceCorrection,
            reviewer: $request->user(),
            decision: $data['decision'],
            reviewNote: $data['review_note'] ?? null,
        );

        return redirect()->route('admin.attendance-corrections.show', $attendanceCorrection)
            ->with('status', 'Keputusan koreksi absensi berhasil disimpan.');
    }
}
