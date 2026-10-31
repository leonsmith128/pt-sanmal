<?php

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\LeaveRequestStatus;
use App\Enums\SelfieSource;
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
        unset($this->records, $this->notYetPresent);
        $this->resetPage();
    }

    /** @return array{0: string, 1: string} */
    private function range(): array
    {
        $from = Carbon::canBeCreatedFromFormat($this->dateFrom, 'Y-m-d') ? $this->dateFrom : '';
        $to = Carbon::canBeCreatedFromFormat($this->dateTo, 'Y-m-d') ? $this->dateTo : '';

        if ($from === '' && $to === '') {
            return ['', ''];
        }

        $from = $from !== '' ? $from : $to;
        $to = $to !== '' ? $to : $from;

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

        // Gunakan ekspresi tanggal yang sesuai dengan driver agar query juga
        // dapat dijalankan oleh SQLite pada feature tests.
        $previousDayBoundary = match (\Illuminate\Support\Facades\DB::connection()->getDriverName()) {
            'sqlite' => "datetime(attendance_records.recorded_at, '-24 hours')",
            default => 'DATE_SUB(attendance_records.recorded_at, INTERVAL 24 HOUR)',
        };

        $sortColumns = [
            'recorded_at' => "COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at)",
            'employee' => 'sort_users.name',
            'type' => 'attendance_records.type',
            'work_type' => 'attendance_records.work_type',
        ];

        return AttendanceRecord::query()
            ->select('attendance_records.*')
            ->join('employees as sort_employees', 'sort_employees.id', '=', 'attendance_records.employee_id')
            ->join('users as sort_users', 'sort_users.id', '=', 'sort_employees.user_id')
            ->with(['employee.user', 'location', 'project', 'approvedCorrection'])
            ->when($from !== '' && $to !== '', function ($query) use ($from, $to, $previousDayBoundary): void {
                $query->where(function ($nested) use ($from, $to, $previousDayBoundary): void {
                    $nested->whereRaw(
                        "COALESCE((SELECT ac.attendance_date FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.attendance_date) BETWEEN ? AND ?",
                        [$from, $to],
                    )->orWhereRaw(
                        "(SELECT COALESCE((SELECT ac2.attendance_date FROM attendance_corrections ac2 WHERE ac2.attendance_record_id = ci.id AND ac2.status = 'approved' ORDER BY ac2.reviewed_at DESC LIMIT 1), ci.attendance_date) FROM attendance_records ci WHERE ci.employee_id = attendance_records.employee_id AND ci.type = 'clock_in' AND ci.recorded_at <= attendance_records.recorded_at AND ci.recorded_at >= {$previousDayBoundary} ORDER BY ci.recorded_at DESC LIMIT 1) BETWEEN ? AND ?",
                        [$from, $to],
                    );
                });
            })
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
            ->orderByRaw(($sortColumns[$this->sortBy] ?? $sortColumns['recorded_at']).' '.($this->sortDirection === 'asc' ? 'asc' : 'desc'))
            ->orderByDesc('attendance_records.id')
            ->paginate(30);
    }

    #[Computed]
    public function notYetPresent()
    {
        [$from, $to] = $this->range();

        if ($from === '' || $from !== $to || $this->employeeId !== '' || trim($this->search) !== '' || $this->attendanceType !== '' || $this->workType !== '') {
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

<section x-data="{ selfiePreviewOpen: false, selfiePreviewUrl: null, selfieDownloadUrl: null }" x-on:keydown.escape.window="selfiePreviewOpen = false" class="w-full">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-5">
        <x-admin.page-header
            :title="__('Kehadiran Pegawai')"
            :description="__('Pantau dan telusuri catatan absensi seluruh pegawai.')"
        />
        @if (session('status'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif
        @error('drive_backup')
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                {{ $message }}
            </div>
        @enderror

        @php
            ($records = $this->records)
        @endphp

        <x-admin.filter-panel class="!p-3">
            <div class="grid w-full grid-cols-2 items-end gap-2 sm:grid-cols-4 lg:grid-cols-6">
                <div class="min-w-0">
                    <flux:input size="sm" type="date" wire:model.live="dateFrom" :label="__('Dari tanggal')" />
                </div>
                <div class="min-w-0">
                    <flux:input size="sm" type="date" wire:model.live="dateTo" :label="__('Sampai tanggal')" />
                </div>
                <div class="min-w-0">
                    <flux:select size="sm" wire:model.live="employeeId" :label="__('Pegawai')">
                        <flux:select.option value="">{{ __('Semua pegawai') }}</flux:select.option>
                        @foreach ($this->employees as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->user->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="min-w-0">
                    <flux:input size="sm" wire:model.live.debounce.300ms="search" :label="__('Pencarian')" :placeholder="__('Nama / nomor / catatan')" />
                </div>
                <div class="min-w-0">
                    <flux:select size="sm" wire:model.live="attendanceType" :label="__('Jenis absensi')">
                        <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                        @foreach (AttendanceType::cases() as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="min-w-0">
                    <flux:select size="sm" wire:model.live="workType" :label="__('Jenis kerja')">
                        <flux:select.option value="">{{ __('Semua jenis kerja') }}</flux:select.option>
                        @foreach (WorkType::cases() as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="col-span-2 flex justify-end sm:col-span-4 lg:col-span-6">
                    <flux:button size="sm" type="button" variant="ghost" icon="arrows-up-down" wire:click="resetAdditionalFilters">
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </div>
        </x-admin.filter-panel>

        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Aktivitas terbaru') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ number_format($records->total()) }} {{ __('catatan absensi') }}
                    </flux:text>
                </div>
                @php
                    $exportFilters = array_filter([
                        'employee_id' => $employeeId,
                        'search' => $search,
                        'attendance_type' => $attendanceType,
                        'work_type' => $workType,
                    ], fn ($value) => $value !== '');
                    $today = Carbon::today();
                    $weekStart = Carbon::today()->startOfWeek();
                    $monthStart = Carbon::today()->startOfMonth();
                @endphp
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="outline" icon="arrow-down-tray">
                        {{ __('Export') }}
                        <flux:icon name="chevron-down" variant="micro" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item
                            :href="route('admin.kehadiran.export', [...$exportFilters, 'from' => $today->toDateString(), 'to' => $today->toDateString()])"
                            icon="calendar-days"
                        >
                            {{ __('Hari ini') }}
                        </flux:menu.item>
                        <flux:menu.item
                            :href="route('admin.kehadiran.export', [...$exportFilters, 'from' => $weekStart->toDateString(), 'to' => $today->toDateString()])"
                            icon="calendar-days"
                        >
                            {{ __('Minggu ini') }}
                        </flux:menu.item>
                        <flux:menu.item
                            :href="route('admin.kehadiran.export', [...$exportFilters, 'from' => $monthStart->toDateString(), 'to' => $today->toDateString()])"
                            icon="calendar"
                        >
                            {{ __('Bulan ini') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item
                            :href="route('admin.kehadiran.export', array_filter([
                                ...$exportFilters,
                                'from' => $dateFrom,
                                'to' => $dateTo,
                            ], fn ($value) => $value !== ''))"
                            icon="arrow-down-tray"
                        >
                            {{ __('Sesuai filter tanggal') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>

            <div id="attendance-records" data-record-count="{{ $records->total() }}" class="max-h-[70vh] overflow-auto rounded-lg border border-neutral-200 p-2 [--flux-bleed:0.5rem] [&_thead]:sticky [&_thead]:top-0 [&_thead]:z-20 [&_thead]:bg-white dark:border-neutral-700 dark:[&_thead]:bg-zinc-900">
                <flux:table bleed :paginate="$records" pagination:scroll-to="#attendance-records">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'recorded_at'" :direction="$sortDirection" wire:click="sort('recorded_at')">{{ __('Waktu') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'employee'" :direction="$sortDirection" wire:click="sort('employee')">{{ __('Pegawai') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">{{ __('Jenis') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'work_type'" :direction="$sortDirection" wire:click="sort('work_type')">{{ __('Lokasi kerja') }}</flux:table.column>
                        <flux:table.column>{{ __('GPS') }}</flux:table.column>
                        <flux:table.column>{{ __('Foto') }}</flux:table.column>
                        <flux:table.column>{{ __('Koreksi waktu') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($records as $record)
                            <flux:table.row :key="$record->id">
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $record->effectiveRecordedAtLocal()->format('d M Y, H:i T') }}
                                    @if ($record->approvedCorrection)
                                        <p class="text-xs text-zinc-500">{{ __('Bukti asli:') }} {{ $record->recordedAtLocal()->format('d M Y, H:i T') }}</p>
                                    @endif
                                    @if ($record->captured_at)
                                        <p class="text-xs text-zinc-500">{{ __('Waktu perangkat (informasi):') }} {{ $record->captured_at->copy()->setTimezone($record->timezone)->format('d M Y, H:i T') }}</p>
                                    @endif
                                </flux:table.cell>
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
                                    @if ($record->location_status === 'unavailable')
                                        <flux:badge color="amber" size="sm" class="ml-1">{{ __('Perlu ditinjau') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($record->selfie_path)
                                        <button
                                            type="button"
                                            data-preview-url="{{ route('admin.attendance.selfie', $record) }}"
                                            data-download-url="{{ route('admin.attendance.selfie.download', $record) }}"
                                            x-on:click="selfiePreviewUrl = $el.dataset.previewUrl; selfieDownloadUrl = $el.dataset.downloadUrl; selfiePreviewOpen = true"
                                            class="text-blue-600 underline dark:text-blue-400"
                                        >{{ __('Pratinjau') }}</button>
                                        @if ($record->selfie_source === SelfieSource::Upload)
                                            <flux:badge color="amber" size="sm" class="ml-1">{{ __('Unggahan') }}</flux:badge>
                                        @endif
                                        @if ($record->drive_backup_status === 'verified')
                                            <flux:badge color="green" size="sm" class="ml-1">{{ __('Drive terverifikasi') }}</flux:badge>
                                            @if ($record->local_deleted_at)
                                                <flux:badge color="zinc" size="sm" class="ml-1">{{ __('Lokal dihapus') }}</flux:badge>
                                            @else
                                                <form method="POST" action="{{ route('admin.attendance.selfie.delete-local', $record) }}" class="mt-2" onsubmit="return confirm('{{ __('Hapus foto lokal setelah backup Drive terverifikasi?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 underline dark:text-red-400">{{ __('Hapus lokal') }}</button>
                                                </form>
                                            @endif
                                        @else
                                            @if ($record->drive_backup_status === 'failed')
                                                <flux:badge color="red" size="sm" class="ml-1">{{ __('Backup gagal') }}</flux:badge>
                                            @endif
                                            <form method="POST" action="{{ route('admin.attendance.selfie.backup', $record) }}" class="mt-2">
                                                @csrf
                                                <button type="submit" class="text-xs text-blue-600 underline dark:text-blue-400">{{ __('Backup ke Drive') }}</button>
                                            </form>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if (in_array($record->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true) && $record->employee->user_id !== auth()->id())
                                        <details class="min-w-56">
                                            <summary class="cursor-pointer text-sm font-medium text-blue-600 underline dark:text-blue-400">{{ __('Koreksi timestamp') }}</summary>
                                            <form method="POST" action="{{ route('admin.kehadiran.correct-timestamp', $record) }}" class="mt-2 grid min-w-64 gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                                                @csrf
                                                <label class="grid gap-1 text-xs">
                                                    <span>{{ __('Waktu efektif') }}</span>
                                                    <input type="datetime-local" name="recorded_at_local" required value="{{ $record->effectiveRecordedAtLocal()->format('Y-m-d\\TH:i') }}" class="rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                                </label>
                                                <label class="grid gap-1 text-xs">
                                                    <span>{{ __('Alasan wajib') }}</span>
                                                    <textarea name="reason" required minlength="5" maxlength="2000" rows="2" class="rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-900"></textarea>
                                                </label>
                                                <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">{{ __('Simpan koreksi') }}</button>
                                                <p class="text-xs text-zinc-500">{{ __('Timestamp bukti asli tidak diubah; perubahan dicatat di audit log.') }}</p>
                                            </form>
                                        </details>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            @if ($this->notYetPresent->isEmpty())
                                <flux:table.row>
                                    <flux:table.cell colspan="7">
                                        <x-admin.empty-state :message="__('Tidak ada catatan pada filter ini.')" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforelse

                        @foreach ($this->notYetPresent as $employee)
                            <flux:table.row :key="'not-present-'.$employee->id">
                                <flux:table.cell class="whitespace-nowrap">—</flux:table.cell>
                                <flux:table.cell>{{ $employee->user->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="amber">{{ __('Belum ada absensi') }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                                <flux:table.cell>—</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
        <div
            x-show="selfiePreviewOpen"
            x-cloak
            x-transition.opacity
            x-on:click.self="selfiePreviewOpen = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Pratinjau foto absensi') }}"
        >
            <div class="flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <h2 class="font-semibold">{{ __('Foto absensi') }}</h2>
                    <button type="button" x-on:click="selfiePreviewOpen = false" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Tutup pratinjau') }}">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <div class="min-h-0 overflow-auto bg-zinc-950 p-2">
                    <img :src="selfiePreviewUrl" alt="{{ __('Foto absensi ukuran penuh') }}" class="mx-auto max-h-[75dvh] w-auto max-w-full object-contain">
                </div>
                <div class="flex justify-end border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <a :href="selfieDownloadUrl" class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                        <flux:icon name="arrow-down-tray" class="size-4" />
                        {{ __('Unduh foto') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
