<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        <div><flux:heading size="xl" level="1">{{ $isManager ? __('Dashboard Administrasi') : __('Dashboard') }}</flux:heading><flux:text class="mt-1">{{ $today }}</flux:text></div>
        @if ($isManager)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Pegawai aktif', $activeEmployees, 'user-group'], ['Hadir hari ini', $presentToday, 'check-circle'], ['Belum lengkap', $incompleteToday, 'clock'], ['Belum ada catatan', $noRecordToday, 'exclamation-circle']] as [$label, $value, $icon])
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"><div class="flex items-center justify-between"><flux:text>{{ __($label) }}</flux:text><flux:icon :name="$icon" class="size-5 text-zinc-400" /></div><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $value }}</p></div>
                @endforeach
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}" class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"><flux:text>{{ __('Permohonan menunggu') }}</flux:text><p class="mt-2 text-2xl font-semibold">{{ $pendingLeave }}</p></a>
                <a href="{{ route('admin.overtime-requests.index', ['status' => 'pending']) }}" class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"><flux:text>{{ __('Persetujuan lembur menunggu') }}</flux:text><p class="mt-2 text-2xl font-semibold">{{ $pendingOvertime }}</p></a>
                <a href="{{ route('admin.daily-attendance', ['date' => now()->toDateString()]) }}" class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"><flux:text>{{ __('Perlu ditinjau') }}</flux:text><p class="mt-2 text-2xl font-semibold">{{ $needsReviewToday }}</p></a>
            </div>
            <section class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"><div class="border-b border-zinc-200 p-4 dark:border-zinc-700"><flux:heading size="lg">{{ __('Aktivitas absensi terbaru') }}</flux:heading></div>
                @forelse ($recentAttendance as $record)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 last:border-0 dark:border-zinc-800"><div><p class="font-medium">{{ $record->employee?->user?->name ?? __('Pegawai tidak ditemukan') }}</p><flux:text size="sm">{{ $record->type->label() }} · {{ $record->recordedAtLocal()->format('d M Y H:i') }}</flux:text></div><flux:badge>{{ $record->work_type->label() }}</flux:badge></div>
                @empty<p class="p-6 text-center text-sm text-zinc-500">{{ __('Belum ada catatan absensi hari ini.') }}</p>@endforelse
            </section>
        @else
            <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading size="lg">{{ __('Selamat datang') }}</flux:heading><flux:text class="mt-1">{{ __('Gunakan menu untuk melakukan absensi, melihat riwayat, dan mengelola permohonan.') }}</flux:text><div class="mt-4 flex flex-wrap gap-2"><flux:button href="{{ route('attendance') }}" variant="primary">{{ __('Buka absensi') }}</flux:button><flux:button href="{{ route('attendance-history.index') }}" variant="ghost">{{ __('Riwayat absensi') }}</flux:button></div></div>
        @endif
    </div>
</x-layouts::app>
