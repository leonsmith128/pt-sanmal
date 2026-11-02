<?php

it('renders a labeled checkbox with description and validation error', function () {
    $this->withViewErrors([
        'remember' => 'Pilihan ini wajib dikonfirmasi.',
    ]);

    $this->blade('
        <x-ui.checkbox
            name="remember"
            label="Ingat saya"
            description="Tetap masuk pada perangkat ini."
            :checked="true"
            required
        />
    ')
        ->assertSee('name="remember"', false)
        ->assertSee('id="remember"', false)
        ->assertSee('Ingat saya')
        ->assertSee('Tetap masuk pada perangkat ini.')
        ->assertSee('checked', false)
        ->assertSee('required', false)
        ->assertSee('Pilihan ini wajib dikonfirmasi.');
});

it('forwards Livewire, Alpine, and native attributes to the checkbox control', function () {
    $this->blade('
        <x-ui.checkbox
            name="remember"
            label="Ingat saya"
            wire:model.live="remember"
            wire:loading.attr="disabled"
            x-model="remember"
            x-on:change="syncRemember()"
            aria-describedby="remember-help"
            disabled
        />
    ')
        ->assertSee('wire:model.live="remember"', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('x-model="remember"', false)
        ->assertSee('x-on:change="syncRemember()"', false)
        ->assertSee('aria-describedby="remember-help"', false)
        ->assertSee('disabled', false)
        ->assertSee('name="remember"', false);
});

it('supports a custom checkbox id and suppresses its validation error', function () {
    $this->withViewErrors([
        'remember' => 'Pilihan ini wajib dikonfirmasi.',
    ]);

    $this->blade('
        <x-ui.checkbox
            name="remember"
            id="remember-device"
            label="Ingat perangkat"
            :show-error="false"
        />
    ')
        ->assertSee('id="remember-device"', false)
        ->assertDontSee('Pilihan ini wajib dikonfirmasi.');
});
