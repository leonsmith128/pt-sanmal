<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;

test('application sidebar composer prepares manager navigation and system notifications', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();

    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['title' => 'Pengajuan disetujui'],
        'read_at' => null,
    ]);

    $view = view('layouts::app.sidebar');
    $data = $view->getData();

    expect($data['sidebarUser']->is($user))->toBeTrue()
        ->and($data['sidebarEmployee'])->toBeNull()
        ->and($data['isManager'])->toBeTrue()
        ->and($data['isEmployee'])->toBeFalse()
        ->and($data['unreadSystemNotifications'])->toBe(1)
        ->and($data['latestSystemNotifications']->pluck('id'))->toContain($notification->id)
        ->and($data['unseenRequestDecisions'])->toBe(0);
});

test('application sidebar composer prepares employee decision badge data', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::query()->create(['user_id' => $user->id]);

    $view = view('layouts::app.sidebar');
    app(AppSidebarComposer::class)->compose($view);
    $data = $view->getData();

    expect($data['sidebarEmployee']->is($employee))->toBeTrue()
        ->and($data['isManager'])->toBeFalse()
        ->and($data['isEmployee'])->toBeTrue()
        ->and($data['unseenRequestDecisions'])->toBe(0)
        ->and($data['latestSystemNotifications'])->toHaveCount(0);
});

test('application sidebar composer safely handles a guest view context', function () {
    auth()->logout();

    $view = view('layouts::app.sidebar');
    app(AppSidebarComposer::class)->compose($view);
    $data = $view->getData();

    expect($data['sidebarUser'])->toBeNull()
        ->and($data['sidebarEmployee'])->toBeNull()
        ->and($data['isManager'])->toBeFalse()
        ->and($data['isEmployee'])->toBeFalse()
        ->and($data['unreadSystemNotifications'])->toBe(0)
        ->and($data['latestSystemNotifications'])->toBeEmpty()
        ->and($data['unseenRequestDecisions'])->toBe(0);
});
