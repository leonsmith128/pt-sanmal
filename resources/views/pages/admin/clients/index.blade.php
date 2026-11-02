<x-layouts::app :title="__('Master Data Client')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-5">
        <x-admin.page-header
            :title="__('Master Data Client')"
            :description="__('Kelola Client, Site milik Client, dan kantor internal SanMal dalam satu halaman.')"
        >
            <flux:button href="{{ route('admin.clients.create') }}" variant="primary" icon="plus">{{ __('Tambah Client') }}</flux:button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <form method="GET" action="{{ route('admin.clients.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <flux:input name="q" value="{{ $term }}" :label="__('Pencarian')" placeholder="Kode/nama Client, nama/alamat Location" />
            </div>
            <flux:button type="submit" icon="magnifying-glass">{{ __('Cari') }}</flux:button>
            @if ($term !== '')
                <flux:button href="{{ route('admin.clients.index') }}" variant="ghost">{{ __('Reset') }}</flux:button>
            @endif
        </form>

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ __('Client') }}</flux:heading>
                    <flux:text>{{ __('Setiap Client menampilkan seluruh Site yang terdaftar di bawahnya.') }}</flux:text>
                </div>
                <flux:badge>{{ number_format($clients->total()) }} Client</flux:badge>
            </div>

            @forelse ($clients as $client)
                <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-zinc-500">{{ $client->code }}</p>
                            <h3 class="font-semibold">{{ $client->name }}</h3>
                            <p class="mt-1 text-sm text-zinc-500">{{ $client->phone ?: 'Kontak belum diisi' }} · {{ $client->locations_count }} Location · {{ $client->projects_count }} Project</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('admin.clients.edit', $client) }}">{{ __('Ubah Client') }}</flux:button>
                            <flux:button size="sm" variant="filled" href="{{ route('admin.clients.show', $client) }}">{{ __('Detail Client') }}</flux:button>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium">{{ __('Site Location') }}</p>
                            <flux:button size="sm" variant="subtle" icon="plus" href="{{ route('admin.locations.create', ['client_id' => $client->id]) }}">{{ __('Tambah Location') }}</flux:button>
                        </div>
                        @forelse ($client->locations as $location)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg px-2 py-2 odd:bg-zinc-50 dark:odd:bg-zinc-800/50">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ $location->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $location->address ?: 'Alamat belum diisi' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$location->is_active ? 'green' : 'zinc'">{{ $location->is_active ? __('Aktif') : __('Nonaktif') }}</flux:badge>
                                    <flux:button size="xs" variant="ghost" href="{{ route('admin.locations.edit', $location) }}">{{ __('Ubah') }}</flux:button>
                                </div>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-zinc-500">{{ __('Belum ada Site Location untuk Client ini.') }}</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    {{ __('Tidak ada Client yang cocok dengan pencarian.') }}
                </div>
            @endforelse

            {{ $clients->links() }}
        </section>

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ __('Office Internal SanMal') }}</flux:heading>
                    <flux:text>{{ __('Lokasi kantor internal tidak terikat pada Client.') }}</flux:text>
                </div>
                <flux:button href="{{ route('admin.locations.create', ['type' => 'office']) }}" variant="primary" icon="plus">{{ __('Tambah Office') }}</flux:button>
            </div>

            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                @forelse ($offices as $office)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4 last:border-b-0 dark:border-zinc-800">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $office->name }}</p>
                            <p class="text-sm text-zinc-500">{{ $office->address ?: 'Alamat belum diisi' }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $office->timezone }} · {{ $office->attendance_records_count }} catatan absensi</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge :color="$office->is_active ? 'green' : 'zinc'">{{ $office->is_active ? __('Aktif') : __('Nonaktif') }}</flux:badge>
                            <flux:button size="sm" variant="ghost" href="{{ route('admin.locations.edit', $office) }}">{{ __('Ubah') }}</flux:button>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-zinc-500">{{ $term !== '' ? 'Tidak ada Office yang cocok dengan pencarian.' : 'Belum ada Office Internal SanMal.' }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
