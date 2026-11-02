<?php

it('renders a reusable button as a semantic action with forwarded attributes', function () {
    $this->blade(
        '<x-ui.button type="submit" variant="primary" x-on:click="ready = true">Simpan</x-ui.button>'
    )
        ->assertSee('type="submit"', false)
        ->assertSee('Simpan')
        ->assertSee('x-on:click="ready = true"', false);
});

it('renders a reusable button link and preserves Alpine href binding', function () {
    $this->blade(
        '<x-ui.button href="#" variant="primary" x-bind:href="exportUrl">Konfirmasi dan ekspor</x-ui.button>'
    )
        ->assertSee('href="#"', false)
        ->assertSee('x-bind:href="exportUrl"', false)
        ->assertSee('Konfirmasi dan ekspor');
});

it('defaults to a non-submitting action button', function () {
    $this->blade('<x-ui.button>Detail</x-ui.button>')
        ->assertSee('type="button"', false)
        ->assertSee('Detail');
});

it('forwards Livewire, accessibility, disabled, and presentation attributes', function () {
    $this->blade(
        '<x-ui.button wire:click="save" disabled aria-label="Simpan perubahan" class="w-full" data-test="save-button">Simpan</x-ui.button>'
    )
        ->assertSee('wire:click="save"', false)
        ->assertSee('disabled', false)
        ->assertSee('aria-label="Simpan perubahan"', false)
        ->assertSee('w-full', false)
        ->assertSee('data-test="save-button"', false);
});
