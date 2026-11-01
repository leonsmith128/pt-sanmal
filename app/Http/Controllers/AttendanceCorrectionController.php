<?php

namespace App\Http\Controllers;

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $records = $employee->attendanceRecords()
            ->whereIn('type', [AttendanceType::ClockIn, AttendanceType::ClockOut])
            ->latest('recorded_at')
            ->limit(30)
            ->get();

        $openSession = RecordAttendance::openSession($employee);

        return view('pages::employee.attendance-corrections.create', compact('records', 'openSession'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $data = $request->validate([
            'requested_type' => ['required', 'in:clock_in,clock_out'],
            'attendance_record_id' => ['nullable', 'integer', 'exists:attendance_records,id'],
            'session_anchor_id' => ['nullable', 'integer', 'exists:attendance_records,id'],
            'requested_recorded_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (empty($data['attendance_record_id']) && ($data['requested_type'] !== 'clock_out' || empty($data['session_anchor_id']))) {
            throw ValidationException::withMessages([
                'attendance_record_id' => 'Pilih event absensi yang ingin dikoreksi atau sesi Clock In yang lupa Clock Out.',
            ]);
        }

        $attachmentPath = $request->file('attachment')?->store('attendance-corrections', 'local');

        try {
            DB::transaction(function () use ($request, $employee, $data, $attachmentPath): void {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $record = null;
                if (! empty($data['attendance_record_id'])) {
                    $record = AttendanceRecord::query()
                        ->whereKey($data['attendance_record_id'])
                        ->where('employee_id', $employee->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    abort_unless($record->type->value === $data['requested_type'], 422, 'Jenis event tidak sesuai dengan permohonan koreksi.');
                    $anchorId = $record->session_anchor_id ?? ($record->type === AttendanceType::ClockIn ? $record->id : null);
                    abort_if($anchorId === null, 422, 'Event belum memiliki relasi sesi yang dapat diverifikasi.');
                } else {
                    $anchorId = (int) $data['session_anchor_id'];
                }

                $anchor = AttendanceRecord::query()
                    ->whereKey($anchorId)
                    ->where('employee_id', $employee->id)
                    ->where('type', AttendanceType::ClockIn)
                    ->whereColumn('session_anchor_id', 'id')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($record === null) {
                    abort_if($anchor->sessionEvents()->where('type', AttendanceType::ClockOut)->exists(), 422, 'Sesi ini sudah memiliki Clock Out.');
                    abort_if(RecordAttendance::openSession($employee)?->id !== $anchor->id, 422, 'Sesi ini tidak lagi menjadi sesi absensi yang terbuka.');
                }

                $requestedAt = Carbon::createFromFormat('!Y-m-d\\TH:i', $data['requested_recorded_at_local'], $anchor->timezone);
                abort_if($requestedAt->isFuture(), 422, 'Waktu koreksi tidak boleh berada di masa depan.');
                if ($data['requested_type'] === 'clock_out') {
                    abort_if($requestedAt->lte($anchor->effectiveRecordedAt()), 422, 'Waktu Clock Out harus setelah Clock In.');
                }

                $pendingQuery = AttendanceCorrection::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending');
                if ($record) {
                    $pendingQuery->where('attendance_record_id', $record->id);
                } else {
                    $pendingQuery->where('session_anchor_id', $anchor->id)->whereNull('attendance_record_id');
                }
                abort_if($pendingQuery->exists(), 422, 'Masih ada permohonan koreksi yang menunggu untuk event atau sesi ini.');

                $correction = AttendanceCorrection::create([
                    'employee_id' => $employee->id,
                    'attendance_record_id' => $record?->id,
                    'session_anchor_id' => $anchor->id,
                    'requested_type' => $data['requested_type'],
                    'requested_recorded_at' => $requestedAt->copy()->utc(),
                    'attendance_date' => $requestedAt->toDateString(),
                    'reason' => $data['reason'],
                    'attachment_path' => $attachmentPath,
                    'status' => 'pending',
                ]);

                AuditLog::record(
                    auditable: $correction,
                    action: 'attendance_correction.submitted',
                    oldValues: [],
                    newValues: [
                        'requested_type' => $correction->requested_type,
                        'attendance_record_id' => $correction->attendance_record_id,
                        'session_anchor_id' => $correction->session_anchor_id,
                        'requested_recorded_at' => $correction->requested_recorded_at->toISOString(),
                        'status' => $correction->status,
                    ],
                    userId: $request->user()->id,
                );
            });
        } catch (\\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('local')->delete($attachmentPath);
            }
            throw $exception;
        }

        return redirect()->route('leave-requests.index')
            ->with('status', 'Permohonan koreksi absensi berhasil dikirim dan menunggu admin.');
    }

    public function cancel(Request $request, AttendanceCorrection $attendanceCorrection): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $attendanceCorrection->employee_id === $employee->id, 404);

        DB::transaction(function () use ($request, $attendanceCorrection): void {
            $locked = AttendanceCorrection::query()->whereKey($attendanceCorrection->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'Hanya koreksi yang masih menunggu yang dapat dibatalkan.');

            $old = ['status' => $locked->status];
            $locked->update(['status' => 'cancelled']);

            AuditLog::record(
                auditable: $locked,
                action: 'attendance_correction.cancelled',
                oldValues: $old,
                newValues: ['status' => $locked->status],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('leave-requests.index')->with('status', 'Permohonan koreksi absensi dibatalkan.');
    }
}
