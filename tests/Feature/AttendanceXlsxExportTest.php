<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Support\AttendanceSelfie;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

function xlsxExportAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

function xlsxExportEmployee(string $name, ?string $joinedAt = null): Employee
{
    $user = User::factory()->create(['name' => $name]);

    return Employee::query()->create(['user_id' => $user->id, 'joined_at' => $joinedAt]);
}

/** @return array{0: AttendanceRecord, 1: AttendanceRecord} */
function xlsxExportSession(Employee $employee, string $in, string $out, array $clockInExtra = []): array
{
    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $in,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => substr($in, 0, 10),
        ...$clockInExtra,
    ]);

    $clockOut = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $out,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => substr($out, 0, 10),
    ]);

    return [$clockIn, $clockOut];
}

it('defaults the admin export to the current month and names the file accordingly', function () {
    $today = Carbon::now('Asia/Makassar')->startOfDay();
    $period = $today->copy()->startOfMonth()->format('Ymd').'-'.$today->format('Ymd');

    $response = $this->actingAs(xlsxExportAdmin())->get(route('admin.kehadiran.export'));

    $xlsx = xlsxContents($response);

    expect($xlsx['sheets'])->toBe(['Ringkasan', 'Detail'])
        ->and($response->headers->get('content-disposition'))->toContain('sanmal_kehadiran_'.$period.'_')
        ->and($response->headers->get('cache-control'))->toContain('no-store');
});

it('skips days before the employee joined date in the daily recap export', function () {
    xlsxExportEmployee('Pegawai Baru Bergabung', '2026-11-03');

    $text = xlsxContents($this->actingAs(xlsxExportAdmin())
        ->get(route('admin.daily-attendance.export', ['month' => '2026-11'])))['text'];

    expect($text)
        ->toContain('Pegawai Baru Bergabung')
        ->toContain('2026-11-03')
        ->not->toContain('2026-11-02')
        ->not->toContain('2026-11-01');
});

it('totals only valid finalized overtime and formats durations as hours and minutes', function () {
    $worker = xlsxExportEmployee('Pegawai Lembur Final');
    $invalid = xlsxExportEmployee('Pegawai Lembur Tidak Valid');

    foreach ([$worker, $invalid] as $employee) {
        xlsxExportSession($employee, '2026-11-02 01:00:00', '2026-11-02 09:00:00');
    }

    OvertimeRequest::query()->create([
        'employee_id' => $worker->id,
        'session_date' => '2026-11-02',
        'scheduled_start_at' => '2026-11-02 10:00:00',
        'finalized_at' => '2026-11-02 13:00:00',
        'final_start_at' => '2026-11-02 10:00:00',
        'final_end_at' => '2026-11-02 12:30:00',
    ]);

    // Pasangan final terbalik (selesai sebelum mulai) tidak boleh dihitung sama sekali.
    OvertimeRequest::query()->create([
        'employee_id' => $invalid->id,
        'session_date' => '2026-11-02',
        'scheduled_start_at' => '2026-11-02 10:00:00',
        'finalized_at' => '2026-11-02 13:00:00',
        'final_start_at' => '2026-11-02 15:00:00',
        'final_end_at' => '2026-11-02 10:00:00',
    ]);

    $text = xlsxContents($this->actingAs(xlsxExportAdmin())->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02', 'to' => '2026-11-02',
    ])))['text'];

    expect($text)
        ->toContain('Total Lembur Final')
        ->toContain('2j 30m')
        ->not->toContain('5j 0m');
});

it('embeds a local selfie thumbnail, links the map, and records missing media as status only', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $image = imagecreatetruecolor(300, 200);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    Storage::disk(AttendanceSelfie::DISK)->put('selfies/1/masuk.png', $png);

    $employee = xlsxExportEmployee('Pegawai Foto Ekspor');
    xlsxExportSession($employee, '2026-11-02 01:00:00', '2026-11-02 09:00:00', [
        'selfie_path' => 'selfies/1/masuk.png',
        'latitude' => '-5.1476540',
        'longitude' => '119.4327310',
        'accuracy_m' => '12.50',
        'location_status' => 'captured',
        'address_snapshot' => 'Jl. Contoh No. 1, Makassar',
    ]);

    $xlsx = xlsxContents($this->actingAs(xlsxExportAdmin())->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02', 'to' => '2026-11-02',
    ])));

    expect($xlsx['media'])->toHaveCount(1)
        ->and($xlsx['xml'])->toContain('<hyperlink')
        ->and($xlsx['text'])
        ->toContain('Di-embed (120x120 px)')
        ->toContain('https://www.google.com/maps?q=-5.1476540,119.4327310')
        ->toContain('Jl. Contoh No. 1, Makassar')
        ->toContain('12.50')
        ->toContain('Tidak ada foto');
});

it('does not fail the export when the selfie file is missing from local storage', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $employee = xlsxExportEmployee('Pegawai Foto Hilang');
    xlsxExportSession($employee, '2026-11-02 01:00:00', '2026-11-02 09:00:00', [
        'selfie_path' => 'selfies/1/tidak-ada.png',
    ]);

    $xlsx = xlsxContents($this->actingAs(xlsxExportAdmin())->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02', 'to' => '2026-11-02',
    ])));

    expect($xlsx['media'])->toBeEmpty()
        ->and($xlsx['text'])->toContain('Tidak tersedia di penyimpanan lokal');
});

it('labels administrative data without a snapshot as not historical', function () {
    $employee = xlsxExportEmployee('Pegawai Tanpa Snapshot');
    xlsxExportSession($employee, '2026-11-02 01:00:00', '2026-11-02 09:00:00');

    $text = xlsxContents($this->actingAs(xlsxExportAdmin())->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02', 'to' => '2026-11-02',
    ])))['text'];

    // Tanpa Project/Location sama sekali, kolom sumber tetap kosong, bukan klaim snapshot.
    expect($text)->toContain('Sumber Data Administrasi')
        ->not->toContain('Snapshot saat absensi');
});
