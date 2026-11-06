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

        <x-admin.filter-panel class="w-full min-w-0">
            <x-admin.date-calendar-filter
                :date="$date"
                :export-options="$exportOptions"
                :export-confirm-title="__('Konfirmasi export rekap harian')"
                :export-warning="__('Export memuat satu baris per pegawai per tanggal, termasuk tanggal tanpa event absensi. Periksa kembali rentang sebelum melanjutkan.')"
            />
        </x-admin.filter-panel>

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
