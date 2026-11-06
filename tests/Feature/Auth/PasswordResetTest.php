<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use App\Notifications\PasswordResetApprovedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function passwordResetAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $admin;
}

function passwordResetSuperadmin(): User
{
    $superadmin = User::factory()->create();
    $superadmin->forceFill([
        'role' => UserRole::Superadmin,
        'is_active' => true,
        'must_change_password' => false,
    ])->save();

    return $superadmin;
}

function passwordResetEmployee(): User
{
    $user = User::factory()->create([
        'email_verified_at' => null,
        'must_change_password' => false,
    ]);
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();
    Employee::query()->create(['user_id' => $user->id]);

    return $user;
}

test('employee can request a password reset without revealing whether an email exists', function () {
    $user = passwordResetEmployee();

    $this->post(route('password-reset.request.store'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('password_reset_requests', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->post(route('password-reset.request.store'), ['email' => 'unknown@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(PasswordResetRequest::query()->count())->toBe(1);
});

test('admin accounts can request password reset but only superadmin can approve their requests', function () {
    Notification::fake();

    $adminAccount = passwordResetAdmin();

    $this->post(route('password-reset.request.store'), ['email' => $adminAccount->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    $resetRequest = PasswordResetRequest::query()->where('user_id', $adminAccount->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest))
        ->assertForbidden();

    $this->actingAs(passwordResetSuperadmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($resetRequest->fresh()->status)->toBe('approved');
    Notification::assertSentTo($adminAccount, PasswordResetApprovedNotification::class);

    $notification = Notification::sent($adminAccount, PasswordResetApprovedNotification::class)->first();
    $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));

    $this->post(route('password-reset.store', ['token' => $token]), [
        'password' => 'adminresetpassword1',
        'password_confirmation' => 'adminresetpassword1',
    ])->assertRedirect(route('login'));

    expect(Hash::check('adminresetpassword1', $adminAccount->fresh()->password))->toBeTrue()
        ->and($resetRequest->fresh()->status)->toBe('used');
});

test('only an administrator can approve a pending reset request and issue a link', function () {
    Notification::fake();

    $user = passwordResetEmployee();
    $this->post(route('password-reset.request.store'), ['email' => $user->email]);

    $resetRequest = PasswordResetRequest::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($resetRequest->fresh()->status)->toBe('approved');
    Notification::assertSentTo($user, PasswordResetApprovedNotification::class);
});

test('approved reset token can be used once, audits completion, notifies the owner, and invalidates previous sessions', function () {
    Notification::fake();

    $user = passwordResetEmployee();
    $this->post(route('password-reset.request.store'), ['email' => $user->email]);
    $resetRequest = PasswordResetRequest::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest));

    $notification = Notification::sent($user, PasswordResetApprovedNotification::class)->first();
    $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));
    $oldPasswordHash = $user->fresh()->getAuthPassword();
    $oldRememberToken = $user->fresh()->remember_token;
    $otherPendingRequest = PasswordResetRequest::create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $payload = [
        'password' => 'newsecurepassword1',
        'password_confirmation' => 'newsecurepassword1',
    ];

    $this->post(route('password-reset.store', ['token' => $token]), $payload)
        ->assertRedirect(route('login'));

    expect(Hash::check('newsecurepassword1', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($oldRememberToken)
        ->and($resetRequest->fresh()->status)->toBe('used')
        ->and($otherPendingRequest->fresh()->status)->toBe('expired');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => $user->getMorphClass(),
        'auditable_id' => $user->id,
        'action' => 'password_reset.completed',
    ]);

    Notification::assertSentTo($user, AccountSecurityNotification::class, fn (AccountSecurityNotification $securityNotification): bool => $securityNotification->subject === 'Password akun SanMal diubah melalui reset');

    // Authenticate with the persisted user state after the reset. The old session
    // still carries the pre-reset password hash and must be rejected by auth.session.
    $this->actingAs($user->fresh())
        ->withSession([
            'password_hash_web' => $oldPasswordHash,
            'auth.password_confirmed_at' => time(),
        ])
        ->get(route('security.edit'))
        ->assertRedirect(route('login'));

    $this->post(route('password-reset.store', ['token' => $token]), $payload)
        ->assertStatus(422);
});

test('approved reset requires at least eight characters and a number without requiring uppercase', function () {
    Notification::fake();

    $user = passwordResetEmployee();
    $this->post(route('password-reset.request.store'), ['email' => $user->email]);
    $resetRequest = PasswordResetRequest::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest));

    $notification = Notification::sent($user, PasswordResetApprovedNotification::class)->first();
    $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));

    $this->from(route('password-reset.show', ['token' => $token]))
        ->post(route('password-reset.store', ['token' => $token]), [
            'password' => 'lowercaseonly',
            'password_confirmation' => 'lowercaseonly',
        ])
        ->assertSessionHasErrors('password');

    $this->from(route('password-reset.show', ['token' => $token]))
        ->post(route('password-reset.store', ['token' => $token]), [
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])
        ->assertSessionHasErrors('password');

    expect($resetRequest->fresh()->status)->toBe('approved');
});

test('employee cannot request reset while account is inactive or awaiting first password change', function () {
    $inactive = passwordResetEmployee();
    $inactive->forceFill(['is_active' => false])->save();

    $pending = passwordResetEmployee();
    $pending->forceFill(['must_change_password' => true])->save();

    $this->post(route('password-reset.request.store'), ['email' => $inactive->email]);
    $this->post(route('password-reset.request.store'), ['email' => $pending->email]);

    expect(PasswordResetRequest::query()->count())->toBe(0);
});

test('employee request page and approved reset form are available in the account recovery flow', function () {
    Notification::fake();

    $this->get(route('password-reset.request.create'))
        ->assertOk()
        ->assertSee('Ajukan reset password');

    $user = passwordResetEmployee();
    $this->post(route('password-reset.request.store'), ['email' => $user->email]);

    $resetRequest = PasswordResetRequest::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest))
        ->assertRedirect();

    $notification = Notification::sent($user, PasswordResetApprovedNotification::class)->first();
    $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));

    $this->get(route('password-reset.show', ['token' => $token]))
        ->assertOk()
        ->assertSee('Buat password baru');
});

test('admin reset queue displays pending requests and decision controls', function () {
    $user = passwordResetEmployee();
    $resetRequest = PasswordResetRequest::create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs(passwordResetAdmin())
        ->get(route('admin.password-reset-requests.index'))
        ->assertOk()
        ->assertSee($user->email)
        ->assertSee('Setujui & kirim tautan')
        ->assertSee('Tolak permintaan');
});


test('password reset requests are additionally throttled per normalized email and IP', function () {
    $user = passwordResetEmployee();
    $action = app(\App\Actions\RequestEmployeePasswordReset::class);

    foreach (range(1, 4) as $attempt) {
        $action->handle(strtoupper($user->email));
    }

    $key = 'password-reset-request:'.hash('sha256', Str::lower($user->email).'|'.request()->ip());

    expect(RateLimiter::tooManyAttempts($key, 3))->toBeTrue();
});


test('Fortify public registration and password reset routes stay disabled', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('register.store'))->toBeFalse()
        ->and(Route::has('password.request'))->toBeFalse()
        ->and(Route::has('password.email'))->toBeFalse()
        ->and(Route::has('password.reset'))->toBeFalse()
        ->and(Route::has('password.update'))->toBeFalse()
        ->and(Route::has('verification.send'))->toBeTrue();
});
