<x-layouts::app :title="__('Koreksi Absensi')">
    <x-ui.page class="!max-w-6xl !gap-5">
        <x-admin.page-header
            :title="__('Pengelolaan Koreksi Absensi')"
            :description="__('Tinjau permohonan perubahan catatan clock-in atau clock-out.')"
        />

        <x-admin.flash-message />

        <x-admin.filter-panel>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-ui.select name="status" :label="__('Status')" :value="request('status')">
                    <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Menunggu') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Disetujui') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Ditolak') }}</flux:select.option>
                    <flux:select.option value="cancelled">{{ __('Dibatalkan') }}</flux:select.option>
                </x-ui.select>
                <x-ui.button type="submit" variant="outline">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-admin.filter-panel>

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                @forelse ($corrections as $correction)
                    <a href="{{ route('admin.attendance-corrections.show', $correction) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition-colors hover:bg-sanmal-surface-secondary/50 dark:hover:bg-sanmal-surface-elevated-dark/30">
                        <div>
                            <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $correction->employee->user->name }}</p>
                            <p class="mt-0.5 text-sm text-[var(--sanmal-text-secondary)]">
                                {{ $correction->attendance_date->format('d M Y') }} ·
                                <span class="uppercase font-medium text-[var(--sanmal-text-primary)]">{{ str_replace('_', ' ', $correction->requested_type) }}</span> ·
                                <span class="font-mono text-xs text-[var(--sanmal-text-muted)]">{{ $correction->requested_recorded_at->format('H:i') }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md border border-sanmal-border px-2.5 py-1 text-xs font-medium text-[var(--sanmal-text-secondary)] dark:border-sanmal-border-dark">{{ __('Tinjau') }}</span>
                            <x-ui.badge :color="match($correction->status) { 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc' }">
                                {{ match($correction->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => ucfirst($correction->status) } }}
                            </x-ui.badge>
                        </div>
                    </a>
                @empty
                    <x-admin.empty-state
                        class="!rounded-none !border-0 !bg-transparent !p-8"
                        :message="__('Tidak ada permohonan koreksi.')"
                    />
                @endforelse
            </div>
        </x-ui.card>

        {{ $corrections->links() }}
    </x-ui.page>
</x-layouts::app>
