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
