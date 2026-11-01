<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveAttendanceCorrection
{
    public function handle(AttendanceCorrection $correction, User $reviewer, string $decision, ?string $reviewNote = null): void
    {
        DB::transaction(function () use ($correction, $reviewer, $decision, $reviewNote): void {
            $employee = Employee::query()->whereKey($correction->employee_id)->lockForUpdate()->firstOrFail();
            $locked = AttendanceCorrection::query()->whereKey($correction->id)->lockForUpdate()->firstOrFail();

            abort_if($employee->user_id === $reviewer->id, 403, 'Anda tidak dapat memproses koreksi absensi milik sendiri.');
            abort_unless($locked->status === 'pending', 422, 'Permohonan koreksi sudah diproses.');
            abort_unless(in_array($decision, ['approved', 'rejected'], true), 422, 'Keputusan koreksi tidak valid.');

            $old = [
                'status' => $locked->status,
                'review_note' => $locked->review_note,
                'attendance_record_id' => $locked->attendance_record_id,
            ];

            $anchorId = $locked->session_anchor_id;

            if ($decision === 'approved') {
                if ($anchorId === null && $locked->attendance_record_id !== null) {
                    $targetRecord = AttendanceRecord::query()
                        ->whereKey($locked->attendance_record_id)
                        ->where('employee_id', $locked->employee_id)
                        ->firstOrFail();
                    $anchorId = $targetRecord->session_anchor_id
                        ?? ($targetRecord->type === AttendanceType::ClockIn ? $targetRecord->id : null);
                }

                abort_if($anchorId === null, 422, 'Permohonan tidak memiliki anchor sesi yang dapat diverifikasi.');

                $anchor = AttendanceRecord::query()
                    ->whereKey($anchorId)
                    ->where('employee_id', $locked->employee_id)
                    ->where('type', AttendanceType::ClockIn)
                    ->whereColumn('session_anchor_id', 'id')
                    ->with('approvedCorrection')
                    ->lockForUpdate()
                    ->firstOrFail();

                $requestedAt = $locked->requested_recorded_at;
                $requestedLocal = $requestedAt->copy()->setTimezone($anchor->timezone);
                abort_if($requestedAt->isFuture(), 422, 'Waktu koreksi tidak boleh berada di masa depan.');

                if ($locked->requested_type === AttendanceType::ClockIn->value) {
                    abort_unless($locked->attendance_record_id === $anchor->id, 422, 'Koreksi Clock In harus merujuk pada anchor sesi.');
                    $conflict = AttendanceRecord::query()
                        ->where('employee_id', $locked->employee_id)
                        ->where('type', AttendanceType::ClockIn)
                        ->where('id', '!=', $anchor->id)
                        ->with('approvedCorrection')
                        ->get()
                        ->contains(fn (AttendanceRecord $other) => $other->effectiveAttendanceDate() === $requestedLocal->toDateString());
                    abort_if($conflict, 422, 'Koreksi ditolak karena tanggal lokal tersebut sudah memiliki sesi absensi lain.');

                    $firstOut = $anchor->sessionEvents()
                        ->where('type', AttendanceType::ClockOut)
                        ->with('approvedCorrection')
                        ->get()
                        ->sortBy(fn (AttendanceRecord $event) => $event->effectiveRecordedAt()->timestamp)
                        ->first();
                    abort_if($firstOut && $requestedAt->gte($firstOut->effectiveRecordedAt()), 422, 'Waktu Clock In harus lebih awal daripada Clock Out sesi.');
                } elseif ($locked->requested_type === AttendanceType::ClockOut->value) {
                    abort_if($requestedAt->lte($anchor->effectiveRecordedAt()), 422, 'Waktu Clock Out harus setelah Clock In.');

                    if ($locked->attendance_record_id !== null) {
                        $record = AttendanceRecord::query()
                            ->whereKey($locked->attendance_record_id)
                            ->where('employee_id', $locked->employee_id)
                            ->where('type', AttendanceType::ClockOut)
                            ->where('session_anchor_id', $anchor->id)
                            ->with('approvedCorrection')
                            ->lockForUpdate()
                            ->firstOrFail();

                        $otherOut = $anchor->sessionEvents()
                            ->where('type', AttendanceType::ClockOut)
                            ->where('id', '!=', $record->id)
                            ->exists();
                        abort_if($otherOut, 422, 'Sesi sudah memiliki event Clock Out lain.');
                    } else {
                        abort_if($anchor->sessionEvents()->where('type', AttendanceType::ClockOut)->exists(), 422, 'Sesi sudah memiliki Clock Out.');
                        AttendanceRecord::create([
                            'employee_id' => $locked->employee_id,
                            'session_anchor_id' => $anchor->id,
                            'attendance_correction_id' => $locked->id,
                            'source' => 'correction',
                            'type' => AttendanceType::ClockOut,
                            'work_type' => $anchor->work_type,
                            'location_id' => $anchor->location_id,
                            'project_id' => $anchor->project_id,
                            'recorded_at' => $requestedAt,
                            'timezone' => $anchor->timezone,
                            'attendance_date' => $anchor->effectiveAttendanceDate(),
                            'location_status' => 'unavailable',
                            'notes' => 'Clock Out dibuat melalui persetujuan koreksi absensi #'.$locked->id,
                        ]);
                    }
                } else {
                    abort(422, 'Jenis koreksi absensi tidak didukung.');
                }
            }

            $locked->update([
                'session_anchor_id' => $anchorId,
                'status' => $decision,
                'review_note' => $reviewNote,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'decision_seen_at' => null,
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'attendance_correction.'.$decision,
                oldValues: $old,
                newValues: [
                    'status' => $locked->status,
                    'review_note' => $locked->review_note,
                    'reviewed_by' => $locked->reviewed_by,
                    'session_anchor_id' => $locked->session_anchor_id,
                    'generated_clock_out' => $decision === 'approved'
                        && $locked->requested_type === AttendanceType::ClockOut->value
                        && $locked->attendance_record_id === null,
                ],
                userId: $reviewer->id,
            );
        });
    }
}
