<?php

it('renders a content card with default spacing and forwarded attributes', function () {
    $this->blade('<x-ui.card class="md:col-span-2"><p>Konten card</p></x-ui.card>')
        ->assertSee('rounded-xl border', false)
        ->assertSee('bg-sanmal-surface-primary', false)
        ->assertSee('p-4', false)
        ->assertSee('md:col-span-2', false)
        ->assertSee('Konten card');
});

it('renders a linked card as a semantic anchor', function () {
    $this->blade('<x-ui.card href="/admin/pegawai" padding="p-5">Buka pegawai</x-ui.card>')
        ->assertSee('<a', false)
        ->assertSee('href="/admin/pegawai"', false)
        ->assertSee('p-5', false)
        ->assertSee('Buka pegawai');
});
