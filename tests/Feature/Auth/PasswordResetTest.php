<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetApprovedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function passwordResetAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $admin;
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

test('approved reset token can be used once and updates the password', function () {
    Notification::fake();

    $user = passwordResetEmployee();
    $this->post(route('password-reset.request.store'), ['email' => $user->email]);
    $resetRequest = PasswordResetRequest::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs(passwordResetAdmin())
        ->post(route('admin.password-reset-requests.approve', $resetRequest));

    $notification = Notification::sent($user, PasswordResetApprovedNotification::class)->first();
    $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));

    $payload = [
        'password' => 'newsecurepassword1',
        'password_confirmation' => 'newsecurepassword1',
    ];

    $this->post(route('password-reset.store', ['token' => $token]), $payload)
        ->assertRedirect(route('login'));

    expect(Hash::check('newsecurepassword1', $user->fresh()->password))->toBeTrue()
        ->and($resetRequest->fresh()->status)->toBe('used');

    $this->post(route('password-reset.store', ['token' => $token]), $payload)
        ->assertStatus(422);
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
