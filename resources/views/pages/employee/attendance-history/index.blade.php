<x-layouts::app :title="__('Riwayat Absensi')">
    <x-ui.page
        max-width="5xl"
        gap="5"
        x-data="{
            detailOpen: false,
            selectedEvent: null,
            lastTrigger: null,
            openEvent(event, trigger) {
                this.selectedEvent = event;
                this.lastTrigger = trigger;
                this.detailOpen = true;
                this.$nextTick(() => this.$refs.detailClose?.focus());
            },
            closeEvent() {
                this.detailOpen = false;
                this.$nextTick(() => this.lastTrigger?.focus());
            }
        }"
        x-on:keydown.escape.window="if (detailOpen) closeEvent()"
    >
        <x-ui.page-header
            :title="__('Riwayat Absensi')"
            :description="__('Tinjau ringkasan kehadiran dan bukti absensi berdasarkan tanggal sesi.')"
        >
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <x-ui.button
                    href="{{ route('attendance-history.export', ['from' => $filters['from'], 'to' => $filters['to']]) }}"
                    variant="outline"
                    icon="arrow-down-tray"
                    class="w-full sm:w-auto"
                >
                    {{ __('Ekspor rekap harian (CSV)') }}
                </x-ui.button>
                <x-ui.button href="{{ route('attendance') }}" variant="primary" icon="clock" class="w-full sm:w-auto">
                    {{ __('Buka halaman absensi') }}
                </x-ui.button>
            </div>
        </x-ui.page-header>

        <section aria-label="{{ __('Ringkasan kehadiran') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-ui.card padding="p-4">
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="calendar" class="size-4" />
                    {{ __('Hari hadir') }}
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($summary['present_days']) }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Termasuk koreksi hadir dari admin.') }}</p>
            </x-ui.card>
            <x-ui.card padding="p-4">
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="check-circle" class="size-4" />
                    {{ __('Absensi lengkap') }}
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($summary['complete_days']) }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Memiliki Clock In dan Clock Out yang valid.') }}</p>
            </x-ui.card>
            <x-ui.card padding="p-4">
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="exclamation-triangle" class="size-4" />
                    {{ __('Perlu ditinjau') }}
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($summary['needs_review_days']) }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Tanggal dengan bukti atau status yang perlu diperiksa.') }}</p>
            </x-ui.card>
        </section>

        <x-ui.card padding="p-4 sm:p-5">
            <section>
            <div class="mb-4">
                <flux:heading size="lg">{{ __('Filter riwayat') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('Pilih rentang tanggal dan urutan kelompok tanggal.') }}</flux:text>
            </div>
            <form method="GET" action="{{ route('attendance-history.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.input
                    name="from"
                    label="{{ __('Dari tanggal') }}"
                    type="date"
                    :value="$filters['from']"
                />
                <x-ui.input
                    name="to"
                    label="{{ __('Sampai tanggal') }}"
                    type="date"
                    :value="$filters['to']"
                />
                <x-ui.select name="direction" label="{{ __('Urutan tanggal') }}">
                    <flux:select.option value="desc" :selected="$filters['direction'] === 'desc'">{{ __('Terbaru dahulu') }}</flux:select.option>
                    <flux:select.option value="asc" :selected="$filters['direction'] === 'asc'">{{ __('Terlama dahulu') }}</flux:select.option>
                </x-ui.select>
                <div class="flex flex-col justify-end gap-2 sm:col-span-2 sm:flex-row lg:col-span-1">
                    <x-ui.button href="{{ route('attendance-history.index') }}" variant="ghost" class="w-full sm:w-auto">
                        {{ __('Reset') }}
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" icon="funnel" class="w-full sm:w-auto">
                        {{ __('Terapkan') }}
                    </x-ui.button>
                </div>
            </form>
            </section>
        </x-ui.card>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <flux:heading size="lg">{{ __('Riwayat per tanggal') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ number_format($dateGroups->total()) }} {{ __('tanggal tercatat') }}</flux:text>
            </div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Maksimal 10 tanggal per halaman') }}</span>
        </div>

        @if ($dateGroups->isEmpty())
            <x-ui.card padding="p-5" class="text-center">
                <flux:icon name="calendar" class="mx-auto size-9 text-zinc-400" />
                <flux:heading size="md" class="mt-3">{{ __('Belum ada riwayat absensi') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Tidak ada status kehadiran atau event pada rentang tanggal ini. Coba ubah filter tanggal.') }}</flux:text>
            </x-ui.card>
        @else
            <div class="space-y-3">
                @foreach ($dateGroups as $group)
                    @php
                        $statusColor = match ($group['status']) {
                            'present' => 'green',
                            'incomplete' => 'amber',
                            'permission' => 'sky',
                            'sick' => 'amber',
                            'leave' => 'purple',
                            'absent', 'needs_review' => 'red',
                            default => 'zinc',
                        };
                    @endphp
                    <x-ui.card padding="p-0" class="overflow-hidden">
                        <article>
                        <header class="flex flex-col gap-3 border-b border-zinc-100 px-4 py-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ \Illuminate\Support\Carbon::parse($group['date'])->locale('id')->translatedFormat('l, d F Y') }}
                                </h2>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $group['events']->count() }} {{ __('event absensi') }}</p>
                            </div>
                            <x-ui.badge :color="$statusColor">{{ $group['label'] }}</x-ui.badge>
                        </header>

                        @if ($group['reason'])
                            <div class="mx-4 mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-200 sm:mx-5">
                                <p class="font-medium">{{ __('Catatan status') }}</p>
                                <p class="mt-0.5 break-words">{{ $group['reason'] }}</p>
                            </div>
                        @endif

                        @if ($group['correction'])
                            <div class="mx-4 mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800 sm:mx-5">
                                <p class="font-medium">{{ __('Koreksi administratif') }}</p>
                                <p class="mt-0.5 break-words text-zinc-600 dark:text-zinc-300">{{ $group['correction']->reason }}</p>
                            </div>
                        @endif

                        @if ($group['leaveRequests']->isNotEmpty())
                            <div class="mx-4 mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800 sm:mx-5">
                                <p class="font-medium">{{ __('Permohonan disetujui') }}</p>
                                @foreach ($group['leaveRequests'] as $leaveRequest)
                                    <p class="mt-0.5 text-zinc-600 dark:text-zinc-300">{{ $leaveRequest->leaveType?->name ?? __('Permohonan') }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if ($group['events']->isEmpty())
                            <div class="px-4 py-5 text-sm text-zinc-500 dark:text-zinc-400 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="clock" class="size-4 shrink-0" />
                                    {{ __('Tidak ada event foto atau waktu untuk tanggal ini. Status berasal dari catatan administratif atau permohonan.') }}
                                </div>
                            </div>
                        @else
                            <ol class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($group['events'] as $record)
                                    @php
                                        $validCoordinates = $record->location_status === 'captured'
                                            && is_numeric($record->latitude)
                                            && is_numeric($record->longitude)
                                            && (float) $record->latitude >= -90
                                            && (float) $record->latitude <= 90
                                            && (float) $record->longitude >= -180
                                            && (float) $record->longitude <= 180;
                                        $eventPayload = [
                                            'type' => $record->type->label(),
                                            'workType' => $record->work_type->label(),
                                            'recordedAt' => $record->recordedAtLocal()->translatedFormat('d M Y, H:i:s'),
                                            'effectiveAt' => $record->effectiveRecordedAtLocal()->translatedFormat('d M Y, H:i:s'),
                                            'timezone' => $record->timezone,
                                            'timezoneSource' => $record->timezone_source,
                                            'locationStatus' => $record->location_status,
                                            'gpsVerified' => $validCoordinates,
                                            'accuracy' => $record->accuracy_m,
                                            'latitude' => $record->latitude,
                                            'longitude' => $record->longitude,
                                            'location' => $record->location_name_snapshot,
                                            'client' => $record->client_name_snapshot ? trim(($record->client_code_snapshot ? $record->client_code_snapshot.' · ' : '').$record->client_name_snapshot) : null,
                                            'project' => $record->project_name_snapshot ? trim(($record->project_code_snapshot ? $record->project_code_snapshot.' · ' : '').$record->project_name_snapshot) : null,
                                            'address' => $record->address_snapshot ?: __('Alamat tidak tersedia'),
                                            'mapUrl' => $validCoordinates ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($record->latitude.','.$record->longitude) : null,
                                            'photoUrl' => $record->selfie_path && ! $record->local_deleted_at ? route('attendance-history.selfie', $record) : null,
                                            'photoDownloadUrl' => $record->selfie_path ? route('attendance-history.selfie.download', $record) : null,
                                            'photoInDrive' => (bool) ($record->selfie_path && $record->local_deleted_at),
                                            'snapshotSource' => $record->snapshot_source,
                                            'notes' => $record->notes,
                                            'hasCorrection' => $record->approvedCorrection !== null,
                                        ];
                                    @endphp
                                    <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                @if ($record->type->value === 'clock_in')
                                                    <flux:icon name="clock" class="size-4" />
                                                @elseif ($record->type->value === 'clock_out')
                                                    <flux:icon name="clock" class="size-4" />
                                                @else
                                                    <flux:icon name="clock" class="size-4" />
                                                @endif
                                            </span>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $record->type->label() }}</p>
                                                    @if ($record->approvedCorrection)
                                                        <x-ui.badge color="amber">{{ __('Waktu dikoreksi') }}</x-ui.badge>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ $record->effectiveRecordedAtLocal()->translatedFormat('H:i') }}</p>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $record->work_type->label() }}
                                                    @if ($record->location_name_snapshot)
                                                        · {{ $record->location_name_snapshot }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                                            @if ($record->selfie_path)
                                                <span class="inline-flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                    <flux:icon name="{{ $record->local_deleted_at ? 'cloud' : 'camera' }}" class="size-4" />
                                                    {{ $record->local_deleted_at ? __('Foto di Drive') : __('Foto tersedia') }}
                                                </span>
                                            @endif
                                            <button
                                                type="button"
                                                data-event="{{ base64_encode(json_encode($eventPayload, JSON_THROW_ON_ERROR)) }}"
                                                x-on:click="openEvent(JSON.parse(atob($el.dataset.event)), $el)"
                                                class="inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-blue-700 transition hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-blue-300 dark:hover:bg-blue-950/30"
                                                aria-haspopup="dialog"
                                                aria-controls="attendance-event-detail-dialog"
                                            >
                                                {{ __('Lihat detail') }}
                                                <flux:icon name="chevron-right" class="size-4" />
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                        </article>
                    </x-ui.card>
                @endforeach
            </div>

            <nav class="pt-2" aria-label="{{ __('Navigasi halaman riwayat absensi') }}">
                {{ $dateGroups->links() }}
            </nav>
        @endif

        <div
            x-show="detailOpen"
            x-cloak
            x-transition.opacity
            x-on:click.self="closeEvent()"
            x-trap.noscroll="detailOpen"
            id="attendance-event-detail-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="attendance-event-detail-title"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-3 sm:p-5"
        >
            <section class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900 sm:max-h-[calc(100dvh-2.5rem)]">
                <header class="flex shrink-0 items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 sm:px-5">
                    <div class="min-w-0">
                        <h2 id="attendance-event-detail-title" class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Detail event absensi') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="selectedEvent?.type"></p>
                    </div>
                    <button
                        type="button"
                        x-ref="detailClose"
                        x-on:click="closeEvent()"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800"
                        aria-label="{{ __('Tutup detail') }}"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </header>

                <div class="min-h-0 space-y-5 overflow-y-auto p-4 sm:p-5">
                    <section>
                        <h3 class="mb-2 text-sm font-semibold">{{ __('Waktu pencatatan') }}</h3>
                        <dl class="grid grid-cols-1 gap-3 rounded-xl bg-zinc-50 p-3 text-sm dark:bg-zinc-800 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Waktu asli') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums" x-text="selectedEvent?.recordedAt"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Waktu efektif') }}</dt>
                                <dd class="mt-1 font-medium tabular-nums" x-text="selectedEvent?.effectiveAt"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Zona waktu sesi') }}</dt>
                                <dd class="mt-1 font-medium" x-text="selectedEvent?.timezone"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Jenis kerja') }}</dt>
                                <dd class="mt-1 font-medium" x-text="selectedEvent?.workType"></dd>
                            </div>
                        </dl>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400" x-show="selectedEvent?.hasCorrection">{{ __('Waktu efektif mengikuti koreksi yang disetujui; waktu asli tetap ditampilkan sebagai bukti pencatatan.') }}</p>
                    </section>

                    <section>
                        <h3 class="mb-2 text-sm font-semibold">{{ __('Lokasi dan GPS') }}</h3>
                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Lokasi kerja') }}</dt>
                                <dd class="mt-1 font-medium" x-text="selectedEvent?.location || '—'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Status GPS') }}</dt>
                                <dd class="mt-1 font-medium" x-text="selectedEvent?.gpsVerified ? 'Terverifikasi' : 'Tidak terverifikasi'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Akurasi') }}</dt>
                                <dd class="mt-1 font-medium" x-text="selectedEvent?.accuracy ? selectedEvent.accuracy + ' m' : '—'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Koordinat') }}</dt>
                                <dd class="mt-1 break-all font-medium tabular-nums" x-text="selectedEvent?.latitude && selectedEvent?.longitude ? selectedEvent.latitude + ', ' + selectedEvent.longitude : '—'"></dd>
                            </div>
                        </dl>
                        <p class="mt-3 break-words text-sm text-zinc-600 dark:text-zinc-300" x-text="selectedEvent?.address"></p>
                        <a
                            x-show="selectedEvent?.mapUrl"
                            x-bind:href="selectedEvent?.mapUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:underline dark:text-blue-300"
                        >
                            <flux:icon name="map-pin" class="size-4" />
                            {{ __('Buka koordinat di peta') }}
                        </a>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400" x-show="selectedEvent?.timezoneSource === 'fallback'">{{ __('Zona waktu menggunakan fallback dan perlu ditinjau.') }}</p>
                    </section>

                    <section>
                        <h3 class="mb-2 text-sm font-semibold">{{ __('Konteks pekerjaan') }}</h3>
                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div x-show="selectedEvent?.client">
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Client') }}</dt>
                                <dd class="mt-1 break-words font-medium" x-text="selectedEvent?.client"></dd>
                            </div>
                            <div x-show="selectedEvent?.project">
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Proyek') }}</dt>
                                <dd class="mt-1 break-words font-medium" x-text="selectedEvent?.project"></dd>
                            </div>
                        </dl>
                    </section>

                    <section x-show="selectedEvent?.notes">
                        <h3 class="mb-2 text-sm font-semibold">{{ __('Catatan') }}</h3>
                        <p class="whitespace-pre-line break-words text-sm text-zinc-600 dark:text-zinc-300" x-text="selectedEvent?.notes"></p>
                    </section>

                    <section>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold">{{ __('Bukti foto') }}</h3>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400" x-show="selectedEvent?.photoInDrive">{{ __('Dicadangkan di Drive') }}</span>
                        </div>
                        <template x-if="selectedEvent?.photoUrl">
                            <img x-bind:src="selectedEvent.photoUrl" alt="{{ __('Foto bukti absensi') }}" class="max-h-80 w-full rounded-xl bg-zinc-100 object-contain dark:bg-zinc-800" />
                        </template>
                        <p x-show="!selectedEvent?.photoUrl && !selectedEvent?.photoInDrive" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Foto tidak tersedia untuk event ini.') }}</p>
                        <a
                            x-show="selectedEvent?.photoDownloadUrl"
                            x-bind:href="selectedEvent?.photoDownloadUrl"
                            class="mt-3 inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-blue-600 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            <flux:icon name="arrow-down-tray" class="size-4" />
                            {{ __('Unduh foto privat') }}
                        </a>
                    </section>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400" x-show="selectedEvent?.snapshotSource === 'migration_backfill'">
                        {{ __('Identitas pekerjaan berasal dari baseline migrasi, bukan snapshot historis terverifikasi.') }}
                    </p>
                </div>
            </section>
        </div>
    </x-ui.page>
</x-layouts::app>
