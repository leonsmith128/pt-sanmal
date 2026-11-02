<x-layouts::app :title="__('Tambah Proyek')">
    <x-ui.page class="!max-w-3xl gap-5">
        <x-ui.page-header
            :title="__('Tambah Proyek')"
            :description="__('Daftarkan pekerjaan kalibrasi atau pelatihan untuk satu Client dan satu lokasi Site.')"
        />

        <x-admin.flash-message />

        @include('pages::admin.projects._form', [
            'action' => route('admin.projects.store'),
            'method' => 'POST',
            'project' => null,
        ])
    </x-ui.page>
</x-layouts::app>
