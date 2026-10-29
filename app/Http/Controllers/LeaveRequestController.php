<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    private const TYPES = ['izin', 'sakit', 'cuti', 'lembur', 'koreksi_absensi'];

    private const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request);
        $rows = $this->filteredRows($employee, $filters);
        $perPage = 10;
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $requests = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('leave-requests.index', [
            'requests' => $requests,
            'filters' => $filters,
            'totalCount' => $rows->count(),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $rows = $this->filteredRows($employee, $this->validatedFilters($request));
        $filename = 'permohonan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            // BOM membantu Excel mengenali UTF-8 (termasuk nama/alasan berbahasa Indonesia).
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Nomor Permohonan', 'Jenis', 'Tanggal Pengajuan', 'Tanggal Mulai', 'Tanggal Selesai',
                'Ringkasan / Alasan', 'Status Persetujuan', 'Status Operasional Lembur',
                'Pemroses', 'Waktu Keputusan', 'Catatan Keputusan',
            ]);

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->spreadsheetSafe(...), [
                    $row->reference,
                    $row->type_label,
                    $row->submitted_at?->format('Y-m-d H:i'),
                    $row->period_start,
                    $row->period_end,
                    $row->summary,
                    $row->status_label,
                    $row->operational_status,
                    $row->reviewer,
                    $row->decided_at?->format('Y-m-d H:i'),
                    $row->decision_note,
                ]));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->employee, 403, 'Akun ini belum terhubung dengan data pegawai.');

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('leave-requests.create', compact('leaveTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'attachment.mimes' => 'Lampiran harus berupa JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        $leaveType = LeaveType::query()
            ->whereKey($validated['leave_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $attachmentPath = $request->file('attachment')?->store('leave-attachments');

        $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => LeaveRequestStatus::Pending,
        ]);

        return redirect()->route('leave-requests.index')
            ->with('status', 'Permohonan berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $leaveRequest->employee_id === $employee->id, 404);
        abort_unless($leaveRequest->isPending(), 422, 'Hanya permohonan yang masih menunggu yang dapat dibatalkan.');

        DB::transaction(function () use ($leaveRequest): void {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                abort(422, 'Hanya permohonan yang masih menunggu yang dapat dibatalkan.');
            }

            $old = ['status' => $locked->status->value];
            $locked->update(['status' => LeaveRequestStatus::Cancelled]);

            AuditLog::record(
                auditable: $locked,
                action: 'leave.cancelled',
                oldValues: $old,
                newValues: ['status' => $locked->status->value],
            );
        });

        return redirect()->route('leave-requests.index')
            ->with('status', 'Permohonan berhasil dibatalkan.');
    }

    /**
     * Normalize the existing request models for one read-only employee list.
     * Approval status and operational overtime state remain separate.
     */
    private function filteredRows(Employee $employee, array $filters): Collection
    {
        $rows = collect();

        $employee->leaveRequests()
            ->with(['leaveType', 'decidedBy'])
            ->get()
            ->each(function (LeaveRequest $item) use ($rows): void {
                $code = $item->leaveType?->code ?? 'izin';
                $rows->push((object) [
                    'source' => 'leave',
                    'source_id' => $item->id,
                    'reference' => 'IZ-' . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT),
                    'type' => in_array($code, ['izin', 'sakit', 'cuti'], true) ? $code : 'izin',
                    'type_label' => $item->leaveType?->name ?? 'Jenis tidak tersedia',
                    'submitted_at' => $item->created_at,
                    'period_start' => $item->start_date?->toDateString(),
                    'period_end' => $item->end_date?->toDateString(),
                    'summary' => $item->reason,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'operational_status' => null,
                    'reviewer' => $item->decidedBy?->name,
                    'decided_at' => $item->decided_at,
                    'decision_note' => $item->decision_note,
                    'cancelable' => $item->isPending(),
                    'cancel_route' => route('leave-requests.cancel', $item),
                ]);
            });

        $employee->overtimeRequests()
            ->with(['decidedBy'])
            ->get()
            ->each(function (OvertimeRequest $item) use ($rows): void {
                $start = $item->scheduledStartLocal();
                $rows->push((object) [
                    'source' => 'overtime',
                    'source_id' => $item->id,
                    'reference' => 'LM-' . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT),
                    'type' => 'lembur',
                    'type_label' => 'Lembur',
                    'submitted_at' => $item->created_at,
                    'period_start' => $start->toDateString(),
                    'period_end' => $start->toDateString(),
                    'summary' => $item->reason,
                    'status' => $item->approval_status->value,
                    'status_label' => $item->approval_status->label(),
                    'operational_status' => $item->state->label(),
                    'reviewer' => $item->decidedBy?->name,
                    'decided_at' => $item->decided_at,
                    'decision_note' => $item->decision_note,
                    'cancelable' => in_array($item->state->value, ['scheduled', 'awaiting_photo'], true)
                        && $item->start_record_id === null && $item->scheduled_start_at->isFuture(),
                    'cancel_route' => route('overtime-requests.cancel', $item),
                ]);
            });

        $employee->attendanceCorrections()
            ->with(['reviewedBy'])
            ->get()
            ->each(function (AttendanceCorrection $item) use ($rows): void {
                $status = (string) $item->status;
                $rows->push((object) [
                    'source' => 'correction',
                    'source_id' => $item->id,
                    'reference' => 'KA-' . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT),
                    'type' => 'koreksi_absensi',
                    'type_label' => 'Koreksi Absensi',
                    'submitted_at' => $item->created_at,
                    'period_start' => $item->attendance_date?->toDateString(),
                    'period_end' => $item->attendance_date?->toDateString(),
                    'summary' => strtoupper(str_replace('_', ' ', $item->requested_type)) . ' · '
                        . $item->requested_recorded_at?->format('d/m/Y H:i') . ' — ' . $item->reason,
                    'status' => $status,
                    'status_label' => match ($status) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst(str_replace('_', ' ', $status)),
                    },
                    'operational_status' => null,
                    'reviewer' => $item->reviewedBy?->name,
                    'decided_at' => $item->reviewed_at,
                    'decision_note' => $item->review_note,
                    'cancelable' => false,
                    'cancel_route' => null,
                ]);
            });

        return $rows
            ->filter(function (object $row) use ($filters): bool {
                if ($filters['type'] !== '' && $row->type !== $filters['type']) {
                    return false;
                }

                if ($filters['status'] !== '' && $row->status !== $filters['status']) {
                    return false;
                }

                if ($filters['from'] !== '' && ($row->period_end === null || $row->period_end < $filters['from'])) {
                    return false;
                }

                if ($filters['to'] !== '' && ($row->period_start === null || $row->period_start > $filters['to'])) {
                    return false;
                }

                if ($filters['q'] !== '') {
                    $haystack = mb_strtolower(implode(' ', [
                        $row->reference, $row->type_label, $row->summary, $row->status_label,
                    ]));
                    if (! str_contains($haystack, mb_strtolower($filters['q']))) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (object $row) => $row->submitted_at?->getTimestamp() ?? 0)
            ->values();
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(self::TYPES)],
            'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
        ], [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        return [
            'type' => $validated['type'] ?? '',
            'status' => $validated['status'] ?? '',
            'from' => isset($validated['from']) ? date('Y-m-d', strtotime($validated['from'])) : '',
            'to' => isset($validated['to']) ? date('Y-m-d', strtotime($validated['to'])) : '',
            'q' => trim($validated['q'] ?? ''),
        ];
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        // CSV can be opened by spreadsheet software; prevent untrusted text becoming a formula.
        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
    }
}
