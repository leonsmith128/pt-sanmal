<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeAccountActivation;
use App\Models\EmployeeAccountCredential;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use App\Notifications\EmployeeAccountActivationNotification;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function accountManagementAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $admin;
}

function accountManagementSuperadmin(): User
{
    $superadmin = User::factory()->create();
    $superadmin->forceFill(['role' => UserRole::Superadmin, 'is_active' => true])->save();

    return $superadmin;
}

function pendingAccountEmployee(): Employee
{
    $user = User::factory()->create([
        'email_verified_at' => null,
        'must_change_password' => true,
    ]);
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true, 'must_change_password' => true])->save();

    return Employee::query()->create(['user_id' => $user->id, 'position' => 'technician']);
}

test('superadmin creates employee account and profile atomically with encrypted temporary credential', function () {
    Notification::fake();

    $this->actingAs(accountManagementSuperadmin())
        ->post(route('admin.employees.store'), [
            'name' => 'Pegawai Baru',
            'email' => 'pegawai.baru@example.com',
            'role' => 'employee',
            'position' => 'technician',
            'employee_number' => 'EMP-TAMPERED',
            'phone' => '081234567890',
            'joined_at' => '2026-11-05',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'pegawai.baru@example.com')->firstOrFail();
    $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

    $temporaryPassword = Crypt::decryptString($user->accountCredential->temporary_password_encrypted);

    expect($temporaryPassword)->toMatch('/\\A(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{8}\\z/')
        ->and($user->role)->toBe(UserRole::Employee)
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and($employee->employee_number)->toBe('EMP-01')
        ->and($user->accountCredential)->not->toBeNull()
        ->and(Crypt::decryptString($user->accountCredential->temporary_password_encrypted))->not->toBeEmpty();

    Notification::assertSentTo($user, EmployeeAccountActivationNotification::class);
});

test('superadmin can create an admin account with an admin-only position', function () {
    Notification::fake();

    $this->actingAs(accountManagementSuperadmin())
        ->post(route('admin.employees.store'), [
            'name' => 'Staf Administrasi Baru',
            'email' => 'admin.baru@example.test',
            'role' => 'admin',
            'position' => 'staff_administration',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'admin.baru@example.test')->firstOrFail();
    $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

    expect($user->role)->toBe(UserRole::Admin)
        ->and($employee->position)->toBe('staff_administration')
        ->and($employee->employee_number)->toBe('EMP-01')
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, EmployeeAccountActivationNotification::class);
});

test('superadmin cannot assign an employee position to an admin account', function () {
    $this->actingAs(accountManagementSuperadmin())
        ->from(route('admin.employees.create'))
        ->post(route('admin.employees.store'), [
            'name' => 'Admin Tidak Valid',
            'email' => 'invalid.admin@example.test',
            'role' => 'admin',
            'position' => 'technician',
        ])
        ->assertSessionHasErrors('position');

    expect(User::query()->where('email', 'invalid.admin@example.test')->exists())->toBeFalse();
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
        ->assertHeader('Cache-Control', 'no-store, private')
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
    $oldRememberToken = $employee->user->remember_token;
    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString('temporary-password-value'),
    ]);

    $this->actingAs($employee->user)
        ->post(route('account.password.first-change.store'), [
            'password' => 'personalpassword1',
            'password_confirmation' => 'personalpassword1',
        ])
        ->assertRedirect(route('dashboard'));

    $user = $employee->user->fresh();

    expect($user->must_change_password)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->accountCredential)->toBeNull()
        ->and(Hash::check('personalpassword1', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken);

    $this->get(route('dashboard'))->assertOk();
});

