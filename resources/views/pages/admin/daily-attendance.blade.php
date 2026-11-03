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

        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-admin.filter-panel>
                <form
                    method="GET"
                    action="{{ route('admin.daily-attendance') }}"
                    class="flex flex-wrap items-end gap-3"
                >
                    <x-ui.input
                        type="date"
                        name="date"
                        :label="__('Tanggal rekap')"
                        :value="$date"
                        required
                    />

                    <x-ui.button type="submit" variant="primary">
                        {{ __('Lihat') }}
                    </x-ui.button>
                </form>
            </x-admin.filter-panel>

            <x-admin.filter-panel>
                <form
                    method="GET"
                    action="{{ route('admin.daily-attendance.export') }}"
                    class="flex flex-wrap items-end gap-3"
                >
                    <x-ui.input
                        type="month"
                        name="month"
                        :label="__('Ekspor bulan')"
                        :value="\Illuminate\Support\Carbon::parse($date)->format('Y-m')"
                        required
                    />

                    <x-ui.button type="submit" variant="outline" icon="arrow-down-tray">
                        {{ __('Unduh CSV') }}
                    </x-ui.button>
                </form>
            </x-admin.filter-panel>
        </div>

        <div class="space-y-4">
            @forelse ($rows as $row)
                <x-admin.attendance.daily-employee-card
                    :row="$row"
                    :date="$date"
                />
            @empty
                <x-ui.empty-state>
                    {{ __('Belum ada data pegawai.') }}
                </x-ui.empty-state>
            @endforelse
        </div>

        {{ $rows->links() }}
    </x-ui.page>
</x-layouts::app>
