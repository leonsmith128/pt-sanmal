<?php

namespace App\Actions\Admin\Employees;

use App\Models\AuditLog;
use App\Models\EmployeeAccountActivation;
use App\Models\User;
use App\Notifications\EmployeeAccountActivationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IssueEmployeeActivation
{
    public function handle(User $user): bool
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(24);

        DB::transaction(function () use ($user, $token, $expiresAt): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($lockedUser->is_active && $lockedUser->must_change_password, 422);

            EmployeeAccountActivation::query()->updateOrCreate(
                ['user_id' => $lockedUser->id],
                [
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => $expiresAt,
                    'consumed_at' => null,
                ],
            );

            AuditLog::record($lockedUser, 'account.activation.issued', null, [
                'expires_at' => $expiresAt->toISOString(),
            ]);
        });

        try {
            $user->notify(new EmployeeAccountActivationNotification(
                route('employee-activation.show', ['token' => $token]),
                $expiresAt->toDateTimeString(),
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Employee account activation email could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
