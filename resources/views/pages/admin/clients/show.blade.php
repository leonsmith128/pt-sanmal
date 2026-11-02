<x-layouts::app :title="$client->name">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('admin.clients.index') }}">{{ __('Kembali ke Master Data Client') }}</flux:button>
                <p class="mt-3 font-mono text-xs text-zinc-500">{{ $client->code }}</p>
                <flux:heading size="xl">{{ $client->name }}</flux:heading>
                <flux:text>{{ $client->address ?: 'Alamat Client belum diisi' }} · {{ $client->phone ?: 'Kontak belum diisi' }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button href="{{ route('admin.clients.edit', $client) }}" variant="ghost">{{ __('Ubah Client') }}</flux:button>
                <flux:button href="{{ route('admin.locations.create', ['client_id' => $client->id]) }}" variant="primary" icon="plus">{{ __('Tambah Location') }}</flux:button>
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

            <div class="grid gap-3">
                @forelse ($client->locations as $location)
                    <article class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">{{ $location->name }}</h3>
                                    <flux:badge :color="$location->is_active ? 'green' : 'zinc'">{{ $location->is_active ? __('Aktif') : __('Nonaktif') }}</flux:badge>
                                </div>
                                <p class="mt-1 text-sm text-zinc-500">{{ $location->address ?: 'Alamat belum diisi' }}</p>
                                <p class="mt-2 text-xs text-zinc-500">{{ $location->timezone }} · {{ $location->projects_count }} Project · {{ $location->attendance_records_count }} catatan absensi</p>
                                @if ($location->latitude === null || $location->longitude === null)
                                    <p class="mt-1 text-xs text-amber-600">{{ __('Koordinat belum diatur; radius absensi belum dapat digunakan.') }}</p>
                                @endif
                            </div>
                            <flux:button size="sm" variant="ghost" href="{{ route('admin.locations.edit', $location) }}">{{ __('Ubah Location') }}</flux:button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        {{ __('Client ini belum memiliki Site Location.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
