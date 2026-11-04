<?php

it('renders a content card with shadow-only surface, default spacing, and forwarded attributes', function () {
    $this->blade('<x-ui.card class="md:col-span-2"><p>Konten card</p></x-ui.card>')
        ->assertSee('rounded-xl bg-sanmal-surface-card text-[var(--sanmal-text-primary)] shadow-sm', false)
        ->assertSee('dark:bg-sanmal-surface-card-dark dark:shadow-black/20', false)
        ->assertSee('bg-sanmal-surface-card', false)
        ->assertDontSee('dark:bg-zinc-900', false)
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
        ->assertSee('dark:bg-sanmal-surface-card-dark', false)
        ->assertSee('dark:hover:bg-sanmal-surface-elevated-dark', false)
        ->assertDontSee('dark:bg-zinc-900', false)
        ->assertDontSee('border', false)
        ->assertSee('p-5', false)
        ->assertSee('Buka pegawai');
});
