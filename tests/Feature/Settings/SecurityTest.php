<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('security settings page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'));

    $response->assertOk()
        ->assertDontSee('Delete account');
});

test('security settings page requires password confirmation when enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('security.edit'));

    $response->assertRedirect(route('password.confirm'));
});

test('active accounts of every role can access the application security page before email verification', function () {
    foreach (UserRole::cases() as $role) {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ])->save();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk();
    }
});

test('account security page renders without two factor when feature is disabled', function () {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Ubah kata sandi')
        ->assertDontSee('Manage your passkeys for passwordless sign-in')
        ->assertDontSee('Add a passkey to sign in without a password')
        ->assertDontSee('Two-factor authentication');
});

test('self-service account deletion endpoint is not available', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/delete-account')
        ->assertNotFound();
});

test('password can be updated, audited, and notifies the account owner', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password1')
        ->set('password_confirmation', 'new-password1')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('new-password1', $user->refresh()->password))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => $user->getMorphClass(),
        'auditable_id' => $user->id,
        'action' => 'account.password.changed',
    ]);

    Notification::assertSentTo($user, AccountSecurityNotification::class, fn (AccountSecurityNotification $notification): bool => $notification->subject === 'Password akun SanMal diubah');
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password1')
        ->set('password_confirmation', 'new-password1')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});

test('email changes for every role require verification, security notification, and audit without blocking an active account', function () {
    Notification::fake();

    foreach (UserRole::cases() as $role) {
        $oldEmail = 'original-'.$role->value.'@example.test';
        $user = User::factory()->create([
            'email' => $oldEmail,
            'email_verified_at' => now(),
        ]);
        $user->forceFill([
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ])->save();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()]);

        Livewire::test('pages::settings.security')
            ->set('email', 'updated-'.$role->value.'@example.test')
            ->call('updateEmail')
            ->assertHasNoErrors();

        $user->refresh();

        expect($user->email)->toBe('updated-'.$role->value.'@example.test')
            ->and($user->email_verified_at)->toBeNull()
            ->and($user->is_active)->toBeTrue();

        Notification::assertSentTo($user, VerifyEmail::class);

        Notification::assertSentOnDemand(
            AccountSecurityNotification::class,
            fn (AccountSecurityNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === $oldEmail
                && $notification->subject === 'Email akun SanMal diubah',
        );

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->id,
            'action' => 'account.email.changed',
        ]);

        $this->get(route('security.edit'))->assertOk();
    }
});

test('authenticated application and security routes use password-hash session authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();

    expect(session()->has('password_hash_web'))->toBeTrue();

    $securityMiddleware = app('router')->getRoutes()->getByName('security.edit')->gatherMiddleware();

    expect($securityMiddleware)->toContain('auth.session');
});
