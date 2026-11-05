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

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div
                role="region"
                aria-label="{{ __('Daftar catatan audit') }}"
                tabindex="0"
                class="overflow-x-auto"
            >
            <table class="w-full min-w-[48rem] text-sm">
                <thead class="bg-sanmal-surface-secondary text-left text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                    <tr>
                        <th scope="col" class="p-3">{{ __('Waktu') }}</th>
                        <th scope="col" class="p-3">{{ __('Pelaku') }}</th>
                        <th scope="col" class="p-3">{{ __('Aksi') }}</th>
                        <th scope="col" class="p-3">{{ __('Objek') }}</th>
                        <th scope="col" class="p-3">{{ __('IP') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
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
        </x-ui.card>

        {{ $logs->links() }}
    </x-ui.page>
</x-layouts::app>
