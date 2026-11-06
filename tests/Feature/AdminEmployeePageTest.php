<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

function employeePageAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill([
        'role' => UserRole::Admin,
        'is_active' => true,
    ])->save();

    return $user;
}

function employeePageRecord(string $name, string $number, bool $active = true): Employee
{
    $user = User::factory()->create(['name' => $name]);
    $user->forceFill([
        'role' => UserRole::Employee,
        'is_active' => $active,
    ])->save();

    return Employee::query()->create([
        'user_id' => $user->id,
        'employee_number' => $number,
    ]);
}

it('renders employee cards for narrower screens and a table for wide screens', function () {
    $employee = employeePageRecord('Pegawai Tampilan Hybrid', 'EMP-HYBRID');

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertSee('lg:grid-cols-2 xl:hidden', false)
        ->assertSee('bg-sanmal-surface-card', false)
        ->assertSee('overflow-x-auto overscroll-x-contain', false)
        ->assertSee('role="region"', false)
        ->assertSee('Tabel manajemen pegawai', false)
        ->assertSee('scope="col"', false)
        ->assertSee('<dt', false)
        ->assertSee('<dd', false)
        ->assertSee('Pegawai Tampilan Hybrid')
        ->assertSee('EMP-HYBRID')
        ->assertSee('Edit pegawai')
        ->assertSee(route('admin.employees.edit', $employee), false)
        ->assertSee('Cari pegawai')
        ->assertSee('Status akun');
});

it('keeps employee search and account status filters working', function () {
    employeePageRecord('Pegawai Aktif Filter', 'EMP-AKTIF', true);
    employeePageRecord('Pegawai Nonaktif Filter', 'EMP-NONAKTIF', false);

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index', ['q' => 'EMP-AKTIF', 'status' => 'active']))
        ->assertOk()
        ->assertSee('Pegawai Aktif Filter')
        ->assertDontSee('Pegawai Nonaktif Filter');

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('Pegawai Nonaktif Filter')
        ->assertDontSee('Pegawai Aktif Filter');
});

it('shows an empty state when no employees match the filters', function () {
    employeePageRecord('Pegawai Ada', 'EMP-ADA');

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index', ['q' => 'tidak-ditemukan']))
        ->assertOk()
        ->assertSee('Belum ada data pegawai yang sesuai dengan filter.');
});

it('shows the add employee button only to superadmin', function () {
    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertDontSee('Tambah pegawai')
        ->assertDontSee(route('admin.employees.create'), false);

    $superadmin = User::factory()->create();
    $superadmin->forceFill(['role' => UserRole::Superadmin, 'is_active' => true])->save();

    $this->actingAs($superadmin)
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertSee('Tambah pegawai')
        ->assertSee(route('admin.employees.create'), false);
});

it('uses the same pending status badge on mobile cards and the desktop table', function () {
    $employee = employeePageRecord('Pegawai Menunggu', 'EMP-PENDING');
    $employee->user->forceFill(['must_change_password' => true])->save();

    $content = $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertSee('Menunggu aktivasi')
        ->getContent();

    expect(substr_count($content, 'sanmal-badge-warning'))->toBeGreaterThanOrEqual(2);
});

it('shows the result summary and a reset link only while filters are applied', function () {
    employeePageRecord('Pegawai Ringkasan', 'EMP-RINGKAS');

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index'))
        ->assertOk()
        ->assertSee('dari 1 pegawai')
        ->assertDontSee('Atur ulang');

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.index', ['q' => 'EMP-RINGKAS']))
        ->assertOk()
        ->assertSee('Atur ulang');
});

it('shows the long pending status label on the employee management page', function () {
    $employee = employeePageRecord('Pegawai Detail Menunggu', 'EMP-DETAIL');
    $employee->user->forceFill(['must_change_password' => true])->save();

    $this->actingAs(employeePageAdmin())
        ->get(route('admin.employees.edit', $employee))
        ->assertOk()
        ->assertSee('Menunggu aktivasi / ganti password')
        ->assertSee('Akun nonaktif tidak dapat menggunakan aplikasi');
});

it('renders the employee data section heading across the two-column create form grid', function () {
    $superadmin = User::factory()->create();
    $superadmin->forceFill(['role' => UserRole::Superadmin, 'is_active' => true])->save();

    $this->actingAs($superadmin)
        ->get(route('admin.employees.create'))
        ->assertOk()
        ->assertSee('Data kepegawaian')
        ->assertSee('EMP-01')
        ->assertSee('readonly', false)
        ->assertSee('sm:grid-cols-2', false)
        ->assertSee('sm:col-span-2', false);
});
