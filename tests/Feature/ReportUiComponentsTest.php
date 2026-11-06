<?php

it('renders a single export link labelled Ekspor without a file format', function () {
    $this->blade('<x-ui.export-button href="/unduh" class="w-full" />')
        ->assertSee('Ekspor')
        ->assertSee('href="/unduh"', false)
        ->assertDontSee('CSV')
        ->assertDontSee('XLSX');
});

it('renders an export submit button for a form owned by the page', function () {
    $this->blade('<form><x-ui.export-button /></form>')
        ->assertSee('Ekspor')
        ->assertSee('type="submit"', false);
});

it('renders export options with a self-contained confirmation dialog', function () {
    $options = [
        ['label' => 'Hari ini', 'href' => '/hari', 'description' => 'Laporan hari ini', 'icon' => 'calendar-days'],
        ['label' => 'Bulan ini', 'href' => '/bulan', 'description' => 'Laporan bulan ini', 'icon' => 'calendar', 'divider_before' => true],
    ];

    $this->blade(
        '<x-ui.export-button :options="$options" summary="Ringkasan filter" warning="Periksa cakupan" confirm-title="Konfirmasi rekap" />',
        ['options' => $options],
    )
        ->assertSee('exportConfirmOpen: false', false)
        ->assertSee('Hari ini')
        ->assertSee('Bulan ini')
        ->assertSee('x-bind:href="exportUrl"', false)
        ->assertSee('Konfirmasi rekap')
        ->assertSee('Ringkasan filter')
        ->assertSee('Periksa cakupan')
        ->assertSee('Konfirmasi dan ekspor');
});

it('renders a filter toggle bound to the page state with an active count', function () {
    $this->blade('<x-ui.filter-toggle controls="panel-filter" :count="3" />')
        ->assertSee('aria-controls="panel-filter"', false)
        ->assertSee('x-bind:aria-expanded="filterOpen.toString()"', false)
        ->assertSee('Filter')
        ->assertSee('3');

    $this->blade('<x-ui.filter-toggle controls="panel-filter" model="open" />')
        ->assertSee('x-bind:aria-expanded="open.toString()"', false);
});

it('renders a collapsible filter panel whose fields are supplied by the page', function () {
    $this->blade(<<<'BLADE'
        <x-ui.filter-panel id="panel-filter" title="Filter contoh" description="Deskripsi contoh">
            <x-slot:aside><span>Samping</span></x-slot:aside>
            <input name="q">
        </x-ui.filter-panel>
    BLADE)
        ->assertSee('id="panel-filter"', false)
        ->assertSee('x-show="filterOpen"', false)
        ->assertSee('Filter contoh')
        ->assertSee('Deskripsi contoh')
        ->assertSee('Samping')
        ->assertSee('name="q"', false);
});
