<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

test('application sidebar composer supplies manager data when the actual Blade component renders', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();
    auth()->login($user);

    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['title' => 'Pengajuan disetujui'],
        'read_at' => null,
    ]);

    $sidebarData = null;

    View::composer('*::app.sidebar', function ($view) use (&$sidebarData): void {
        $sidebarData = $view->getData();
    });

    $rendered = Blade::render('<x-layouts::app.sidebar>Sidebar test content</x-layouts::app.sidebar>', [], true);

    expect($rendered)->toContain('Sidebar test content')
        ->and($sidebarData)->not->toBeNull()
        ->and($sidebarData['sidebarUser']->is($user))->toBeTrue()
        ->and($sidebarData['sidebarEmployee'])->toBeNull()
        ->and($sidebarData['isManager'])->toBeTrue()
        ->and($sidebarData['isEmployee'])->toBeFalse()
        ->and($sidebarData['unreadSystemNotifications'])->toBe(1)
        ->and($sidebarData['latestSystemNotifications']->pluck('id'))->toContain($notification->id)
        ->and($sidebarData['unseenRequestDecisions'])->toBe(0);
});

test('application sidebar composer supplies employee data when the actual Blade component renders', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    auth()->login($user);
    $employee = Employee::query()->create(['user_id' => $user->id]);

    $sidebarData = null;

    View::composer('*::app.sidebar', function ($view) use (&$sidebarData): void {
        $sidebarData = $view->getData();
    });

    $rendered = Blade::render('<x-layouts::app.sidebar>Sidebar test content</x-layouts::app.sidebar>', [], true);

    expect($rendered)->toContain('Sidebar test content')
        ->and($sidebarData)->not->toBeNull()
        ->and($sidebarData['sidebarEmployee']->is($employee))->toBeTrue()
        ->and($sidebarData['isManager'])->toBeFalse()
        ->and($sidebarData['isEmployee'])->toBeTrue()
        ->and($sidebarData['unseenRequestDecisions'])->toBe(0)
        ->and($sidebarData['latestSystemNotifications'])->toHaveCount(0);
});

test('application sidebar composer safely handles a guest view context', function () {
    auth()->logout();

    $view = view('layouts::app.sidebar');
    app(App\View\Composers\AppSidebarComposer::class)->compose($view);
    $data = $view->getData();

    expect($data['sidebarUser'])->toBeNull()
        ->and($data['sidebarEmployee'])->toBeNull()
        ->and($data['isManager'])->toBeFalse()
        ->and($data['isEmployee'])->toBeFalse()
        ->and($data['unreadSystemNotifications'])->toBe(0)
        ->and($data['latestSystemNotifications'])->toBeEmpty()
        ->and($data['unseenRequestDecisions'])->toBe(0);
});
