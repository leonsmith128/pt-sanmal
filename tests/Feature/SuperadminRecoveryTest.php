<?php

use App\Enums\UserRole;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('recovers the existing superadmin without creating a second account and requires email verification after an email change', function () {
    Notification::fake();

    $superadmin = User::factory()->create([
        'name' => 'Superadmin Lama',
        'email' => 'old-superadmin@example.test',
        'email_verified_at' => now(),
    ]);
    $superadmin->forceFill([
        'role' => UserRole::Superadmin,
        'is_active' => true,
        'must_change_password' => false,
    ])->save();

    $pendingReset = PasswordResetRequest::create([
        'user_id' => $superadmin->id,
        'status' => 'pending',
    ]);

    $this->artisan('sanmal:recover-superadmin')
        ->expectsQuestion('Nama superadmin', 'Superadmin Pulih')
        ->expectsQuestion('Email baru', 'recovered-superadmin@example.test')
        ->expectsQuestion('Password baru', 'RecoveredPassword123')
        ->expectsQuestion('Ulangi password baru', 'RecoveredPassword123')
        ->expectsConfirmation('Lanjutkan pemulihan akun superadmin ini?', 'yes')
        ->assertExitCode(0);

    $superadmin->refresh();

    expect($superadmin->name)->toBe('Superadmin Pulih')
        ->and($superadmin->email)->toBe('recovered-superadmin@example.test')
        ->and($superadmin->role)->toBe(UserRole::Superadmin)
        ->and($superadmin->email_verified_at)->toBeNull()
        ->and(Hash::check('RecoveredPassword123', $superadmin->password))->toBeTrue()
        ->and(User::query()->where('role', UserRole::Superadmin)->count())->toBe(1)
        ->and($pendingReset->fresh()->status)->toBe('expired')
        ->and($pendingReset->fresh()->token_hash)->toBeNull();

    Notification::assertSentTo($superadmin, VerifyEmail::class);
    Notification::assertSentOnDemand(
        AccountSecurityNotification::class,
        fn (AccountSecurityNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'old-superadmin@example.test'
            && $notification->subject === 'Akun Superadmin SanMal dipulihkan',
    );

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => $superadmin->getMorphClass(),
        'auditable_id' => $superadmin->id,
        'action' => 'superadmin.recovered',
    ]);
});

it('refuses superadmin recovery when no superadmin exists', function () {
    $this->artisan('sanmal:recover-superadmin')
        ->expectsOutputToContain('Akun superadmin belum tersedia')
        ->assertExitCode(1);

    expect(User::query()->where('role', UserRole::Superadmin)->count())->toBe(0);
});
