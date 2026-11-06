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

    $this->actingAs($user)->get(route('leave-requests.index', [
        'q' => 'Keperluan keluarga',
        'type' => 'izin',
        'status' => 'pending',
        'from' => '2026-11-02',
        'to' => '2026-11-03',
    ]))
        ->assertOk()
        ->assertSee('Pusat Permohonan')
        ->assertSee('Keperluan keluarga.')
        ->assertDontSee('Rahasia pegawai lain.')
        ->assertSee('Terapkan filter')
        ->assertSee('name="q"', false)
        ->assertSee('value="Keperluan keluarga"', false)
        ->assertSee('name="type"', false)
        ->assertSee('name="status"', false)
        ->assertSee('name="from"', false)
        ->assertSee('value="2026-11-02"', false)
        ->assertSee('name="to"', false)
        ->assertSee('value="2026-11-03"', false);
});

it('exports filtered rows as a two-sheet XLSX without formulas', function () {
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

    $xlsx = xlsxContents($response);

    expect($response->headers->get('content-disposition'))->toContain('.xlsx')
        ->and($xlsx['sheets'])->toBe(['Ringkasan', 'Detail'])
        ->and($xlsx['xml'])->not->toMatch('/<f[ >]/')
        ->and($xlsx['text'])->toContain("'=HYPERLINK");
});

it('paginates request center results without paginator contract errors', function () {
    $user = requestCenterUser(UserRole::Employee);
    $employee = requestCenterEmployee($user);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    foreach (range(1, 31) as $number) {
        $request = $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Permohonan keluarga nomor '.$number,
            'status' => 'pending',
        ]);

        // Use distinct timestamps so the expected page is deterministic.
        $timestamp = now()->subSeconds(31 - $number);
        $request->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();
    }

    $this->actingAs($user)
        ->get(route('leave-requests.index', ['page' => 2]))
        ->assertOk()
        ->assertViewHas('requests', fn ($requests): bool => $requests->currentPage() === 2
            && $requests->lastPage() === 2
            && $requests->perPage() === 30
            && $requests->count() === 1
            && str_contains($requests->url(2), 'page=2'))
        ->assertSee('Permohonan keluarga nomor 1')
        ->assertDontSee('Permohonan keluarga nomor 31');
});

it('validates an inverted date range on the request center', function () {
    $user = requestCenterUser(UserRole::Employee);
    requestCenterEmployee($user);

    $this->actingAs($user)->get(route('leave-requests.index', [
        'from' => '2026-11-10',
        'to' => '2026-11-01',
    ]))->assertSessionHasErrors('to');
});

it('renders the request center UI foundation elements, filter disclosure, and status toolbar', function () {
    $user = requestCenterUser(UserRole::Employee);
    $employee = requestCenterEmployee($user);
    $type = LeaveType::create(['code' => 'cuti', 'name' => 'Cuti Tahunan', 'is_active' => true]);

    $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-15',
        'end_date' => '2026-11-17',
        'reason' => 'Liburan akhir tahun.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('leave-requests.index', [
        'status' => 'pending',
    ]));

    $response->assertOk()
        ->assertSee('aria-controls="leave-request-filter-panel"', false)
        ->assertSee('id="leave-request-filter-panel"', false)
        ->assertSee('Filter cepat status')
        ->assertSee('bg-sanmal-surface-card', false)
        ->assertSee('Cuti Tahunan')
        ->assertSee('Liburan akhir tahun.')
        ->assertSee(route('leave-requests.create'))
        ->assertSee(route('attendance-corrections.create'))
        ->assertSee(route('overtime-requests.index'))
        ->assertSee('Batalkan permohonan');
});
