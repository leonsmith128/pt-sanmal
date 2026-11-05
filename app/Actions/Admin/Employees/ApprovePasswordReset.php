<?php

namespace App\Actions\Admin\Employees;

use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ApprovePasswordReset
{
    public function handle(PasswordResetRequest $passwordResetRequest, User $reviewer, ?string $note = null): bool
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(24);

        $user = DB::transaction(function () use ($passwordResetRequest, $reviewer, $note, $token, $expiresAt): User {
            $request = PasswordResetRequest::query()->lockForUpdate()->findOrFail($passwordResetRequest->id);
            abort_unless($request->status === 'pending', 409, 'Permintaan reset sudah diproses.');

            $user = User::query()->lockForUpdate()->findOrFail($request->user_id);
            abort_unless($user->is_active && ! $user->must_change_password, 422);

            $request->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'used_at' => null,
            ])->save();

            AuditLog::record($user, 'password_reset.approved', null, [
                'request_id' => $request->id,
                'expires_at' => $expiresAt->toISOString(),
            ], $reviewer->id);

            return $user;
        });

        try {
            $user->notify(new PasswordResetApprovedNotification(
                route('password-reset.show', ['token' => $token]),
                $expiresAt->toDateTimeString(),
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Approved employee password reset email could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
