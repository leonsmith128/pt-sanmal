<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        <header>
            <flux:heading size="xl" level="1">
                {{ $isManager ? __('Dashboard Administrasi') : __('Dashboard') }}
            </flux:heading>
            <flux:text class="mt-1">{{ $today }}</flux:text>
        </header>

        @if ($isManager)
            <section
                aria-label="{{ __('Ringkasan kehadiran') }}"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <x-dashboard.stat-card
                    :label="__('Pegawai aktif')"
                    :value="$activeEmployees"
                    icon="users"
                />

                <x-dashboard.stat-card
                    :label="__('Hadir hari ini')"
                    :value="$presentToday"
                    icon="check-circle"
                />

                <x-dashboard.stat-card
                    :label="__('Belum lengkap')"
                    :value="$incompleteToday"
                    icon="clock"
                />

                <x-dashboard.stat-card
                    :label="__('Belum ada catatan')"
                    :value="$noRecordToday"
                    icon="alert-circle"
                />
            </section>

            <section
                aria-label="{{ __('Tindakan administrasi') }}"
                class="grid gap-4 md:grid-cols-3"
            >
                <x-dashboard.action-card
                    :label="__('Permohonan menunggu')"
                    :value="$pendingLeave"
                    :href="route('admin.leave-requests.index', ['status' => 'pending'])"
                />

                <x-dashboard.action-card
                    :label="__('Persetujuan lembur menunggu')"
                    :value="$pendingOvertime"
                    :href="route('admin.overtime-requests.index', ['status' => 'pending'])"
                />

                <x-dashboard.action-card
                    :label="__('Perlu ditinjau')"
                    :value="$needsReviewToday"
                    :href="route('admin.daily-attendance', ['date' => now()->toDateString()])"
                />
            </section>

            <section class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Aktivitas absensi terbaru') }}</flux:heading>
                </div>

                @forelse ($recentAttendance as $record)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 last:border-0 dark:border-zinc-800">
                        <div>
                            <p class="font-medium">
                                {{ $record->employee?->user?->name ?? __('Pegawai tidak ditemukan') }}
                            </p>
                            <flux:text size="sm">
                                {{ $record->type->label() }}
                                ·
                                {{ $record->recordedAtLocal()->format('d M Y H:i') }}
                            </flux:text>
                        </div>

                        <flux:badge>{{ $record->work_type->label() }}</flux:badge>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-zinc-500">
                        {{ __('Belum ada catatan absensi hari ini.') }}
                    </p>
                @endforelse
            </section>
        @else
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Selamat datang') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Gunakan menu untuk melakukan absensi, melihat riwayat, dan mengelola permohonan.') }}
                </flux:text>

                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button href="{{ route('attendance') }}" variant="primary">
                        {{ __('Buka absensi') }}
                    </flux:button>

                    <flux:button href="{{ route('attendance-history.index') }}" variant="ghost">
                        {{ __('Riwayat absensi') }}
                    </flux:button>
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
