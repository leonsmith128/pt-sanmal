<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Selamat datang')
        ->assertDontSee('Dashboard Administrasi')
        ->assertDontSee('Pegawai aktif');
});

test('administrators see the administrative dashboard summary', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Administrasi')
        ->assertSee('Pegawai aktif')
        ->assertSee('Aktivitas absensi terbaru')
        ->assertSee('Permohonan menunggu');
});

test('employee dashboard does not expose administrative summary when no employee profile is linked', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profil pegawai belum tersedia')
        ->assertDontSee('Dashboard Administrasi')
        ->assertDontSee('Pegawai aktif')
        ->assertDontSee('Permohonan menunggu');
});


test('employee dashboard shows pending leave request breakdown for the authenticated employee and keeps notifications visible on mobile', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $otherUser = User::factory()->create();
    $otherUser->forceFill(['role' => UserRole::Employee])->save();
    $otherEmployee = Employee::create(['user_id' => $otherUser->id]);

    $leaveType = LeaveType::create([
        'code' => 'cuti',
        'name' => 'Cuti Tahunan',
        'is_active' => true,
    ]);
    $sickType = LeaveType::create([
        'code' => 'sakit',
        'name' => 'Sakit',
        'is_active' => true,
    ]);

    foreach ([
        [$employee, $leaveType, 'pending'],
        [$employee, $sickType, 'pending'],
        [$employee, $leaveType, 'approved'],
        [$otherEmployee, $leaveType, 'pending'],
    ] as [$owner, $type, $status]) {
        LeaveRequest::create([
            'employee_id' => $owner->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Keperluan pengujian',
            'status' => $status,
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pengajuan Saya')
        ->assertSee('Cuti Tahunan: 1')
        ->assertSee('Sakit: 1')
        ->assertSee('aria-label="Buka notifikasi"', false)
        ->assertDontSee('hidden lg:block');
});
