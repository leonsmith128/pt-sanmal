<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config([
        'sanmal.superadmin.name' => 'SanMal Superadmin',
        'sanmal.superadmin.email' => 'superadmin-bootstrap@example.test',
        'sanmal.superadmin.password' => 'InitialSecure123',
    ]);
});

it('creates the first superadmin without an employee profile or automatic email verification', function () {
    expect(Artisan::call('sanmal:bootstrap-superadmin'))->toBe(0);

    $user = User::query()->where('email', 'superadmin-bootstrap@example.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Superadmin)
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->employee()->exists())->toBeFalse()
        ->and(Hash::check('InitialSecure123', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('InitialSecure123');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => $user->getMorphClass(),
        'auditable_id' => $user->id,
        'action' => 'superadmin.bootstrapped',
    ]);
});

it('does not mutate or replace an existing superadmin when bootstrap runs again', function () {
    $existing = User::factory()->create([
        'name' => 'Existing Superadmin',
        'email' => 'existing-superadmin@example.test',
        'password' => 'ExistingPassword123',
    ]);
    $existing->forceFill(['role' => UserRole::Superadmin])->save();

    config(['sanmal.superadmin.name' => null, 'sanmal.superadmin.email' => null, 'sanmal.superadmin.password' => null]);

    expect(Artisan::call('sanmal:bootstrap-superadmin'))->toBe(0)
        ->and(User::query()->where('role', UserRole::Superadmin)->count())->toBe(1)
        ->and($existing->fresh()->email)->toBe('existing-superadmin@example.test')
        ->and(Hash::check('ExistingPassword123', $existing->fresh()->password))->toBeTrue();
});

it('refuses to use a configured email that belongs to another account', function () {
    User::factory()->create(['email' => 'superadmin-bootstrap@example.test']);

    expect(Artisan::call('sanmal:bootstrap-superadmin'))->toBe(1)
        ->and(User::query()->where('role', UserRole::Superadmin)->exists())->toBeFalse();
});

it('refuses incomplete or weak bootstrap credentials', function () {
    config(['sanmal.superadmin.password' => 'weak']);

    expect(Artisan::call('sanmal:bootstrap-superadmin'))->toBe(1)
        ->and(User::query()->where('role', UserRole::Superadmin)->exists())->toBeFalse();

    config(['sanmal.superadmin.name' => null]);

    expect(Artisan::call('sanmal:bootstrap-superadmin'))->toBe(1)
        ->and(User::query()->where('role', UserRole::Superadmin)->exists())->toBeFalse();
});

it('enforces a single superadmin through a database unique generated key', function () {
    $first = User::factory()->create();
    $first->forceFill(['role' => UserRole::Superadmin])->save();

    $second = User::factory()->create();

    expect(fn () => $second->forceFill(['role' => UserRole::Superadmin])->save())
        ->toThrow(QueryException::class);

    expect(User::query()->where('role', UserRole::Superadmin)->count())->toBe(1);
});
