<x-layouts::app :title="__('Dashboard')">
    <x-ui.page gap="6">
        @if ($isManager)
            <x-ui.page-header
                :title="__('Dashboard Administrasi')"
                :description="$today"
            />

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
                    icon="exclamation-triangle"
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

                        <x-ui.badge>{{ $record->work_type->label() }}</x-ui.badge>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-zinc-500">
                        {{ __('Belum ada catatan absensi hari ini.') }}
                    </p>
                @endforelse
            </section>
        @else
            @if (! $employeeDashboard)
                <x-ui.page-header
                    :title="__('Selamat datang, :name', ['name' => auth()->user()->name])"
                    :description="$today"
                />

                <x-ui.empty-state>
                    <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Profil pegawai belum tersedia') }}</h2>
                    <p class="mt-1">{{ __('Akun ini belum terhubung dengan profil pegawai. Hubungi administrator untuk melanjutkan.') }}</p>
                </x-ui.empty-state>
            @else
                @php
                    $dashboardUser = auth()->user();
                    $todayStatus = $employeeDashboard['todayStatus'];
                    $monthly = $employeeDashboard['monthly'];
                    $pending = $employeeDashboard['pending'];
                    $statusColor = match ($todayStatus['status']) {
                        'present' => 'green',
                        'incomplete' => 'amber',
                        'leave' => 'purple',
                        'sick' => 'amber',
                        'permission' => 'sky',
                        'needs_review' => 'red',
                        default => 'zinc',
                    };
                    $formatDuration = fn (int $minutes) => intdiv($minutes, 60).'j '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
                @endphp

                <x-ui.page-header
                    :title="__('Selamat datang, :name', ['name' => $dashboardUser->name])"
                    :description="$employeeDashboard['today']"
                >
                    @if ($dashboardUser->profilePhotoUrl())
                        <img
                            src="{{ $dashboardUser->profilePhotoUrl() }}"
                            alt="{{ __('Foto profil :name', ['name' => $dashboardUser->name]) }}"
                            class="size-11 rounded-full object-cover"
                        >
                    @else
                        <div
                            aria-hidden="true"
                            class="flex size-11 items-center justify-center rounded-full bg-sanmal-100 font-semibold text-sanmal-700 dark:bg-sanmal-900 dark:text-sanmal-200"
                        >
                            {{ $dashboardUser->initials() }}
                        </div>
                    @endif
                </x-ui.page-header>

                <x-ui.card padding="p-5 sm:p-6" class="border-sanmal-200 bg-sanmal-50 dark:border-sanmal-800 dark:bg-sanmal-950/40">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 space-y-2">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Status absensi hari ini') }}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold tracking-tight">{{ $todayStatus['label'] }}</h2>
                                <x-ui.badge :color="$statusColor">{{ $todayStatus['label'] }}</x-ui.badge>
                            </div>

                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                @if ($employeeDashboard['todayClockIn'])
                                    <span>{{ __('Masuk') }}: {{ $employeeDashboard['todayClockIn']->effectiveRecordedAtLocal()->format('H:i') }}</span>
                                @endif
                                @if ($employeeDashboard['todayClockOut'])
                                    <span>{{ __('Pulang') }}: {{ $employeeDashboard['todayClockOut']->effectiveRecordedAtLocal()->format('H:i') }}</span>
                                @endif
                                @if ($employeeDashboard['todayDurationMinutes'] !== null)
                                    <span>
                                        {{ __('Durasi kerja') }}:
                                        {{ $formatDuration($employeeDashboard['todayDurationMinutes']) }}
                                        @if ($employeeDashboard['todayIsRunning'])
                                            <span class="font-medium">{{ __('· Berjalan') }}</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <x-ui.button
                            :href="route('attendance')"
                            variant="primary"
                            class="w-full shrink-0 sm:w-auto"
                        >
                            {{ __('Buka halaman absensi') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <section aria-labelledby="monthly-summary-heading" class="space-y-3">
                    <h2 id="monthly-summary-heading" class="text-base font-semibold">{{ __('Ringkasan bulan ini') }}</h2>
                    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                        <x-ui.card>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Hari hadir') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $monthly['presentDays'] }}</p>
                        </x-ui.card>
                        <x-ui.card>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total jam kerja') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $formatDuration($monthly['workMinutes']) }}</p>
                            @if ($monthly['workIsProvisional'])
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Termasuk sesi berjalan') }}</p>
                            @endif
                        </x-ui.card>
                        <x-ui.card>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Belum lengkap') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $monthly['incompleteDays'] }}</p>
                        </x-ui.card>
                        <x-ui.card>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total lembur') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $formatDuration($monthly['overtimeMinutes']) }}</p>
                            @if ($monthly['overtimeIsProvisional'])
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Termasuk lembur berjalan') }}</p>
                            @endif
                        </x-ui.card>
                    </div>
                </section>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)]">
                    <div class="space-y-4">
                        <x-ui.card>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-semibold">{{ __('Pengajuan Saya') }}</h2>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Pengajuan yang masih menunggu keputusan') }}</p>
                                </div>
                                <x-ui.badge :color="$pending['total'] > 0 ? 'amber' : 'zinc'">{{ $pending['total'] }}</x-ui.badge>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Koreksi') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['corrections'] }}</p>
                                </div>
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Lembur') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['overtime'] }}</p>
                                </div>
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Izin/cuti/sakit') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['leave'] }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-ui.button :href="route('leave-requests.index')" variant="outline">
                                    {{ __('Lihat pengajuan') }}
                                </x-ui.button>
                            </div>
                        </x-ui.card>

                        <x-ui.card>
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-semibold">{{ __('Aktivitas absensi terbaru') }}</h2>
                                <a href="{{ route('attendance-history.index') }}" class="text-sm font-medium text-sanmal-700 hover:underline dark:text-sanmal-300">{{ __('Lihat riwayat') }}</a>
                            </div>

                            <div class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($employeeDashboard['recentAttendance'] as $record)
                                    <div class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                                        <div>
                                            <p class="font-medium">{{ $record->type->label() }}</p>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $record->effectiveRecordedAtLocal()->translatedFormat('d M Y, H:i') }}</p>
                                        </div>
                                        <x-ui.badge>{{ $record->work_type->label() }}</x-ui.badge>
                                    </div>
                                @empty
                                    <p class="py-5 text-sm text-zinc-500">{{ __('Belum ada aktivitas absensi.') }}</p>
                                @endforelse
                            </div>
                        </x-ui.card>
                    </div>

                    <div class="space-y-4">
                        <x-ui.card>
                            <h2 class="font-semibold">{{ __('Akses cepat') }}</h2>
                            <nav aria-label="{{ __('Akses cepat') }}" class="mt-3 grid grid-cols-2 gap-2">
                                <x-ui.button :href="route('attendance-history.index')" variant="outline" class="justify-center">{{ __('Riwayat absensi') }}</x-ui.button>
                                <x-ui.button :href="route('leave-requests.index')" variant="outline" class="justify-center">{{ __('Pengajuan') }}</x-ui.button>
                                <x-ui.button :href="route('overtime-requests.index')" variant="outline" class="justify-center">{{ __('Lembur') }}</x-ui.button>
                                <x-ui.button :href="route('profile.edit')" variant="outline" class="justify-center">{{ __('Profil pegawai') }}</x-ui.button>
                            </nav>
                        </x-ui.card>
                    </div>
                </div>
            @endif
        @endif
    </x-ui.page>
</x-layouts::app>
