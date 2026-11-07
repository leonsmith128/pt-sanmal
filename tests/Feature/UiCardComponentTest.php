<?php

it('renders a content card with a 1px bordered surface, default spacing, and forwarded attributes', function () {
    $this->blade('<x-ui.card class="md:col-span-2"><p>Konten card</p></x-ui.card>')
        ->assertSee('rounded-xl border border-sanmal-border bg-sanmal-surface-card text-[var(--sanmal-text-primary)]', false)
        ->assertSee('dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark', false)
        ->assertDontSee('shadow', false)
        ->assertDontSee('dark:bg-zinc-900', false)
        ->assertSee('p-4', false)
        ->assertSee('md:col-span-2', false)
        ->assertSee('Konten card');
});

it('renders a linked card as a semantic anchor with border and surface hover treatment', function () {
    $this->blade('<x-ui.card href="/admin/pegawai" padding="p-5">Buka pegawai</x-ui.card>')
        ->assertSee('<a', false)
        ->assertSee('href="/admin/pegawai"', false)
        ->assertSee('border border-sanmal-border', false)
        ->assertSee('hover:border-accent', false)
        ->assertSee('hover:bg-sanmal-surface-secondary', false)
        ->assertSee('focus-visible:ring-[var(--sanmal-focus)]', false)
        ->assertSee('dark:bg-sanmal-surface-card-dark', false)
        ->assertSee('dark:hover:bg-sanmal-surface-elevated-dark', false)
        ->assertDontSee('shadow', false)
        ->assertDontSee('dark:bg-zinc-900', false)
        ->assertSee('p-5', false)
        ->assertSee('Buka pegawai');
});
