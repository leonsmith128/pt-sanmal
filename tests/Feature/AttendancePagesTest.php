<?php

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Support\AttendanceSelfie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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
        ->assertSee('Selamat datang')
        ->assertSee('text-xl font-bold tracking-tight', false)
        ->assertSee('size-14', false)
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
        ->assertSee(__('Absen Masuk'));
});
test('attendance page renders the shared full-size photo preview when a selfie is available', function () {
    $employee = userWithRole(UserRole::Employee);

    $this->actingAs($employee)
        ->get(route('attendance'))
        ->assertOk()
        ->assertSee('watermarkPreview', false)
        ->assertSee('flex w-full items-center gap-2 pt-1', false)
        ->assertDontSee(__('Lihat foto ukuran penuh'));

    Livewire::actingAs($employee)
        ->test('pages::employee.attendance')
        ->set('selfie', UploadedFile::fake()->image('attendance.jpg'))
        ->assertSee('previewOpen', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('fixed inset-0 z-50 flex items-center justify-center', false)
        ->assertSee('x-on:click.self="previewOpen = false"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('max-h-[calc(100dvh-7rem)]', false)
        ->assertSee('download="foto-absensi.', false)
        ->assertSee('max-w-[calc(100vw-3.5rem)] rounded-lg object-contain', false);
});

test('attendance photo preview provides a download link for the captured temporary photo', function () {
    $employee = userWithRole(UserRole::Employee);

    Livewire::actingAs($employee)
        ->test('pages::employee.attendance')
        ->set('selfie', UploadedFile::fake()->image('attendance.jpg'))
        ->assertSee('download="foto-absensi.', false)
        ->assertSee(__('Unduh'));
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

test('inline sidebar uses its dedicated surface while the inset main uses the page surface', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.daily-attendance'))
        ->assertOk()
        ->assertSee('bg-sanmal-surface-sidebar dark:bg-sanmal-surface-page-dark', false)
        ->assertSee('bg-sanmal-surface-page p-0', false)
        ->assertDontSee('border-e border-sanmal-border', false);
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

    expect($xpath->query('//*[@data-flux-sidebar-item]')->length)->toBe(15)
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

test('attendance page orders calendar, time summary, status cards, and one attendance form with its action', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $html = $this->get(route('attendance'))
        ->assertOk()
        ->assertSee('wire:click="openMonthlyCalendar"', false)
        ->assertSee(__('Total kerja'))
        ->assertSee(__('Status kehadiran'))
        ->assertSee(__('Lembur'))
        ->assertSee('id="attendance-form"', false)
        ->assertSee(__('Absen Masuk'))
        ->getContent();

    $calendarPosition = strpos($html, 'wire:click="openMonthlyCalendar"');
    $summaryPosition = strpos($html, __('Total kerja'));
    $statusPosition = strpos($html, __('Status kehadiran'));
    $formPosition = strpos($html, 'id="attendance-form"');
    $actionPosition = strpos($html, __('Absen Masuk'), $formPosition);
    $formClosePosition = strpos($html, '</form>', $formPosition);

    expect($calendarPosition)->not->toBeFalse()
        ->and($summaryPosition)->not->toBeFalse()
        ->and($statusPosition)->not->toBeFalse()
        ->and($formPosition)->not->toBeFalse()
        ->and($actionPosition)->not->toBeFalse()
        ->and($formClosePosition)->not->toBeFalse()
        ->and($calendarPosition)->toBeLessThan($summaryPosition)
        ->and($summaryPosition)->toBeLessThan($statusPosition)
        ->and($statusPosition)->toBeLessThan($formPosition)
        ->and($formPosition)->toBeLessThan($actionPosition)
        ->and($actionPosition)->toBeLessThan($formClosePosition);
});

test('attendance calendar opens a monthly grid and synchronizes the selected date back to the week', function () {
    $employee = userWithRole(UserRole::Employee);

    $component = Livewire::actingAs($employee)
        ->test('pages::employee.attendance')
        ->assertSee('wire:click="openMonthlyCalendar"', false)
        ->assertSee('aria-controls="attendance-month-calendar-dialog"', false)
        ->set('selectedDate', '2026-11-18')
        ->call('openMonthlyCalendar')
        ->assertSet('showMonthlyCalendar', true)
        ->assertSet('calendarMonth', '2026-11-01');

    expect(substr_count($component->html(), 'attendance-month-day-'))->toBe(42);

    $component
        ->call('shiftCalendarMonth', 1)
        ->assertSet('calendarMonth', '2026-12-01')
        ->call('selectCalendarDate', '2026-12-02')
        ->assertSet('selectedDate', '2026-12-02')
        ->assertSet('calendarMonth', '2026-12-01')
        ->assertSet('showMonthlyCalendar', false);
});

test('attendance history defaults to the current WITA month through today', function () {
    $this->travelTo(Carbon::parse('2026-11-04 12:00:00', 'Asia/Makassar'));

    $employee = userWithRole(UserRole::Employee);

    foreach (['2026-10-31', '2026-11-03', '2026-11-05'] as $date) {
        AttendanceRecord::create([
            'employee_id' => $employee->employee->id,
            'type' => AttendanceType::ClockIn,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse($date.' 08:00:00', 'Asia/Makassar')->utc(),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => $date,
        ]);
    }

    $this->actingAs($employee)
        ->get(route('attendance-history.index'))
        ->assertOk()
        ->assertSee('name="from"', false)
        ->assertSee('value="2026-11-01"', false)
        ->assertSee('name="to"', false)
        ->assertSee('value="2026-11-04"', false)
        ->assertViewHas('filters', fn (array $filters): bool => $filters['from'] === '2026-11-01'
            && $filters['to'] === '2026-11-04');
});

test('attendance history paginates by date groups and keeps every event for a date together', function () {
    $this->travelTo(Carbon::parse('2026-11-20 12:00:00', 'Asia/Makassar'));

    $employee = userWithRole(UserRole::Employee);

    for ($offset = 0; $offset < 11; $offset++) {
        $date = Carbon::parse('2026-11-01')->addDays($offset)->toDateString();

        $clockIn = AttendanceRecord::create([
            'employee_id' => $employee->employee->id,
            'type' => AttendanceType::ClockIn,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse($date.' 08:00:00', 'Asia/Makassar')->utc(),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => $date,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->employee->id,
            'session_anchor_id' => $clockIn->id,
            'type' => AttendanceType::ClockOut,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse($date.' 17:00:00', 'Asia/Makassar')->utc(),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => $date,
        ]);
    }

    $this->actingAs($employee)
        ->get(route('attendance-history.index', [
            'from' => '2026-11-01',
            'to' => '2026-11-11',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertViewHas('dateGroups', function ($groups): bool {
            $lastPageGroup = $groups->first();

            return $groups->total() === 11
                && $groups->perPage() === 10
                && $groups->count() === 1
                && $lastPageGroup['date'] === '2026-11-01'
                && $lastPageGroup['events']->count() === 2
                && $lastPageGroup['events']->first()->type === AttendanceType::ClockIn
                && $lastPageGroup['events']->last()->type === AttendanceType::ClockOut;
        })
        ->assertViewHas('summary', fn (array $summary): bool => $summary['present_days'] === 11
            && $summary['complete_days'] === 11
            && $summary['needs_review_days'] === 0);
});

test('attendance history summary respects administrative present corrections without inventing events', function () {
    $this->travelTo(Carbon::parse('2026-11-04 12:00:00', 'Asia/Makassar'));

    $employee = userWithRole(UserRole::Employee);
    $admin = userWithRole(UserRole::Admin);

    DailyStatusCorrection::create([
        'employee_id' => $employee->employee->id,
        'attendance_date' => '2026-11-03',
        'status' => DailyAttendanceStatus::Present,
        'reason' => 'Koreksi administratif.',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee)
        ->get(route('attendance-history.index', [
            'from' => '2026-11-01',
            'to' => '2026-11-04',
        ]))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary): bool => $summary['present_days'] === 1
            && $summary['complete_days'] === 0
            && $summary['needs_review_days'] === 0)
        ->assertViewHas('dateGroups', fn ($groups): bool => $groups->total() === 1
            && $groups->first()['date'] === '2026-11-03'
            && $groups->first()['events']->isEmpty());
});
