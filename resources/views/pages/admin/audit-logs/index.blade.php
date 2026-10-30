<x-layouts::app :title="__('Audit Log')">
    <div class="mx-auto max-w-7xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Audit Log') }}</flux:heading>
            <flux:text>{{ __('Riwayat tindakan administratif. Catatan audit bersifat hanya-baca.') }}</flux:text>
        </div>

        <x-admin.flash-message />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:input name="action" :label="__('Aksi')" value="{{ request('action') }}" placeholder="leave.approved" />
            <flux:input type="date" name="from" :label="__('Dari tanggal')" value="{{ request('from') }}" />
            <flux:input type="date" name="to" :label="__('Sampai tanggal')" value="{{ request('to') }}" />
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th class="p-3">{{ __('Waktu') }}</th>
                        <th class="p-3">{{ __('Pelaku') }}</th>
                        <th class="p-3">{{ __('Aksi') }}</th>
                        <th class="p-3">{{ __('Objek') }}</th>
                        <th class="p-3">{{ __('IP') }}</th>
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
                        <tr>
                            <td colspan="5" class="p-8 text-center text-zinc-500">{{ __('Belum ada catatan audit.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</x-layouts::app>
