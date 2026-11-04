<?php

use App\Enums\AttendanceType;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
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
        ->set('clientId', '3')
        ->set('locationId', '4')
        ->set('projectId', '5')
        ->call('resetAdditionalFilters')
        ->assertSet('search', '')
        ->assertSet('attendanceType', '')
        ->assertSet('workType', '')
        ->assertSet('employeeId', '')
        ->assertSet('clientId', '')
        ->assertSet('locationId', '')
        ->assertSet('projectId', '')
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

it('applies Client, historical Location, and Project filters together and offers inactive history options', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Filter Master');
    $client = Client::query()->create(['code' => 'CL-FILTER', 'name' => 'Client Filter']);
    $historicalLocation = Location::query()->create([
        'client_id' => $client->id, 'name' => 'Site Historis Filter', 'type' => 'site',
        'timezone' => 'Asia/Makassar', 'is_active' => true,
    ]);
    $currentLocation = Location::query()->create([
        'client_id' => $client->id, 'name' => 'Site Terkini Filter', 'type' => 'site',
        'timezone' => 'Asia/Makassar', 'is_active' => true,
    ]);
    $project = Project::query()->create([
        'client_id' => $client->id, 'code' => 'PRJ-FILTER', 'name' => 'Project Filter',
        'project_type' => ProjectType::Service, 'location_id' => $historicalLocation->id,
        'status' => ProjectStatus::Active,
    ]);
    AttendanceRecord::query()->create([
        'employee_id' => $employee->id, 'type' => AttendanceType::ClockIn, 'work_type' => WorkType::Onsite,
        'location_id' => $historicalLocation->id, 'project_id' => $project->id,
        'recorded_at' => '2026-11-02 01:00:00', 'timezone' => 'Asia/Makassar', 'attendance_date' => '2026-11-02',
    ]);
    $project->update(['location_id' => $currentLocation->id, 'status' => ProjectStatus::Completed]);
    $historicalLocation->update(['is_active' => false]);

    Livewire::actingAs($admin)->test('pages::admin.kehadiran')
        ->assertSee('Site Historis Filter (Nonaktif)')
        ->assertSee('PRJ-FILTER · Project Filter — Client Filter (Nonaktif)')
        ->set('dateFrom', '2026-11-02')->set('dateTo', '2026-11-02')
        ->set('clientId', (string) $client->id)
        ->set('locationId', (string) $historicalLocation->id)
        ->set('projectId', (string) $project->id)
        ->assertSee('data-record-count="1"', false)
        ->set('locationId', (string) $currentLocation->id)
        ->assertSee('data-record-count="0"', false)
        ->set('clientId', '')
        ->set('locationId', (string) $historicalLocation->id)
        ->set('projectId', (string) $project->id)
        ->assertSee('data-record-count="1"', false);
});

it('normalizes a reversed date range consistently in the attendance page and CSV export', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Rentang Terbalik');
    foreach (['2026-11-02', '2026-11-03'] as $date) {
        AttendanceRecord::query()->create([
            'employee_id' => $employee->id, 'type' => AttendanceType::ClockIn, 'work_type' => WorkType::Office,
            'recorded_at' => $date.' 01:00:00', 'timezone' => 'Asia/Makassar', 'attendance_date' => $date,
        ]);
    }
    Livewire::actingAs($admin)->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-03')->set('dateTo', '2026-11-02')
        ->assertSee('data-record-count="2"', false);

    $csv = $this->actingAs($admin)->get(route('admin.kehadiran.export', [
        'from' => '2026-11-03', 'to' => '2026-11-02',
    ]))->streamedContent();
    expect($csv)->toContain('2026-11-02')->toContain('2026-11-03')->toContain('Pegawai Rentang Terbalik');
});

