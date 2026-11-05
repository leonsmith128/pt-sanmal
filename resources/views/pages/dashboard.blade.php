<x-layouts::app :title="__('Dashboard')">
    <x-ui.page :gap="$isManager ? '6' : '4'">
        @if ($isManager)
            <x-ui.page-header :title="__('Dashboard Administrasi')" :description="$today">
                <x-ui.button
                    :href="route('admin.daily-attendance', ['date' => $todayDate])"
                    variant="outline"
                    icon="calendar"
                >
                    {{ __('Lihat kehadiran hari ini') }}
                </x-ui.button>
            </x-ui.page-header>

            <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(19rem,0.85fr)]">
                <section aria-labelledby="attendance-overview-heading">
                    <x-ui.card padding="p-0" class="overflow-hidden">
                        <div class="bg-sanmal-brand-blue px-5 py-5 text-white dark:bg-sanmal-surface-primary-dark sm:px-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-white/75 dark:text-[var(--sanmal-text-secondary)]">{{ __('Kehadiran hari ini') }}</p>
                                    <h2 id="attendance-overview-heading" class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">
                                        {{ number_format($presentToday) }} <span class="text-lg font-medium text-white/75 dark:text-[var(--sanmal-text-secondary)]">/ {{ number_format($activeEmployees) }}</span>
                                    </h2>
                                    <p class="mt-1 text-sm text-white/75 dark:text-[var(--sanmal-text-secondary)]">{{ __('pegawai aktif telah hadir') }}</p>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-white/80 dark:text-[var(--sanmal-text-secondary)]">
                                    <flux:icon name="users" class="size-4" />
                                    <span>{{ __('Pegawai aktif') }}: {{ number_format($activeEmployees) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-px bg-sanmal-border dark:bg-sanmal-border-dark">
                            <div class="min-w-0 bg-sanmal-surface-card p-3 dark:bg-sanmal-surface-card-dark sm:p-4">
                                <p class="text-xs leading-4 text-[var(--sanmal-text-secondary)] sm:text-sm">{{ __('Belum lengkap') }}</p>
                                <p class="mt-1 text-xl font-semibold tabular-nums text-sanmal-warning sm:text-2xl">{{ number_format($incompleteToday) }}</p>
                            </div>
                            <div class="min-w-0 bg-sanmal-surface-card p-3 dark:bg-sanmal-surface-card-dark sm:p-4">
                                <p class="text-xs leading-4 text-[var(--sanmal-text-secondary)] sm:text-sm">{{ __('Perlu ditinjau') }}</p>
                                <p class="mt-1 text-xl font-semibold tabular-nums text-sanmal-danger sm:text-2xl">{{ number_format($needsReviewToday) }}</p>
                            </div>
                            <div class="min-w-0 bg-sanmal-surface-card p-3 dark:bg-sanmal-surface-card-dark sm:p-4">
                                <p class="text-xs leading-4 text-[var(--sanmal-text-secondary)] sm:text-sm">{{ __('Belum ada catatan') }}</p>
                                <p class="mt-1 text-xl font-semibold tabular-nums text-[var(--sanmal-text-primary)] sm:text-2xl">{{ number_format($noRecordToday) }}</p>
                            </div>
                        </div>
                    </x-ui.card>
                </section>

                <section aria-labelledby="admin-queue-heading" class="space-y-3">
                    <div class="flex items-baseline justify-between gap-3 px-1">
                        <h2 id="admin-queue-heading" class="text-base font-semibold text-[var(--sanmal-text-primary)]">{{ __('Antrean tindakan') }}</h2>
                        <p class="text-sm text-[var(--sanmal-text-muted)]">{{ __('Butuh keputusan') }}</p>
                    </div>

                    <x-ui.card padding="p-0">
                        <nav aria-label="{{ __('Antrean tindakan') }}" class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                            <a href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}" class="group flex min-h-20 items-center gap-3 rounded-t-xl px-4 py-3 transition-colors hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark dark:focus-visible:ring-sanmal-brand-blue-light">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                                    <flux:icon name="document-text" class="size-4" />
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-medium leading-5 text-[var(--sanmal-text-primary)]">{{ __('Permohonan menunggu') }}</span>
                                <span class="text-xl font-semibold tabular-nums text-[var(--sanmal-text-primary)]">{{ number_format($pendingLeave) }}</span>
                                <flux:icon name="chevron-right" class="size-4 shrink-0 text-[var(--sanmal-text-muted)] transition-transform motion-reduce:transition-none group-hover:translate-x-0.5" />
                            </a>
                            <a href="{{ route('admin.overtime-requests.index', ['status' => 'pending']) }}" class="group flex min-h-20 items-center gap-3 px-4 py-3 transition-colors hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark dark:focus-visible:ring-sanmal-brand-blue-light">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                                    <flux:icon name="clock" class="size-4" />
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-medium leading-5 text-[var(--sanmal-text-primary)]">{{ __('Persetujuan lembur menunggu') }}</span>
                                <span class="text-xl font-semibold tabular-nums text-[var(--sanmal-text-primary)]">{{ number_format($pendingOvertime) }}</span>
                                <flux:icon name="chevron-right" class="size-4 shrink-0 text-[var(--sanmal-text-muted)] transition-transform motion-reduce:transition-none group-hover:translate-x-0.5" />
                            </a>
                            <a href="{{ route('admin.daily-attendance', ['date' => $todayDate]) }}" class="group flex min-h-20 items-center gap-3 rounded-b-xl px-4 py-3 transition-colors hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark dark:focus-visible:ring-sanmal-brand-blue-light">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                                    <flux:icon name="exclamation-triangle" class="size-4" />
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-medium leading-5 text-[var(--sanmal-text-primary)]">{{ __('Catatan perlu ditinjau') }}</span>
                                <span class="text-xl font-semibold tabular-nums text-[var(--sanmal-text-primary)]">{{ number_format($needsReviewToday) }}</span>
                                <flux:icon name="chevron-right" class="size-4 shrink-0 text-[var(--sanmal-text-muted)] transition-transform motion-reduce:transition-none group-hover:translate-x-0.5" />
                            </a>
                        </nav>
                    </x-ui.card>
                </section>
            </div>

            <section aria-labelledby="recent-attendance-heading">
                <x-ui.card padding="p-0" class="overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-sanmal-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-sanmal-border-dark">
                        <div>
                            <h2 id="recent-attendance-heading" class="text-lg font-semibold text-[var(--sanmal-text-primary)]">{{ __('Aktivitas absensi terbaru') }}</h2>
                            <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">{{ __('Catatan masuk dan pulang yang tercatat hari ini') }}</p>
                        </div>
                        <x-ui.button :href="route('admin.daily-attendance', ['date' => $todayDate])" variant="ghost" icon-trailing="chevron-right">
                            {{ __('Buka daftar') }}
                        </x-ui.button>
                    </div>

                    <div class="divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                        @forelse ($recentAttendance as $record)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                                        <flux:icon :name="$record->type->value === 'clock_in' ? 'arrow-right' : 'arrow-left'" class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-[var(--sanmal-text-primary)]">{{ $record->employee?->user?->name ?? __('Pegawai tidak ditemukan') }}</p>
                                        <p class="mt-0.5 text-sm text-[var(--sanmal-text-secondary)]">
                                            {{ $record->type->label() }} · <time datetime="{{ $record->recordedAtLocal()->toIso8601String() }}">{{ $record->recordedAtLocal()->format('d M Y, H:i') }}</time>
                                        </p>
                                    </div>
                                </div>

                                <x-ui.badge>{{ $record->work_type->label() }}</x-ui.badge>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-[var(--sanmal-text-muted)]">
                                {{ __('Belum ada catatan absensi hari ini.') }}
                            </p>
                        @endforelse
                    </div>
                </x-ui.card>
            </section>
        @else
            @if (! $employeeDashboard)
                <x-ui.page-header
                    :title="__('Selamat datang, :name', ['name' => auth()->user()->name])"
                    :description="$today"
                />

                <x-ui.empty-state>
                    <h2 class="font-semibold text-[var(--sanmal-text-primary)]">{{ __('Profil pegawai belum tersedia') }}</h2>
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

                <x-employee.identity-header
                    :name="$dashboardUser->name"
                    :initials="$dashboardUser->initials()"
                    :src="$dashboardUser->profilePhotoUrl()"
                    :greeting="__('Selamat datang,')"
                    :description="$employeeDashboard['today']"
                    avatar-size="lg"
                >
                    <x-slot:actions>
                        <x-ui.notification-dropdown
                            :notifications="$notifications"
                            :unread-count="$unreadNotificationCount"
                        />
                    </x-slot:actions>
                </x-employee.identity-header>

                <section aria-labelledby="today-attendance-heading">
                    <x-ui.card padding="p-0" class="overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-sanmal-brand-blue px-4 py-4 text-white sm:px-5 dark:bg-sanmal-surface-primary-dark">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white dark:text-sanmal-brand-yellow-dark">
                                    <flux:icon.clock class="size-5" aria-hidden="true" />
                                </span>
                                <h2 id="today-attendance-heading" class="min-w-0 text-sm font-semibold text-white sm:text-base">
                                    {{ __('Status absensi hari ini') }}
                                </h2>
                            </div>
                            <x-ui.badge :color="$statusColor" class="shrink-0 px-2 py-0.5 text-xs font-semibold leading-4">{{ $todayStatus['label'] }}</x-ui.badge>
                        </div>

                        <div class="grid gap-4 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5">
                            <dl class="grid min-w-0 grid-cols-2 gap-x-4 gap-y-3 sm:flex sm:flex-wrap sm:gap-x-6">
                                @if ($employeeDashboard['todayClockIn'])
                                    <div class="min-w-0">
                                        <dt class="text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Masuk') }}</dt>
                                        <dd class="mt-1 text-sm font-semibold tabular-nums text-[var(--sanmal-text-primary)]">{{ $employeeDashboard['todayClockIn']->effectiveRecordedAtLocal()->format('H:i') }}</dd>
                                    </div>
                                @endif
                                @if ($employeeDashboard['todayClockOut'])
                                    <div class="min-w-0">
                                        <dt class="text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Pulang') }}</dt>
                                        <dd class="mt-1 text-sm font-semibold tabular-nums text-[var(--sanmal-text-primary)]">{{ $employeeDashboard['todayClockOut']->effectiveRecordedAtLocal()->format('H:i') }}</dd>
                                    </div>
                                @endif
                                @if ($employeeDashboard['todayDurationMinutes'] !== null)
                                    <div class="min-w-0">
                                        <dt class="text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Durasi kerja') }}</dt>
                                        <dd class="mt-1 text-sm font-semibold tabular-nums text-[var(--sanmal-text-primary)]">
                                            {{ $formatDuration($employeeDashboard['todayDurationMinutes']) }}
                                            @if ($employeeDashboard['todayIsRunning'])
                                                <span class="font-medium text-[var(--sanmal-text-secondary)]">· {{ __('Berjalan') }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            <x-ui.button
                                :href="route('attendance')"
                                variant="primary"
                                class="min-h-11 w-full shrink-0 sm:w-auto"
                            >
                                {{ __('Buka halaman absensi') }}
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                </section>

                <x-ui.card>
                    <h2 id="monthly-summary-heading" class="text-base font-semibold">{{ __('Ringkasan bulan ini') }}</h2>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-center xl:grid-cols-4">
                        <div class="flex min-w-0 flex-col items-center justify-center text-center">
                            <p class="text-sm leading-5 text-[var(--sanmal-text-secondary)]">{{ __('Hari hadir') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums sm:text-3xl">{{ $monthly['presentDays'] }}</p>
                        </div>
                        <div class="flex min-w-0 flex-col items-center justify-center text-center">
                            <p class="text-sm leading-5 text-[var(--sanmal-text-secondary)]">{{ __('Total jam kerja') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums sm:text-3xl">{{ $formatDuration($monthly['workMinutes']) }}</p>
                            @if ($monthly['workIsProvisional'])
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Termasuk sesi berjalan') }}</p>
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-col items-center justify-center text-center">
                            <p class="text-sm leading-5 text-[var(--sanmal-text-secondary)]">{{ __('Belum lengkap') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums sm:text-3xl">{{ $monthly['incompleteDays'] }}</p>
                        </div>
                        <div class="flex min-w-0 flex-col items-center justify-center text-center">
                            <p class="text-sm leading-5 text-[var(--sanmal-text-secondary)]">{{ __('Total lembur') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums sm:text-3xl">{{ $formatDuration($monthly['overtimeMinutes']) }}</p>
                            @if ($monthly['overtimeIsProvisional'])
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Termasuk lembur berjalan') }}</p>
                            @endif
                            @if ($monthly['overtimeNeedsReview'])
                                <p class="mt-1 text-xs font-medium text-red-700 dark:text-red-300">{{ __('Ada interval lembur di luar sesi absensi dan perlu ditinjau admin') }}</p>
                            @endif
                        </div>
                    </div>
                </x-ui.card>

                <div class="grid gap-4 lg:grid-cols-[minmax(18rem,0.85fr)_minmax(0,1.15fr)]">
                    <div class="space-y-4">
                        <x-ui.card>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-semibold">{{ __('Pengajuan Saya') }}</h2>
                                    <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">{{ __('Pengajuan yang masih menunggu keputusan') }}</p>
                                </div>
                                <x-ui.badge :color="$pending['total'] > 0 ? 'amber' : 'zinc'" class="shrink-0 px-2 py-0.5 text-xs leading-4">{{ $pending['total'] }}</x-ui.badge>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                                <div class="flex flex-col items-center text-center">
                                    <p class="text-[var(--sanmal-text-muted)]">{{ __('Koreksi') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['corrections'] }}</p>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <p class="text-[var(--sanmal-text-muted)]">{{ __('Lembur') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['overtime'] }}</p>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <p class="text-[var(--sanmal-text-muted)]">{{ __('Izin/cuti/sakit') }}</p>
                                    <p class="mt-1 text-lg font-semibold tabular-nums">{{ $pending['leave'] }}</p>
                                </div>
                            </div>

                            @if ($pending['leaveBreakdown']->isNotEmpty())
                                <div class="mt-4 border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                                    <p class="mb-2 text-xs font-medium text-[var(--sanmal-text-muted)]">{{ __('Rincian permohonan') }}</p>
                                    <ul class="flex flex-wrap gap-2 text-xs">
                                        @foreach ($pending['leaveBreakdown'] as $type => $count)
                                            <li class="rounded-full bg-sanmal-surface-inset px-3 py-1.5 text-[var(--sanmal-text-secondary)] dark:bg-sanmal-surface-inset-dark">
                                                {{ $type }}: <span class="font-semibold tabular-nums">{{ $count }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-4">
                                <x-ui.button :href="route('leave-requests.index')" variant="outline">
                                    {{ __('Lihat pengajuan') }}
                                </x-ui.button>
                            </div>
                        </x-ui.card>

                        <x-ui.card>
                            <h2 class="font-semibold">{{ __('Akses cepat') }}</h2>
                            <nav aria-label="{{ __('Akses cepat') }}" class="mt-3 grid grid-cols-2 gap-2">
                                <x-ui.button :href="route('attendance-history.index')" variant="outline" icon="calendar" class="min-h-11 justify-center">{{ __('Riwayat absensi') }}</x-ui.button>
                                <x-ui.button :href="route('leave-requests.index')" variant="outline" icon="document-text" class="min-h-11 justify-center">{{ __('Pengajuan') }}</x-ui.button>
                                <x-ui.button :href="route('overtime-requests.index')" variant="outline" icon="clock" class="min-h-11 justify-center">{{ __('Lembur') }}</x-ui.button>
                                <x-ui.button :href="route('profile.edit')" variant="outline" icon="user" class="min-h-11 justify-center">{{ __('Profil pegawai') }}</x-ui.button>
                            </nav>
                        </x-ui.card>
                    </div>

                    <div class="space-y-4">
                        <x-ui.card>
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-semibold">{{ __('Aktivitas absensi terbaru') }}</h2>
                                <a href="{{ route('attendance-history.index') }}" class="text-sm font-medium text-sanmal-brand-blue hover:underline dark:text-sanmal-brand-blue-light">{{ __('Lihat riwayat') }}</a>
                            </div>

                            <div class="mt-3 divide-y divide-sanmal-border dark:divide-sanmal-border-dark">
                                @forelse ($employeeDashboard['recentAttendance'] as $record)
                                    <div class="flex min-w-0 flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                                                <flux:icon :name="match ($record->type->value) { 'clock_in' => 'arrow-right', 'clock_out' => 'arrow-left', default => 'clock' }" class="size-4" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium">{{ $record->type->label() }}</p>
                                                <p class="text-sm text-[var(--sanmal-text-muted)]">{{ $record->effectiveRecordedAtLocal()->translatedFormat('d M Y, H:i') }}</p>
                                            </div>
                                        </div>
                                        <x-ui.badge class="shrink-0">{{ $record->work_type->label() }}</x-ui.badge>
                                    </div>
                                @empty
                                    <p class="py-5 text-sm text-[var(--sanmal-text-muted)]">{{ __('Belum ada aktivitas absensi.') }}</p>
                                @endforelse
                            </div>
                        </x-ui.card>
                    </div>
                </div>
            @endif
        @endif
    </x-ui.page>
</x-layouts::app>
