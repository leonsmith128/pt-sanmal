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

        <div class="grid min-w-0 grid-cols-1 items-stretch gap-3 md:grid-cols-2">
            <x-admin.filter-panel class="flex w-full min-w-0 items-center">
                <x-admin.date-calendar-filter :date="$date" />
            </x-admin.filter-panel>

            <x-admin.filter-panel class="flex w-full min-w-0 items-center">
                <form
                    method="GET"
                    action="{{ route('admin.daily-attendance.export') }}"
                    class="grid w-full min-w-0 gap-2"
                >
                    <div class="grid min-w-0 gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <x-ui.input
                            type="month"
                            name="month"
                            :label="__('Ekspor rekap bulanan')"
                            :value="\Illuminate\Support\Carbon::parse($date)->format('Y-m')"
                            :show-error="false"
                            required
                        />

                        <x-ui.button
                            type="submit"
                            variant="outline"
                            size="base"
                            icon="arrow-down-tray"
                            class="w-full lg:w-auto"
                        >
                            {{ __('Unduh CSV') }}
                        </x-ui.button>
                    </div>

                    <flux:error name="month" />
                </form>
            </x-admin.filter-panel>
        </div>

        <div class="grid grid-cols-1 items-stretch gap-4 md:auto-rows-fr md:grid-cols-2 lg:grid-cols-3">
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
