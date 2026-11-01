<x-layouts::app :title="__('Master Lokasi')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Master Lokasi') }}</flux:heading>
                <flux:text>{{ __('Kelola lokasi kantor internal dan lokasi kerja milik client.') }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.locations.create') }}" variant="primary" icon="plus">{{ __('Tambah lokasi') }}</flux:button>
        </div>

        <x-admin.flash-message />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:input name="q" value="{{ request('q') }}" :label="__('Cari lokasi')" placeholder="Nama atau alamat" />
            <flux:select name="client_id" :label="__('Pemilik lokasi')" :value="request('client_id')">
                <flux:select.option value="">{{ __('Semua pemilik') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Internal Sanmal') }}</flux:select.option>
                @foreach ($clients as $client)
                    <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select name="type" :label="__('Jenis')" :value="request('type')">
                <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                <flux:select.option value="office">{{ __('Kantor') }}</flux:select.option>
                <flux:select.option value="site">{{ __('Site / lapangan') }}</flux:select.option>
            </flux:select>
            <flux:select name="status" :label="__('Status')" :value="request('status')">
                <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Nonaktif') }}</flux:select.option>
            </flux:select>
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th class="p-3">{{ __('Lokasi') }}</th>
                        <th class="p-3">{{ __('Pemilik') }}</th>
                        <th class="p-3">{{ __('Jenis') }}</th>
                        <th class="p-3">{{ __('Timezone') }}</th>
                        <th class="p-3">{{ __('Koordinat') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($locations as $location)
                        <tr>
                            <td class="p-3">
                                <p class="font-medium">{{ $location->name }}</p>
                                <p class="max-w-sm text-zinc-500">{{ $location->address ?: '—' }}</p>
                            </td>
                            <td class="p-3">{{ $location->client?->name ?? __('Internal Sanmal') }}</td>
                            <td class="p-3">{{ $location->type === 'office' ? __('Kantor') : __('Site / lapangan') }}</td>
                            <td class="p-3 font-mono text-xs">{{ $location->timezone }}</td>
                            <td class="p-3 font-mono text-xs">
                                @if ($location->latitude !== null && $location->longitude !== null)
                                    {{ $location->latitude }}, {{ $location->longitude }}
                                @else
                                    <span class="text-amber-600">{{ __('Belum diatur') }}</span>
                                @endif
                            </td>
                            <td class="p-3"><flux:badge :color="$location->is_active ? 'green' : 'zinc'">{{ $location->is_active ? __('Aktif') : __('Nonaktif') }}</flux:badge></td>
                            <td class="p-3 text-right"><flux:button size="sm" variant="ghost" href="{{ route('admin.locations.edit', $location) }}">{{ __('Ubah') }}</flux:button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-zinc-500">{{ __('Belum ada data lokasi.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $locations->links() }}
    </div>
</x-layouts::app>
