<?php

use App\Actions\RecordAttendance;
use App\Data\Attendance\AttendanceFilterData;
use App\Enums\AttendanceType;
use App\Enums\LeaveRequestStatus;
use App\Enums\ProjectStatus;
use App\Enums\WorkType;
use App\Filters\AttendanceRecordFilter;
use App\Models\AttendanceRecord;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Support\AttendanceDateFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kehadiran Pegawai')] class extends Component {
    use WithPagination;

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $employeeId = '';
    public string $clientId = '';
    public string $locationId = '';
    public string $projectId = '';
    public string $search = '';
    public string $attendanceType = '';
    public string $workType = '';
    public string $sortBy = 'recorded_at';
    public string $sortDirection = 'desc';

    public function boot(): void
    {
        abort_unless(Auth::user()?->isManager(), 403);
    }

    public function mount(): void
    {
        // Default view menampilkan aktivitas terbaru lintas tanggal.
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    public function updated(string $property): void
    {
        unset($this->records, $this->notYetPresent);
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['recorded_at', 'employee', 'type', 'work_type'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $column === 'recorded_at' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function resetAdditionalFilters(): void
    {
        $this->search = '';
        $this->attendanceType = '';
        $this->workType = '';
        $this->employeeId = '';
        $this->clientId = '';
        $this->locationId = '';
        $this->projectId = '';
        unset($this->records, $this->notYetPresent);
        $this->resetPage();
    }

    private function attendanceFilterData(): AttendanceFilterData
    {
        return AttendanceFilterData::fromArray([
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
            'employee_id' => $this->employeeId,
            'client_id' => $this->clientId,
            'location_id' => $this->locationId,
            'project_id' => $this->projectId,
            'search' => $this->search,
            'attendance_type' => $this->attendanceType,
            'work_type' => $this->workType,
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function range(): array
    {
        $filters = $this->attendanceFilterData();

        return [$filters->from ?? '', $filters->to ?? ''];
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()->with('user:id,name')->get()
            ->sortBy(fn ($employee) => $employee->user->name)
            ->values();
    }

    #[Computed]
    public function filterOptions(): array
    {
        return [
            'clients' => Client::query()
                ->whereHas('projects', fn ($query) => $query->whereHas('attendanceRecords'))
                ->orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn (Client $client) => ['id' => $client->id, 'label' => $client->code.' · '.$client->name])->all(),
            'locations' => Location::query()->whereHas('attendanceRecords')->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'label' => $location->name.(! $location->is_active ? ' (Nonaktif)' : ''),
                ])->all(),
            'projects' => Project::query()->whereHas('attendanceRecords')->with('client:id,name')->orderBy('name')
                ->get(['id', 'client_id', 'code', 'name', 'status'])
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'label' => $project->code.' · '.$project->name.' — '.$project->client?->name
                        .($project->status !== ProjectStatus::Active ? ' (Nonaktif)' : ''),
                ])->all(),
            'employees' => $this->employees->map(fn (Employee $employee) => [
                'id' => $employee->id, 'label' => $employee->user->name,
            ])->all(),
            'attendance_types' => collect(AttendanceType::cases())->map(fn (AttendanceType $type) => [
                'value' => $type->value, 'label' => $type->label(),
            ])->all(),
            'work_types' => collect(WorkType::cases())->map(fn (WorkType $type) => [
                'value' => $type->value, 'label' => $type->label(),
            ])->all(),
        ];
    }

    #[Computed]
    public function filterValues(): array
    {
        return [
            'from' => $this->dateFrom, 'to' => $this->dateTo, 'employee_id' => $this->employeeId,
            'client_id' => $this->clientId, 'location_id' => $this->locationId, 'project_id' => $this->projectId,
            'search' => $this->search, 'attendance_type' => $this->attendanceType, 'work_type' => $this->workType,
        ];
    }

    #[Computed]
    public function records()
    {
        $sortColumns = [
            'recorded_at' => "COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at)",
            'employee' => 'sort_users.name',
            'type' => 'attendance_records.type',
            'work_type' => 'attendance_records.work_type',
        ];

        $query = AttendanceRecord::query()
            ->select('attendance_records.*')
            ->join('employees as sort_employees', 'sort_employees.id', '=', 'attendance_records.employee_id')
            ->join('users as sort_users', 'sort_users.id', '=', 'sort_employees.user_id')
            ->with(['employee.user', 'location', 'project', 'approvedCorrection', 'sessionAnchor.approvedCorrection']);

        (new AttendanceRecordFilter($this->attendanceFilterData()))->apply($query, 'Asia/Makassar');

        return $query
            ->orderByRaw(($sortColumns[$this->sortBy] ?? $sortColumns['recorded_at']).' '.($this->sortDirection === 'asc' ? 'asc' : 'desc'))
            ->orderByDesc('attendance_records.id')
            ->paginate(30);
    }

    #[Computed]
    public function notYetPresent()
    {
        [$from, $to] = $this->range();

        if ($from === '' || $from !== $to || $this->employeeId !== '' || $this->clientId !== '' || $this->locationId !== '' || $this->projectId !== '' || trim($this->search) !== '' || $this->attendanceType !== '' || $this->workType !== '') {
            return collect();
        }

        return Employee::query()
            ->with('user:id,name')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereDoesntHave('attendanceRecords', fn ($query) => AttendanceDateFilter::applyInTimezone($query, $from, $from, 'Asia/Makassar'))
            ->whereDoesntHave('leaveRequests', fn ($query) => $query
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('start_date', '<=', $from)
                ->whereDate('end_date', '>=', $from)
            )
            ->whereDoesntHave('dailyStatusCorrections', fn ($query) => $query->whereDate('attendance_date', $from))
            ->get()
            ->sortBy(fn ($employee) => $employee->user->name)
            ->values();
    }
}; ?>

