<x-layouts::app :title="__('Audit Log')">
    <x-ui.page class="!gap-5">
        <x-admin.page-header
            :title="__('Audit Log')"
            :description="__('Riwayat tindakan administratif. Catatan audit bersifat hanya-baca.')"
        />

        <x-admin.flash-message />

        <x-admin.filter-panel>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-ui.input
                    name="action"
                    :label="__('Aksi')"
                    :value="request('action')"
                    placeholder="leave.approved"
                />
                <x-ui.input
                    type="date"
                    name="from"
                    :label="__('Dari tanggal')"
                    :value="request('from')"
                />
                <x-ui.input
                    type="date"
                    name="to"
                    :label="__('Sampai tanggal')"
                    :value="request('to')"
                />
                <x-ui.button type="submit" variant="outline">
                    {{ __('Filter') }}
                </x-ui.button>
            </form>
        </x-admin.filter-panel>

        <div
            role="region"
            aria-label="{{ __('Daftar catatan audit') }}"
            tabindex="0"
            class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700"
        >
            <table class="w-full min-w-[48rem] text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th scope="col" class="p-3">{{ __('Waktu') }}</th>
                        <th scope="col" class="p-3">{{ __('Pelaku') }}</th>
                        <th scope="col" class="p-3">{{ __('Aksi') }}</th>
                        <th scope="col" class="p-3">{{ __('Objek') }}</th>
                        <th scope="col" class="p-3">{{ __('IP') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap p-3">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="p-3">{{ $log->user?->name ?? __('Akun dihapus') }}</td>
                            <td class="p-3 font-mono text-xs">{{ $log->action }}</td>
                            <td class="p-3">{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id ?? '—' }}</td>
                            <td class="p-3">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-state
                            colspan="5"
                            :message="__('Belum ada catatan audit.')"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </x-ui.page>
</x-layouts::app>
