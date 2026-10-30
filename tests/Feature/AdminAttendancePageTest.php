<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

function attendancePageAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

function attendancePageEmployee(string $name): Employee
{
    $user = User::factory()->create(['name' => $name]);

    return Employee::query()->create(['user_id' => $user->id]);
}

it('shows employees without attendance only for a single-day unfiltered view', function () {
    $admin = attendancePageAdmin();
    attendancePageEmployee('Pegawai Alpha');
    attendancePageEmployee('Pegawai Beta');

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->assertSee('Belum ada absensi')
        ->assertSee('Pegawai Alpha')
        ->assertSee('Pegawai Beta')
        ->set('dateTo', '2026-11-03')
        ->assertDontSee('Belum ada absensi');
});

it('hides the not-yet-present list when an employee filter is selected', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Terpilih');
    attendancePageEmployee('Pegawai Lain');

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->set('employeeId', (string) $employee->id)
        ->assertDontSee('Belum ada absensi');
});

it('rejects non-manager access during Livewire requests', function () {
    $employee = User::factory()->create();
    $employee->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    Livewire::actingAs($employee)
        ->test('pages::admin.kehadiran')
        ->assertForbidden();
});

it('supports an allowlisted attendance table sort and toggles its direction', function () {
    $admin = attendancePageAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertSet('sortBy', 'recorded_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'employee')
        ->assertSet('sortBy', 'employee')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'employee')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'untrusted_column')
        ->assertSet('sortBy', 'employee');
});

it('resets the additional attendance filters without changing the date range', function () {
    $admin = attendancePageAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-03')
        ->set('search', 'Budi')
        ->set('attendanceType', 'clock_in')
        ->set('workType', 'onsite')
        ->set('employeeId', '12')
        ->call('resetAdditionalFilters')
        ->assertSet('search', '')
        ->assertSet('attendanceType', '')
        ->assertSet('workType', '')
        ->assertSet('employeeId', '')
        ->assertSet('dateFrom', '2026-11-02')
        ->assertSet('dateTo', '2026-11-03');
});

it('filters attendance records by employee text, event type, and work type', function () {
    $admin = attendancePageAdmin();
    $alpha = attendancePageEmployee('Pegawai Alpha');
    $beta = attendancePageEmployee('Pegawai Beta');

    AttendanceRecord::query()->create([
        'employee_id' => $alpha->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => '2026-11-02 01:00:00',
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-02',
        'notes' => 'Masuk kantor',
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $beta->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-02 09:00:00',
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-02',
        'notes' => 'Pulang proyek',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->set('search', 'Pegawai Beta')
        ->assertSee('data-record-count="1"', false)
        ->set('search', '')
        ->set('attendanceType', 'clock_in')
        ->assertSee('data-record-count="1"', false)
        ->set('attendanceType', '')
        ->set('workType', 'onsite')
        ->assertSee('data-record-count="1"', false);
});

it('shows recent attendance across dates and paginates after thirty records', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Aktivitas');

    foreach (range(1, 31) as $number) {
        AttendanceRecord::query()->create([
            'employee_id' => $employee->id,
            'type' => AttendanceType::ClockIn,
            'work_type' => WorkType::Office,
            'recorded_at' => now()->subMinutes($number),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => now()->subMinutes($number)->toDateString(),
        ]);
    }

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertSee('Aktivitas terbaru')
        ->assertSet('paginators.page', 1)
        ->call('nextPage')
        ->assertSet('paginators.page', 2);
});

it('exports filtered admin attendance as spreadsheet-safe CSV', function () {
    $admin = attendancePageAdmin();
    $alpha = attendancePageEmployee('=Pegawai Formula');
    $beta = attendancePageEmployee('Pegawai Lain');

    foreach ([[$alpha, '2026-11-02'], [$beta, '2026-11-03']] as [$employee, $date]) {
        AttendanceRecord::query()->create([
            'employee_id' => $employee->id,
            'type' => AttendanceType::ClockIn,
            'work_type' => WorkType::Office,
            'recorded_at' => $date.' 01:00:00',
            'timezone' => 'Asia/Makassar',
            'attendance_date' => $date,
        ]);
    }

    $response = $this->actingAs($admin)->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02',
        'to' => '2026-11-02',
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain("'=Pegawai Formula")
        ->toContain('2026-11-02')
        ->not->toContain('2026-11-03');
});
