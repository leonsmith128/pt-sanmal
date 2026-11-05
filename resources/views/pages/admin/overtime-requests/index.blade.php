<x-layouts::app :title="__('Pengelolaan Lembur')">
    <x-ui.page class="!max-w-6xl !gap-6">
        <x-admin.page-header
            :title="__('Pengelolaan lembur')"
            :description="__('Tinjau pengajuan, status konfirmasi foto, dan keputusan lembur pegawai.')"
        />

        <x-admin.flash-message />

        <x-admin.filter-panel>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-ui.select name="status" :label="__('Status persetujuan')" :value="request('status')" class="min-w-48">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Menunggu') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Disetujui') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Ditolak') }}</flux:select.option>
                </x-ui.select>
                <x-ui.button type="submit" variant="primary">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-admin.filter-panel>

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                @forelse ($requests as $request)
                    <a href="{{ route('admin.overtime-requests.show', $request) }}" class="flex flex-col gap-3 p-4 transition hover:bg-sanmal-surface-secondary sm:flex-row sm:items-center sm:justify-between dark:hover:bg-sanmal-surface-elevated-dark/40">
                        <div>
                            <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $request->employee->user->name }}</p>
                            <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }} · {{ Str::limit($request->reason ?? 'Alasan tidak dicantumkan', 110) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge :color="$request->approval_status->badgeColor()">
                                {{ $request->approval_status->label() }}
                            </x-ui.badge>
                            <x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge>
                            <span class="inline-flex items-center gap-1 rounded-md border border-sanmal-border px-2.5 py-1 text-xs font-medium text-[var(--sanmal-text-secondary)] dark:border-sanmal-border-dark">{{ __('Tinjau') }} <flux:icon name="chevron-right" class="size-3.5" /></span>
                        </div>
                    </a>
                @empty
                    <x-ui.empty-state
                        class="!rounded-none !border-0 !bg-transparent !p-10"
                        :message="__('Tidak ada pengajuan lembur yang cocok.')"
                    />
                @endforelse
            </div>
        </x-ui.card>
        {{ $requests->links() }}
    </x-ui.page>
</x-layouts::app>
