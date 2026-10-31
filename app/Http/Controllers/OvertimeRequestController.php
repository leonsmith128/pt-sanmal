<?php

namespace App\Http\Controllers;

use App\Actions\CreateOvertimeRequest;
use App\Enums\OvertimeState;
use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $requests = $employee->overtimeRequests()
            ->with(['clockInRecord', 'startRecord', 'clockOutRecord', 'decidedBy'])
            ->latest('scheduled_start_at')
            ->paginate(15);

        return view('pages::employee.overtime-requests.index', compact('requests'));
    }

    public function store(Request $request, CreateOvertimeRequest $createOvertimeRequest): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($request->user()->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
            'scheduled_start_at' => ['required', 'date_format:Y-m-d\\TH:i'],
        ], [
            'scheduled_start_at.date_format' => 'Waktu mulai lembur tidak valid.',
        ]);

        $createOvertimeRequest->handle(
            employee: $employee,
            reason: $validated['reason'] ?? null,
            scheduledStartLocal: $validated['scheduled_start_at'],
        );

        return redirect()->route('overtime-requests.index')
            ->with('status', 'Pengajuan lembur tersimpan. Status persetujuan menunggu admin.');
    }

    public function cancel(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $overtimeRequest->employee_id === $employee->id, 404);

        DB::transaction(function () use ($overtimeRequest): void {
            $locked = OvertimeRequest::query()->whereKey($overtimeRequest->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true)
                || $locked->start_record_id !== null) {
                abort(422, 'Lembur yang sudah dikonfirmasi atau selesai tidak dapat dibatalkan oleh pegawai.');
            }

            $old = ['state' => $locked->state->value, 'cancelled_at' => $locked->cancelled_at?->toISOString()];
            $locked->update([
                'state' => OvertimeState::Cancelled,
                'cancelled_at' => now(),
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'overtime.cancelled',
                oldValues: $old,
                newValues: ['state' => $locked->state->value, 'cancelled_at' => $locked->cancelled_at->toISOString()],
            );
        });

        return redirect()->route('overtime-requests.index')
            ->with('status', 'Pengajuan lembur dibatalkan. Anda sekarang dapat melanjutkan clock-out jika sesi masih aktif.');
    }
}
