<?php

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\LeaveRequestStatus;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
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
        $today = now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString();
        $this->dateFrom = $today;
        $this->dateTo = $today;
    }

    public function updated(string $property): void
    {
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
        $this->resetPage();
    }

    /** @return array{0: string, 1: string} */
    private function range(): array
    {
        $today = now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString();
        $from = Carbon::canBeCreatedFromFormat($this->dateFrom, 'Y-m-d') ? $this->dateFrom : $today;
        $to = Carbon::canBeCreatedFromFormat($this->dateTo, 'Y-m-d') ? $this->dateTo : $from;

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()->with('user:id,name')->get()
            ->sortBy(fn ($employee) => $employee->user->name)
            ->values();
    }

    #[Computed]
    public function records()
    {
        [$from, $to] = $this->range();

        $sortColumns = [
            'recorded_at' => 'attendance_records.recorded_at',
            'employee' => 'sort_users.name',
            'type' => 'attendance_records.type',
            'work_type' => 'attendance_records.work_type',
        ];

        return AttendanceRecord::query()
            ->select('attendance_records.*')
            ->join('employees as sort_employees', 'sort_employees.id', '=', 'attendance_records.employee_id')
            ->join('users as sort_users', 'sort_users.id', '=', 'sort_employees.user_id')
            ->with(['employee.user', 'location', 'project'])
            ->whereBetween('attendance_records.attendance_date', [$from, $to])
            ->when($this->employeeId !== '', fn ($query) => $query->where('attendance_records.employee_id', (int) $this->employeeId))
            ->when($this->attendanceType !== '', fn ($query) => $query->where('attendance_records.type', $this->attendanceType))
            ->when($this->workType !== '', fn ($query) => $query->where('attendance_records.work_type', $this->workType))
            ->when(trim($this->search) !== '', function ($query) {
                $term = trim($this->search);
                $query->where(function ($nested) use ($term) {
                    $nested->where('sort_users.name', 'like', '%'.$term.'%')
                        ->orWhere('sort_employees.employee_number', 'like', '%'.$term.'%')
                        ->orWhere('attendance_records.notes', 'like', '%'.$term.'%');
                });
            })
            ->orderBy($sortColumns[$this->sortBy] ?? $sortColumns['recorded_at'], $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('attendance_records.id')
            ->paginate(20);
    }

    #[Computed]
    public function notYetPresent()
    {
        [$from, $to] = $this->range();

        if ($from !== $to || $this->employeeId !== '' || trim($this->search) !== '' || $this->attendanceType !== '' || $this->workType !== '') {
            return collect();
        }

        return Employee::query()
            ->with('user:id,name')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereDoesntHave('attendanceRecords', fn ($query) => $query->where('attendance_date', $from))
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

<section class="w-full">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        <x-admin.page-header
            :title="__('Kehadiran Pegawai')"
            :description="__('Pantau dan telusuri catatan absensi seluruh pegawai.')"
        />

        @php($records = $this->records)

        <x-admin.filter-panel class="space-y-4">
            <flux:input type="date" wire:model.live="dateFrom" :label="__('Dari tanggal')" />
            <flux:input type="date" wire:model.live="dateTo" :label="__('Sampai tanggal')" />
            <flux:select wire:model.live="employeeId" :label="__('Pegawai')">
                <flux:select.option value="">{{ __('Semua pegawai') }}</flux:select.option>
                @foreach ($this->employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->user->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Pencarian')" :placeholder="__('Nama, nomor pegawai, atau catatan')" />
            <flux:select wire:model.live="attendanceType" :label="__('Jenis absensi')">
                <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                @foreach (AttendanceType::cases() as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="workType" :label="__('Jenis kerja')">
                <flux:select.option value="">{{ __('Semua jenis kerja') }}</flux:select.option>
                @foreach (WorkType::cases() as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="button" variant="ghost" wire:click="resetAdditionalFilters">
                {{ __('Reset filter tambahan') }}
            </flux:button>
        </x-admin.filter-panel>

        @if ($this->notYetPresent->isNotEmpty())
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-700/60 dark:bg-amber-950/30">
                <flux:heading level="2">{{ __('Belum ada absensi') }} ({{ $this->notYetPresent->count() }})</flux:heading>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->notYetPresent as $employee)
                        <flux:badge color="amber">{{ $employee->user->name }}</flux:badge>
                    @endforeach
                </div>
                <flux:text class="mt-3 text-sm">{{ __('Tidak mencakup pegawai yang izin/cutinya telah disetujui atau memiliki koreksi status.') }}</flux:text>
            </section>
        @endif

        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:text class="text-sm">{{ number_format($records->total()) }} {{ __('catatan') }}</flux:text>
                <flux:text class="text-xs text-zinc-500">{{ __('Pilih judul kolom untuk mengurutkan data.') }}</flux:text>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'recorded_at'" :direction="$sortDirection" wire:click="sort('recorded_at')">{{ __('Waktu') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'employee'" :direction="$sortDirection" wire:click="sort('employee')">{{ __('Pegawai') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">{{ __('Jenis') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'work_type'" :direction="$sortDirection" wire:click="sort('work_type')">{{ __('Lokasi kerja') }}</flux:table.column>
                        <flux:table.column>{{ __('GPS') }}</flux:table.column>
                        <flux:table.column>{{ __('Foto') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($records as $record)
                            <flux:table.row :key="$record->id">
                                <flux:table.cell class="whitespace-nowrap">{{ $record->recordedAtLocal()->format('d M Y, H:i T') }}</flux:table.cell>
                                <flux:table.cell>{{ $record->employee->user->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$record->type === AttendanceType::ClockIn ? 'green' : 'blue'">
                                        {{ $record->type->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $record->work_type->label() }}
                                    @if ($record->project)
                                        <span class="text-zinc-500">· {{ $record->project->code }}</span>
                                    @elseif ($record->location)
                                        <span class="text-zinc-500">· {{ $record->location->name }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($record->latitude !== null && $record->longitude !== null)
                                        @if ($record->accuracy_m !== null)
                                            ±{{ round((float) $record->accuracy_m) }} m
                                        @endif
                                        <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-blue-600 underline dark:text-blue-400">{{ __('Peta') }}</a>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($record->selfie_path)
                                        <a href="{{ route('admin.attendance.selfie', $record) }}" target="_blank" rel="noopener" class="text-blue-600 underline dark:text-blue-400">{{ __('Lihat') }}</a>
                                        @if ($record->selfie_source === \App\Enums\SelfieSource::Upload)
                                            <flux:badge color="amber" size="sm" class="ml-1">{{ __('Unggahan') }}</flux:badge>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6">
                                    <x-admin.empty-state :message="__('Tidak ada catatan pada filter ini.')" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            {{ $records->links() }}
        </div>
    </div>
</section>
