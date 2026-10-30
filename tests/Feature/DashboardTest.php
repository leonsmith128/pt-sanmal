<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Selamat datang')
        ->assertDontSee('Dashboard Administrasi')
        ->assertDontSee('Pegawai aktif');
});

test('administrators see the administrative dashboard summary', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => \App\Enums\UserRole::Admin])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Administrasi')
        ->assertSee('Pegawai aktif')
        ->assertSee('Aktivitas absensi terbaru')
        ->assertSee('Permohonan menunggu');
});
