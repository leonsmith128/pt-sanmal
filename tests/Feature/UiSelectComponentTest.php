<?php

it('renders a labeled select with description and validation error', function () {
    $this->withViewErrors([
        'title_id' => 'Jabatan tidak valid.',
    ]);

    $this->blade('
        <x-ui.select
            name="title_id"
            label="Jabatan"
            description="Pilih jabatan pegawai."
            placeholder="Pilih jabatan"
        >
            <flux:select.option value="">Belum ditentukan</flux:select.option>
            <flux:select.option value="1">Teknisi</flux:select.option>
        </x-ui.select>
    ')
        ->assertSee('for="title_id"', false)
        ->assertSee('id="title_id"', false)
        ->assertSee('name="title_id"', false)
        ->assertSee('Jabatan')
        ->assertSee('Pilih jabatan pegawai.')
        ->assertSee('Pilih jabatan')
        ->assertSee('Teknisi')
        ->assertSee('Jabatan tidak valid.');
});

it('forwards Livewire, Alpine, and native attributes to the select control', function () {
    $this->blade('
        <x-ui.select
            name="title_id"
            label="Jabatan"
            wire:model.live="titleId"
            wire:loading.attr="disabled"
            x-model="titleId"
            x-on:change="refreshTitle()"
            aria-describedby="title-help"
            required
            disabled
        >
            <option value="1">Teknisi</option>
        </x-ui.select>
    ')
        ->assertSee('wire:model.live="titleId"', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('x-model="titleId"', false)
        ->assertSee('x-on:change="refreshTitle()"', false)
        ->assertSee('aria-describedby="title-help"', false)
        ->assertSee('required', false)
        ->assertSee('disabled', false)
        ->assertSee('name="title_id"', false);
});

it('allows a select to use a custom id and suppress its validation error', function () {
    $this->withViewErrors([
        'title_id' => 'Jabatan tidak valid.',
    ]);

    $this->blade('
        <x-ui.select
            name="title_id"
            id="employee-title"
            label="Jabatan"
            :show-error="false"
        >
            <option value="1">Teknisi</option>
        </x-ui.select>
    ')
        ->assertSee('for="employee-title"', false)
        ->assertSee('id="employee-title"', false)
        ->assertDontSee('Jabatan tidak valid.');
});
