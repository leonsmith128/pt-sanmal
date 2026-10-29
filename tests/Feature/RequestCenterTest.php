<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;

function requestCenterUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user;
}

function requestCenterEmployee(User $user): Employee
{
    return Employee::create(['user_id' => $user->id]);
}

it('shows a unified request center with filters limited to the signed-in employee', function () {
    $user = requestCenterUser(UserRole::Employee);
    $employee = requestCenterEmployee($user);
    $other = requestCenterEmployee(requestCenterUser(UserRole::Employee));
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-03',
        'reason' => 'Keperluan keluarga.',
        'status' => 'pending',
    ]);
    $other->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-03',
        'reason' => 'Rahasia pegawai lain.',
        'status' => 'pending',
    ]);

    $this->actingAs($user)->get(route('leave-requests.index', ['type' => 'izin', 'status' => 'pending']))
        ->assertOk()
        ->assertSee('Pusat Permohonan')
        ->assertSee('Keperluan keluarga.')
        ->assertDontSee('Rahasia pegawai lain.')
        ->assertSee('Terapkan filter');
});

it('exports filtered rows as spreadsheet-safe UTF-8 CSV', function () {
    $user = requestCenterUser(UserRole::Employee);
    $employee = requestCenterEmployee($user);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-03',
        'reason' => '=HYPERLINK("https://example.com","click")',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('leave-requests.export', ['status' => 'pending']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $content = $response->streamedContent();
    expect($content)->toStartWith("\xEF\xBB\xBF")
        ->and($content)->toContain("'=HYPERLINK");
});

it('paginates request center results without paginator contract errors', function () {
    $user = requestCenterUser(UserRole::Employee);
    $employee = requestCenterEmployee($user);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    foreach (range(1, 11) as $number) {
        $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Permohonan keluarga nomor '.$number,
            'status' => 'pending',
        ]);
    }

    $this->actingAs($user)
        ->get(route('leave-requests.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Permohonan keluarga nomor 1')
        ->assertDontSee('Permohonan keluarga nomor 11');
});

it('validates an inverted date range on the request center', function () {
    $user = requestCenterUser(UserRole::Employee);
    requestCenterEmployee($user);

    $this->actingAs($user)->get(route('leave-requests.index', [
        'from' => '2026-11-10',
        'to' => '2026-11-01',
    ]))->assertSessionHasErrors('to');
});
