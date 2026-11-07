<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

test('notification dropdown renders unread state and the all notifications link', function () {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\TestNotification',
        'data' => [
            'title' => 'Pengajuan disetujui',
            'message' => 'Pengajuan koreksi absensi telah disetujui.',
        ],
        'read_at' => null,
    ]);

    $html = Blade::render(
        '<x-ui.notification-dropdown :notifications="$notifications" :unread-count="1" />',
        ['notifications' => collect([$notification])],
    );

    expect($html)
        ->toContain('Pengajuan disetujui')
        ->toContain('Pengajuan koreksi absensi telah disetujui.')
        ->toContain('Belum dibaca')
        ->toContain(route('notifications.index'))
        ->toContain(route('notifications.read', $notification));
});

test('notification dropdown renders an empty state when there are no notifications', function () {
    $html = Blade::render(
        '<x-ui.notification-dropdown :notifications="$notifications" :unread-count="0" />',
        ['notifications' => collect()],
    );

    expect($html)
        ->toContain('Belum ada notifikasi')
        ->toContain('Lihat semua notifikasi')
        ->toContain(route('notifications.index'));
});

test('notification dropdown is a popover that closes on outside click and Escape', function () {
    $html = Blade::render(
        '<x-ui.notification-dropdown :notifications="$notifications" :unread-count="0" />',
        ['notifications' => collect()],
    );

    expect($html)
        ->toContain('x-on:click.outside="open = false"')
        ->toContain('x-on:keydown.escape.window')
        ->toContain('aria-expanded')
        ->toContain('aria-controls="notification-panel-')
        ->toContain('aria-label="Buka notifikasi"')
        ->not->toContain('<details')
        ->not->toContain('<summary');
});

test('opening a notification marks only the authenticated user notification as read', function () {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['title' => 'Pengajuan disetujui'],
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification))
        ->assertRedirect(route('dashboard'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user cannot open another users notification', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = $owner->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['title' => 'Pengajuan disetujui'],
        'read_at' => null,
    ]);

    $this->actingAs($otherUser)
        ->patch(route('notifications.read', $notification))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});
