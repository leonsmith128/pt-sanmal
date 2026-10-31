<?php

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SuperadminSeeder;

it('creates a superadmin account without automatically creating an employee profile', function () {
    $environment = [
        'SUPERADMIN_NAME' => 'SanMal Superadmin',
        'SUPERADMIN_EMAIL' => 'superadmin-seeder@example.test',
        'SUPERADMIN_PASSWORD' => 'temporary-test-password',
    ];

    foreach ($environment as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    try {
        app(SuperadminSeeder::class)->run();
    } finally {
        foreach (array_keys($environment) as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    $user = User::query()->where('email', 'superadmin-seeder@example.test')->firstOrFail();

    expect($user->isSuperadmin())->toBeTrue()
        ->and(Employee::query()->where('user_id', $user->id)->exists())->toBeFalse();
});
