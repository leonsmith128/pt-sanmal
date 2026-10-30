<?php

namespace App\\Http\\Controllers;

use App\\Models\\AttendanceCorrection;
use App\\Models\\AuditLog;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Storage;
use Illuminate\\View\\View;

class AdminAttendanceCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $corrections = AttendanceCorrection::query()
            ->with(['employee.user', 'reviewedBy'])
            ->when($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)->withQueryString();

        return view('admin.attendance-corrections.index', compact('corrections'));
    }

    public function show(AttendanceCorrection $attendanceCorrection): View
    {
        $attendanceCorrection->load(['employee.user', 'attendanceRecord', 'reviewedBy']);
        return view('admin.attendance-corrections.show', ['correction' => $attendanceCorrection]);
    }

    public function attachment(AttendanceCorrection $attendanceCorrection)
    {
        abort_unless($attendanceCorrection->attachment_path && Storage::disk('local')->exists($attendanceCorrection->attachment_path), 404);
        return Storage::disk('local')->download($attendanceCorrection->attachment_path);
    }

    public function decide(Request $request, AttendanceCorrection $attendanceCorrection): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $attendanceCorrection, $data): void {
            $locked = AttendanceCorrection::query()->whereKey($attendanceCorrection->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'Permohonan koreksi sudah diproses.');

            $old = ['status' => $locked->status, 'review_note' => $locked->review_note];
            $locked->update([
                'status' => $data['decision'],
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'decision_seen_at' => null,
            ]);

            AuditLog::record($locked, 'attendance_correction.'.$data['decision'], $old, [
                'status' => $locked->status,
                'review_note' => $locked->review_note,
                'reviewed_by' => $locked->reviewed_by,
            ], $request->user()->id);
        });

        return redirect()->route('admin.attendance-corrections.show', $attendanceCorrection)
            ->with('status', 'Keputusan koreksi absensi berhasil disimpan.');
    }
}
