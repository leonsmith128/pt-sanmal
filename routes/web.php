<?php

use App\Enums\UserRole;
use App\Http\Controllers\AttendanceSelfieController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('absensi', 'pages::attendance')->name('attendance');

    // Halaman pengelolaan: admin dan superadmin saja.
    // Berada DI DALAM grup auth + verified, jadi tamu selalu diarahkan ke login lebih dulu.
    Route::middleware(EnsureUserHasRole::using(UserRole::Admin, UserRole::Superadmin))
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('kehadiran', 'pages::admin.kehadiran')->name('kehadiran');
        Route::get('attendance/{record}/selfie', [AttendanceSelfieController::class, 'show'])->name('attendance.selfie');
    });
});

Route::fallback(function () {
    return auth()->check() ? abort(404) : redirect()->route('login');
});

require __DIR__.'/settings.php';
