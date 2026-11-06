<?php

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', EnsureUserIsActive::class, EnsurePasswordIsChanged::class])->group(function () {
    Route::redirect('settings', '/profil');
    Route::redirect('settings/profile', '/profil');
    Route::redirect('settings/appearance', '/profil');
    Route::redirect('settings/security', '/keamanan-akun');

    Route::livewire('keamanan-akun', 'pages::settings.security')
        ->middleware('password.confirm')
        ->name('security.edit');
});
