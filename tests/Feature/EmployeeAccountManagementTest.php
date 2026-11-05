<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeAccountActivation;
use App\Models\EmployeeAccountCredential;
use App\Models\User;
use App\Notifications\EmployeeAccountActivationNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function accountManagementAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $admin;
}

function pendingAccountEmployee(): Employee
{
    $user = User::factory()->create([
        'email_verified_at' => null,
        'must_change_password' => true,
    ]);
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    return Employee::query()->create(['user_id' => $user->id]);
}

test('admin creates employee account and profile atomically with encrypted temporary credential', function () {
    Notification::fake();

    $this->actingAs(accountManagementAdmin())
        ->post(route('admin.employees.store'), [
            'name' => 'Pegawai Baru',
            'email' => 'pegawai.baru@example.com',
            'employee_number' => 'EMP-NEW',
            'phone' => '081234567890',
            'title_id' => null,
            'joined_at' => '2026-11-05',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'pegawai.baru@example.com')->firstOrFail();
    $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

    expect($user->role)->toBe(UserRole::Employee)
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and($employee->employee_number)->toBe('EMP-NEW')
        ->and($user->accountCredential)->not->toBeNull()
        ->and(Crypt::decryptString($user->accountCredential->temporary_password_encrypted))->not->toBeEmpty();

    Notification::assertSentTo($user, EmployeeAccountActivationNotification::class);
});

test('admin can copy the same temporary password repeatedly while employee cannot access protected endpoints', function () {
    $employee = pendingAccountEmployee();
    $temporaryPassword = 'temporary-password-value';
    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString($temporaryPassword),
    ]);

    $admin = accountManagementAdmin();

    $first = $this->actingAs($admin)
        ->postJson(route('admin.employees.temporary-password', $employee))
        ->assertOk()
        ->assertJsonPath('password', $temporaryPassword);

    $second = $this->actingAs($admin)
        ->postJson(route('admin.employees.temporary-password', $employee))
        ->assertOk()
        ->assertJsonPath('password', $temporaryPassword);

    expect($first->json('password'))->toBe($second->json('password'));

    $this->actingAs($employee->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('account.password.first-change'));
});

test('first password change removes temporary credential without verifying email', function () {
    $employee = pendingAccountEmployee();
    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString('temporary-password-value'),
    ]);

    $this->actingAs($employee->user)
        ->post(route('account.password.first-change.store'), [
            'password' => 'personal-password-value',
            'password_confirmation' => 'personal-password-value',
        ])
        ->assertRedirect(route('dashboard'));

    $user = $employee->user->fresh();

    expect($user->must_change_password)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->accountCredential)->toBeNull()
        ->and(Hash::check('personal-password-value', $user->password))->toBeTrue();
});

test('activation link sets a password, verifies email, and cannot be reused', function () {
    $employee = pendingAccountEmployee();
    $token = str_repeat('a', 64);

    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString('temporary-password-value'),
    ]);
    EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHours(24),
    ]);

    $payload = [
        'password' => 'activated-password-value',
        'password_confirmation' => 'activated-password-value',
    ];

    $this->post(route('employee-activation.store', ['token' => $token]), $payload)
        ->assertRedirect(route('login'));

    $user = $employee->user->fresh();

    expect($user->must_change_password)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->accountCredential)->toBeNull()
        ->and($user->accountActivation->consumed_at)->not->toBeNull()
        ->and(Hash::check('activated-password-value', $user->password))->toBeTrue();

    $this->post(route('employee-activation.store', ['token' => $token]), $payload)
        ->assertStatus(422);
});

test('email change clears previous email verification', function () {
    $employee = pendingAccountEmployee();
    $employee->user->forceFill([
        'must_change_password' => false,
        'email_verified_at' => now(),
    ])->save();

    $this->actingAs(accountManagementAdmin())
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->user->name,
            'email' => 'updated.employee@example.com',
            'employee_number' => $employee->employee_number,
            'phone' => $employee->phone,
            'title_id' => null,
            'joined_at' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($employee->user->fresh()->email)->toBe('updated.employee@example.com')
        ->and($employee->user->fresh()->email_verified_at)->toBeNull();
});
