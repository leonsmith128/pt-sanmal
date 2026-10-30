<?php

use App\Actions\ConfirmOvertimeStart;
use App\Actions\CreateOvertimeRequest;
use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\OvertimeState;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use App\Services\DailyAttendanceStatusService;
use App\Models\AttendanceRecord;
use App\Models\OvertimeRequest;
use App\Support\AttendanceEvidence;
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
            'overtimeReason' => ['nullable', 'string', 'max:2000'],
            'overtimeStartAt' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);

        app(CreateOvertimeRequest::class)->handle(
            employee: $this->employee,
            reason: $validated['overtimeReason'] ?: null,
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
        $dayStart = Carbon::createFromFormat('!Y-m-d', $this->selectedDate, RecordAttendance::DEFAULT_TIMEZONE)->utc();
        $dayEnd = $dayStart->copy()->addDay();

        return $this->employee->overtimeRequests()
            ->where('scheduled_start_at', '>=', $dayStart)
            ->where('scheduled_start_at', '<', $dayEnd)
            ->whereNotIn('state', [OvertimeState::Cancelled, OvertimeState::Completed])
            ->latest('scheduled_start_at')
            ->first();
    }

    #[Computed]
    public function history()
    {
        $events = app(DailyAttendanceStatusService::class)
            ->forEmployeeDate($this->employee, $this->selectedDate)['events'];

        $events->each(fn (AttendanceRecord $record) => $record->load(['location', 'project', 'approvedCorrection']));

        return $events->take(50);
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
            ...AttendanceEvidence::rules([
                'latitude'=>'latitude','longitude'=>'longitude','accuracy'=>'accuracy',
                'address'=>'addressSnapshot','selfie'=>'selfie',
            ]),
            'selfieSource' => ['required', Rule::enum(SelfieSource::class)],
        ];

        if ($type === AttendanceType::ClockIn) {
            $rules['workType'] = ['required', Rule::enum(WorkType::class)];
        }

        $this->validate($rules, [
            'latitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'longitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'accuracy.required' => 'Akurasi GPS belum tersedia. Tunggu hingga GPS siap lalu ambil foto.',
            ...AttendanceEvidence::messages('accuracy', 'selfie'),
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
            $mode = $session ? (($this->overtimeRequest && $this->overtimeRequest->start_record_id === null && $this->overtimeRequest->state !== OvertimeState::NeedsReview) ? ($this->overtimeRequest->scheduled_start_at->lte(now()) ? 'overtime_start' : 'overtime_locked') : 'clock_out') : 'clock_in';
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
            $attendancePlace = $clockInForDisplay?->project?->name
                ?? $clockInForDisplay?->location?->name
                ?? $session?->project?->name
                ?? $session?->location?->name;
            $attendanceAddress = $this->history->first()?->address_snapshot
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
                <flux:heading size="lg">{{ $clockInForDisplay?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
            </div>
            <div class="rounded-xl border border-neutral-200 p-3 dark:border-neutral-700">
                <flux:text class="text-xs">{{ __('Pulang') }}</flux:text>
                <flux:heading size="lg">{{ $clockOutRecord?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
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
                    <p>{{ __('Mulai terjadwal') }}: {{ $overtimeForDate->scheduledStartLocal()->format('d M Y, H:i') }}</p>
                    <flux:badge class="mt-2" :color="$overtimeForDate->approval_status->badgeColor()">{{ $overtimeForDate->approval_status->label() }}</flux:badge>
                    @if ($overtimeForDate->start_record_id === null && $overtimeForDate->scheduled_start_at->lte(now()) && $overtimeForDate->state !== OvertimeState::NeedsReview)
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ __('Waktu lembur telah tiba. Ambil foto mulai lembur sebelum absen pulang.') }}</p>
                    @elseif ($overtimeForDate->isInProgress())
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lembur sedang berjalan.') }}</p>
                    @endif
                    @if (in_array($overtimeForDate->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true) && $overtimeForDate->start_record_id === null)
                        <form method="POST" action="{{ route('overtime-requests.cancel', $overtimeForDate) }}" class="mt-3" onsubmit="return confirm('Batalkan jadwal lembur ini? Anda harus membatalkannya sebelum dapat absen pulang.')">
                            @csrf @method('PATCH')
                            <flux:button type="submit" variant="primary" color="red" size="sm" icon="x-circle">{{ __('Batalkan Lembur') }}</flux:button>
                        </form>
                    @endif
                    <a href="{{ route('overtime-requests.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 underline underline-offset-4 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        {{ __('Detail lembur') }} <span aria-hidden="true">↗</span>
                    </a>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengajuan lembur untuk tanggal ini.') }}</p>
                    @if ($selectedDate >= now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString())
                        <flux:modal.trigger name="request-overtime">
                            <button type="button" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 underline underline-offset-4 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('Ajukan lembur') }} <span aria-hidden="true">↗</span>
                            </button>
                        </flux:modal.trigger>
                    @endif
                @endif
            </x-attendance.info-card>
        </div>

        {{-- Form absensi --}}
        <form wire:submit="confirm('{{ $mode }}')" class="flex flex-col gap-5 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700 {{ $selectedDate !== now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? 'hidden' : '' }}">
            @if ($mode === 'overtime_locked')
                <flux:callout variant="warning" icon="lock-closed">
                    <flux:callout.heading>{{ __('Clock-out terkunci') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Anda memiliki jadwal lembur aktif. Batalkan Lembur terlebih dahulu jika tidak jadi lembur, atau tunggu jadwal mulai untuk melakukan konfirmasi.') }}</flux:callout.text>
                </flux:callout>
            @elseif ($mode === 'clock_in')
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

            <x-attendance.camera
                :name="Auth::user()->name"
                :event-label="$mode === 'overtime_start' ? 'Lembur' : ($mode === 'clock_in' ? 'Clock In' : 'Clock Out')"
                :selfie="$selfie"
            />

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
                    <flux:subheading>{{ __('Pengajuan dapat dibuat sebelum clock-in. Tentukan waktu mulai; alasan bersifat opsional. Waktu selesai dicatat dari clock-out aktual. Jika jadwal tiba sebelum admin menyetujui, Anda tetap harus mengambil foto konfirmasi lembur.') }}</flux:subheading>
                </div>
                <flux:field>
                    <flux:label>{{ __('Waktu mulai lembur') }}</flux:label>
                    <flux:input type="datetime-local" wire:model="overtimeStartAt" required />
                    <flux:error name="overtimeStartAt" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Alasan lembur (opsional)') }}</flux:label>
                    <flux:textarea wire:model="overtimeReason" rows="3" placeholder="{{ __('Jika diperlukan, jelaskan pekerjaan yang perlu diselesaikan...') }}" />
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
