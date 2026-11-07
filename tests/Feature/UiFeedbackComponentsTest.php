<?php

it('renders a reusable empty state with a message and forwarded classes', function () {
    $this->blade('<x-ui.empty-state message="Belum ada data." class="mt-4" />')
        ->assertSee('Belum ada data.')
        ->assertSee('mt-4', false)
        ->assertSee('rounded-xl border', false);
});

it('renders an empty state with a title and an action without removing the message', function () {
    $this->blade('
        <x-ui.empty-state title="Belum ada akun pegawai" message="Akun yang dibuat akan muncul di sini.">
            <x-slot:action><a href="/admin/pegawai/create">Tambah akun</a></x-slot:action>
        </x-ui.empty-state>
    ')
        ->assertSee('Belum ada akun pegawai')
        ->assertSee('Akun yang dibuat akan muncul di sini.')
        ->assertSee('href="/admin/pegawai/create"', false)
        ->assertSee('Tambah akun');
});

it('does not render title or action markup when they are not provided', function () {
    $this->blade('<x-ui.empty-state message="Kosong." />')
        ->assertSee('Kosong.')
        ->assertDontSee('text-base font-semibold', false)
        ->assertDontSee('mt-4 flex flex-wrap', false);
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

it('forwards attributes to the table cell for a table empty state', function () {
    $this->blade('
        <table>
            <tbody>
                <x-ui.empty-state
                    message="Tidak ada catatan."
                    :colspan="3"
                    class="font-medium"
                    data-testid="empty-attendance"
                />
            </tbody>
        </table>
    ')
        ->assertSee('colspan="3"', false)
        ->assertSee('font-medium', false)
        ->assertSee('data-testid="empty-attendance"', false)
        ->assertSee('Tidak ada catatan.');
});
