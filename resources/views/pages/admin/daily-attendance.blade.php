<x-layouts::app :title="__('Rekap Absensi Harian')">
    <div class="mx-auto max-w-7xl space-y-6 p-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">
                    {{ __('Rekap Absensi Harian') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ __('Status cuti, sakit, dan izin dihitung dari permohonan yang disetujui tanpa membuat event absensi sintetis.') }}
                </flux:text>
            </div>

            <flux:button
                href="{{ route('admin.leave-requests.index') }}"
                variant="filled"
                icon="calendar"
            >
                {{ __('Daftar permohonan') }}
            </flux:button>
        </header>

        <x-admin.flash-message />

        <section class="flex flex-wrap items-end justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
            <form
                method="GET"
                action="{{ route('admin.daily-attendance') }}"
                class="flex flex-wrap items-end gap-3"
            >
                <flux:field>
                    <flux:label>{{ __('Tanggal rekap') }}</flux:label>
                    <flux:input type="date" name="date" value="{{ $date }}" required />
                </flux:field>

                <flux:button type="submit" variant="primary">
                    {{ __('Lihat') }}
                </flux:button>
            </form>

            <form
                method="GET"
                action="{{ route('admin.daily-attendance.export') }}"
                class="flex flex-wrap items-end gap-3"
            >
                <flux:field>
                    <flux:label>{{ __('Ekspor bulan') }}</flux:label>
                    <flux:input
                        type="month"
                        name="month"
                        value="{{ \Illuminate\Support\Carbon::parse($date)->format('Y-m') }}"
                        required
                    />
                </flux:field>

                <flux:button type="submit" variant="filled" icon="arrow-down-tray">
                    {{ __('Unduh CSV') }}
                </flux:button>
            </form>
        </section>

        <div class="space-y-4">
            @forelse ($rows as $row)
                <x-admin.attendance.daily-employee-card
                    :row="$row"
                    :date="$date"
                />
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-8 text-center text-zinc-500 dark:border-neutral-700 dark:bg-zinc-900">
                    {{ __('Belum ada data pegawai.') }}
                </div>
            @endforelse
        </div>

        {{ $rows->links() }}
    </div>
</x-layouts::app>
