<?php

namespace Database\Seeders;

use App\Enums\AttendanceType;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceDemoSeeder extends Seeder
{
    private const TIMEZONE = 'Asia/Makassar';

    private const PERIOD_START = '2026-05-01';

    private const PERIOD_END = '2026-10-31';

    public function run(): void
    {
        if (! app()->environment('local') && ! app()->environment('testing')) {
            return;
        }

        DB::transaction(function (): void {
            $office = Location::query()->where('name', 'Kantor PT SanMal Calibration')->first();
            $site = Location::query()->where('name', 'Lokasi Proyek Contoh')->first();
            $project = Project::query()->where('code', 'PRJ-001')->first();

            if ($office === null || $site === null || $project === null) {
                throw new RuntimeException('Jalankan DevDataSeeder terlebih dahulu sebelum AttendanceDemoSeeder.');
            }

            $demoEmails = [
                'pegawai1@sanmal.test',
                'pegawai2@sanmal.test',
                ...array_map(
                    fn (int $number): string => sprintf('pegawai%02d@sanmal.test', $number),
                    range(3, 20),
                ),
            ];

            $employees = Employee::query()
                ->with(['user', 'title'])
                ->whereHas('user', fn ($query) => $query->whereIn('email', $demoEmails))
                ->get();

            foreach ($employees as $employee) {
                // Dua akun lama dibuat oleh DevDataSeeder dengan joined_at saat seeder dijalankan.
                // Hanya profil demo yang belum memiliki histori yang diselaraskan ke tanggal tetap.
                if (
                    in_array($employee->user->email, ['pegawai1@sanmal.test', 'pegawai2@sanmal.test'], true)
                    && $employee->joined_at?->toDateString() > self::PERIOD_START
                    && ! $employee->attendanceRecords()->exists()
                ) {
                    $employee->forceFill(['joined_at' => '2025-01-06'])->save();
                    $employee->refresh();
                }

                $joinedAt = CarbonImmutable::parse(
                    $employee->joined_at?->toDateString() ?? self::PERIOD_START,
                    self::TIMEZONE,
                );
                $start = CarbonImmutable::parse(self::PERIOD_START, self::TIMEZONE);
                $end = CarbonImmutable::parse(self::PERIOD_END, self::TIMEZONE);

                if ($joinedAt->gt($start)) {
                    $start = $joinedAt;
                }

                $isFieldRole = str_contains(
                    mb_strtolower($employee->title?->name ?? ''),
                    'teknisi',
                );

                for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                    $dayOfWeek = $date->dayOfWeek;

                    // Pola utama Senin-Jumat. Sebagian teknisi mendapat jadwal Sabtu sesekali.
                    if ($dayOfWeek === CarbonImmutable::SUNDAY) {
                        continue;
                    }

                    $dailySeed = crc32($employee->id.'|'.$date->toDateString());
                    $isSaturday = $dayOfWeek === CarbonImmutable::SATURDAY;

                    if ($isSaturday && (! $isFieldRole || $dailySeed % 100 >= 16)) {
                        continue;
                    }

                    // Hari tanpa event mewakili variasi ketidakhadiran, bukan data yang dipaksakan lengkap.
                    if ($dailySeed % 100 < 4) {
                        continue;
                    }

                    $onsite = $isFieldRole && (
                        ($isSaturday && $dailySeed % 100 < 75)
                        || (! $isSaturday && $dailySeed % 100 < 24)
                    );

                    $location = $onsite ? $site : $office;
                    $workType = $onsite ? WorkType::Onsite : WorkType::Office;
                    $arrivalMinute = $dailySeed % 100 < 18
                        ? 8 * 60 + 5 + (int) (($dailySeed >> 4) % 21)
                        : 7 * 60 + 45 + (int) (($dailySeed >> 5) % 21);

                    $departureMinute = 17 * 60 + (int) (($dailySeed >> 7) % 26);

                    // Sesekali ada hari kerja lebih panjang, tanpa mengarang persetujuan lembur formal.
                    if ($dailySeed % 100 >= 94) {
                        $departureMinute = 18 * 60 + (int) (($dailySeed >> 9) % 46);
                    }

                    $clockInAt = $date->startOfDay()->addMinutes($arrivalMinute);
                    $clockIn = $this->firstOrCreateEvent(
                        $employee,
                        AttendanceType::ClockIn,
                        $workType,
                        $location,
                        $onsite ? $project : null,
                        $clockInAt,
                    );

                    // Sedikit sesi tidak lengkap sengaja dipertahankan untuk menguji deteksi anomali.
                    if (
                        ($employee->employee_number === 'EMP-004' && $date->toDateString() === '2026-06-15')
                        || $dailySeed % 1700 === 0
                    ) {
                        continue;
                    }

                    $clockOutAt = $date->startOfDay()->addMinutes($departureMinute);
                    $this->firstOrCreateEvent(
                        $employee,
                        AttendanceType::ClockOut,
                        $workType,
                        $location,
                        $onsite ? $project : null,
                        $clockOutAt,
                        $clockIn->id,
                    );
                }
            }
        });
    }

    private function firstOrCreateEvent(
        Employee $employee,
        AttendanceType $type,
        WorkType $workType,
        Location $location,
        ?Project $project,
        CarbonImmutable $localTime,
        ?int $sessionAnchorId = null,
    ): AttendanceRecord {
        $recordedAt = $localTime->utc();

        $attributes = [
            'employee_id' => $employee->id,
            'type' => $type,
            'recorded_at' => $recordedAt,
        ];

        $client = $project?->client;

        return AttendanceRecord::query()->firstOrCreate($attributes, [
            'session_anchor_id' => $sessionAnchorId,
            'source' => 'attendance',
            'work_type' => $workType,
            'location_id' => $location->id,
            'project_id' => $project?->id,
            'captured_at' => $recordedAt,
            'timezone' => self::TIMEZONE,
            'timezone_source' => $workType === WorkType::Office ? 'office_fixed' : 'gps_resolved',
            'attendance_date' => $localTime->toDateString(),
            'client_code_snapshot' => $client?->code,
            'client_name_snapshot' => $client?->name,
            'project_code_snapshot' => $project?->code,
            'project_name_snapshot' => $project?->name,
            'location_name_snapshot' => $location->name,
            'snapshot_source' => 'attendance',
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'accuracy_m' => 8 + (crc32($employee->id.'|'.$localTime->toDateTimeString()) % 2800) / 100,
            'location_status' => 'captured',
            'address_snapshot' => $location->address,
            'notes' => 'Data simulasi development; bukan bukti absensi aktual.',
        ]);
    }
}
