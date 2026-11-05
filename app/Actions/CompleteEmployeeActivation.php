<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\EmployeeAccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CompleteEmployeeActivation
{
    public function handle(string $token, string $password): User
    {
        return DB::transaction(function () use ($token, $password): User {
            $tokenHash = hash('sha256', $token);
            $snapshot = EmployeeAccountActivation::query()->where('token_hash', $tokenHash)->first();
            abort_unless($snapshot, 422, 'Tautan aktivasi tidak valid atau sudah kedaluwarsa.');

            $user = User::query()->lockForUpdate()->findOrFail($snapshot->user_id);
            $activation = EmployeeAccountActivation::query()->whereKey($snapshot->id)->lockForUpdate()->first();

            abort_unless(
                $activation && $activation->isUsable() && $user->is_active && $user->must_change_password,
                422,
                'Tautan aktivasi tidak valid atau sudah kedaluwarsa.',
            );

            if (Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password baru harus berbeda dari password sementara.',
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
            ])->save();

            $activation->forceFill(['consumed_at' => now()])->save();
            $user->accountCredential()->delete();
            AuditLog::record($user, 'account.activation.completed', null, ['method' => 'email']);

            return $user;
        });
    }
}
