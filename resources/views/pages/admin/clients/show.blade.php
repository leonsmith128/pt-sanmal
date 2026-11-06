<x-layouts::app :title="$client->name">
    <x-ui.page class="!max-w-6xl gap-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <x-ui.button size="sm" variant="ghost" icon="arrow-left" href="{{ route('admin.clients.index') }}">{{ __('Kembali ke Master Data Client') }}</x-ui.button>
                <p class="mt-3 font-mono text-xs text-[var(--sanmal-text-muted)]">{{ $client->code }}</p>
                <flux:heading size="xl">{{ $client->name }}</flux:heading>
                <flux:text>{{ $client->address ?: 'Alamat Client belum diisi' }} · {{ $client->phone ?: 'Kontak belum diisi' }}</flux:text>
            </div>
            <div class="flex gap-2">
                <x-ui.button href="{{ route('admin.clients.edit', $client) }}" variant="ghost">{{ __('Edit Client') }}</x-ui.button>
                <x-ui.button href="{{ route('admin.locations.create', ['client_id' => $client->id]) }}" variant="primary" icon="plus">{{ __('Tambah Location') }}</x-ui.button>
            </div>
        </header>

        <x-admin.flash-message />

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ __('Site Location') }}</flux:heading>
                    <flux:text>{{ $client->locations->count() }} Location terdaftar · {{ $client->projects_count }} Project</flux:text>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($client->locations as $location)
                    <article class="min-w-0">
                        <x-ui.card padding="p-4" class="h-full space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">{{ $location->name }}</h3>
                                    <x-ui.badge :color="$location->is_active ? 'green' : 'zinc'">{{ $location->is_active ? __('Aktif') : __('Nonaktif') }}</x-ui.badge>
                                </div>
                                <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">{{ $location->address ?: 'Alamat belum diisi' }}</p>
                                <p class="mt-2 text-xs text-[var(--sanmal-text-muted)]">{{ $location->timezone }} · {{ $location->projects_count }} Project · {{ $location->attendance_records_count }} catatan absensi</p>
                                @if ($location->latitude === null || $location->longitude === null)
                                    <p class="mt-1 text-xs text-amber-600">{{ __('Koordinat belum diatur; radius absensi belum dapat digunakan.') }}</p>
                                @endif
                            </div>
                            <x-ui.button size="sm" variant="ghost" href="{{ route('admin.locations.edit', $location) }}">{{ __('Edit Location') }}</x-ui.button>
                        </div>
                        </x-ui.card>
                    </article>
                @empty
                    <x-ui.empty-state
                        class="!border-dashed !border-sanmal-border !bg-transparent dark:!border-sanmal-border-dark"
                        :message="__('Client ini belum memiliki Site Location.')"
                    />
                @endforelse
            </div>
        </section>
    </x-ui.page>
</x-layouts::app>
