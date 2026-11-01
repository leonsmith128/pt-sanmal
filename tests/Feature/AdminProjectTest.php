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
use Illuminate\Validation\ValidationException;

function projectAdminUser(UserRole $role = UserRole::Admin): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user;
}

function projectClient(string $code): Client
{
    return Client::query()->create([
        'code' => $code,
        'name' => 'Client '.$code,
    ]);
}

function projectSite(Client $client, string $name): Location
{
    return Location::query()->create([
        'client_id' => $client->id,
        'name' => $name,
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]);
}

it('allows admins and superadmins to manage project master data', function () {
    $client = projectClient('CL-PROJECT');
    $site = projectSite($client, 'Site Kalibrasi');

    foreach ([UserRole::Admin, UserRole::Superadmin] as $role) {
        $user = projectAdminUser($role);

        $this->actingAs($user)
            ->get(route('admin.projects.index'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('admin.projects.store'), [
                'client_id' => $client->id,
                'code' => 'PRJ-'.$role->value,
                'name' => 'Kalibrasi Timbangan',
                'project_type' => ProjectType::Calibration->value,
                'location_id' => $site->id,
                'description' => 'Jasa kalibrasi tahunan.',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
                'status' => ProjectStatus::Active->value,
            ])
            ->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseHas('projects', [
            'code' => 'PRJ-'.$role->value,
            'client_id' => $client->id,
            'location_id' => $site->id,
            'project_type' => ProjectType::Calibration->value,
        ]);
    }
});

it('requires a project site to belong to its selected client', function () {
    $client = projectClient('CL-OWNER-A');
    $otherClient = projectClient('CL-OWNER-B');
    $otherSite = projectSite($otherClient, 'Site Client B');

    expect(fn () => Project::query()->create([
        'client_id' => $client->id,
        'code' => 'PRJ-WRONG-OWNER',
        'name' => 'Project salah pemilik',
        'project_type' => ProjectType::Training,
        'location_id' => $otherSite->id,
        'status' => ProjectStatus::Pending,
    ]))->toThrow(ValidationException::class);
});

it('allows project ownership and site changes before attendance but protects their history afterwards', function () {
    $client = projectClient('CL-MOVE-A');
    $otherClient = projectClient('CL-MOVE-B');
    $site = projectSite($client, 'Site Asal');
    $otherSite = projectSite($otherClient, 'Site Tujuan');

    $project = Project::query()->create([
        'client_id' => $client->id,
        'code' => 'PRJ-MOVE',
        'name' => 'Project Pindah',
        'project_type' => ProjectType::Training,
        'location_id' => $site->id,
        'status' => ProjectStatus::Pending,
    ]);

    $project->update([
        'client_id' => $otherClient->id,
        'location_id' => $otherSite->id,
    ]);

    expect($project->fresh()->client_id)->toBe($otherClient->id)
        ->and($project->fresh()->location_id)->toBe($otherSite->id);

    $employee = Employee::query()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Onsite,
        'location_id' => $otherSite->id,
        'project_id' => $project->id,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
    ]);

    expect(fn () => $project->update([
        'client_id' => $client->id,
        'location_id' => $site->id,
    ]))->toThrow(ValidationException::class);
});

it('keeps completed projects in history but excludes them from attendance choices', function () {
    $client = projectClient('CL-STATUS');
    $site = projectSite($client, 'Site Status');
    $project = Project::query()->create([
        'client_id' => $client->id,
        'code' => 'PRJ-COMPLETE',
        'name' => 'Project Selesai',
        'project_type' => ProjectType::Calibration,
        'location_id' => $site->id,
        'status' => ProjectStatus::Active,
    ]);

    expect(\App\Actions\RecordAttendance::availableForAttendance()->whereKey($project->id)->exists())->toBeTrue();

    $project->update(['status' => ProjectStatus::Completed]);

    expect(\App\Actions\RecordAttendance::availableForAttendance()->whereKey($project->id)->exists())->toBeFalse()
        ->and($project->fresh()->attendanceRecords)->toBeEmpty();
});

it('denies employees access to project management pages', function () {
    $employee = projectAdminUser(UserRole::Employee);

    $this->actingAs($employee)
        ->get(route('admin.projects.index'))
        ->assertForbidden();
});
