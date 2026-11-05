<?php

namespace App\Actions;

use App\Models\EmployeeAccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompleteEmployeeActivation
{
    public function handle(string $token, string $password): User
    {
        return DB::transaction(function () use ($token, $password): User {
            $activation = EmployeeAccountActivation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            abort_unless($activation && $activation->isUsable(), 422, 'Tautan aktivasi tidak valid atau sudah kedaluwarsa.');

            $user = User::query()->lockForUpdate()->findOrFail($activation->user_id);
            abort_unless($user->is_active && $user->must_change_password, 403);

            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ])->save();

            $activation->forceFill(['consumed_at' => now()])->save();
            $user->accountCredential()->delete();

            return $user;
        });
    }
}