it('exports the complete daily recap when one event matches the selected Project filter', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Multi Project');
    $clientA = Client::query()->create(['code' => 'CL-A', 'name' => 'Client A']);
    $clientB = Client::query()->create(['code' => 'CL-B', 'name' => 'Client B']);
    $locationA = Location::query()->create(['client_id' => $clientA->id, 'name' => 'Site A', 'type' => 'site', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
    $locationB = Location::query()->create(['client_id' => $clientB->id, 'name' => 'Site B', 'type' => 'site', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
    $projectA = Project::query()->create(['client_id' => $clientA->id, 'code' => 'PRJ-A', 'name' => 'Project A', 'project_type' => ProjectType::Service, 'location_id' => $locationA->id, 'status' => ProjectStatus::Active]);
    $projectB = Project::query()->create(['client_id' => $clientB->id, 'code' => 'PRJ-B', 'name' => 'Project B', 'project_type' => ProjectType::Service, 'location_id' => $locationB->id, 'status' => ProjectStatus::Active]);

    foreach ([[$projectA, $locationA, 'CL-A', 'Client A', 'PRJ-A', 'Project A'], [$projectB, $locationB, 'CL-B', 'Client B', 'PRJ-B', 'Project B']] as [$project, $location, $clientCode, $clientName, $projectCode, $projectName]) {
        AttendanceRecord::query()->create([
            'employee_id' => $employee->id, 'type' => AttendanceType::ClockIn, 'work_type' => WorkType::Onsite,
            'location_id' => $location->id, 'project_id' => $project->id,
            'recorded_at' => '2026-11-02 01:00:00', 'timezone' => 'Asia/Makassar', 'attendance_date' => '2026-11-02',
            'client_code_snapshot' => $clientCode, 'client_name_snapshot' => $clientName,
            'project_code_snapshot' => $projectCode, 'project_name_snapshot' => $projectName,
            'location_name_snapshot' => $location->name, 'snapshot_source' => 'clock_in',
        ]);
    }
    $csv = $this->actingAs($admin)->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02', 'to' => '2026-11-02', 'project_id' => $projectA->id,
    ]))->streamedContent();
    expect($csv)->toContain('PRJ-A')->toContain('PRJ-B')->toContain('Project A')->toContain('Project B');
});

it('renders compact attendance media, map link, and historical project context', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Proyek');

    $record = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-02 01:00:00',
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-02',
        'latitude' => -5.1477,
        'longitude' => 119.4327,
        'address_snapshot' => 'Jl. Kalibrasi No. 1, Makassar',
        'selfie_path' => 'selfies/attendance.jpg',
        'client_code_snapshot' => 'CL-01',
        'client_name_snapshot' => 'Client Kalibrasi',
        'project_code_snapshot' => 'PRJ-01',
        'project_name_snapshot' => 'Proyek Pengujian',
        'location_name_snapshot' => 'Site Makassar',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->assertSee('Jl. Kalibrasi No. 1, Makassar')
        ->assertSee('https://www.google.com/maps?q=', false)
        ->assertSee('PRJ-01 · Proyek Pengujian')
        ->assertSee('attendance-project-'.$record->id, false)
        ->assertSee('Konteks proyek')
        ->assertSee(route('admin.attendance.selfie', $record), false)
        ->assertDontSee('Backup ke Drive')
        ->assertDontSee('Hapus lokal');
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

it('renders the admin attendance page with a full-width responsive container and mobile-first header', function () {
    $admin = attendancePageAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertSee('mx-auto flex w-full max-w-7xl flex-col gap-4', false)
        ->assertSee('flex flex-col gap-3 md:flex-row md:items-start md:justify-between', false);
});

it('renders page header actions in a responsive wrapper when provided', function () {
    $admin = attendancePageAdmin();

    $this->actingAs($admin)
        ->get(route('admin.leave-requests.index'))
        ->assertOk()
        ->assertSee('md:w-auto md:shrink-0 md:justify-end', false)
        ->assertSee(__('Rekap harian'));
});

it('keeps the attendance filter panel compact and removes redundant page descriptions', function () {
    $admin = attendancePageAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertSeeInOrder(['Filter absensi', 'Reset filter', 'Aktivitas terbaru', 'Export'])
        ->assertDontSee('Cakupan catatan')
        ->assertDontSee('Tanggal mengikuti kalender perusahaan WITA.')
        ->assertDontSee('Pantau dan telusuri catatan absensi seluruh pegawai.');
});

it('renders the attendance export confirmation with an Alpine-bound link', function () {
    $admin = attendancePageAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertSee('x-bind:href="exportUrl"', false)
        ->assertSee('Konfirmasi dan ekspor');
});
