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

        <div class="space-y-3 md:hidden">
            @forelse ($requests as $request)
                <x-ui.card padding="p-4">
                    <article class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold text-[var(--sanmal-text-primary)]">{{ $request->employee->user->name }}</h2>
                                <p class="mt-1 text-sm tabular-nums text-[var(--sanmal-text-secondary)]">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }}</p>
                            </div>
                            <x-ui.badge :color="$request->approval_status->badgeColor()">{{ $request->approval_status->label() }}</x-ui.badge>
                        </div>
                        <p class="text-sm text-[var(--sanmal-text-secondary)]">{{ Str::limit($request->reason ?? 'Alasan tidak dicantumkan', 110) }}</p>
                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                            <x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge>
                            <x-ui.button href="{{ route('admin.overtime-requests.show', $request) }}" size="sm" variant="outline">
                                {{ __('Tinjau') }}
                            </x-ui.button>
                        </div>
                    </article>
                </x-ui.card>
            @empty
                <x-ui.empty-state :message="__('Tidak ada pengajuan lembur yang cocok.')" />
            @endforelse
        </div>

        <x-ui.card padding="p-0" class="hidden overflow-hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="bg-sanmal-surface-secondary text-xs uppercase tracking-wide text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('Pegawai') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Jadwal mulai') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Alasan') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Persetujuan') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Status operasional') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                        @forelse ($requests as $request)
                            <tr class="transition-colors hover:bg-sanmal-surface-secondary/40 dark:hover:bg-sanmal-surface-elevated-dark/20">
                                <td class="px-4 py-3 font-medium text-[var(--sanmal-text-primary)]">{{ $request->employee->user->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs tabular-nums text-[var(--sanmal-text-secondary)]">{{ $request->scheduledStartLocal()->format('d M Y, H:i') }}</td>
                                <td class="max-w-sm px-4 py-3 text-[var(--sanmal-text-secondary)]">{{ Str::limit($request->reason ?? 'Alasan tidak dicantumkan', 110) }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><x-ui.badge :color="$request->approval_status->badgeColor()">{{ $request->approval_status->label() }}</x-ui.badge></td>
                                <td class="whitespace-nowrap px-4 py-3"><x-ui.badge :color="$request->state->badgeColor()">{{ $request->state->label() }}</x-ui.badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-ui.button href="{{ route('admin.overtime-requests.show', $request) }}" size="sm" variant="outline">{{ __('Tinjau') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-state :colspan="6" :message="__('Tidak ada pengajuan lembur yang cocok.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
        {{ $requests->links() }}
    </x-ui.page>
</x-layouts::app>
