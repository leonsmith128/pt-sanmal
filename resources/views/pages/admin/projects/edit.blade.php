<x-layouts::app :title="__('Ubah Proyek')">
    <x-ui.page max-width="2xl" gap="5">
        <x-ui.page-header
            :title="__('Ubah Proyek')"
            :description="__('Perbarui data, jenis layanan, periode, atau status proyek.')"
        />

        <x-admin.flash-message />

        @include('pages::admin.projects._form', [
            'action' => route('admin.projects.update', $project),
            'method' => 'PUT',
            'project' => $project,
        ])
    </x-ui.page>
</x-layouts::app>
