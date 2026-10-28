<?php

use App\Actions\RecordAttendance;
use App\Enums\LeaveRequestStatus;
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

    /**
     * boot() berjalan di SETIAP request Livewire (bukan hanya saat halaman dibuka),
     * jadi pengecekan role tetap berlaku untuk request update.
     */
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

    /** @return array{0: string, 1: string} rentang tanggal yang valid (from <= to) */
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

        return AttendanceRecord::query()
            ->with(['employee.user', 'location', 'project'])
            ->whereBetween('attendance_date', [$from, $to])
            ->when($this->employeeId !== '', fn ($query) => $query->where('employee_id', (int) $this->employeeId))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    /**
     * Pegawai aktif yang belum punya catatan absensi pada tanggal tersebut,
     * serta tidak memiliki permohonan izin/cuti/sakit yang telah disetujui
     * maupun koreksi status harian.
     */
    #[Computed]
    public function notYetPresent()
    {
        [$from, $to] = $this->range();

        if ($from !== $to || $this->employeeId !== '') {
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
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Kehadiran Pegawai') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Pantau catatan absensi semua pegawai.') }}</flux:text>
        </div>

        @php($records = $this->records)

        {{-- Filter --}}
        <div class="grid gap-4 rounded-xl border border-neutral-200 p-5 sm:grid-cols-3 dark:border-neutral-700">
            <flux:input type="date" wire:model.live="dateFrom" :label="__('Dari tanggal')" />
            <flux:input type="date" wire:model.live="dateTo" :label="__('Sampai tanggal')" />
            <flux:select wire:model.live="employeeId" :label="__('Pegawai')">
                <flux:select.option value="">{{ __('Semua pegawai') }}</flux:select.option>
                @foreach ($this->employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->user->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Belum ada absensi (khusus filter satu hari, semua pegawai) --}}
        @if ($this->notYetPresent->isNotEmpty())
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-700/60 dark:bg-amber-950/30">
                <flux:heading level="2">{{ __('Belum ada absensi') }} ({{ $this->notYetPresent->count() }})</flux:heading>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->notYetPresent as $employee)
                        <flux:badge color="amber">{{ $employee->user->name }}</flux:badge>
                    @endforeach
                </div>
                <flux:text class="mt-3 text-sm">{{ __('Tidak mencakup pegawai yang izin/cutinya telah disetujui.') }}</flux:text>
            </div>
        @endif

        {{-- Tabel catatan --}}
        <div>
            <flux:text class="mb-2 text-sm">{{ $records->total() }} {{ __('catatan') }}</flux:text>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Waktu') }}</th>
                            <th class="px-4 py-3">{{ __('Pegawai') }}</th>
                            <th class="px-4 py-3">{{ __('Jenis') }}</th>
                            <th class="px-4 py-3">{{ __('Lokasi kerja') }}</th>
                            <th class="px-4 py-3">{{ __('GPS') }}</th>
                            <th class="px-4 py-3">{{ __('Foto') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($records as $record)
                            <tr wire:key="record-{{ $record->id }}">
                                <td class="whitespace-nowrap px-4 py-3">{{ $record->recordedAtLocal()->format('d M Y, H:i T') }}</td>
                                <td class="px-4 py-3">{{ $record->employee->user->name }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge :color="$record->type === \App\Enums\AttendanceType::ClockIn ? 'green' : 'blue'">
                                        {{ $record->type->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $record->work_type->label() }}
                                    @if ($record->project)
                                        <span class="text-zinc-500">&middot; {{ $record->project->code }}</span>
                                    @elseif ($record->location)
                                        <span class="text-zinc-500">&middot; {{ $record->location->name }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($record->latitude !== null)
                                        @if ($record->accuracy_m !== null)
                                            &plusmn;{{ round((float) $record->accuracy_m) }} m
                                        @endif
                                        <a href="https://www.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-blue-600 underline dark:text-blue-400">{{ __('Peta') }}</a>
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($record->selfie_path)
                                        <a href="{{ route('admin.attendance.selfie', $record) }}" target="_blank" rel="noopener" class="text-blue-600 underline dark:text-blue-400">{{ __('Lihat') }}</a>
                                        @if ($record->selfie_source === \App\Enums\SelfieSource::Upload)
                                            <flux:badge color="amber" size="sm" class="ml-1">{{ __('Unggahan') }}</flux:badge>
                                        @endif
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('Tidak ada catatan pada filter ini.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
</section>
