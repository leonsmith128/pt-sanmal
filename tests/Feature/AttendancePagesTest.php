<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Support\AttendanceSelfie;
use Illuminate\Support\Facades\Storage;

function userWithRole(UserRole $role, bool $withEmployee = true): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    if ($withEmployee) {
        Employee::query()->create([
            'user_id' => $user->id,
            'joined_at' => now()->toDateString(),
        ]);
    }

    return $user->fresh();
}

test('guests are redirected from attendance pages to login', function () {
    $this->get(route('attendance'))->assertRedirect(route('login'));
    $this->get(route('attendance-history.index'))->assertRedirect(route('login'));
    $this->get(route('attendance-history.export'))->assertRedirect(route('login'));
    $this->get(route('attendance-history.selfie', 1))->assertRedirect(route('login'));
    $this->get(route('admin.kehadiran'))->assertRedirect(route('login'));
});

test('employees can visit the attendance clock page', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee(__('Absensi'))
        ->assertDontSee(__('Kehadiran Pegawai'));
});

test('employees can visit their own attendance history', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance-history.index'))
        ->assertOk()
        ->assertSee(__('Riwayat Absensi'))
        ->assertSee(__('Ekspor Excel (CSV)'))
        ->assertSee(__('Belum ada catatan absensi'));
});

test('employees are forbidden from the admin attendance page', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('admin.kehadiran'))->assertForbidden();
});

test('admins can visit the admin attendance page', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.kehadiran'))
        ->assertOk()
        ->assertSee(__('Kehadiran Pegawai'));
});

test('employees can download only their own private attendance photo', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $owner = userWithRole(UserRole::Employee);
    $otherEmployee = userWithRole(UserRole::Employee);
    $path = 'selfies/'.$owner->employee->id.'/2026/10/attendance.jpg';
    Storage::disk(AttendanceSelfie::DISK)->put($path, 'private-photo-content');

    $record = AttendanceRecord::create([
        'employee_id' => $owner->employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
        'selfie_path' => $path,
        'selfie_source' => 'camera',
    ]);

    $this->actingAs($owner)
        ->get(route('attendance-history.selfie.download', $record))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($otherEmployee)
        ->get(route('attendance-history.selfie.download', $record))
        ->assertForbidden();
});

test('admins can download private attendance photos', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $owner = userWithRole(UserRole::Employee);
    $admin = userWithRole(UserRole::Admin);
    $path = 'selfies/'.$owner->employee->id.'/2026/10/attendance.jpg';
    Storage::disk(AttendanceSelfie::DISK)->put($path, 'private-photo-content');

    $record = AttendanceRecord::create([
        'employee_id' => $owner->employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
        'selfie_path' => $path,
        'selfie_source' => 'camera',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance.selfie.download', $record))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('attendance camera controls reflect camera availability and video readiness', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee('facingMode ===', false)
        ->assertSee('! canSwitchCamera', false)
        ->assertSee('! videoReady', false)
        ->assertSee('error && active', false);
});
test('attendance camera preserves the complete photo frame without cropping', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee('object-contain', false)
        ->assertSee('watermarkPreview', false);
});
