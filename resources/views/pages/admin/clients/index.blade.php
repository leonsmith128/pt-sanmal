<x-layouts::app :title="__('Master Client')">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Master Client') }}</flux:heading>
                <flux:text>{{ __('Kelola perusahaan atau organisasi pengguna layanan Sanmal.') }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.clients.create') }}" variant="primary" icon="plus">{{ __('Tambah client') }}</flux:button>
        </div>

        <x-admin.flash-message />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:input name="q" value="{{ request('q') }}" :label="__('Cari client')" placeholder="Kode, nama, atau telepon" />
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th class="p-3">{{ __('Kode') }}</th>
                        <th class="p-3">{{ __('Client') }}</th>
                        <th class="p-3">{{ __('Kontak') }}</th>
                        <th class="p-3">{{ __('Lokasi') }}</th>
                        <th class="p-3">{{ __('Proyek') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($clients as $client)
                        <tr>
                            <td class="p-3 font-mono">{{ $client->code }}</td>
                            <td class="p-3 font-medium">{{ $client->name }}</td>
                            <td class="p-3">{{ $client->phone ?: '—' }}</td>
                            <td class="p-3">{{ $client->locations_count }}</td>
                            <td class="p-3">{{ $client->projects_count }}</td>
                            <td class="p-3 text-right">
                                <flux:button size="sm" variant="ghost" href="{{ route('admin.clients.edit', $client) }}">{{ __('Ubah') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-zinc-500">{{ __('Belum ada data client.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clients->links() }}
    </div>
</x-layouts::app>
