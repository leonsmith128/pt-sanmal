<x-layouts::app :title="__('Ubah Proyek')">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Ubah Proyek') }}</flux:heading>
            <flux:text>{{ __('Perbarui data, jenis layanan, periode, atau status proyek.') }}</flux:text>
        </div>
        <x-admin.flash-message />
        @include('pages::admin.projects._form', [
            'action' => route('admin.projects.update', $project),
            'method' => 'PUT',
            'project' => $project,
        ])
    </div>
</x-layouts::app>
