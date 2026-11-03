<?php

it('renders a content card with shadow-only surface, default spacing, and forwarded attributes', function () {
    $this->blade('<x-ui.card class="md:col-span-2"><p>Konten card</p></x-ui.card>')
        ->assertSee('rounded-xl bg-white text-zinc-900 shadow-sm', false)
        ->assertSee('dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-black/20', false)
        ->assertDontSee('border', false)
        ->assertSee('p-4', false)
        ->assertSee('md:col-span-2', false)
        ->assertSee('Konten card');
});

it('renders a linked card as a semantic anchor with shadow hover treatment', function () {
    $this->blade('<x-ui.card href="/admin/pegawai" padding="p-5">Buka pegawai</x-ui.card>')
        ->assertSee('<a', false)
        ->assertSee('href="/admin/pegawai"', false)
        ->assertSee('shadow-sm transition-shadow', false)
        ->assertSee('hover:shadow-md', false)
        ->assertDontSee('border', false)
        ->assertSee('p-5', false)
        ->assertSee('Buka pegawai');
});
