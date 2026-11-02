<?php

it('renders the generic page header with title, description, and action slot', function () {
    $this->blade(
        '<x-ui.page-header title="Daftar Pegawai" description="Kelola data pegawai">' .
        '<a href="/pegawai/create">Tambah pegawai</a>' .
        '</x-ui.page-header>'
    )
        ->assertSee('Daftar Pegawai')
        ->assertSee('Kelola data pegawai')
        ->assertSee('Tambah pegawai')
        ->assertSee('md:flex-row md:items-start md:justify-between', false);
});

it('renders a reusable page container with standard width and spacing', function () {
    $this->blade('<x-ui.page><p>Konten halaman</p></x-ui.page>')
        ->assertSee('max-w-7xl', false)
        ->assertSee('gap-4', false)
        ->assertSee('Konten halaman');
});

it('renders a reusable section with caller-provided attributes', function () {
    $this->blade('<x-ui.section class="md:grid md:grid-cols-2"><p>Isi section</p></x-ui.section>')
        ->assertSee('md:grid md:grid-cols-2', false)
        ->assertSee('Isi section');
});
