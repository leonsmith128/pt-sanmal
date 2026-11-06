<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Employees\ApprovePasswordReset;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPasswordResetRequestController extends Controller
{
    public function index(): View
    {
        $actor = auth()->user();
        $requests = PasswordResetRequest::query()
            ->when($actor?->isAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', UserRole::Employee->value)))
            ->with(['user.employee', 'reviewer'])
            ->latest()
            ->paginate(20);

        return view('pages::admin.password-reset-requests.index', compact('requests'));
    }

    public function approve(Request $request, PasswordResetRequest $passwordResetRequest, ApprovePasswordReset $approvePasswordReset): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);
        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);
        abort_unless(
            $reviewer->isSuperadmin()
                || ($reviewer->isAdmin() && $passwordResetRequest->user->role === UserRole::Employee),
            403,
        );

        $sent = $approvePasswordReset->handle($passwordResetRequest, $reviewer, $data['review_note'] ?? null);

        if (! $sent) {
            return back()->withErrors(['email' => 'Email gagal dikirim. Permintaan dikembalikan ke status menunggu agar admin dapat menyetujui kembali setelah konfigurasi email diperbaiki.']);
        }

        return back()->with('status', 'Permintaan disetujui dan tautan reset dikirim.');
    }

    public function reject(Request $request, PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);
        $reviewer = $request->user();
        abort_unless(
            $reviewer?->isSuperadmin()
                || ($reviewer?->isAdmin() && $passwordResetRequest->user->role === UserRole::Employee),
            403,
        );

        DB::transaction(function () use ($passwordResetRequest, $request, $data): void {
            $lockedRequest = PasswordResetRequest::query()->lockForUpdate()->findOrFail($passwordResetRequest->id);
            abort_unless($lockedRequest->status === 'pending', 409, 'Permintaan reset sudah diproses.');

            $lockedRequest->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'],
            ])->save();

            AuditLog::record($lockedRequest->user, 'password_reset.rejected', null, [
                'request_id' => $lockedRequest->id,
            ]);
        });

        return back()->with('status', 'Permintaan reset password ditolak.');
    }
}
