<?php

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Admin')] class extends Component {
    public string $search = '';
    public string $employeeFilter = 'all';
    public string $period = 'month_to_date';
    public string $chartType = 'bar';
    public ?int $selectedEmployeeId = null;

    public function boot(): void
    {
        abort_unless(Auth::user()?->isManager(), 403);
    }

    private function today(): string
    {
        return now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString();
    }

    /** @return array{0: string, 1: string} */
    private function periodRange(): array
    {
        $today = Carbon::parse($this->today());

        return match ($this->period) {
            'day' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
        };
    }

    #[Computed]
    public function activeEmployees()
    {
        return Employee::query()
            ->with(['user:id,name,email,is_active', 'title:id,name'])
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->sortBy(fn ($employee) => $employee->user?->name ?? '')
            ->values();
    }

    /** Latest event today per employee; selfie/time/GPS are from the same record. */
    #[Computed]
    public function employeeActivity()
    {
        $employees = $this->activeEmployees;
        $latest = AttendanceRecord::query()
            ->with(['location:id,name', 'project:id,code,name'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('attendance_date', $this->today())
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get()
            ->unique('employee_id')
            ->keyBy('employee_id');

        $search = mb_strtolower(trim($this->search));

        return $employees->map(function ($employee) use ($latest) {
            $employee->setRelation('latestActivity', $latest->get($employee->id));
            return $employee;
        })->filter(function ($employee) use ($search) {
            $activity = $employee->getRelation('latestActivity');
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower($employee->user?->name ?? ''), $search)
                || str_contains(mb_strtolower($employee->employee_number ?? ''), $search);
            $matchesFilter = match ($this->employeeFilter) {
                'hadir' => $activity !== null && $activity->type === AttendanceType::ClockIn,
                'belum' => $activity === null,
                default => true,
            };

            return $matchesSearch && $matchesFilter;
        })->values();
    }

    #[Computed]
    public function summary(): array
    {
        $employees = $this->activeEmployees;
        $records = AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('attendance_date', $this->today())
            ->get(['employee_id', 'type'])
            ->groupBy('employee_id');

        $present = $records->filter(
            fn ($items) => $items->contains(fn ($item) => $item->type === AttendanceType::ClockIn->value)
        )->count();

        $pendingLeaves = Schema::hasTable('leave_requests')
            ? DB::table('leave_requests')->where('status', 'pending')->count()
            : 0;

        return [
            'employees' => $employees->count(),
            'present' => $present,
            'not_recorded' => max(0, $employees->count() - $present),
            'pending_leaves' => $pendingLeaves,
        ];
    }

    /**
     * Counts unique employees with Clock In per date, not the number of events.
     * Leave/sick/vacation categories must use the shared daily-status service once integrated.
     *
     * @return array<int, array{label: string, date: string, count: int}>
     */
    #[Computed]
    public function chartData(): array
    {
        [$from, $to] = $this->periodRange();
        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);
        $counts = AttendanceRecord::query()
            ->where('type', AttendanceType::ClockIn->value)
            ->whereBetween('attendance_date', [$from, $to])
            ->select('attendance_date')
            ->selectRaw('COUNT(DISTINCT employee_id) as total')
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->pluck('total', 'attendance_date');

        $days = [];
        for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
            $key = $date->toDateString();
            $days[] = [
                'label' => $this->period === 'year' ? $date->format('M') : $date->format('d M'),
                'date' => $key,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        if ($this->period === 'year') {
            $monthly = [];
            foreach ($days as $day) {
                $month = Carbon::parse($day['date'])->format('Y-m');
                if (! isset($monthly[$month])) {
                    $monthly[$month] = ['label' => Carbon::parse($day['date'])->format('M'), 'date' => $month, 'count' => 0];
                }
                $monthly[$month]['count'] += $day['count'];
            }
            return array_values($monthly);
        }

        return $days;
    }

    #[Computed]
    public function selectedEmployee()
    {
        return $this->selectedEmployeeId
            ? Employee::query()->with(['user:id,name,email,is_active', 'title:id,name'])->find($this->selectedEmployeeId)
            : null;
    }

    public function showEmployee(int $employeeId): void
    {
        abort_unless($this->activeEmployees->contains('id', $employeeId), 404);
        $this->selectedEmployeeId = $employeeId;
    }

    public function closeEmployee(): void
    {
        $this->selectedEmployeeId = null;
    }
}; ?>

