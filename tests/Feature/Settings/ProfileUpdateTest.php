<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed at the short profile URL', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/profil')->assertOk();
});

test('employees can update login email from account security without changing official name', function () {
    $user = User::factory()->create([
        'name' => 'Official Name',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('email', 'new@example.com')
        ->call('updateEmail')
        ->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('new@example.com')
        ->and($user->fresh()->name)->toBe('Official Name')
        ->and($user->fresh()->email_verified_at)->toBeNull();
});

test('email verification status remains unchanged when email does not change', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('email', $user->email)
        ->call('updateEmail')
        ->assertHasNoErrors();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('security page combines email and password controls', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->get('/keamanan-akun')
        ->assertOk();

    Livewire::test('pages::settings.security')
        ->assertSee('Alamat email')
        ->assertSee('Ubah kata sandi');
});
