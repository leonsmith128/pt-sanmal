<?php

use App\Enums\DailyAttendanceStatus;
use App\Enums\UserRole;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\User;

function reportingUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill([
        'role' => $role,
        'is_active' => true,
    ])->save();

    return $user;
}

it('prevents employees from accessing administrative reports', function () {
    $employee = reportingUser(UserRole::Employee);

    $this->actingAs($employee)
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.daily-attendance.export', ['month' => '2026-11']))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.employees.index'))
        ->assertForbidden();
});

it('validates audit log date filters before querying', function () {
    $admin = reportingUser(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index', ['from' => 'not-a-date']))
        ->assertSessionHasErrors('from');

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index', [
            'from' => '2026-11-20',
            'to' => '2026-11-01',
        ]))
        ->assertSessionHasErrors('to');
});

it('renders the audit log with accessible table navigation and shared filters', function () {
    $admin = reportingUser(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Log')
        ->assertSee('name="action"', false)
        ->assertSee('name="from"', false)
        ->assertSee('name="to"', false)
        ->assertSee('role="region"', false)
        ->assertSee('aria-label="Daftar catatan audit"', false)
        ->assertSee('Belum ada catatan audit.');
});

it('exports daily attendance XLSX without executable formulas', function () {
    $admin = reportingUser(UserRole::Admin);
    $employeeUser = User::factory()->create([
        'name' => '=HYPERLINK("https://attacker.test","Open")',
    ]);
    $employee = Employee::query()->create([
        'user_id' => $employeeUser->id,
        'employee_number' => '@SUM(1+1)',
    ]);

    DailyStatusCorrection::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-02',
        'status' => DailyAttendanceStatus::Absent,
        'reason' => '=HYPERLINK("https://attacker.test","Click")',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.daily-attendance.export', ['month' => '2026-11']));

    $xlsx = xlsxContents($response);

    expect($response->headers->get('content-disposition'))->toContain('.xlsx')
        ->and($xlsx['sheets'])->toBe(['Ringkasan', 'Detail'])
        ->and($xlsx['xml'])->not->toMatch('/<f[ >]/')
        ->and($xlsx['text'])->toContain("'@SUM(1+1)")
        ->toContain("'=HYPERLINK");
});

it('renders the daily attendance report with a reusable employee card', function () {
    $admin = reportingUser(UserRole::Admin);
    $employeeUser = User::factory()->create(['name' => 'Pegawai Uji']);
    Employee::query()->create(['user_id' => $employeeUser->id]);

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', ['date' => '2026-11-02']))
        ->assertOk()
        ->assertSee('Rekap Absensi Harian')
        ->assertSee('Pegawai Uji')
        ->assertSee('Koreksi status harian')
        ->assertSee('name="date"', false)
        ->assertSee('November 2026');
});

it('logs out inactive users before they can access authenticated pages', function () {
    $inactiveUser = User::factory()->create();
    $inactiveUser->forceFill(['is_active' => false])->save();

    $this->actingAs($inactiveUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('logs out inactive administrators before they can access admin pages', function () {
    $inactiveAdmin = reportingUser(UserRole::Admin);
    $inactiveAdmin->forceFill(['is_active' => false])->save();

    $this->actingAs($inactiveAdmin)
        ->get(route('admin.audit-logs.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('paginates daily attendance employees before calculating their status', function () {
    $admin = reportingUser(UserRole::Admin);

    foreach (range(1, 51) as $number) {
        $user = User::factory()->create([
            'name' => 'Pegawai nomor '.$number,
        ]);

        Employee::query()->create(['user_id' => $user->id]);
    }

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', [
            'date' => '2026-11-02',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertSee('Pegawai nomor 51')
        ->assertDontSee('Pegawai nomor 1');
});
