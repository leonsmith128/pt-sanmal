<?php

it('renders a labeled input with a connected description and validation error', function () {
    $this->withViewErrors([
        'email' => 'Email wajib diisi.',
    ]);

    $this->blade('
        <x-ui.input
            name="email"
            label="Email akun"
            type="email"
            description="Gunakan email aktif."
            autocomplete="email"
            required
        />
    ')
        ->assertSee('for="email"', false)
        ->assertSee('id="email"', false)
        ->assertSee('name="email"', false)
        ->assertSee('type="email"', false)
        ->assertSee('autocomplete="email"', false)
        ->assertSee('required', false)
        ->assertSee('Email akun')
        ->assertSee('Gunakan email aktif.')
        ->assertSee('Email wajib diisi.');
});

it('forwards Livewire and Alpine attributes to the interactive input', function () {
    $this->blade('
        <x-ui.input
            name="email"
            label="Email akun"
            wire:model.live="email"
            wire:loading.attr="disabled"
            wire:target="save"
            x-model="email"
            x-on:change="validateEmail()"
            aria-describedby="email-help"
        />
    ')
        ->assertSee('wire:model.live="email"', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('wire:target="save"', false)
        ->assertSee('x-model="email"', false)
        ->assertSee('x-on:change="validateEmail()"', false)
        ->assertSee('aria-describedby="email-help"', false);
});

it('allows a field wrapper to render custom controls without a label or error', function () {
    $this->blade('
        <x-ui.field name="custom" description="Informasi tambahan." :show-error="false">
            <select id="custom" name="custom">
                <option value="one">Satu</option>
            </select>
        </x-ui.field>
    ')
        ->assertSee('name="custom"', false)
        ->assertSee('Informasi tambahan.')
        ->assertSee('Satu');
});
