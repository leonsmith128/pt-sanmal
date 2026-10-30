<x-layouts::app :title="__('Koreksi Absensi')">
    <div class="mx-auto max-w-6xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Pengelolaan Koreksi Absensi') }}</flux:heading>
            <flux:text>{{ __('Tinjau permohonan perubahan catatan clock-in atau clock-out.') }}</flux:text>
        </div>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <form method="GET" class="flex items-end gap-3">
            <flux:select name="status" :label="__('Status')" :value="request('status')">
                <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Menunggu') }}</flux:select.option>
                <flux:select.option value="approved">{{ __('Disetujui') }}</flux:select.option>
                <flux:select.option value="rejected">{{ __('Ditolak') }}</flux:select.option>
            </flux:select>
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

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
                    <flux:badge :color="$correction->status === 'pending' ? 'amber' : ($correction->status === 'approved' ? 'green' : 'red')">
                        {{ ucfirst($correction->status) }}
                    </flux:badge>
                </a>
            @empty
                <div class="p-8 text-center text-zinc-500">{{ __('Tidak ada permohonan koreksi.') }}</div>
            @endforelse
        </div>

        {{ $corrections->links() }}
    </div>
</x-layouts::app>
