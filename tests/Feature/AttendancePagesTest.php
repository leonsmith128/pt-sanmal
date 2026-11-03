<?php

use App\Enums\AttendanceType;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Support\AttendanceSelfie;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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
        ->assertDontSee(__('Kehadiran Pegawai'))
        ->assertSee('max-w-7xl', false);
});

test('employees can visit their own attendance history', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance-history.index'))
        ->assertOk()
        ->assertSee(__('Riwayat Absensi'))
        ->assertSee(__('Ekspor rekap harian (CSV)'))
        ->assertSee(__('Belum ada catatan absensi'));
});

test('attendance history date filters use shared controls and retain submitted values', function () {
    $employee = userWithRole(UserRole::Employee);

    $this->actingAs($employee)
        ->get(route('attendance-history.index', [
            'from' => '2026-11-01',
            'to' => '2026-11-03',
        ]))
        ->assertOk()
        ->assertSee('name="from"', false)
        ->assertSee('value="2026-11-01"', false)
        ->assertSee('name="to"', false)
        ->assertSee('value="2026-11-03"', false)
        ->assertSee(__('Terapkan filter'))
        ->assertSee(__('Reset'));
});

test('saved address snapshots are visible to employees and admins', function () {
    $employee = userWithRole(UserRole::Employee);
    $admin = userWithRole(UserRole::Admin);
    $address = 'Jl. Contoh No. 10, Makassar, Sulawesi Selatan';

    AttendanceRecord::create([
        'employee_id' => $employee->employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
        'latitude' => -5.1477,
        'longitude' => 119.4327,
        'accuracy_m' => 12,
        'address_snapshot' => $address,
    ]);

    $this->actingAs($employee)
        ->get(route('attendance-history.index'))
        ->assertOk()
        ->assertSee(__('Alamat tersimpan:'))
        ->assertSee($address);

    $this->actingAs($admin)
        ->get(route('admin.kehadiran'))
        ->assertOk()
        ->assertSee(__('Alamat tersimpan:'))
        ->assertSee($address);
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

test('admin attendance filters use a mobile-first responsive grid and full-width controls', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.kehadiran'))
        ->assertOk()
        ->assertSee('grid w-full min-w-0 grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6', false)
        ->assertSee('w-full min-w-0', false)
        ->assertSee('w-full sm:w-auto', false);
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
        ->assertSee('! locationSnapshotReady', false)
        ->assertSee('locationStatusMessage', false)
        ->assertSee('error && active', false)
        ->assertSee('$dispatch(\'attendance-camera-open\')', false)
        ->assertSee(__('Lanjutkan'));
});
test('attendance camera preserves the complete photo frame without cropping', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee('object-contain', false)
        ->assertSee('watermarkPreview', false);
});

test('employees receive an ungrouped sidebar and mobile header drawer without bottom navigation', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $response = $this->get(route('attendance'))
        ->assertOk()
        ->assertSee('data-flux-sidebar-toggle', false)
        ->assertSee('aria-label="Navigasi utama"', false)
        ->assertSee(__('Beranda'))
        ->assertSee(__('Absensi'))
        ->assertSee(__('Riwayat'))
        ->assertSee(__('Permohonan'))
        ->assertSee(__('Lembur'))
        ->assertDontSee(__('Aktivitas Pegawai'))
        ->assertDontSee('fixed inset-x-0 bottom-0', false)
        ->assertDontSee(__('Operasional'))
        ->assertDontSee(__('Master Data'))
        ->assertDontSee('data-test="sidebar-group-separator"', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[@data-flux-sidebar-item]')->length)->toBe(5);
});

test('admins retain personal employee navigation alongside grouped administration menus', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee(__('Absensi'))
        ->assertSee(__('Riwayat'))
        ->assertSee(__('Dashboard Pribadi'))
        ->assertSee(__('Permohonan Saya'))
        ->assertSee(__('Lembur Saya'))
        ->assertSee(__('Aktivitas Saya'))
        ->assertSee(__('Operasional'))
        ->assertSee(__('Persetujuan'))
        ->assertSee(__('Data Master'))
        ->assertSee(__('Sistem'))
        ->assertSee(__('Audit Log'))
        ->assertDontSee(__('Platform'))
        ->assertSee('aria-label="Navigasi utama"', false);
});

