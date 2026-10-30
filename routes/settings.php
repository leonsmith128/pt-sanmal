<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Keep old settings URLs working while directing users to the simpler account pages.
    Route::redirect('settings', '/profil');
    Route::redirect('settings/profile', '/profil');
    Route::redirect('settings/appearance', '/profil');
    Route::redirect('settings/security', '/keamanan-akun');

    // Keep account security accessible after an email change, even before the new
    // address is verified, so the user can resend verification or correct a typo.
    Route::livewire('keamanan-akun', 'pages::settings.security')
        ->middleware('password.confirm')
        ->name('security.edit');
});
