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
    public $capturedAt = null;
    public $selfie = null;
    public string $selfieSource = 'camera';
    public string $selectedDate = '';
    public string $overtimeReason = '';
    public string $overtimeStartAt = '';
    public ?int $selectedOvertimeId = null;

    public function showOvertimeDetails(int $overtimeId): void
    {
        abort_unless($this->selectedDateOvertimeRequests->contains('id', $overtimeId), 404);

        $this->selectedOvertimeId = $overtimeId;
        Flux::modal('overtime-details')->show();
    }

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
    public function hasClockInToday(): bool
    {
        return RecordAttendance::hasClockInOnDate(
            $this->employee,
            now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString(),
        );
    }

    #[Computed]
    public function officeLocations()
    {
        return RecordAttendance::officeLocations()->get();
    }

    #[Computed]
    public function projects()
    {
        return RecordAttendance::availableForAttendance()->get();
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
        unset($this->history, $this->selectedDateOvertimeRequests);
    }

    public function shiftWeek(int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $current = Carbon::parse($this->selectedDate ?: now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString());
        $this->selectedDate = $current->addWeeks($direction)->startOfWeek(Carbon::MONDAY)->toDateString();
        unset($this->weekDays, $this->history, $this->selectedDateOvertimeRequests);
    }

    public function requestOvertime(): void
    {
        $validated = $this->validate([
            'overtimeReason' => ['nullable', 'string', 'max:2000'],
            'overtimeStartAt' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);

        try {
            app(CreateOvertimeRequest::class)->handle(
                employee: $this->employee,
                reason: $validated['overtimeReason'] ?: null,
                scheduledStartLocal: $validated['overtimeStartAt'],
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $componentField = $field === 'scheduled_start_at' ? 'overtimeStartAt' : $field;

                foreach ($messages as $message) {
                    $this->addError($componentField, $message);
                }
            }

            return;
        }

        $this->reset('overtimeReason', 'overtimeStartAt');
        unset($this->overtimeRequest, $this->selectedDateOvertimeRequests);
        Flux::modal('request-overtime')->close();
        Flux::toast(variant: 'success', text: 'Pengajuan lembur berhasil disimpan.');
    }

    #[Computed]
    public function selectedDateOvertimeRequests()
    {
        $dayStart = Carbon::createFromFormat('!Y-m-d', $this->selectedDate, RecordAttendance::DEFAULT_TIMEZONE)->utc();
        $dayEnd = $dayStart->copy()->addDay();

        return $this->employee->overtimeRequests()
            ->where(function ($query) use ($dayStart, $dayEnd): void {
                $query->whereDate('session_date', $this->selectedDate)
                    ->orWhere(function ($legacy) use ($dayStart, $dayEnd): void {
                        $legacy->whereNull('session_date')
                            ->where('scheduled_start_at', '>=', $dayStart)
                            ->where('scheduled_start_at', '<', $dayEnd);
                    });
            })
            ->with(['startRecord.approvedCorrection', 'clockOutRecord.approvedCorrection'])
            ->orderBy('scheduled_start_at')
            ->orderBy('id')
            ->get();
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
        $this->reset('selfie', 'selfieSource', 'latitude', 'longitude', 'accuracy', 'addressSnapshot', 'capturedAt');
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
            'capturedAt' => ['nullable', 'date'],
        ];

        if ($type === AttendanceType::ClockIn) {
            $rules['workType'] = ['required', Rule::enum(WorkType::class)];
        }

        $this->validate($rules, [
            ...AttendanceEvidence::messages('accuracy', 'selfie'),
            'selfie.required' => 'Foto belum diambil.',
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
                    latitude: $this->latitude !== null ? (float) $this->latitude : null,
                    longitude: $this->longitude !== null ? (float) $this->longitude : null,
                    accuracy: $this->accuracy !== null ? (float) $this->accuracy : null,
                    addressSnapshot: $this->addressSnapshot,
                    selfie: $this->selfie,
                    selfieSource: SelfieSource::from($this->selfieSource),
                    capturedAt: $this->capturedAt ? Carbon::parse($this->capturedAt)->utc() : null,
                );
            } else {
                app(RecordAttendance::class)->handle(
                    employee: $this->employee,
                    type: $attendanceType,
                    workType: WorkType::tryFrom($this->workType),
                    locationId: $this->locationId,
                    projectId: $this->projectId,
                    latitude: $this->latitude !== null ? (float) $this->latitude : null,
                    longitude: $this->longitude !== null ? (float) $this->longitude : null,
                    accuracy: $this->accuracy !== null ? (float) $this->accuracy : null,
                    addressSnapshot: $this->addressSnapshot,
                    selfie: $this->selfie,
                    selfieSource: SelfieSource::from($this->selfieSource),
                    capturedAt: $this->capturedAt ? Carbon::parse($this->capturedAt)->utc() : null,
                );
            }
        } catch (ValidationException $e) {
            // Tutup modal supaya pesan error terlihat di formulir.
            Flux::modal('confirm-attendance')->close();

            throw $e;
        }

        $this->reset('latitude', 'longitude', 'accuracy', 'addressSnapshot', 'capturedAt', 'selfie', 'selfieSource');
        unset($this->openSession, $this->history, $this->overtimeRequest, $this->selectedDateOvertimeRequests, $this->hasClockInToday);

        Flux::modal('confirm-attendance')->close();

        Flux::toast(
            variant: 'success',
            text: match ($attendanceType) { AttendanceType::ClockIn => 'Absensi masuk tercatat.', AttendanceType::OvertimeStart => 'Foto mulai lembur berhasil dikonfirmasi.', default => 'Absensi pulang tercatat.' },
        );
    }
}; ?>

