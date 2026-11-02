<x-layouts::app :title="__('Rekap Absensi Harian')">
    <div class="mx-auto max-w-7xl space-y-6 p-6">
        <x-admin.page-header
            :title="__('Rekap Absensi Harian')"
            :description="__('Status cuti, sakit, dan izin dihitung dari permohonan yang disetujui tanpa membuat event absensi sintetis.')"
        >
            <flux:button
                href="{{ route('admin.leave-requests.index') }}"
                variant="outline"
                icon="calendar"
            >
                {{ __('Daftar permohonan') }}
            </flux:button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-admin.filter-panel>
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
            </x-admin.filter-panel>

            <form
                method="GET"
                action="{{ route('admin.daily-attendance.export') }}"
                class="flex flex-wrap items-end gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900"
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

                <flux:button type="submit" variant="outline" icon="arrow-down-tray">
                    {{ __('Unduh CSV') }}
                </flux:button>
            </form>
        </div>

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
