<?php

it('renders a semantic metadata item with its label and value', function () {
    $this->blade('<dl><x-ui.metadata-item label="Tanggal">3 Nov 2026</x-ui.metadata-item></dl>')
        ->assertSee('<dt', false)
        ->assertSee('Tanggal')
        ->assertSee('<dd', false)
        ->assertSee('3 Nov 2026');
});

it('provides consistent default label and value styling', function () {
    $this->blade('<dl><x-ui.metadata-item label="Jenis">Clock In</x-ui.metadata-item></dl>')
        ->assertSee('text-sm text-zinc-500', false)
        ->assertSee('mt-1 text-sm text-zinc-900 dark:text-zinc-100', false);
});

it('allows callers to customize label and value classes', function () {
    $this->blade('<dl><x-ui.metadata-item label="Alasan" label-class="text-xs uppercase" value-class="whitespace-pre-line">Keterangan</x-ui.metadata-item></dl>')
        ->assertSee('text-xs uppercase', false)
        ->assertSee('whitespace-pre-line', false)
        ->assertSee('Keterangan');
});

it('forwards wrapper attributes without losing the default min-width guard', function () {
    $this->blade('<dl><x-ui.metadata-item label="Pegawai" class="sm:col-span-2" data-testid="employee-meta">Leon</x-ui.metadata-item></dl>')
        ->assertSee('min-w-0', false)
        ->assertSee('sm:col-span-2', false)
        ->assertSee('data-testid="employee-meta"', false);
});
