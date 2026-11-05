<x-layouts::app :title="__('Rekap Absensi Harian')">
    <x-ui.page class="!gap-6">
        <x-admin.page-header
            :title="__('Rekap Absensi Harian')"
            :description="__('Tanggal rekap mengikuti kalender perusahaan (WITA). Status cuti, sakit, dan izin dihitung dari permohonan yang disetujui tanpa membuat event absensi sintetis.')"
        >
            <x-ui.button
                href="{{ route('admin.leave-requests.index') }}"
                variant="outline"
                icon="calendar"
            >
                {{ __('Daftar permohonan') }}
            </x-ui.button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <div class="grid min-w-0 grid-cols-[repeat(2,minmax(0,1fr))] items-stretch gap-1.5 sm:gap-3">
            <x-admin.filter-panel class="flex w-full min-w-0 items-center !p-1.5 sm:!p-3">
                <x-admin.date-calendar-filter :date="$date" />
            </x-admin.filter-panel>

            <x-admin.filter-panel class="flex w-full min-w-0 items-center !p-1.5 sm:!p-3">
                <form
                    method="GET"
                    action="{{ route('admin.daily-attendance.export') }}"
                    class="flex w-full min-w-0 flex-col items-stretch gap-1.5 sm:flex-row sm:items-end"
                >
                    <div class="min-w-0 flex-1 [&_input]:min-w-0 [&_input]:px-2 [&_input]:pr-1 [&_input]:text-xs [&_label]:text-xs">
                        <x-ui.input
                            type="month"
                            name="month"
                            :label="__('Ekspor bulan')"
                            :value="\Illuminate\Support\Carbon::parse($date)->format('Y-m')"
                            required
                        />
                    </div>

                    <x-ui.button type="submit" variant="outline" icon="arrow-down-tray" class="w-full min-w-0 shrink-0 !px-2 !py-1.5 text-xs sm:w-auto">
                        {{ __('Unduh CSV') }}
                    </x-ui.button>
                </form>
            </x-admin.filter-panel>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($rows as $row)
                <x-admin.attendance.daily-employee-card
                    :row="$row"
                    :date="$date"
                />
            @empty
                <x-ui.empty-state class="col-span-full">
                    {{ __('Belum ada data pegawai.') }}
                </x-ui.empty-state>
            @endforelse
        </div>

        {{ $rows->links() }}
    </x-ui.page>
</x-layouts::app>
