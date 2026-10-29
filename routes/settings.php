<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Keep old settings URLs working while directing users to the simpler account pages.
    Route::redirect('settings', '/profil');
    Route::redirect('settings/profile', '/profil');
    Route::redirect('settings/appearance', '/profil');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('keamanan-akun', 'pages::settings.security')
        ->middleware('password.confirm')
        ->name('security.edit');

    Route::redirect('settings/security', '/keamanan-akun');
});
