<?php

namespace App\Http\Controllers;

use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminOvertimeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = OvertimeRequest::query()
            ->with(['employee.user', 'clockInRecord', 'startRecord', 'clockOutRecord', 'decidedBy']);

        if ($request->filled('status') && OvertimeApprovalStatus::tryFrom($request->string('status')->toString())) {
            $query->where('approval_status', $request->string('status')->toString());
        }

        $requests = $query->latest('scheduled_start_at')->paginate(20)->withQueryString();

        return view('admin.overtime-requests.index', compact('requests'));
    }

    public function show(OvertimeRequest $overtimeRequest): View
    {
        $overtimeRequest->load(['employee.user', 'clockInRecord', 'startRecord', 'clockOutRecord', 'decidedBy']);

        return view('admin.overtime-requests.show', compact('overtimeRequest'));
    }

    public function decide(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $decision = OvertimeApprovalStatus::from($validated['decision']);

        DB::transaction(function () use ($request, $overtimeRequest, $decision, $validated): void {
            $locked = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->approval_status !== OvertimeApprovalStatus::Pending) {
                abort(422, 'Pengajuan lembur sudah diputuskan dan tidak dapat diputuskan kembali.');
            }

            $old = [
                'approval_status' => $locked->approval_status->value,
                'decided_by' => $locked->decided_by,
                'decided_at' => $locked->decided_at?->toISOString(),
                'decision_note' => $locked->decision_note,
            ];

            $locked->update([
                'approval_status' => $decision,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'overtime.'.$decision->value,
                oldValues: $old,
                newValues: [
                    'approval_status' => $locked->approval_status->value,
                    'decided_by' => $locked->decided_by,
                    'decided_at' => $locked->decided_at?->toISOString(),
                    'decision_note' => $locked->decision_note,
                ],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('admin.overtime-requests.show', $overtimeRequest)
            ->with('status', 'Keputusan pengajuan lembur berhasil disimpan.');
    }

    /** Admin dapat membuka jalur koreksi jika pegawai lupa foto; alasan wajib dicatat. */
    public function allowClockOutWithoutPhoto(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'photo_exception_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $overtimeRequest, $validated): void {
            $locked = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            abort_if(
                $locked->start_record_id !== null
                    || ! in_array($locked->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true),
                422,
                'Pengecualian hanya dapat diberikan untuk lembur yang belum memiliki foto konfirmasi.'
            );

            $old = [
                'state' => $locked->state->value,
                'photo_exception_reason' => $locked->photo_exception_reason,
            ];

            $locked->update([
                'state' => OvertimeState::NeedsReview,
                'photo_exception_reason' => $validated['photo_exception_reason'],
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'overtime.photo_exception_allowed',
                oldValues: $old,
                newValues: [
                    'state' => $locked->state->value,
                    'photo_exception_reason' => $locked->photo_exception_reason,
                ],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('admin.overtime-requests.show', $overtimeRequest)
            ->with('status', 'Pengecualian foto lembur dicatat. Pegawai dapat melanjutkan absensi pulang.');
    }
}
