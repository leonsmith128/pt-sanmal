<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('pages::notifications.index', compact('notifications'));
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_id === $request->user()->getKey()
            && $notification->notifiable_type === $request->user()::class, 404);

        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');
        $target = is_string($url) && Str::startsWith($url, url('/'))
            ? $url
            : route('dashboard');

        return redirect()->to($target);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return to_route('notifications.index')->with('status', 'Semua notifikasi ditandai telah dibaca.');
    }
}
