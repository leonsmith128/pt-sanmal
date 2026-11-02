<?php

it('renders a reusable empty state with a message and forwarded classes', function () {
    $this->blade('<x-ui.empty-state message="Belum ada data." class="mt-4" />')
        ->assertSee('Belum ada data.')
        ->assertSee('mt-4', false)
        ->assertSee('rounded-xl border', false);
});

it('renders a reusable empty state as a table row when colspan is provided', function () {
    $this->blade('<table><tbody><x-ui.empty-state message="Data kosong" :colspan="4" /></tbody></table>')
        ->assertSee('<td colspan="4"', false)
        ->assertSee('Data kosong');
});

it('keeps the Admin empty state component compatible with its existing API', function () {
    $this->blade('<x-admin.empty-state message="Tidak ada pegawai." />')
        ->assertSee('Tidak ada pegawai.');
});
