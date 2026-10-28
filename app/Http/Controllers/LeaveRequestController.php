<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Akun ini belum terhubung dengan data pegawai.');

        $requests = $employee->leaveRequests()
            ->with(['leaveType', 'decidedBy'])
            ->latest()
            ->paginate(15);

        return view('leave-requests.index', compact('requests'));
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
        abort_unless($employee, 403, 'Akun ini belum terhubung dengan data pegawai.');

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
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        return redirect()->route('leave-requests.index')
            ->with('status', 'Permohonan berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $leaveRequest->employee_id === $employee->id, 404);
        abort_unless($leaveRequest->status === LeaveRequest::STATUS_PENDING, 422, 'Hanya permohonan yang masih menunggu yang dapat dibatalkan.');

        DB::transaction(function () use ($request, $leaveRequest): void {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                abort(422, 'Hanya permohonan yang masih menunggu yang dapat dibatalkan.');
            }

            $old = ['status' => $locked->status];
            $locked->update(['status' => LeaveRequest::STATUS_CANCELLED]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'leave.cancelled',
                'auditable_type' => LeaveRequest::class,
                'auditable_id' => $locked->id,
                'old_values' => $old,
                'new_values' => ['status' => $locked->status],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        });

        return redirect()->route('leave-requests.index')
            ->with('status', 'Permohonan berhasil dibatalkan.');
    }
}
