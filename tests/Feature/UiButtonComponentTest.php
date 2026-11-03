<?php

it('renders a reusable button as a semantic action with forwarded attributes', function () {
    $this->blade(
        '<x-ui.button type="submit" variant="primary" x-on:click="ready = true">Simpan</x-ui.button>'
    )
        ->assertSee('type="submit"', false)
        ->assertSee('sanmal-button-primary', false)
        ->assertSee('Simpan')
        ->assertSee('x-on:click="ready = true"', false);
});

it('renders a reusable button link and preserves Alpine href binding', function () {
    $this->blade(
        '<x-ui.button href="#" variant="primary" x-bind:href="exportUrl">Konfirmasi dan ekspor</x-ui.button>'
    )
        ->assertSee('href="#"', false)
        ->assertSee('sanmal-button-primary', false)
        ->assertSee('x-bind:href="exportUrl"', false)
        ->assertSee('Konfirmasi dan ekspor');
});

it('defaults to a non-submitting action button', function () {
    $this->blade('<x-ui.button>Detail</x-ui.button>')
        ->assertSee('type="button"', false)
        ->assertSee('Detail');
});