<section x-data="{ exportConfirmOpen: false, exportUrl: '', exportDescription: '' }" x-on:keydown.escape.window="exportConfirmOpen = false" class="w-full">
    <style>
        .attendance-mobile-list { display: none; }

        .attendance-desktop-table,
        .attendance-desktop-table [data-flux-table] {
            width: 100%;
            max-width: none;
        }

        .attendance-desktop-table table {
            width: 100%;
            table-layout: auto;
        }

        .attendance-desktop-table thead th:nth-child(1),
        .attendance-desktop-table tbody td:nth-child(1) { width: 13%; }
        .attendance-desktop-table thead th:nth-child(2),
        .attendance-desktop-table tbody td:nth-child(2) { width: 14%; }
        .attendance-desktop-table thead th:nth-child(3),
        .attendance-desktop-table tbody td:nth-child(3) { width: 11%; }
        .attendance-desktop-table thead th:nth-child(4),
        .attendance-desktop-table tbody td:nth-child(4) { width: 19%; }
        .attendance-desktop-table thead th:nth-child(5),
        .attendance-desktop-table tbody td:nth-child(5) { width: 27%; }
        .attendance-desktop-table thead th:nth-child(6),
        .attendance-desktop-table tbody td:nth-child(6) { width: 7%; }
        .attendance-desktop-table thead th:nth-child(7),
        .attendance-desktop-table tbody td:nth-child(7) { width: 9%; }

        @media (max-width: 767px) {
            .attendance-responsive-table .attendance-desktop-table { display: none; }
            .attendance-mobile-list { display: grid; }
        }
    </style>
    <x-ui.page>
        <x-admin.page-header :title="__('Kehadiran Pegawai')" />
        @if (session('status'))
            <div role="status" class="rounded-lg border border-sanmal-success/30 bg-sanmal-success/10 px-4 py-3 text-sm text-sanmal-success dark:border-sanmal-success/50 dark:bg-sanmal-success/15">
                {{ session('status') }}
            </div>
        @endif
        @error('drive_backup')
            <div role="alert" class="rounded-lg border border-sanmal-danger/30 bg-sanmal-danger/10 px-4 py-3 text-sm text-sanmal-danger dark:border-sanmal-danger/50 dark:bg-sanmal-danger/15">
                {{ $message }}
            </div>
        @enderror

        @php
            $records = $this->records;
            $notYetPresent = $this->notYetPresent;
        @endphp

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="p-4 sm:p-5">
                <x-admin.attendance-filters
                    :filters="['date', 'client', 'location', 'project', 'employee', 'search', 'attendance_type', 'work_type']"
                    :values="$this->filterValues"
                    :options="$this->filterOptions"
                />

                <div class="mt-3 flex justify-end border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                    <x-ui.button
                        size="sm"
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        icon="arrow-path"
                        wire:click="resetAdditionalFilters"
                    >
                        {{ __('Reset filter') }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-sanmal-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-sanmal-border-dark">
                <div>
                    <flux:heading size="lg">{{ __('Aktivitas terbaru') }}</flux:heading>
                    <p class="mt-1 text-sm text-[var(--sanmal-text-secondary)]">
                        {{ number_format($records->total()) }} {{ __('catatan absensi') }}
                        @if ($notYetPresent->isNotEmpty())
                            <span class="text-[var(--sanmal-text-muted)]">{{ __('dan :count pegawai belum memiliki catatan pada tanggal ini.', ['count' => number_format($notYetPresent->count())]) }}</span>
                        @endif
                    </p>
                </div>
                @php
                    $exportFilters = array_filter([
                        'employee_id' => $employeeId,
                        'client_id' => $clientId,
                        'location_id' => $locationId,
                        'project_id' => $projectId,
                        'search' => $search,
                        'attendance_type' => $attendanceType,
                        'work_type' => $workType,
                    ], fn ($value) => $value !== '');
                    $today = Carbon::now('Asia/Makassar')->startOfDay();
                    $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
                    $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
                    $monthStart = $today->copy()->startOfMonth();
                    $monthEnd = $today->copy()->endOfMonth();
                    $customDateRange = AttendanceFilterData::fromArray([
                        'from' => $dateFrom,
                        'to' => $dateTo,
                    ]);
                    $customExportDateDescription = $customDateRange->from !== null
                        ? 'Rentang tanggal mengikuti filter aktif: '
                            .Carbon::parse($customDateRange->from)->translatedFormat('d M Y')
                            .' sampai '.Carbon::parse($customDateRange->to)->translatedFormat('d M Y').'.'
                        : 'Tidak ada filter tanggal. Ekspor mencakup seluruh riwayat yang tersedia sampai hari ini.';
                    $activeExportFilters = [];
                    if ($employeeId !== '') {
                        $selectedEmployee = $this->employees->firstWhere('id', (int) $employeeId);
                        $activeExportFilters[] = 'Pegawai: '.($selectedEmployee?->user?->name ?? 'ID '.$employeeId);
                    }
                    if ($clientId !== '') {
                        $selected = collect($this->filterOptions['clients'])->firstWhere('id', (int) $clientId);
                        $activeExportFilters[] = 'Client: '.($selected['label'] ?? 'ID '.$clientId);
                    }
                    if ($locationId !== '') {
                        $selected = collect($this->filterOptions['locations'])->firstWhere('id', (int) $locationId);
                        $activeExportFilters[] = 'Location: '.($selected['label'] ?? 'ID '.$locationId);
                    }
                    if ($projectId !== '') {
                        $selected = collect($this->filterOptions['projects'])->firstWhere('id', (int) $projectId);
                        $activeExportFilters[] = 'Project: '.($selected['label'] ?? 'ID '.$projectId);
                    }
                    if (trim($search) !== '') {
                        $activeExportFilters[] = 'Pencarian: '.trim($search);
                    }
                    if ($attendanceType !== '') {
                        $activeExportFilters[] = 'Jenis absensi: '.(AttendanceType::tryFrom($attendanceType)?->label() ?? $attendanceType);
                    }
                    if ($workType !== '') {
                        $activeExportFilters[] = 'Jenis kerja: '.(WorkType::tryFrom($workType)?->label() ?? $workType);
                    }
                    $exportFilterSummary = $activeExportFilters
                        ? 'Filter tambahan aktif: '.implode(' · ', $activeExportFilters)
                        : 'Filter tambahan: tidak ada; seluruh pegawai masuk dalam cakupan laporan.';
                    $exportOptions = [
                        [
                            'label' => __('Hari ini'),
                            'href' => route('admin.kehadiran.export', [...$exportFilters, 'from' => $today->toDateString(), 'to' => $today->toDateString()]),
                            'description' => 'Laporan absensi tanggal '.$today->translatedFormat('d M Y').'. Seluruh filter aktif tetap diterapkan.',
                            'icon' => 'calendar-days',
                        ],
                        [
                            'label' => __('Minggu ini'),
                            'href' => route('admin.kehadiran.export', [...$exportFilters, 'from' => $weekStart->toDateString(), 'to' => $weekEnd->toDateString()]),
                            'description' => 'Laporan seluruh pegawai dari '.$weekStart->translatedFormat('d M Y').' sampai '.$weekEnd->translatedFormat('d M Y').'. Filter aktif tetap diterapkan.',
                            'icon' => 'calendar-days',
                        ],
                        [
                            'label' => __('Bulan ini'),
                            'href' => route('admin.kehadiran.export', [...$exportFilters, 'from' => $monthStart->toDateString(), 'to' => $monthEnd->toDateString()]),
                            'description' => 'Laporan seluruh pegawai dari '.$monthStart->translatedFormat('d M Y').' sampai '.$monthEnd->translatedFormat('d M Y').'. Filter aktif tetap diterapkan.',
                            'icon' => 'calendar',
                        ],
                        [
                            'label' => __('Sesuai filter tanggal'),
                            'href' => route('admin.kehadiran.export', array_filter([
                                ...$exportFilters,
                                'from' => $dateFrom,
                                'to' => $dateTo,
                            ], fn ($value) => $value !== '')),
                            'description' => $customExportDateDescription.' Seluruh filter tambahan yang aktif tetap diterapkan.',
                            'icon' => 'arrow-down-tray',
                            'divider_before' => true,
                        ],
                    ];
                @endphp

                <x-admin.attendance-export
                    :options="$exportOptions"
                    :filter-summary="$exportFilterSummary"
                    :warning="__('Ekspor menggunakan satu baris per pegawai per tanggal. Tanggal tanpa event absensi tetap disertakan. Periksa kembali cakupan sebelum melanjutkan.')"
                />
            </div>
            <div id="attendance-records" data-record-count="{{ $records->total() }}" class="attendance-responsive-table p-3 sm:p-4 [&_thead]:sticky [&_thead]:top-0 [&_thead]:z-20 [&_thead]:bg-sanmal-surface-secondary dark:[&_thead]:bg-sanmal-surface-secondary-dark">
                <div class="attendance-mobile-list gap-3">
                @forelse ($records as $record)
                    <article wire:key="attendance-mobile-{{ $record->id }}" class="flex gap-3 rounded-xl border border-sanmal-border bg-sanmal-surface-primary p-3 shadow-sm dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark">
                        <div class="w-20 shrink-0">
                            @if ($record->selfie_path)
                                <x-ui.photo-preview
                                    :src="route('admin.attendance.selfie', $record)"
                                    :alt="__('Foto absensi :name', ['name' => $record->employee->user->name])"
                                    :title="__('Foto absensi')"
                                    :trigger-label="__('Buka foto absensi :name', ['name' => $record->employee->user->name])"
                                    :download-url="route('admin.attendance.selfie.download', $record)"
                                    :download-name="'absensi-'.$record->id.'.jpg'"
                                    class="block w-full"
                                >
                                    <img src="{{ route('admin.attendance.selfie', $record) }}" alt="" class="h-24 w-20 rounded-lg object-cover" />
                                </x-ui.photo-preview>
                            @else
                                <div class="flex h-24 w-20 items-center justify-center rounded-lg bg-sanmal-surface-secondary text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                                    <flux:icon name="user" class="size-7" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--sanmal-text-primary)]">{{ $record->employee->user->name }}</p>
                                    <p class="mt-1 text-xs tabular-nums text-[var(--sanmal-text-secondary)]">{{ $record->effectiveRecordedAtLocal()->format('d M Y · H:i T') }}</p>
                                </div>
                                <x-ui.badge :color="$record->type === AttendanceType::ClockIn ? 'green' : 'blue'">{{ $record->type->label() }}</x-ui.badge>
                            </div>
                            <p class="mt-2 text-xs text-[var(--sanmal-text-secondary)]">{{ $record->work_type->label() }}</p>
                            @if ($record->project_name_snapshot)
                                <p class="mt-1 truncate text-xs text-[var(--sanmal-text-muted)]">{{ $record->project_code_snapshot ? $record->project_code_snapshot.' · ' : '' }}{{ $record->project_name_snapshot }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center justify-end gap-1.5">
                                @if (in_array($record->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true) && $record->employee->user_id !== auth()->id())
                                    <flux:modal.trigger :name="'attendance-correction-'.$record->id">
                                        <x-ui.button size="xs" variant="outline">{{ __('Koreksi') }}</x-ui.button>
                                    </flux:modal.trigger>
                                @endif
                                @if ($record->latitude !== null && $record->longitude !== null)
                                    <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-7 items-center justify-center rounded-md border border-sanmal-border px-2 text-xs font-medium text-sanmal-brand-blue dark:border-sanmal-border-dark dark:text-sanmal-brand-yellow-dark">{{ __('Peta') }}</a>
                                @endif
                                <flux:modal.trigger :name="'attendance-detail-'.$record->id">
                                    <x-ui.button size="xs" variant="outline" icon="eye">{{ __('Detail') }}</x-ui.button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </article>
                @empty
                    @if ($notYetPresent->isEmpty())
                        <x-admin.empty-state :message="__('Tidak ada catatan pada filter ini.')" />
                    @endif
                @endforelse
                @foreach ($notYetPresent as $employee)
                    <article wire:key="attendance-mobile-not-present-{{ $employee->id }}" class="flex gap-3 rounded-xl border border-sanmal-border bg-sanmal-surface-primary p-3 dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-secondary text-[var(--sanmal-text-muted)] dark:bg-sanmal-surface-secondary-dark">
                            <flux:icon name="user" class="size-6" />
                        </div>
                        <div class="flex min-w-0 flex-1 items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold">{{ $employee->user->name }}</p>
                            <x-ui.badge color="amber">{{ __('Belum ada absensi') }}</x-ui.badge>
                        </div>
                    </article>
                @endforeach
                <div class="pt-1">{{ $records->links() }}</div>
            </div>
            <div class="attendance-desktop-table">
            <flux:table class="w-full" :paginate="$records" pagination:scroll-to="#attendance-records">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'recorded_at'" :direction="$sortDirection" wire:click="sort('recorded_at')" class="px-3 py-3 sm:py-2">{{ __('Waktu') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'employee'" :direction="$sortDirection" wire:click="sort('employee')" class="px-3 py-3 sm:py-2">{{ __('Pegawai') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')" class="px-3 py-3 sm:py-2">{{ __('Jenis') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'work_type'" :direction="$sortDirection" wire:click="sort('work_type')" class="px-3 py-3 sm:py-2">{{ __('Kerja') }}</flux:table.column>
                        <flux:table.column class="px-3 py-3 sm:py-2">{{ __('Lokasi GPS') }}</flux:table.column>
                        <flux:table.column class="px-3 py-3 sm:py-2" align="center">{{ __('Foto') }}</flux:table.column>
                        <flux:table.column class="px-3 py-3 sm:py-2">{{ __('Koreksi') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($records as $record)
                            <flux:table.row :key="$record->id">
                                <flux:table.cell data-label="Waktu" class="whitespace-normal px-3 py-3 sm:py-2">
                                    <p class="font-medium text-[var(--sanmal-text-primary)]">{{ $record->effectiveRecordedAtLocal()->format('d M Y') }}</p>
                                    <p class="text-xs text-[var(--sanmal-text-secondary)]">{{ $record->effectiveRecordedAtLocal()->format('H:i T') }}</p>
                                    @if ($record->approvedCorrection)
                                        <p class="mt-1 text-xs text-[var(--sanmal-text-muted)]">{{ __('Asli:') }} {{ $record->recordedAtLocal()->format('d M, H:i T') }}</p>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell data-label="Pegawai" class="px-3 py-3 sm:py-2" title="{{ $record->employee->user->name }}">
                                    <span class="block truncate font-medium text-[var(--sanmal-text-primary)]">{{ $record->employee->user->name }}</span>
                                </flux:table.cell>
                                <flux:table.cell data-label="Jenis" class="px-3 py-3 sm:py-2">
                                    <x-ui.badge :color="$record->type === AttendanceType::ClockIn ? 'green' : 'blue'">
                                        {{ $record->type->label() }}
                                    </x-ui.badge>
                                </flux:table.cell>
                                <flux:table.cell data-label="Jenis kerja / Project" class="whitespace-normal px-3 py-3 sm:py-2">
                                    <p class="text-sm text-[var(--sanmal-text-primary)]">{{ $record->work_type->label() }}</p>
                                    @if ($record->project_name_snapshot)
                                        <div class="mt-1 max-w-full">
                                            <x-admin.attendance.project-snapshot-modal
                                                :modal-name="'attendance-project-'.$record->id"
                                                :project-code="$record->project_code_snapshot"
                                                :project-name="$record->project_name_snapshot"
                                                :client-code="$record->client_code_snapshot"
                                                :client-name="$record->client_name_snapshot"
                                                :location-name="$record->location_name_snapshot"
                                            />
                                        </div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell data-label="Lokasi GPS" class="whitespace-normal px-3 py-3 sm:py-2">
                                    @if ($record->latitude !== null && $record->longitude !== null)
                                        <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" rel="noopener noreferrer" class="block break-words text-sm leading-5 text-sanmal-brand-blue underline decoration-sanmal-brand-blue/30 underline-offset-2 transition hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sanmal-brand-blue dark:text-sanmal-brand-yellow-dark dark:decoration-sanmal-brand-yellow-dark/40 dark:focus-visible:outline-sanmal-brand-yellow-dark">
                                            <span class="sr-only">{{ __('Alamat tersimpan:') }}</span>
                                            {{ $record->address_snapshot ?: __('Buka lokasi di peta') }}
                                        </a>
                                    @else
                                        <span class="block break-words text-sm text-[var(--sanmal-text-muted)]">{{ $record->address_snapshot ?: '—' }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell data-label="Bukti foto" class="px-3 py-3 sm:py-2" align="center">
                                    @if ($record->selfie_path)
                                        <x-ui.photo-preview
                                            :src="route('admin.attendance.selfie', $record)"
                                            :alt="__('Foto absensi :name', ['name' => $record->employee->user->name])"
                                            :title="__('Foto absensi')"
                                            :trigger-label="__('Buka foto absensi :name', ['name' => $record->employee->user->name])"
                                            :download-url="route('admin.attendance.selfie.download', $record)"
                                            :download-name="'absensi-'.$record->id.'.jpg'"
                                            class="rounded-md"
                                        >
                                            <img src="{{ route('admin.attendance.selfie', $record) }}" alt="" class="size-10 rounded-md object-cover" />
                                        </x-ui.photo-preview>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell data-label="Koreksi" class="px-3 py-3 sm:py-2">
                                    @if (in_array($record->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true) && $record->employee->user_id !== auth()->id())
                                        <flux:modal.trigger :name="'attendance-correction-'.$record->id">
                                            <x-ui.button type="button" size="sm" variant="outline">{{ __('Koreksi') }}</x-ui.button>
                                        </flux:modal.trigger>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            @if ($notYetPresent->isEmpty())
                                <flux:table.row>
                                    <flux:table.cell colspan="7" class="px-3 py-3 sm:py-2">
                                        <x-admin.empty-state :message="__('Tidak ada catatan pada filter ini.')" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforelse

                        @foreach ($notYetPresent as $employee)
                            <flux:table.row :key="'not-present-'.$employee->id">
                                <flux:table.cell data-label="Waktu" class="whitespace-nowrap px-3 py-3 sm:py-2">—</flux:table.cell>
                                <flux:table.cell data-label="Pegawai" class="px-3 py-3 sm:py-2">{{ $employee->user->name }}</flux:table.cell>
                                <flux:table.cell data-label="Jenis" class="px-3 py-3 sm:py-2">
                                    <x-ui.badge color="amber">{{ __('Belum ada absensi') }}</x-ui.badge>
                                </flux:table.cell>
                                <flux:table.cell class="px-3 py-3 sm:py-2">—</flux:table.cell>
                                <flux:table.cell class="px-3 py-3 sm:py-2">—</flux:table.cell>
                                <flux:table.cell class="px-3 py-3 sm:py-2">—</flux:table.cell>
                                <flux:table.cell class="px-3 py-3 sm:py-2">—</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            @foreach ($records as $record)
                <flux:modal :name="'attendance-detail-'.$record->id" class="max-w-lg">
                    <div class="space-y-4">
                        <div>
                            <flux:heading size="lg">{{ __('Detail absensi') }}</flux:heading>
                            <flux:text class="mt-1">{{ $record->employee->user->name }} · {{ $record->type->label() }}</flux:text>
                        </div>
                        <dl class="grid gap-3 rounded-lg bg-sanmal-surface-secondary p-4 text-sm dark:bg-sanmal-surface-secondary-dark">
                            <div>
                                <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Waktu efektif') }}</dt>
                                <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->effectiveRecordedAtLocal()->format('d M Y · H:i T') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Jenis kerja') }}</dt>
                                <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->work_type->label() }}</dd>
                            </div>
                            @if ($record->project_name_snapshot)
                                <div>
                                    <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Proyek') }}</dt>
                                    <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->project_code_snapshot ? $record->project_code_snapshot.' · ' : '' }}{{ $record->project_name_snapshot }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Client') }}</dt>
                                    <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->client_code_snapshot ? $record->client_code_snapshot.' · ' : '' }}{{ $record->client_name_snapshot ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Lokasi kerja') }}</dt>
                                    <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->location_name_snapshot ?: '—' }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Alamat GPS') }}</dt>
                                <dd class="mt-1 break-words font-medium text-[var(--sanmal-text-primary)]">{{ $record->address_snapshot ?: '—' }}</dd>
                            </div>
                            @if ($record->latitude !== null && $record->longitude !== null)
                                <div>
                                    <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Koordinat GPS') }}</dt>
                                    <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->latitude }}, {{ $record->longitude }}</dd>
                                    <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-xs font-medium text-sanmal-brand-blue underline underline-offset-2 dark:text-sanmal-brand-yellow-dark">{{ __('Buka di Google Maps') }}</a>
                                </div>
                            @endif
                            @if ($record->approvedCorrection)
                                <div>
                                    <dt class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Waktu asli') }}</dt>
                                    <dd class="mt-1 font-medium text-[var(--sanmal-text-primary)]">{{ $record->recordedAtLocal()->format('d M Y · H:i T') }}</dd>
                                </div>
                            @endif
                        </dl>
                        <div class="flex justify-end">
                            <flux:modal.close><x-ui.button size="sm" variant="outline">{{ __('Tutup') }}</x-ui.button></flux:modal.close>
                        </div>
                    </div>
                </flux:modal>
                @if (in_array($record->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true) && $record->employee->user_id !== auth()->id())
                    <flux:modal :name="'attendance-correction-'.$record->id" class="max-w-md">
                        <form method="POST" action="{{ route('admin.kehadiran.correct-timestamp', $record) }}" class="grid gap-4">
                            @csrf
                            <div>
                                <flux:heading size="lg">{{ __('Koreksi timestamp') }}</flux:heading>
                                <flux:text class="mt-1">{{ $record->employee->user->name }} · {{ $record->type->label() }}</flux:text>
                            </div>
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">{{ __('Waktu efektif') }}</span>
                                <input type="datetime-local" name="recorded_at_local" required value="{{ $record->effectiveRecordedAtLocal()->format('Y-m-d\\TH:i') }}" class="rounded-md border-sanmal-border bg-sanmal-surface-primary text-sm dark:border-sanmal-border-dark dark:bg-sanmal-surface-inset-dark">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="font-medium">{{ __('Alasan wajib') }}</span>
                                <textarea name="reason" required minlength="5" maxlength="2000" rows="3" class="rounded-md border-sanmal-border bg-sanmal-surface-primary text-sm dark:border-sanmal-border-dark dark:bg-sanmal-surface-inset-dark"></textarea>
                            </label>
                            <p class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Timestamp bukti asli tidak diubah; perubahan dicatat di audit log.') }}</p>
                            <div class="flex justify-end gap-2">
                                <flux:modal.close><x-ui.button type="button" variant="outline">{{ __('Batal') }}</x-ui.button></flux:modal.close>
                                <x-ui.button type="submit" variant="primary">{{ __('Simpan koreksi') }}</x-ui.button>
                            </div>
                        </form>
                    </flux:modal>
                @endif
            @endforeach
            </div>
        </x-ui.card>
    </x-ui.page>
</section>