test('Flux main is a direct body child on the key admin and employee pages', function () {
    $admin = userWithRole(UserRole::Admin);
    $employee = userWithRole(UserRole::Employee);

    foreach ([route('dashboard'), route('admin.daily-attendance'), route('admin.kehadiran')] as $url) {
        $response = $this->actingAs($admin)->get($url)->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);

        expect($xpath->query('//body/*[@data-flux-main]')->length)->toBe(1);
    }

    $response = $this->actingAs($employee)->get(route('attendance'))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//body/*[@data-flux-main]')->length)->toBe(1)
        ->and($response->getContent())->toContain('inset')
        ->and($response->getContent())->not->toContain('pb-[calc(4rem+env(safe-area-inset-bottom))]');
});

test('admin sidebar hides brand and theme controls when collapsed', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.daily-attendance'))
        ->assertOk()
        ->assertSee('in-data-flux-sidebar-collapsed-desktop:hidden', false)
        ->assertSee('data-test="sidebar-theme-menu"', false);
});

test('admin sidebar collapse control stays visible and sidebar prevents horizontal overflow', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.daily-attendance'))
        ->assertOk()
        ->assertSee('overflow-x-hidden', false)
        ->assertSee('data-flux-sidebar-collapse', false);
});

test('collapsed admin sidebar keeps every navigation item visible as an icon', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $response = $this->get(route('admin.daily-attendance'))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[@data-flux-sidebar-item]')->length)->toBe(16)
        ->and(substr_count($response->getContent(), 'data-test="sidebar-group-separator"'))->toBe(5)
        ->and($response->getContent())->toContain('in-data-flux-sidebar-collapsed-desktop:not-in-data-flux-sidebar-group-dropdown:hidden')
        ->and($response->getContent())->toContain('data-flux-sidebar-item')
        ->and($response->getContent())->toContain('in-data-flux-sidebar-collapsed-desktop:hidden')
        ->and($response->getContent())->toContain('in-data-flux-sidebar-collapsed-desktop:block');
});

test('attendance status and overtime cards use a responsive two-column grid with overtime details modal', function () {
    $employee = userWithRole(UserRole::Employee);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
    ]);

    $overtime = OvertimeRequest::create([
        'employee_id' => $employee->employee->id,
        'session_date' => $clockIn->attendance_date->toDateString(),
        'clock_in_record_id' => $clockIn->id,
        'reason' => 'Penyelesaian pekerjaan kalibrasi.',
        'scheduled_start_at' => now('Asia/Makassar')->addHour(),
        'timezone' => 'Asia/Makassar',
        'state' => OvertimeState::AwaitingPhoto,
        'approval_status' => OvertimeApprovalStatus::Pending,
    ]);

    $this->actingAs($employee)
        ->get(route('attendance'))
        ->assertOk()
        ->assertSee('grid grid-cols-1 items-stretch gap-3 sm:grid-cols-2', false)
        ->assertSee('showOvertimeDetails('.$overtime->id.')', false)
        ->assertSee('wire:submit="requestOvertime"', false);

    Livewire::actingAs($employee)
        ->test('pages::employee.attendance')
        ->call('showOvertimeDetails', $overtime->id)
        ->assertSet('selectedOvertimeId', $overtime->id);
});

test('attendance action is presented before the historical date selector', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $html = $this->get(route('attendance'))
        ->assertOk()
        ->assertSee(__('Mulai absensi'))
        ->assertSee(__('Pilih tanggal untuk melihat riwayat.'))
        ->getContent();

    $actionPosition = strpos($html, 'wire:submit="confirm');
    $historyPosition = strpos($html, __('Pilih tanggal untuk melihat riwayat.'));

    expect($actionPosition)->not->toBeFalse()
        ->and($historyPosition)->not->toBeFalse()
        ->and($actionPosition)->toBeLessThan($historyPosition);
});