test('first password change requires at least eight characters and a number without requiring uppercase', function () {
    $employee = pendingAccountEmployee();

    $this->actingAs($employee->user)
        ->from(route('account.password.first-change'))
        ->post(route('account.password.first-change.store'), [
            'password' => 'lowercaseonly',
            'password_confirmation' => 'lowercaseonly',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($employee->user)
        ->from(route('account.password.first-change'))
        ->post(route('account.password.first-change.store'), [
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])
        ->assertSessionHasErrors('password');

    $this->assertTrue($employee->user->fresh()->must_change_password);
});

test('activation link sets a password without verifying email and cannot be reused', function () {
    $employee = pendingAccountEmployee();
    $oldRememberToken = $employee->user->remember_token;
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
        'password' => 'activatedpassword1',
        'password_confirmation' => 'activatedpassword1',
    ];

    $this->post(route('employee-activation.store', ['token' => $token]), $payload)
        ->assertRedirect(route('login'));

    $user = $employee->user->fresh();

    expect($user->must_change_password)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->accountCredential)->toBeNull()
        ->and($user->accountActivation->consumed_at)->not->toBeNull()
        ->and(Hash::check('activatedpassword1', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken);

    $this->post(route('employee-activation.store', ['token' => $token]), $payload)
        ->assertStatus(422);
});

test('email change clears previous email verification and notifies the old address', function () {
    Notification::fake();

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
            'position' => 'technician',
            'joined_at' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($employee->user->fresh()->email)->toBe('updated.employee@example.com')
        ->and($employee->user->fresh()->email_verified_at)->toBeNull();

    Notification::assertSentTo($employee->user->fresh(), VerifyEmail::class);
    Notification::assertSentOnDemand(
        AccountSecurityNotification::class,
        fn (AccountSecurityNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === $employee->user->email
            && $notification->subject === 'Email akun SanMal diubah',
    );
});

test('admin can rotate a temporary password and invalidate the previous activation link', function () {
    Notification::fake();

    $employee = pendingAccountEmployee();
    $oldPassword = 'old-temporary-password';
    $oldToken = str_repeat('b', 64);

    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString($oldPassword),
    ]);
    EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', $oldToken),
        'expires_at' => now()->addHours(24),
    ]);

    $this->actingAs(accountManagementAdmin())
        ->post(route('admin.employees.rotate-temporary-password', $employee))
        ->assertRedirect();

    $user = $employee->user->fresh();
    $newPassword = Crypt::decryptString($user->accountCredential->temporary_password_encrypted);

    expect($newPassword)->toMatch('/\\A(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{8}\\z/')
        ->and($newPassword)->not->toBe($oldPassword)
        ->and(Hash::check($newPassword, $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->accountActivation->token_hash)->not->toBe(hash('sha256', $oldToken))
        ->and($user->accountActivation->consumed_at)->toBeNull();

    Notification::assertSentTo($user, EmployeeAccountActivationNotification::class);
});

test('deactivation revokes activation and password reset tokens without deleting employee data', function () {
    $employee = pendingAccountEmployee();
    $activation = EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', str_repeat('c', 64)),
        'expires_at' => now()->addHours(24),
    ]);
    $resetRequest = PasswordResetRequest::create([
        'user_id' => $employee->user_id,
        'status' => 'pending',
    ]);

    $this->actingAs(accountManagementAdmin())
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'employee_number' => $employee->employee_number,
            'phone' => $employee->phone,
            'position' => 'technician',
            'joined_at' => null,
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($employee->fresh())->not->toBeNull()
        ->and($employee->user->fresh()->is_active)->toBeFalse()
        ->and($activation->fresh()->consumed_at)->not->toBeNull()
        ->and($resetRequest->fresh()->status)->toBe('expired')
        ->and($resetRequest->fresh()->token_hash)->toBeNull();
});

test('changing the email of a pending employee issues a new activation link to the new address', function () {
    Notification::fake();

    $employee = pendingAccountEmployee();
    $oldToken = str_repeat('d', 64);

    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString('temporary-password-value'),
    ]);
    EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', $oldToken),
        'expires_at' => now()->addHours(24),
    ]);

    $this->actingAs(accountManagementAdmin())
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->user->name,
            'email' => 'new.address@example.com',
            'employee_number' => $employee->employee_number,
            'phone' => $employee->phone,
            'position' => 'technician',
            'joined_at' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    $user = $employee->user->fresh();

    expect($user->email)->toBe('new.address@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->accountActivation->token_hash)->not->toBe(hash('sha256', $oldToken))
        ->and($user->accountActivation->consumed_at)->toBeNull();

    Notification::assertSentTo($user, EmployeeAccountActivationNotification::class);
});

