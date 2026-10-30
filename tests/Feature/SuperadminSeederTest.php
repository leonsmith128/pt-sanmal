<?php

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SuperadminSeeder;

it('creates a superadmin account without automatically creating an employee profile', function () {
    putenv('SUPERADMIN_NAME=SanMal Superadmin');
    putenv('SUPERADMIN_EMAIL=superadmin-seeder@example.test');
    putenv('SUPERADMIN_PASSWORD=temporary-test-password');

    try {
        app(SuperadminSeeder::class)->run();
    } finally {
        putenv('SUPERADMIN_NAME');
        putenv('SUPERADMIN_EMAIL');
        putenv('SUPERADMIN_PASSWORD');
    }

    $user = User::query()->where('email', 'superadmin-seeder@example.test')->firstOrFail();

    expect($user->isSuperadmin())->toBeTrue()
        ->and(Employee::query()->where('user_id', $user->id)->exists())->toBeFalse();
});
