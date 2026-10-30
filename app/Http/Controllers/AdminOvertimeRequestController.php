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

        return view('pages::admin.overtime-requests.index', compact('requests'));
    }

    public function show(OvertimeRequest $overtimeRequest): View
    {
        $overtimeRequest->load(['employee.user', 'clockInRecord', 'startRecord', 'clockOutRecord', 'decidedBy']);

        return view('pages::admin.overtime-requests.show', compact('overtimeRequest'));
    }

    public function decide(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $decision = OvertimeApprovalStatus::from($validated['decision']);

        DB::transaction(function () use ($request, $overtimeRequest, $decision, $validated): void {
            $locked = OvertimeRequest::query()->with('employee.user')->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->employee?->user_id === $request->user()->id, 403, 'Anda tidak dapat memutuskan pengajuan lembur milik sendiri.');

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
                'decision_seen_at' => null,
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
            $locked = OvertimeRequest::query()->with('employee.user')->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->employee?->user_id === $request->user()->id, 403, 'Anda tidak dapat memberi pengecualian pada lembur milik sendiri.');

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

    /** Finalisasi memilih jadwal, aktual, atau koreksi manual tanpa mengubah event asli. */
    public function finalize(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'finalization_source' => ['required', 'in:scheduled,actual,manual'],
            'final_start_local' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'final_end_local' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'finalization_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $overtimeRequest, $validated): void {
            $locked = OvertimeRequest::query()
                ->with(['employee.user', 'startRecord', 'clockOutRecord'])
                ->whereKey($overtimeRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($locked->employee?->user_id === $request->user()->id, 403, 'Anda tidak dapat memfinalisasi lembur milik sendiri.');
            abort_unless($locked->clock_out_record_id !== null, 422, 'Finalisasi hanya dapat dilakukan setelah sesi absensi ditutup.');

            $timezone = $locked->timezone;
            $source = $validated['finalization_source'];

            if ($source === 'scheduled') {
                abort_unless($locked->scheduled_start_at && $locked->scheduled_end_at, 422, 'Rentang jadwal lembur belum lengkap.');
                $finalStart = $locked->scheduled_start_at->copy();
                $finalEnd = $locked->scheduled_end_at->copy();
            } elseif ($source === 'actual') {
                abort_unless($locked->startRecord && $locked->clockOutRecord, 422, 'Rentang aktual belum lengkap sehingga tidak dapat dipilih.');
                $finalStart = $locked->startRecord->recorded_at->copy();
                $finalEnd = $locked->clockOutRecord->recorded_at->copy();
            } else {
                try {
                    $finalStart = Carbon::createFromFormat('!Y-m-d\\TH:i', $validated['final_start_local'] ?? '', $timezone);
                    $finalEnd = Carbon::createFromFormat('!Y-m-d\\TH:i', $validated['final_end_local'] ?? '', $timezone);
                } catch (\\Throwable) {
                    abort(422, 'Rentang waktu koreksi manual tidak valid.');
                }

                abort_unless($finalStart && $finalEnd && $finalEnd->gt($finalStart), 422, 'Waktu selesai final harus setelah waktu mulai final.');
                $finalStart = $finalStart->utc();
                $finalEnd = $finalEnd->utc();
            }

            abort_unless($finalEnd->gt($finalStart), 422, 'Rentang waktu final tidak valid.');

            $old = [
                'finalization_source' => $locked->finalization_source,
                'final_start_at' => $locked->final_start_at?->toISOString(),
                'final_end_at' => $locked->final_end_at?->toISOString(),
                'finalization_reason' => $locked->finalization_reason,
                'finalized_by' => $locked->finalized_by,
                'finalized_at' => $locked->finalized_at?->toISOString(),
            ];

            $locked->update([
                'finalization_source' => $source,
                'final_start_at' => $finalStart,
                'final_end_at' => $finalEnd,
                'finalization_reason' => $validated['finalization_reason'],
                'finalized_by' => $request->user()->id,
                'finalized_at' => now(),
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'overtime.finalized',
                oldValues: $old,
                newValues: [
                    'finalization_source' => $locked->finalization_source,
                    'final_start_at' => $locked->final_start_at->toISOString(),
                    'final_end_at' => $locked->final_end_at->toISOString(),
                    'finalization_reason' => $locked->finalization_reason,
                    'finalized_by' => $locked->finalized_by,
                    'finalized_at' => $locked->finalized_at->toISOString(),
                ],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('admin.overtime-requests.show', $overtimeRequest)
            ->with('status', 'Rentang final lembur berhasil disimpan dan dicatat dalam audit log.');
    }

}
