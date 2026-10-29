<?php

use App\Actions\ConfirmOvertimeStart;
use App\Actions\CreateOvertimeRequest;
use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use App\Enums\OvertimeState;
use App\Models\AttendanceRecord;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Absensi')] class extends Component {
    use WithFileUploads;

    public string $workType = 'office';
    public $locationId = null;
    public $projectId = null;

    public $latitude = null;
    public $longitude = null;
    public $accuracy = null;
    public $addressSnapshot = null;
    public $selfie = null;
    public string $selfieSource = 'camera';
    public string $selectedDate = '';
    public string $overtimeReason = '';
    public string $overtimeStartAt = '';

    public function mount(): void
    {
        $user = Auth::user();

        // Superadmin (tanpa profil pegawai) dan akun nonaktif tidak boleh absen.
        abort_unless($user->is_active && $user->employee, 403);

        $this->selectedDate = now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString();
        $this->locationId = $this->officeLocations->first()?->id;
        $this->projectId = $this->projects->first()?->id;
    }

    #[Computed]
    public function employee()
    {
        return Auth::user()->employee;
    }

    #[Computed]
    public function openSession(): ?AttendanceRecord
    {
        return RecordAttendance::openSession($this->employee);
    }

    #[Computed]
    public function officeLocations()
    {
        return RecordAttendance::officeLocations()->get();
    }

    #[Computed]
    public function projects()
    {
        return RecordAttendance::assignableProjects($this->employee)->get();
    }

    #[Computed]
    public function overtimeRequest(): ?OvertimeRequest
    {
        $session = $this->openSession;

        if (! $session) {
            return null;
        }

        return $this->employee->overtimeRequests()
            ->where('clock_in_record_id', $session->id)
            ->whereNotIn('state', [OvertimeState::Completed, OvertimeState::Cancelled])
            ->latest('scheduled_start_at')
            ->first();
    }

    #[Computed]
    public function weekDays(): array
    {
        $today = now(RecordAttendance::DEFAULT_TIMEZONE)->startOfDay();
        $start = Carbon::parse($this->selectedDate ?: $today->toDateString())->startOfWeek(Carbon::MONDAY);

        return collect(range(0, 6))->map(fn (int $offset) => [
            'date' => $start->copy()->addDays($offset)->toDateString(),
            'label' => $start->copy()->addDays($offset)->translatedFormat('D'),
            'day' => $start->copy()->addDays($offset)->format('j'),
            'today' => $start->copy()->addDays($offset)->isSameDay($today),
        ])->all();
    }

    public function selectDate(string $date): void
    {
        abort_unless(Carbon::hasFormat($date, 'Y-m-d') && (collect($this->weekDays)->contains(fn ($day) => $day['date'] === $date) || $date === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString()), 422);
        $this->selectedDate = $date;
        unset($this->history, $this->selectedDateOvertimeRequest);
    }

    public function shiftWeek(int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $current = Carbon::parse($this->selectedDate ?: now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString());
        $this->selectedDate = $current->addWeeks($direction)->startOfWeek(Carbon::MONDAY)->toDateString();
        unset($this->weekDays, $this->history, $this->selectedDateOvertimeRequest);
    }

    public function requestOvertime(): void
    {
        $validated = $this->validate([
            'overtimeReason' => ['required', 'string', 'min:5', 'max:2000'],
            'overtimeStartAt' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);

        app(CreateOvertimeRequest::class)->handle(
            employee: $this->employee,
            reason: $validated['overtimeReason'],
            scheduledStartLocal: $validated['overtimeStartAt'],
        );

        $this->reset('overtimeReason', 'overtimeStartAt');
        unset($this->overtimeRequest);
        Flux::modal('request-overtime')->close();
        Flux::toast(variant: 'success', text: 'Pengajuan lembur berhasil disimpan.');
    }

    #[Computed]
    public function selectedDateOvertimeRequest(): ?OvertimeRequest
    {
        return $this->employee->overtimeRequests()
            ->whereDate('scheduled_start_at', $this->selectedDate)
            ->whereNotIn('state', [OvertimeState::Cancelled, OvertimeState::Completed])
            ->latest('scheduled_start_at')
            ->first();
    }

    #[Computed]
    public function history()
    {
        return $this->employee->attendanceRecords()
            ->with(['location', 'project'])
            ->whereDate('attendance_date', $this->selectedDate)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function retakeSelfie(): void
    {
        $this->reset('selfie', 'selfieSource', 'latitude', 'longitude', 'accuracy', 'addressSnapshot');
        $this->resetErrorBag();
    }

    private function attendanceType(string $type): AttendanceType
    {
        $attendanceType = AttendanceType::tryFrom($type);
        abort_if($attendanceType === null, 422);

        return $attendanceType;
    }

    private function validateInput(AttendanceType $type): void
    {
        $rules = [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'addressSnapshot' => ['nullable', 'string', 'max:500'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:200'],
            'selfieSource' => ['required', Rule::enum(SelfieSource::class)],
        ];

        if ($type === AttendanceType::ClockIn) {
            $rules['workType'] = ['required', Rule::enum(WorkType::class)];
        }

        $this->validate($rules, [
            'latitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'longitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'selfie.required' => 'Foto selfie wajib diambil.',
            'selfie.image' => 'File harus berupa gambar.',
            'selfie.mimes' => 'Format foto harus jpg, png, atau webp.',
            'selfie.max' => 'Ukuran foto maksimal 200 KB. Coba ambil ulang foto.',
        ]);
    }

    /** Langkah 1: validasi dulu, lalu minta konfirmasi lewat modal. */
    public function confirm(string $type): void
    {
        $this->validateInput($this->attendanceType($type));

        Flux::modal('confirm-attendance')->show();
    }

    /**
     * Langkah 2: dijalankan setelah pegawai menekan "Ya" di modal.
     * Modal hanya kenyamanan; validasi dan aturan absensi tetap ditegakkan di server.
     */
    public function submit(string $type): void
    {
        $attendanceType = $this->attendanceType($type);

        try {
            $this->validateInput($attendanceType);

            if ($attendanceType === AttendanceType::OvertimeStart) {
                $overtime = $this->overtimeRequest;
                abort_unless($overtime, 422, 'Tidak ada pengajuan lembur aktif.');

                app(ConfirmOvertimeStart::class)->handle(
                    employee: $this->employee,
                    overtime: $overtime,
                    latitude: (float) $this->latitude,
                    longitude: (float) $this->longitude,
                    accuracy: $this->accuracy !== null ? (float) $this->accuracy : null,
                    addressSnapshot: $this->addressSnapshot,
                    selfie: $this->selfie,
                    selfieSource: SelfieSource::from($this->selfieSource),
                );
            } else {
                app(RecordAttendance::class)->handle(
                    employee: $this->employee,
                    type: $attendanceType,
                    workType: WorkType::tryFrom($this->workType),
                    locationId: $this->locationId,
                    projectId: $this->projectId,
                    latitude: (float) $this->latitude,
                    longitude: (float) $this->longitude,
                    accuracy: $this->accuracy !== null ? (float) $this->accuracy : null,
                    addressSnapshot: $this->addressSnapshot,
                    selfie: $this->selfie,
                    selfieSource: SelfieSource::from($this->selfieSource),
                );
            }
        } catch (ValidationException $e) {
            // Tutup modal supaya pesan error terlihat di formulir.
            Flux::modal('confirm-attendance')->close();

            throw $e;
        }

        $this->reset('latitude', 'longitude', 'accuracy', 'addressSnapshot', 'selfie', 'selfieSource');
        unset($this->openSession, $this->history, $this->overtimeRequest);

        Flux::modal('confirm-attendance')->close();

        Flux::toast(
            variant: 'success',
            text: match ($attendanceType) { AttendanceType::ClockIn => 'Absensi masuk tercatat.', AttendanceType::OvertimeStart => 'Foto mulai lembur berhasil dikonfirmasi.', default => 'Absensi pulang tercatat.' },
        );
    }
}; ?>

<section class="w-full" wire:poll.30s>
    <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
        {{-- Header pegawai: avatar fallback, identitas, dan pintasan notifikasi --}}
        <header class="flex items-center gap-3">
            <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" aria-hidden="true">
                {{ str($this->employee->user->name)->squish()->explode(' ')->take(2)->map(fn ($part) => str($part)->substr(0, 1)->upper())->implode('') }}
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->employee->user->name }}</h1>
                <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->employee->employee_number ?: __('Kode pegawai belum diatur') }}
                    <span aria-hidden="true">·</span>
                    {{ $this->employee->title?->name ?? __('Jabatan belum diatur') }}
                </p>
            </div>
            <button type="button" disabled aria-label="{{ __('Notifikasi akan tersedia') }}" title="{{ __('Notifikasi akan tersedia') }}" class="flex size-10 shrink-0 items-center justify-center rounded-full text-zinc-400 disabled:cursor-not-allowed dark:text-zinc-500">
                <flux:icon name="bell" variant="outline" class="size-5" />
            </button>
        </header>


        <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ Carbon::parse($selectedDate)->translatedFormat('F Y') }}</flux:heading>
                    <flux:text class="text-sm">{{ __('Pilih tanggal untuk melihat riwayat.') }}</flux:text>
                </div>
                <div class="flex gap-1">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftWeek(-1)" aria-label="{{ __('Minggu sebelumnya') }}" />
                    <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftWeek(1)" aria-label="{{ __('Minggu berikutnya') }}" />
                    <flux:button size="sm" variant="ghost" wire:click="selectDate('{{ now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() }}')">{{ __('Hari ini') }}</flux:button>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                @foreach ($this->weekDays as $day)
                    <button type="button" wire:click="selectDate('{{ $day['date'] }}')" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1 py-2.5 text-sm transition {{ $selectedDate === $day['date'] ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                        <span class="text-xs {{ $selectedDate === $day['date'] ? 'opacity-80' : 'text-zinc-500' }}">{{ $day['label'] }}</span>
                        <span class="flex size-8 items-center justify-center rounded-full {{ $day['today'] && $selectedDate !== $day['date'] ? 'ring-1 ring-zinc-400' : '' }} font-semibold">{{ $day['day'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        @php
            $session = $this->openSession;
            $mode = $session ? (($this->overtimeRequest && $this->overtimeRequest->start_record_id === null && $this->overtimeRequest->scheduled_start_at->lte(now()) && $this->overtimeRequest->state !== OvertimeState::NeedsReview) ? 'overtime_start' : 'clock_out') : 'clock_in';
        @endphp

        {{-- Ringkasan waktu kerja --}}
        @php
            $clockInRecord = $this->history->firstWhere('type', AttendanceType::ClockIn);
            $clockOutRecord = $this->history->where('type', AttendanceType::ClockOut)->last();
            $workedMinutes = ($clockInRecord && $clockOutRecord)
                ? max(0, (int) $clockInRecord->recorded_at->diffInMinutes($clockOutRecord->recorded_at))
                : (($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() && $session)
                    ? max(0, (int) $session->recorded_at->diffInMinutes(now(RecordAttendance::DEFAULT_TIMEZONE)))
                    : null);
            $attendanceStatus = $clockInRecord ? __('Hadir') : __('Belum absen');
            $attendanceStatusColor = $clockInRecord ? 'green' : 'zinc';
            $attendancePlace = $clockInRecord?->project?->name
                ?? $clockInRecord?->location?->name
                ?? $session?->project?->name
                ?? $session?->location?->name;
            $attendanceAddress = $clockInRecord?->address_snapshot
                ?? ($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? $session?->address_snapshot : null);
            $overtimeForDate = $this->selectedDateOvertimeRequest;
            $overtimeStatus = $overtimeForDate
                ? ($overtimeForDate->state === OvertimeState::InProgress
                    ? 'Berjalan'
                    : ($overtimeForDate->state === OvertimeState::Completed
                        ? 'Selesai'
                        : $overtimeForDate->approval_status->label()))
                : __('Tidak mengajukan');
            $overtimeStatusColor = match (true) {
                ! $overtimeForDate => 'zinc',
                $overtimeForDate->approval_status === \App\Enums\OvertimeApprovalStatus::Rejected => 'red',
                $overtimeForDate->state === OvertimeState::InProgress => 'green',
                $overtimeForDate->state === OvertimeState::Completed => 'blue',
                $overtimeForDate->approval_status === \App\Enums\OvertimeApprovalStatus::Approved => 'green',
                default => 'amber',
            };
        @endphp

        {{-- Ringkasan jam --}}
        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                <flux:text class="text-xs">{{ __('Masuk') }}</flux:text>
                <flux:heading size="lg">{{ $clockInRecord?->recordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
            </div>
            <div class="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                <flux:text class="text-xs">{{ __('Pulang') }}</flux:text>
                <flux:heading size="lg">{{ $clockOutRecord?->recordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
            </div>
            <div class="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                <flux:text class="text-xs">{{ __('Total kerja') }}</flux:text>
                <flux:heading size="sm">
                    @if ($workedMinutes !== null)
                        {{ intdiv($workedMinutes, 60) }}j {{ $workedMinutes % 60 }}m
                    @else
                        —
                    @endif
                </flux:heading>
            </div>
        </div>

        <div class="flex flex-wrap items-stretch gap-3">
            <x-attendance.info-card class="min-w-0 flex-1 basis-64"
                :title="__('Status kehadiran')"
                :status="$attendanceStatus"
                :status-color="$attendanceStatusColor"
            >
                <p>{{ Carbon::parse($selectedDate)->translatedFormat('d M Y') }}</p>
                @if ($attendancePlace)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $attendancePlace }}</p>
                @else
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lokasi belum tercatat') }}</p>
                @endif
                @if ($attendanceAddress)
                    <p class="mt-1 break-words text-sm text-zinc-500 dark:text-zinc-400">{{ $attendanceAddress }}</p>
                @endif
                @if ($this->history->isNotEmpty())
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Masuk') }}: {{ $clockInRecord?->recordedAtLocal()->format('H:i') ?? '—' }}
                        <span aria-hidden="true">·</span>
                        {{ __('Pulang') }}: {{ $clockOutRecord?->recordedAtLocal()->format('H:i') ?? '—' }}
                    </p>
                @endif
            </x-attendance.info-card>

            <x-attendance.info-card class="min-w-0 flex-1 basis-64"
                :title="__('Lembur')"
                :status="$overtimeStatus"
                :status-color="$overtimeStatusColor"
            >
                @if ($overtimeForDate)
                    <p>{{ __('Jadwal') }}: {{ $overtimeForDate->scheduledStartLocal()->format('d M Y, H:i') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $overtimeForDate->approval_status->label() }}</p>
                    @if ($overtimeForDate->isAwaitingPhoto())
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ __('Waktu lembur telah tiba. Ambil foto mulai lembur sebelum absen pulang.') }}</p>
                    @elseif ($overtimeForDate->isInProgress())
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lembur sedang berjalan.') }}</p>
                    @endif
                    <a href="{{ route('overtime-requests.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 underline underline-offset-4 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        {{ __('Detail lembur') }} <span aria-hidden="true">↗</span>
                    </a>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengajuan lembur untuk tanggal ini.') }}</p>
                    @if ($session && $selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString())
                        <flux:modal.trigger name="request-overtime">
                            <button type="button" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 underline underline-offset-4 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('Ajukan lembur') }} <span aria-hidden="true">↗</span>
                            </button>
                        </flux:modal.trigger>
                    @elseif ($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString())
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Absen masuk terlebih dahulu untuk mengajukan lembur.') }}</p>
                    @endif
                @endif
            </x-attendance.info-card>
        </div>

        {{-- Form absensi --}}
        <form wire:submit="confirm('{{ $mode }}')" class="flex flex-col gap-5 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700 {{ $selectedDate !== now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? 'hidden' : '' }}">
            @if ($mode === 'clock_in')
                <flux:select wire:model.live="workType" :label="__('Jenis lokasi kerja')">
                    <flux:select.option value="office">{{ __('Kantor') }}</flux:select.option>
                    <flux:select.option value="onsite">{{ __('Onsite (Proyek)') }}</flux:select.option>
                </flux:select>

                @if ($workType === 'office')
                    @if ($this->officeLocations->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada lokasi kantor aktif. Hubungi admin.') }}</flux:text>
                    @else
                        <flux:select wire:model="locationId" :label="__('Kantor')">
                            @foreach ($this->officeLocations as $office)
                                <flux:select.option value="{{ $office->id }}">{{ $office->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    @error('locationId') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @else
                    @if ($this->projects->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada proyek aktif yang ditugaskan kepada Anda. Hubungi admin.') }}</flux:text>
                    @else
                        <flux:select wire:model="projectId" :label="__('Proyek')">
                            @foreach ($this->projects as $project)
                                <flux:select.option value="{{ $project->id }}">{{ $project->code }} &ndash; {{ $project->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    @error('projectId') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @endif
            @elseif ($mode === 'overtime_start')
                <flux:text>{{ __('Konfirmasi mulai lembur memerlukan foto dan lokasi terbaru. Sesi kerja utama tetap berjalan.') }}</flux:text>
            @endif

            {{-- Kamera dibuat sebagai overlay rasio tetap; hasil capture dipreview sebelum diunggah. --}}
            <div
                x-data="attendanceCamera()"
                data-attendance-name="{{ Auth::user()->name }}"
                data-attendance-event="{{ $mode === 'overtime_start' ? 'Lembur' : ($mode === 'clock_in' ? 'Clock In' : 'Clock Out') }}"
                x-on:livewire:navigating.window="stop()"
                x-on:attendance-camera-open.window="start()"
                class="flex flex-col gap-2"
            >
                @if ($selfie && $selfie->isPreviewable())
                    <img src="{{ $selfie->temporaryUrl() }}" alt="Pratinjau foto absensi" class="w-full max-w-xs rounded-lg object-cover" />
                    <div>
                        <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
                    </div>
                @endif

                <div
                    x-show="active"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-transparent"
                    aria-label="{{ __('Kamera absensi') }}"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="relative isolate overflow-hidden rounded-[1.75rem] bg-zinc-900 shadow-2xl ring-1 ring-white/20"
                        :class="isLandscape ? 'attendance-camera-landscape' : 'attendance-camera-portrait'"
                        :style="viewportStyle"
                    >
                        <video
                            x-ref="video"
                            x-show="! capturedUrl"
                            autoplay
                            playsinline
                            muted
                            class="absolute inset-0 h-full w-full object-cover [transform:scaleX(-1)]"
                        ></video>

                        <img
                            x-show="capturedUrl"
                            x-cloak
                            :src="capturedUrl"
                            alt="{{ __('Pratinjau akhir foto absensi') }}"
                            class="absolute inset-0 h-full w-full object-cover"
                        />

                        <div
                            x-show="! capturedUrl"
                            class="pointer-events-none absolute inset-x-2 bottom-16 max-h-[32%] overflow-hidden rounded-xl border border-white/40 bg-white/75 p-2.5 text-zinc-900 shadow-sm backdrop-blur-md sm:inset-x-3 sm:bottom-20 sm:p-3"
                        >
                            <p class="truncate text-xs font-semibold sm:text-sm" x-text="name"></p>
                            <p class="text-[11px] font-medium sm:text-xs" x-text="eventLabel"></p>
                            <p class="mt-0.5 text-[10px] leading-snug sm:text-xs" x-text="formattedTimestamp || currentTimestamp"></p>
                            <p class="mt-0.5 line-clamp-2 break-words text-[10px] leading-snug sm:text-xs" x-text="address || (addressLoading ? 'Mencari alamat…' : 'Alamat belum tersedia')"></p>
                        </div>

                        <button
                            type="button"
                            x-show="canSwitchCamera && ! capturedUrl"
                            x-cloak
                            x-on:click="switchCamera()"
                            x-bind:disabled="busy || starting"
                            class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-full bg-transparent text-white shadow-sm transition hover:bg-black/20 disabled:opacity-50"
                            aria-label="{{ __('Ganti kamera depan/belakang') }}"
                            title="{{ __('Ganti kamera') }}"
                        >
                            <flux:icon name="arrow-path" variant="outline" class="size-5" />
                        </button>

                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-5 px-4 pb-4 pt-2 sm:gap-7 sm:pb-5">
                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="remeasureLocation()"
                                x-bind:disabled="busy || starting"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50 sm:size-11"
                                aria-label="{{ __('Refresh lokasi') }}"
                                title="{{ __('Refresh lokasi') }}"
                            >
                                <flux:icon name="map-pin" variant="outline" class="size-5 sm:size-6" />
                            </button>

                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="capture()"
                                x-bind:disabled="busy || starting || ! best"
                                class="flex size-16 shrink-0 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 transition active:scale-95 disabled:opacity-50 sm:size-[4.5rem]"
                                aria-label="{{ __('Ambil foto') }}"
                                title="{{ __('Ambil foto') }}"
                            >
                                <flux:icon name="camera" variant="outline" class="size-7 sm:size-8" />
                            </button>

                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="stop()"
                                x-bind:disabled="busy"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50 sm:size-11"
                                aria-label="{{ __('Tutup kamera') }}"
                                title="{{ __('Tutup kamera') }}"
                            >
                                <flux:icon name="x" variant="outline" class="size-5 sm:size-6" />
                            </button>

                            <button
                                type="button"
                                x-show="capturedUrl"
                                x-on:click="retake()"
                                x-bind:disabled="busy"
                                class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 disabled:opacity-50"
                                aria-label="{{ __('Foto ulang') }}"
                                title="{{ __('Foto ulang') }}"
                            >
                                <flux:icon name="arrow-left" variant="outline" class="size-5" />
                            </button>
                            <button
                                type="button"
                                x-show="capturedUrl"
                                x-on:click="useCapturedPhoto()"
                                x-bind:disabled="busy || ! capturedBlob"
                                class="flex size-16 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 disabled:opacity-50"
                                aria-label="{{ __('Gunakan foto ini') }}"
                                title="{{ __('Gunakan foto ini') }}"
                            >
                                <flux:icon name="check" variant="outline" class="size-7" />
                            </button>
                            <button
                                type="button"
                                x-show="capturedUrl"
                                x-on:click="stop()"
                                x-bind:disabled="busy"
                                class="flex size-11 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50"
                                aria-label="{{ __('Tutup kamera') }}"
                                title="{{ __('Tutup kamera') }}"
                            >
                                <flux:icon name="x" variant="outline" class="size-5" />
                            </button>
                        </div>

                        <div x-show="busy" x-cloak class="absolute inset-0 flex items-center justify-center bg-black/35 text-sm font-medium text-white">
                            {{ __('Memproses foto…') }}
                        </div>
                    </div>
                </div>

                <div x-show="fallback && ! active" x-cloak class="rounded-xl bg-zinc-100 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <p>{{ __('Kamera tidak tersedia. Pastikan izin kamera diberikan dan halaman dibuka melalui HTTPS atau localhost.') }}</p>
                    <p x-show="error" x-text="error" class="mt-1 text-red-600 dark:text-red-400"></p>
                </div>

                <flux:text x-show="$wire.latitude !== null" x-cloak class="text-sm text-green-600 dark:text-green-400">
                    {{ __('Lokasi terambil') }} &middot; {{ __('akurasi') }} &plusmn;<span x-text="Math.round($wire.accuracy ?? 0)"></span> m
                </flux:text>

                <flux:text x-show="error && ! active" x-cloak x-text="error" class="text-sm text-red-500"></flux:text>
                @error('selfie') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @error('latitude') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            </div>

                        @error('form') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            @error('workType') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror

            <div class="flex items-center gap-2">
                <flux:button
                    type="submit"
                    variant="primary"
                    class="min-w-0 flex-1"
                    wire:loading.attr="disabled"
                    wire:target="confirm,submit,selfie"
                >
                    {{ match ($mode) { 'clock_in' => __('Absen Masuk'), 'overtime_start' => __('Absen Lembur'), default => __('Absen Pulang') } }}
                </flux:button>
                <flux:button
                    type="button"
                    variant="ghost"
                    icon="camera"
                    square
                    aria-label="{{ __('Buka kamera') }}"
                    title="{{ __('Buka kamera') }}"
                    x-on:click="$dispatch('attendance-camera-open')"
                />
            </div>
        </form>

        {{-- Modal konfirmasi sebelum absensi dikirim --}}
        @php
            $confirmWork = $session
                ? $session->work_type->label()
                : \App\Enums\WorkType::tryFrom($workType)?->label();

            $confirmPlace = match (true) {
                (bool) $session => $session->project?->name ?? $session->location?->name,
                $workType === 'onsite' => $this->projects->firstWhere('id', (int) $projectId)?->name,
                default => $this->officeLocations->firstWhere('id', (int) $locationId)?->name,
            };
        @endphp
        <flux:modal name="confirm-attendance" class="max-w-md">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">
                        {{ match ($mode) { 'clock_in' => __('Konfirmasi Absen Masuk'), 'overtime_start' => __('Konfirmasi Absen Lembur'), default => __('Konfirmasi Absen Pulang') } }}
                    </flux:heading>
                    <flux:subheading>{{ __('Periksa data berikut sebelum mengirim absensi.') }}</flux:subheading>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Jenis absensi') }}</dt>
                        <dd class="font-medium">{{ match ($mode) { 'clock_in' => __('Masuk'), 'overtime_start' => __('Mulai lembur'), default => __('Pulang') } }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Lokasi kerja') }}</dt>
                        <dd class="font-medium">{{ $confirmWork ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Tempat / proyek') }}</dt>
                        <dd class="text-right font-medium">{{ $confirmPlace ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Akurasi GPS') }}</dt>
                        <dd class="font-medium">
                            @if ($accuracy !== null) &plusmn;{{ round((float) $accuracy) }} m @else — @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Sumber foto') }}</dt>
                        <dd class="font-medium">{{ $selfieSource === 'upload' ? __('Unggah manual') : __('Kamera') }}</dd>
                    </div>
                </dl>

                @if ($selfie && $selfie->isPreviewable())
                    <img src="{{ $selfie->temporaryUrl() }}" alt="Pratinjau selfie" class="h-32 w-32 rounded-lg object-cover" />
                @endif

                <flux:text class="text-sm">
                    {{ __('Waktu absensi dicatat otomatis oleh sistem saat Anda menekan tombol konfirmasi.') }}
                </flux:text>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                    </flux:modal.close>

                    <flux:button
                        variant="primary"
                        wire:click="submit('{{ $mode }}')"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        {{ match ($mode) { 'clock_in' => __('Ya, Absen Masuk'), 'overtime_start' => __('Ya, Absen Lembur'), default => __('Ya, Absen Pulang') } }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
        <flux:modal name="request-overtime" class="max-w-lg">
            <form wire:submit="requestOvertime" class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">{{ __('Ajukan lembur') }}</flux:heading>
                    <flux:subheading>{{ __('Tentukan waktu mulai dan alasan. Jika jadwal tiba sebelum admin menyetujui, Anda tetap harus mengambil foto konfirmasi lembur.') }}</flux:subheading>
                </div>
                <flux:field>
                    <flux:label>{{ __('Waktu mulai lembur') }}</flux:label>
                    <flux:input type="datetime-local" wire:model="overtimeStartAt" required />
                    <flux:error name="overtimeStartAt" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Alasan lembur') }}</flux:label>
                    <flux:textarea wire:model="overtimeReason" rows="3" required placeholder="{{ __('Pekerjaan yang perlu diselesaikan...') }}" />
                    <flux:error name="overtimeReason" />
                </flux:field>
                @error('form') <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text> @enderror
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">{{ __('Batal') }}</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="requestOvertime">{{ __('Kirim pengajuan') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</section>