<div wire:poll.20s class="w-full min-w-0">
    <div class="mx-auto flex w-full max-w-7xl min-w-0 flex-col gap-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">Dashboard Admin</flux:heading>
                <flux:text class="mt-1">Ringkasan absensi dan aktivitas pegawai hari ini.</flux:text>
            </div>
            <div class="flex items-center gap-2 text-sm text-zinc-500">
                <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                Diperbarui otomatis setiap 20 detik
            </div>
        </header>

        @php($summary = $this->summary)
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan absensi hari ini">
            <button type="button" wire:click="$set('employeeFilter', 'hadir')" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-left transition hover:border-emerald-400 dark:border-emerald-900 dark:bg-emerald-950/30">
                <span class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Hadir</span>
                <span class="mt-2 block text-3xl font-semibold text-emerald-950 dark:text-emerald-100">{{ $summary['present'] }}</span>
                <span class="mt-1 block text-xs text-emerald-800/80 dark:text-emerald-300/80">Pegawai unik dengan Clock In</span>
            </button>
            <button type="button" wire:click="$set('employeeFilter', 'belum')" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left transition hover:border-amber-400 dark:border-amber-900 dark:bg-amber-950/30">
                <span class="text-sm font-medium text-amber-800 dark:text-amber-300">Belum tercatat</span>
                <span class="mt-2 block text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ $summary['not_recorded'] }}</span>
                <span class="mt-1 block text-xs text-amber-800/80 dark:text-amber-300/80">Belum ada Clock In hari ini</span>
            </button>
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                <span class="text-sm font-medium text-sky-800 dark:text-sky-300">Pegawai aktif</span>
                <span class="mt-2 block text-3xl font-semibold text-sky-950 dark:text-sky-100">{{ $summary['employees'] }}</span>
                <span class="mt-1 block text-xs text-sky-800/80 dark:text-sky-300/80">Dengan profil pegawai aktif</span>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                <span class="text-sm font-medium text-violet-800 dark:text-violet-300">Pengajuan menunggu</span>
                <span class="mt-2 block text-3xl font-semibold text-violet-950 dark:text-violet-100">{{ $summary['pending_leaves'] }}</span>
                <span class="mt-1 block text-xs text-violet-800/80 dark:text-violet-300/80">Pengajuan izin/sakit/cuti</span>
            </div>
        </section>

        <section class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(18rem,1fr)]">
            <div class="min-w-0 rounded-2xl border border-zinc-200 p-4 sm:p-5 dark:border-zinc-700">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading level="2" size="lg">Tren kehadiran</flux:heading>
                        <flux:text class="mt-1 text-sm">Jumlah pegawai unik yang melakukan Clock In.</flux:text>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <select wire:model.live="period" aria-label="Periode grafik" class="min-w-0 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                            <option value="month_to_date">Bulanan berjalan</option>
                            <option value="day">Harian</option>
                            <option value="week">Mingguan</option>
                            <option value="month">Bulanan</option>
                            <option value="year">Tahunan</option>
                        </select>
                        <select wire:model.live="chartType" aria-label="Tipe grafik" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                            <option value="bar">Batang</option>
                            <option value="line">Garis</option>
                        </select>
                    </div>
                </div>

                @php
                    $chart = $this->chartData;
                    $maxValue = max(1, collect($chart)->max('count') ?? 0);
                    $chartWidth = 640;
                    $chartHeight = 190;
                    $padding = 12;
                    $pointCount = max(1, count($chart));
                    $points = collect($chart)->values()->map(function ($item, $index) use ($maxValue, $chartWidth, $chartHeight, $padding, $pointCount) {
                        $x = $pointCount === 1 ? $chartWidth / 2 : $padding + ($index * ($chartWidth - 2 * $padding) / ($pointCount - 1));
                        $y = $chartHeight - $padding - (($item['count'] / $maxValue) * ($chartHeight - 2 * $padding));
                        return round($x, 2).','.round($y, 2);
                    })->implode(' ');
                @endphp

                @if (collect($chart)->sum('count') === 0)
                    <div class="mt-5 flex min-h-48 items-center justify-center rounded-xl bg-zinc-50 px-4 text-center text-sm text-zinc-500 dark:bg-zinc-900">Belum ada data Clock In pada periode ini.</div>
                @elseif ($chartType === 'line')
                    <div class="mt-5 min-w-0">
                        <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Grafik garis jumlah pegawai clock-in" class="h-48 w-full overflow-visible">
                            <line x1="0" y1="{{ $chartHeight - $padding }}" x2="{{ $chartWidth }}" y2="{{ $chartHeight - $padding }}" stroke="currentColor" stroke-opacity=".15" />
                            <polyline points="{{ $points }}" fill="none" stroke="#0d9488" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            @foreach ($chart as $index => $item)
                                @php
                                    $x = count($chart) === 1 ? $chartWidth / 2 : $padding + ($index * ($chartWidth - 2 * $padding) / (count($chart) - 1));
                                    $y = $chartHeight - $padding - (($item['count'] / $maxValue) * ($chartHeight - 2 * $padding));
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#0d9488" />
                            @endforeach
                        </svg>
                        <div class="mt-2 flex flex-wrap justify-between gap-x-2 gap-y-1 text-xs text-zinc-500">
                            @foreach ($chart as $item)
                                <span>{{ $item['label'] }}: {{ $item['count'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-5 grid min-h-48 grid-cols-7 items-end gap-2 sm:grid-cols-10 lg:grid-cols-12">
                        @foreach ($chart as $item)
                            <div wire:key="chart-{{ $item['date'] }}" class="flex min-w-0 flex-col items-center justify-end gap-2">
                                <span class="text-xs text-zinc-500">{{ $item['count'] }}</span>
                                <div class="w-full max-w-8 rounded-t-md bg-teal-600 transition-all" style="height: {{ max(4, (int) (($item['count'] / $maxValue) * 140)) }}px"></div>
                                <span class="max-w-full truncate text-[10px] text-zinc-500">{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="mt-4 text-xs text-zinc-500">Grafik saat ini menghitung Clock In per pegawai unik. Rekap izin, sakit, dan cuti perlu dihubungkan ke service status harian setelah perubahan terkait digabungkan.</p>
            </div>

            <div class="min-w-0 rounded-2xl border border-zinc-200 p-4 sm:p-5 dark:border-zinc-700">
                <flux:heading level="2" size="lg">Perlu ditindaklanjuti</flux:heading>
                <flux:text class="mt-1 text-sm">Pintasan untuk pekerjaan administrasi.</flux:text>
                <div class="mt-4 space-y-3">
                    @if ($summary['pending_leaves'] > 0)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 p-3 text-sm dark:bg-amber-950/30">
                            <span>Pengajuan izin/sakit/cuti menunggu</span><flux:badge color="amber">{{ $summary['pending_leaves'] }}</flux:badge>
                        </div>
                        <flux:text class="text-xs text-zinc-500">Alur persetujuan akan ditautkan setelah modul pengajuan terintegrasi.</flux:text>
                    @endif
                    <a href="{{ route('admin.kehadiran') }}" class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 p-3 text-sm hover:bg-zinc-100 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <span>Lihat semua catatan absensi</span><flux:icon name="arrow-up-right" variant="micro" />
                    </a>
                    @if ($summary['pending_leaves'] === 0)
                        <flux:text class="text-sm text-zinc-500">Tidak ada pengajuan yang menunggu berdasarkan data saat ini.</flux:text>
                    @endif
                </div>
            </div>
        </section>

        <section class="min-w-0 rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-zinc-700">
                <div>
                    <flux:heading level="2" size="lg">Aktivitas absensi hari ini</flux:heading>
                    <flux:text class="mt-1 text-sm">Aktivitas terbaru hari ini untuk setiap pegawai aktif.</flux:text>
                </div>
                <div class="flex min-w-0 flex-col gap-2 sm:flex-row">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau nomor pegawai" aria-label="Cari pegawai" class="min-w-0 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm sm:w-64 dark:border-zinc-600 dark:bg-zinc-900" />
                    <select wire:model.live="employeeFilter" aria-label="Filter aktivitas pegawai" class="min-w-0 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                        <option value="all">Semua pegawai</option><option value="hadir">Sudah Clock In</option><option value="belum">Belum ada absensi</option>
                    </select>
                </div>
            </div>

            @php($activities = $this->employeeActivity)
            @if ($activities->isEmpty())
                <div class="p-8 text-center">
                    <flux:icon name="calendar-clock" class="mx-auto size-8 text-zinc-400" />
                    <flux:heading level="3" class="mt-3">Tidak ada aktivitas yang cocok</flux:heading>
                    <flux:text class="mt-1 text-sm">Belum ada absensi hari ini atau filter pencarian tidak menemukan pegawai.</flux:text>
                </div>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($activities as $employee)
                        @php($activity = $employee->getRelation('latestActivity'))
                        <li wire:key="employee-activity-{{ $employee->id }}" class="flex min-w-0 items-start gap-3 p-4 sm:items-center sm:gap-4 sm:p-5">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ \Illuminate\Support\Str::initials($employee->user->name) }}</div>
                            <div class="min-w-0 flex-1">
                                <button type="button" wire:click="showEmployee({{ $employee->id }})" class="max-w-full truncate text-left font-semibold text-zinc-900 underline-offset-4 hover:underline dark:text-white">{{ $employee->user->name }}</button>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-zinc-500">
                                    @if ($activity)
                                        <span>{{ $activity->recordedAtLocal()->format('H:i T') }}</span><span aria-hidden="true">&middot;</span><span>{{ $activity->type->label() }}</span>
                                        @if ($activity->project)<span aria-hidden="true">&middot;</span><span>{{ $activity->project->code }} - {{ $activity->project->name }}</span>
                                        @elseif ($activity->location)<span aria-hidden="true">&middot;</span><span>{{ $activity->location->name }}</span>@endif
                                    @else
                                        <span class="italic">Belum ada absensi hari ini</span>
                                    @endif
                                </div>
                                @if ($activity && $activity->latitude !== null && $activity->longitude !== null)
                                    <a class="mt-1 inline-block text-xs text-teal-700 underline dark:text-teal-400" href="https://www.google.com/maps?q={{ $activity->latitude }},{{ $activity->longitude }}" target="_blank" rel="noopener noreferrer">Lihat lokasi di Google Maps</a>
                                @endif
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                @if ($activity)
                                    <flux:badge :color="$activity->type === AttendanceType::ClockIn ? 'green' : 'blue'">{{ $activity->type->label() }}</flux:badge>
                                    @if ($activity->selfie_path)<a href="{{ route('admin.attendance.selfie', $activity) }}" target="_blank" rel="noopener noreferrer" class="text-xs text-teal-700 underline dark:text-teal-400">Lihat selfie</a>@endif
                                @else
                                    <flux:badge color="zinc">Belum tercatat</flux:badge>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @if ($this->selectedEmployee)
        @php($profile = $this->selectedEmployee)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" role="presentation" wire:click.self="closeEmployee">
            <section role="dialog" aria-modal="true" aria-labelledby="employee-profile-title" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div><flux:heading id="employee-profile-title" size="lg">{{ $profile->user->name }}</flux:heading><flux:text class="mt-1 text-sm">Profil pegawai</flux:text></div>
                    <button type="button" wire:click="closeEmployee" aria-label="Tutup profil" class="rounded-lg p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon name="x-mark" /></button>
                </div>
                <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-zinc-500">Nomor pegawai</dt><dd class="mt-1 text-sm">{{ $profile->employee_number ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Jabatan</dt><dd class="mt-1 text-sm">{{ $profile->title?->name ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Email</dt><dd class="mt-1 break-words text-sm">{{ $profile->user->email }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Telepon</dt><dd class="mt-1 text-sm">{{ $profile->phone ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Bergabung</dt><dd class="mt-1 text-sm">{{ $profile->joined_at?->format('d M Y') ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Status akun</dt><dd class="mt-1 text-sm">{{ $profile->user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end"><flux:button wire:click="closeEmployee" variant="primary">Tutup</flux:button></div>
            </section>
        </div>
    @endif
</div>