test('pending employee cannot bypass the first password change through account settings', function () {
    $employee = pendingAccountEmployee();

    $this->actingAs($employee->user)
        ->get(route('security.edit'))
        ->assertRedirect(route('account.password.first-change'));
});

test('admin employee detail exposes account status and a protected temporary-password copy action', function () {
    $employee = pendingAccountEmployee();

    EmployeeAccountCredential::create([
        'user_id' => $employee->user_id,
        'temporary_password_encrypted' => Crypt::encryptString('temporary-password-value'),
    ]);
    EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', str_repeat('e', 64)),
        'expires_at' => now()->addHours(24),
    ]);

    $this->actingAs(accountManagementAdmin())
        ->get(route('admin.employees.edit', $employee))
        ->assertOk()
        ->assertSee('Status akun')
        ->assertSee('Belum terverifikasi')
        ->assertSee('Salin password sementara')
        ->assertSee('password-sementara');
});

test('admin employee index labels pending accounts consistently on desktop and mobile', function () {
    pendingAccountEmployee();

    $this->actingAs(accountManagementAdmin())
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertSee('Menunggu aktivasi');
});

test('activation and first password change views render independently from email verification', function () {
    $employee = pendingAccountEmployee();
    $token = str_repeat('f', 64);

    EmployeeAccountActivation::create([
        'user_id' => $employee->user_id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHours(24),
    ]);

    $this->get(route('employee-activation.show', ['token' => $token]))
        ->assertOk()
        ->assertSee('Verifikasi email merupakan proses terpisah');

    $this->actingAs($employee->user)
        ->get(route('account.password.first-change'))
        ->assertOk()
        ->assertSee('Verifikasi email dilakukan terpisah');
});

test('employee number generation continues from EMP-99 to EMP-100', function () {
    Notification::fake();

    foreach (range(1, 98) as $number) {
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
        ]);
    }

    $superadmin = accountManagementSuperadmin();

    $this->actingAs($superadmin)
        ->post(route('admin.employees.store'), [
            'name' => 'Pegawai Sembilan Puluh Sembilan',
            'email' => 'employee.99@example.test',
            'role' => 'employee',
            'position' => 'technician',
        ])
        ->assertRedirect();

    $employee99 = Employee::query()
        ->whereHas('user', fn ($query) => $query->where('email', 'employee.99@example.test'))
        ->firstOrFail();

    expect($employee99->employee_number)->toBe('EMP-99');

    $this->actingAs($superadmin)
        ->post(route('admin.employees.store'), [
            'name' => 'Pegawai Seratus',
            'email' => 'employee.100@example.test',
            'role' => 'employee',
            'position' => 'technician',
        ])
        ->assertRedirect();

    $employee100 = Employee::query()
        ->whereHas('user', fn ($query) => $query->where('email', 'employee.100@example.test'))
        ->firstOrFail();

    expect($employee100->employee_number)->toBe('EMP-100');
});

test('employee number allocation rolls back with the employee creation transaction', function () {
    $generator = app(EmployeeNumberGenerator::class);

    expect(fn () => DB::transaction(function () use ($generator): void {
        expect($generator->next())->toBe('EMP-01');

        throw new RuntimeException('Rollback employee creation.');
    }))->toThrow(RuntimeException::class);

    expect($generator->preview())->toBe('EMP-01');
});

test('employee number cannot be changed through the employee update request', function () {
    $employee = pendingAccountEmployee();
    $employee->update(['employee_number' => 'EMP-07']);

    $this->actingAs(accountManagementAdmin())
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'employee_number' => 'EMP-88',
            'phone' => $employee->phone,
            'position' => 'technician',
            'joined_at' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($employee->fresh()->employee_number)->toBe('EMP-07');
});