<section class="w-full" wire:poll.30s>
    <x-ui.page gap="6">
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
            @php
                $unreadCount = Auth::user()->unreadNotifications()->count();
            @endphp
                    
            <a href="{{ route('notifications.index') }}" wire:navigate aria-label="{{ __('Buka notifikasi') }}" title="{{ __('Notifikasi') }}" class="relative flex size-10 shrink-0 items-center justify-center rounded-full text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <flux:icon name="bell" variant="outline" class="size-5" />
                @if ($unreadCount > 0)
                    <span class="absolute right-0.5 top-0.5 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </a>
        </header>

        @php
            $session = $this->openSession;
            $sessionOvertime = $this->overtimeRequest;
            $waitingForOvertimeStart = $sessionOvertime
                && in_array($sessionOvertime->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true)
                && $sessionOvertime->start_record_id === null;
            $mode = $session
                ? ($waitingForOvertimeStart
                    ? ($sessionOvertime->scheduled_start_at->isFuture() ? 'overtime_locked' : 'overtime_start')
                    : 'clock_out')
                : ($this->hasClockInToday ? 'clock_in_blocked' : 'clock_in');
        @endphp

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ Carbon::parse($selectedDate)->translatedFormat('F Y') }}</flux:heading>
                    <flux:text class="text-sm">{{ __('Pilih tanggal untuk melihat riwayat.') }}</flux:text>
                </div>
                <div class="flex gap-1">
                    <x-ui.button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftWeek(-1)" aria-label="{{ __('Minggu sebelumnya') }}" />
                    <x-ui.button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftWeek(1)" aria-label="{{ __('Minggu berikutnya') }}" />
                    <x-ui.button size="sm" variant="ghost" wire:click="selectDate('{{ now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() }}')">{{ __('Hari ini') }}</x-ui.button>
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

        {{-- Ringkasan waktu kerja --}}
        @php
            $clockInRecord = $this->history->firstWhere('type', AttendanceType::ClockIn);
            $clockOutRecord = $this->history->where('type', AttendanceType::ClockOut)->last();
            $multipleDailySessions = $this->history->where('type', AttendanceType::ClockIn)->count() > 1
                || $this->history->where('type', AttendanceType::ClockOut)->count() > 1;
            $clockInForDisplay = $clockInRecord
                ?? ($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? $session : null);
            $workedMinutes = $multipleDailySessions
                ? null
                : (($clockInForDisplay && $clockOutRecord)
                    ? max(0, (int) $clockInForDisplay->effectiveRecordedAt()->diffInMinutes($clockOutRecord->effectiveRecordedAt()))
                    : (($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() && $session)
                        ? max(0, (int) $session->recorded_at->diffInMinutes(now(RecordAttendance::DEFAULT_TIMEZONE)))
                        : null));
            $attendanceStatus = $multipleDailySessions
                ? __('Perlu ditinjau')
                : ($clockInForDisplay ? __('Hadir') : __('Belum absen'));
            $attendanceStatusColor = $multipleDailySessions ? 'amber' : ($clockInForDisplay ? 'green' : 'zinc');
            $attendancePlace = $clockInForDisplay?->project?->name
                ?? $clockInForDisplay?->location?->name
                ?? $session?->project?->name
                ?? $session?->location?->name;
            $attendanceAddress = $clockInForDisplay?->address_snapshot
                ?? $this->history->first()?->address_snapshot
                ?? ($selectedDate === now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? $session?->address_snapshot : null);
            $overtimeRequestsForDate = $this->selectedDateOvertimeRequests;
            $overtimeForDate = $overtimeRequestsForDate->last();
            $overtimeStatus = $overtimeRequestsForDate->count() > 1
                ? __('Perlu ditinjau')
                : ($overtimeForDate?->state->label() ?? __('Tidak mengajukan'));
            $overtimeStatusColor = $overtimeRequestsForDate->count() > 1
                ? 'amber'
                : ($overtimeForDate?->state->badgeColor() ?? 'zinc');
        @endphp

        {{-- Ringkasan jam --}}
        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 text-center dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark">
                <flux:text class="text-xs">{{ __('Masuk') }}</flux:text>
                <flux:heading size="lg">{{ $clockInForDisplay?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
            </div>
            <div class="rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 text-center dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark">
                <flux:text class="text-xs">{{ __('Pulang') }}</flux:text>
                <flux:heading size="lg">{{ $clockOutRecord?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}</flux:heading>
            </div>
            <div class="rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 text-center dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark">
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

        <div class="grid grid-cols-1 items-stretch gap-3 sm:grid-cols-2">
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
                @if ($clockInForDisplay)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Masuk') }}: {{ $clockInForDisplay?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}
                        <span aria-hidden="true">·</span>
                        {{ __('Pulang') }}: {{ $clockOutRecord?->effectiveRecordedAtLocal()->format('H:i') ?? '—' }}
                    </p>
                @endif
            </x-attendance.info-card>

            <x-attendance.info-card class="min-w-0 flex-1 basis-64"
                :title="__('Lembur')"
                :status="$overtimeStatus"
                :status-color="$overtimeStatusColor"
            >
                @if ($overtimeRequestsForDate->count() > 1)
                    <p class="mb-3 text-sm text-amber-700 dark:text-amber-300">
                        {{ __('Ditemukan :count pengajuan lembur pada sesi ini. Seluruh riwayat ditampilkan.', ['count' => $overtimeRequestsForDate->count()]) }}
                    </p>
                @endif
                @forelse ($overtimeRequestsForDate as $overtimeItem)
                    <div @class(['border-b border-zinc-200 pb-3 dark:border-zinc-700' => ! $loop->last, 'pt-2' => ! $loop->first])>
                        <p>{{ __('Mulai terjadwal') }}: {{ $overtimeItem->scheduledStartLocal()->format('d M Y, H:i') }}</p>
                        @if ($overtimeItem->scheduledEndLocal())
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Selesai terjadwal (riwayat)') }}: {{ $overtimeItem->scheduledEndLocal()->format('d M Y, H:i') }}
                            </p>
                        @endif
                        <x-ui.badge class="mt-2" :color="$overtimeItem->state->badgeColor()">{{ $overtimeItem->state->label() }}</x-ui.badge>
                        <x-ui.badge class="mt-2" :color="$overtimeItem->approval_status->badgeColor()">{{ $overtimeItem->approval_status->label() }}</x-ui.badge>
                        @if ($overtimeItem->startRecord)
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Mulai aktual') }}: {{ $overtimeItem->startRecord->effectiveRecordedAtLocal()->format('d M Y, H:i') }}
                            </p>
                            @if ($session && $overtimeItem->id === $sessionOvertime?->id && $overtimeItem->isInProgress() && ! $overtimeItem->clockOutRecord)
                                @php
                                    $actualOvertimeSeconds = max(0, (int) $overtimeItem->startRecord->recorded_at->copy()->setTimezone($overtimeItem->timezone)->diffInSeconds(now($overtimeItem->timezone)));
                                @endphp
                                <p class="mt-1 text-sm font-medium text-blue-700 dark:text-blue-300">
                                    {{ __('Durasi aktual') }}: {{ intdiv($actualOvertimeSeconds, 3600) }}j {{ intdiv($actualOvertimeSeconds % 3600, 60) }}m
                                </p>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mulai aktual') }}: —</p>
                        @endif
                        @if ($overtimeItem->clockOutRecord)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Selesai aktual') }}: {{ $overtimeItem->clockOutRecord->effectiveRecordedAtLocal()->format('d M Y, H:i') }}
                            </p>
                        @elseif ($overtimeItem->isInProgress())
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lembur sedang berjalan.') }}</p>
                        @endif
                        @if ($overtimeItem->finalized_at)
                            <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                <p class="font-medium">{{ __('Waktu final admin') }}</p>
                                <p>{{ $overtimeItem->final_start_at?->copy()->setTimezone($overtimeItem->timezone)->format('d M Y, H:i') ?? '—' }}
                                    – {{ $overtimeItem->final_end_at?->copy()->setTimezone($overtimeItem->timezone)->format('d M Y, H:i') ?? '—' }}</p>
                                <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Sumber') }}: {{ $overtimeItem->finalization_source }}</p>
                                @if ($overtimeItem->finalization_reason)
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Alasan final admin') }}: {{ $overtimeItem->finalization_reason }}</p>
                                @endif
                            </div>
                        @endif
                        @if ($session && $overtimeItem->id === $sessionOvertime?->id && $overtimeItem->start_record_id === null && $overtimeItem->scheduled_start_at->lte(now()) && $overtimeItem->state !== OvertimeState::NeedsReview)
                            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ __('Waktu sejak jadwal mulai') }}: {{ intdiv(max(0, (int) $overtimeItem->scheduledStartLocal()->diffInSeconds(now($overtimeItem->timezone))), 3600) }}j {{ intdiv(max(0, (int) $overtimeItem->scheduledStartLocal()->diffInSeconds(now($overtimeItem->timezone))) % 3600, 60) }}m. {{ __('Segera konfirmasi lembur dengan foto.') }}</p>
                        @endif
                        @if (in_array($overtimeItem->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true) && $overtimeItem->start_record_id === null)
                            <form method="POST" action="{{ route('overtime-requests.cancel', $overtimeItem) }}" class="mt-3" onsubmit="return confirm('Batalkan jadwal lembur ini? Anda harus membatalkannya sebelum dapat absen pulang.')">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" variant="outline" color="red" size="sm" icon="x-circle">{{ __('Batalkan Lembur') }}</x-ui.button>
                            </form>
                        @endif
                        <button
                            type="button"
                            wire:click="showOvertimeDetails({{ $overtimeItem->id }})"
                            class="mt-3 inline-flex min-h-9 items-center gap-1 rounded-lg px-2 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40"
                        >
                            {{ __('Lihat detail') }}
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengajuan lembur untuk tanggal ini.') }}</p>
                @endforelse
                @if (
                    $selectedDate >= now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString()
                    && ! $overtimeForDate
                    && (! $session || ! $this->overtimeRequest)
                    && ($session || ! RecordAttendance::hasClockInOnDate($this->employee, $selectedDate))
                )
                    <flux:modal.trigger name="request-overtime">
                        <button type="button" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 underline underline-offset-4 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            {{ __('Ajukan lembur') }} <span aria-hidden="true">↗</span>
                        </button>
                    </flux:modal.trigger>
                @endif
            </x-attendance.info-card>
        </div>



        {{-- Form absensi --}}
        <form id="attendance-form" wire:submit="confirm('{{ $mode }}')" class="flex flex-col gap-5 rounded-xl border border-sanmal-border bg-sanmal-surface-card p-4 dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark sm:p-5 {{ $selectedDate !== now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString() ? 'hidden' : '' }}">
            <div class="space-y-1">
                <flux:heading size="sm">
                    {{ match ($mode) {
                        'clock_in' => __('Mulai absensi'),
                        'clock_out' => __('Selesaikan sesi kerja'),
                        'overtime_start' => __('Konfirmasi mulai lembur'),
                        'overtime_locked' => __('Jadwal lembur belum dimulai'),
                        'clock_in_blocked' => __('Absensi hari ini sudah digunakan'),
                        default => __('Absensi'),
                    } }}
                </flux:heading>
                <flux:text class="text-sm">
                    {{ match ($mode) {
                        'clock_in' => __('Pilih lokasi kerja, ambil foto, lalu konfirmasi absensi masuk.'),
                        'clock_out' => __('Ambil foto dan konfirmasi untuk mengakhiri sesi kerja.'),
                        'overtime_start' => __('Ambil foto untuk mencatat konfirmasi mulai lembur.'),
                        'overtime_locked' => __('Konfirmasi lembur tersedia setelah waktu jadwal dimulai.'),
                        'clock_in_blocked' => __('Sesi absensi untuk tanggal ini sudah digunakan.'),
                        default => __('Ikuti langkah berikut untuk melanjutkan absensi.'),
                    } }}
                </flux:text>
            </div>

            @if ($mode === 'clock_in_blocked')
                <flux:callout variant="info" icon="lock-closed">
                    <flux:callout.heading>{{ __('Absensi hari ini sudah digunakan') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Anda sudah melakukan Clock In pada tanggal ini. Sesi kedua tidak dapat dibuat setelah Clock Out.') }}
                    </flux:callout.text>
                </flux:callout>
            @elseif ($mode === 'overtime_locked')
                <flux:callout variant="warning" icon="lock-closed">
                    <flux:callout.heading>{{ __('Menunggu jadwal lembur') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Konfirmasi Lembur tersedia setelah jadwal mulai:') }}
                        <strong>{{ $sessionOvertime->scheduledStartLocal()->format('d M Y, H:i') }}</strong>.
                        {{ __('Tombol Clock Out tidak tersedia selama pengajuan ini masih aktif. Jika tidak jadi lembur, batalkan pengajuan terlebih dahulu.') }}
                    </flux:callout.text>
                </flux:callout>
            @elseif ($mode === 'clock_in')
                <x-ui.select name="workType" wire:model.live="workType" :label="__('Jenis lokasi kerja')">
                    <flux:select.option value="office">{{ __('Kantor') }}</flux:select.option>
                    <flux:select.option value="onsite">{{ __('Onsite (Proyek)') }}</flux:select.option>
                </x-ui.select>

                @if ($workType === 'office')
                    @if ($this->officeLocations->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada lokasi kantor aktif. Hubungi admin.') }}</flux:text>
                    @elseif ($this->officeLocations->count() === 1)
                        <div class="rounded-lg border border-sanmal-border bg-sanmal-surface-secondary p-3 text-sm dark:border-sanmal-border-dark dark:bg-sanmal-surface-secondary-dark">
                            <span class="font-medium">{{ __('Lokasi kantor') }}:</span>
                            {{ $this->officeLocations->first()->name }}
                        </div>
                    @else
                        <x-ui.select name="locationId" wire:model="locationId" :label="__('Kantor')">
                            @foreach ($this->officeLocations as $office)
                                <flux:select.option value="{{ $office->id }}">{{ $office->name }}</flux:select.option>
                            @endforeach
                        </x-ui.select>
                    @endif
                @else
                    @if ($this->projects->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada proyek aktif yang tersedia untuk absensi. Hubungi admin.') }}</flux:text>
                    @else
                        <x-ui.select name="projectId" wire:model.live="projectId" :label="__('Proyek')">
                            @foreach ($this->projects as $project)
                                <flux:select.option value="{{ $project->id }}">
                                    {{ $project->code }} — {{ $project->name }} ({{ $project->client->name }})
                                </flux:select.option>
                            @endforeach
                        </x-ui.select>
                        @if ($selectedProject = $this->projects->firstWhere('id', (int) $projectId))
                            <div class="rounded-lg border border-sanmal-border bg-sanmal-surface-secondary p-3 text-sm dark:border-sanmal-border-dark dark:bg-sanmal-surface-secondary-dark">
                                <p><span class="font-medium">{{ __('Client') }}:</span> {{ $selectedProject->client->name }}</p>
                                <p><span class="font-medium">{{ __('Lokasi Site') }}:</span> {{ $selectedProject->location->name }}</p>
                                <p><span class="font-medium">{{ __('Jenis layanan') }}:</span> {{ $selectedProject->project_type->label() }}</p>
                            </div>
                        @endif
                    @endif
                 @endif
            @elseif ($mode === 'overtime_start')
                <flux:text>{{ __('Konfirmasi mulai lembur memerlukan foto dan lokasi terbaru. Sesi kerja utama tetap berjalan.') }}</flux:text>
            @endif

            @if (! in_array($mode, ['overtime_locked', 'clock_in_blocked'], true))
                <x-attendance.camera
                    :name="Auth::user()->name"
                    :event-label="$mode === 'overtime_start' ? 'Lembur' : ($mode === 'clock_in' ? 'Clock In' : 'Clock Out')"
                />

                <x-attendance.photo-preview :selfie="$selfie" />
            @endif

                        @error('form') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            @error('workType') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror


                    <div class="flex w-full items-center gap-2 pt-1">
                        @if ($mode === 'overtime_locked')
                            <x-ui.button type="button" variant="primary" class="min-w-0 flex-1" disabled icon="lock-closed">
                                {{ __('Konfirmasi Lembur') }}
                            </x-ui.button>
                        @elseif ($mode === 'clock_in_blocked')
                            <x-ui.button type="button" variant="primary" class="min-w-0 flex-1" disabled icon="lock-closed">
                                {{ __('Sesi hari ini sudah digunakan') }}
                            </x-ui.button>
                        @else
                            @if ($selfie)
                                <x-ui.button
                                    type="submit"
                                    variant="primary"
                                    class="min-w-0 flex-1"
                                    wire:loading.attr="disabled"
                                    wire:target="confirm,submit,selfie"
                                >
                                    {{ __('Lanjutkan') }}
                                </x-ui.button>
                            @else
                                <x-ui.button
                                    type="button"
                                    variant="primary"
                                    class="min-w-0 flex-1"
                                    x-on:click="$dispatch('attendance-camera-open')"
                                >
                                    {{ match ($mode) { 'clock_in' => __('Absen Masuk'), 'overtime_start' => __('Konfirmasi Lembur'), default => __('Absen Pulang') } }}
                                </x-ui.button>
                            @endif
                        @endif
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
                        {{ match ($mode) { 'clock_in' => __('Konfirmasi Absen Masuk'), 'overtime_start' => __('Konfirmasi Lembur'), 'overtime_locked' => __('Konfirmasi Lembur'), default => __('Konfirmasi Absen Pulang') } }}
                    </flux:heading>
                    <flux:subheading>{{ __('Periksa data berikut sebelum mengirim absensi.') }}</flux:subheading>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Jenis absensi') }}</dt>
                        <dd class="font-medium">{{ match ($mode) { 'clock_in' => __('Masuk'), 'overtime_start' => __('Mulai lembur'), 'overtime_locked' => __('Mulai lembur'), default => __('Pulang') } }}</dd>
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
                    <div class="grid gap-1 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <dt class="text-zinc-500">{{ __('Alamat pada watermark dan bukti yang akan disimpan') }}</dt>
                        <dd class="break-words font-medium">{{ filled($addressSnapshot ?? null) ? $addressSnapshot : __('Alamat belum tersedia untuk koordinat ini.') }}</dd>
                        <p class="text-xs text-zinc-500">{{ __('Alamat yang ditampilkan di sini adalah snapshot yang sama dengan yang dikirim bersama foto.') }}</p>
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
                        <x-ui.button variant="outline">{{ __('Batal') }}</x-ui.button>
                    </flux:modal.close>

                    <x-ui.button
                        variant="primary"
                        wire:click="submit('{{ $mode }}')"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        {{ match ($mode) { 'clock_in' => __('Ya, Absen Masuk'), 'overtime_start' => __('Ya, Konfirmasi Lembur'), default => __('Ya, Absen Pulang') } }}
                    </x-ui.button>
                </div>
            </div>
        </flux:modal>
        @php
            $selectedOvertime = $overtimeRequestsForDate->firstWhere('id', $selectedOvertimeId);
        @endphp
        <flux:modal name="overtime-details" class="max-w-lg">
            @if ($selectedOvertime)
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">{{ __('Detail lembur') }}</flux:heading>
                        <flux:subheading>{{ __('Rincian pengajuan pada :date', ['date' => $selectedOvertime->scheduledStartLocal()->translatedFormat('d M Y')]) }}</flux:subheading>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge :color="$selectedOvertime->state->badgeColor()">{{ $selectedOvertime->state->label() }}</x-ui.badge>
                        <x-ui.badge :color="$selectedOvertime->approval_status->badgeColor()">{{ $selectedOvertime->approval_status->label() }}</x-ui.badge>
                    </div>
                    <dl class="grid gap-3 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800">
                        <div>
                            <dt class="text-zinc-500">{{ __('Mulai terjadwal') }}</dt>
                            <dd class="mt-1 font-medium">{{ $selectedOvertime->scheduledStartLocal()->format('d M Y, H:i T') }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">{{ __('Mulai aktual') }}</dt>
                            <dd class="mt-1 font-medium">{{ $selectedOvertime->startRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i T') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">{{ __('Selesai aktual') }}</dt>
                            <dd class="mt-1 font-medium">{{ $selectedOvertime->clockOutRecord?->effectiveRecordedAtLocal()->format('d M Y, H:i T') ?? '—' }}</dd>
                        </div>
                        @if ($selectedOvertime->reason)
                            <div>
                                <dt class="text-zinc-500">{{ __('Alasan') }}</dt>
                                <dd class="mt-1 whitespace-pre-line">{{ $selectedOvertime->reason }}</dd>
                            </div>
                        @endif
                        @if ($selectedOvertime->decision_note)
                            <div>
                                <dt class="text-zinc-500">{{ __('Catatan admin') }}</dt>
                                <dd class="mt-1 whitespace-pre-line">{{ $selectedOvertime->decision_note }}</dd>
                            </div>
                        @endif
                        @if ($selectedOvertime->finalized_at)
                            <div>
                                <dt class="text-zinc-500">{{ __('Waktu final admin') }}</dt>
                                <dd class="mt-1 font-medium">
                                    {{ $selectedOvertime->final_start_at?->copy()->setTimezone($selectedOvertime->timezone)->format('d M Y, H:i') ?? '—' }}
                                    – {{ $selectedOvertime->final_end_at?->copy()->setTimezone($selectedOvertime->timezone)->format('d M Y, H:i') ?? '—' }}
                                </dd>
                                <dd class="mt-1 text-zinc-500">{{ __('Sumber') }}: {{ $selectedOvertime->finalization_source }}</dd>
                                @if ($selectedOvertime->finalization_reason)
                                    <dd class="mt-1 whitespace-pre-line text-zinc-500">{{ $selectedOvertime->finalization_reason }}</dd>
                                @endif
                            </div>
                        @endif
                    </dl>
                    <div class="flex justify-end">
                        <flux:modal.close>
                            <x-ui.button variant="primary">{{ __('Tutup') }}</x-ui.button>
                        </flux:modal.close>
                    </div>
                </div>
            @endif
        </flux:modal>
        <flux:modal name="request-overtime" class="max-w-lg">
            <form wire:submit="requestOvertime" class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">{{ __('Ajukan lembur') }}</flux:heading>
                    <flux:subheading>{{ __('Pengajuan dapat dibuat sebelum clock-in. Tentukan waktu mulai; alasan bersifat opsional. Waktu selesai dicatat dari clock-out aktual. Jika jadwal tiba sebelum admin menyetujui, Anda tetap harus mengambil foto konfirmasi lembur.') }}</flux:subheading>
                </div>
                <x-ui.input
                    name="overtimeStartAt"
                    wire:model="overtimeStartAt"
                    :label="__('Waktu mulai lembur')"
                    type="datetime-local"
                    required
                />
                <x-ui.textarea
                    name="overtimeReason"
                    wire:model="overtimeReason"
                    :label="__('Alasan lembur (opsional)')"
                    rows="3"
                    :placeholder="__('Jika diperlukan, jelaskan pekerjaan yang perlu diselesaikan...')"
                />
                @error('form') <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text> @enderror
                <div class="flex justify-end gap-2">
                    <flux:modal.close><x-ui.button variant="ghost">{{ __('Batal') }}</x-ui.button></flux:modal.close>
                    <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="requestOvertime">{{ __('Kirim pengajuan') }}</x-ui.button>
                </div>
            </form>
        </flux:modal>
    </x-ui.page>
</section>
