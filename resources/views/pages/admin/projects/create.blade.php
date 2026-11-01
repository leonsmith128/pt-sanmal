<x-layouts::app :title="__('Tambah Proyek')">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Tambah Proyek') }}</flux:heading>
            <flux:text>{{ __('Daftarkan pekerjaan kalibrasi atau pelatihan untuk satu Client dan satu lokasi Site.') }}</flux:text>
        </div>
        <x-admin.flash-message />
        @include('pages::admin.projects._form', [
            'action' => route('admin.projects.store'),
            'method' => 'POST',
            'project' => null,
        ])
    </div>
</x-layouts::app>
