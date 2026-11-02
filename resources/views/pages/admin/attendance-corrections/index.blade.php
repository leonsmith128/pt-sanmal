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

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            @forelse ($corrections as $correction)
                <a href="{{ route('admin.attendance-corrections.show', $correction) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                    <div>
                        <p class="font-medium">{{ $correction->employee->user->name }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ $correction->attendance_date->format('d M Y') }} ·
                            {{ $correction->requested_type }} ·
                            {{ $correction->requested_recorded_at->format('H:i') }}
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-600 dark:text-zinc-200">{{ __('Tinjau') }}</span>
                    <x-ui.badge :color="match($correction->status) { 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc' }">
                        {{ match($correction->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => ucfirst($correction->status) } }}
                    </x-ui.badge>
                </a>
            @empty
                <x-admin.empty-state
                    class="!rounded-none !border-0 !bg-transparent"
                    :message="__('Tidak ada permohonan koreksi.')"
                />
            @endforelse
        </div>

        {{ $corrections->links() }}
    </x-ui.page>
</x-layouts::app>
