<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminLeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = LeaveRequest::query()->with(['employee.user', 'leaveType', 'decidedBy']);

        if ($request->filled('status') && LeaveRequestStatus::tryFrom($request->string('status')->toString())) {
            $query->where('status', $request->string('status')->toString());
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('pages::admin.leave-requests.index', compact('requests'));
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load(['employee.user', 'leaveType', 'decidedBy']);

        return view('pages::admin.leave-requests.show', compact('leaveRequest'));
    }

    public function downloadAttachment(LeaveRequest $leaveRequest): StreamedResponse
    {
        abort_unless($leaveRequest->attachment_path !== null && $leaveRequest->attachment_path !== '', 404);
        abort_unless(Storage::disk('local')->exists($leaveRequest->attachment_path), 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $decision = LeaveRequestStatus::from($validated['decision']);

        DB::transaction(function () use ($request, $leaveRequest, $decision, $validated): void {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                abort(422, 'Permohonan sudah diproses dan tidak dapat diputuskan kembali.');
            }

            $old = [
                'status' => $this->enumValue($locked->status),
                'decided_by' => $locked->decided_by,
                'decided_at' => $locked->decided_at
                    ? Carbon::parse($locked->decided_at)->toISOString()
                    : null,
                'decision_note' => $locked->decision_note,
            ];

            $locked->update([
                'status' => $decision,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'decision_seen_at' => null,
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            AuditLog::record(
                auditable: $locked,
                action: 'leave.'.$decision->value,
                oldValues: $old,
                newValues: [
                    'status' => $this->enumValue($locked->status),
                    'decided_by' => $locked->decided_by,
                    'decided_at' => $locked->decided_at
                    ? Carbon::parse($locked->decided_at)->toISOString()
                    : null,
                    'decision_note' => $locked->decision_note,
                ],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('admin.leave-requests.show', $leaveRequest)
            ->with('status', 'Keputusan permohonan berhasil disimpan.');
    }

    /**
     * Return the scalar value for enum-cast attributes used in audit snapshots.
     */
    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum
            ? (string) $value->value
            : (string) $value;
    }
}
