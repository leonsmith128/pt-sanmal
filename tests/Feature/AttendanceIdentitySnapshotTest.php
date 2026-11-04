<?php

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\SelfieSource;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Support\AttendanceSelfie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('captures master identity at clock in and reuses it after master names change', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();
    $employee = Employee::query()->create(['user_id' => $user->id]);

    $client = Client::query()->create([
        'code' => 'CL-SNAPSHOT',
        'name' => 'Client Sebelum',
    ]);
    $location = Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Site Sebelum',
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]);
    $project = Project::query()->create([
        'client_id' => $client->id,
        'code' => 'PRJ-SNAPSHOT',
        'name' => 'Project Sebelum',
        'project_type' => ProjectType::Service,
        'location_id' => $location->id,
        'status' => ProjectStatus::Active,
    ]);

    $action = app(RecordAttendance::class);
    $clockIn = $action->handle(
        $employee,
        AttendanceType::ClockIn,
        WorkType::Onsite,
        null,
        $project->id,
        null,
        null,
        null,
        UploadedFile::fake()->createWithContent('clock-in.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/b1cAAAAASUVORK5CYII=')),
        SelfieSource::Camera,
    );

    expect($clockIn->fresh()->client_code_snapshot)->toBe('CL-SNAPSHOT')
        ->and($clockIn->fresh()->client_name_snapshot)->toBe('Client Sebelum')
        ->and($clockIn->fresh()->project_code_snapshot)->toBe('PRJ-SNAPSHOT')
        ->and($clockIn->fresh()->project_name_snapshot)->toBe('Project Sebelum')
        ->and($clockIn->fresh()->location_name_snapshot)->toBe('Site Sebelum')
        ->and($clockIn->fresh()->snapshot_source)->toBe('clock_in');

    $client->update(['name' => 'Client Sesudah']);
    $project->update(['name' => 'Project Sesudah']);
    $location->update(['name' => 'Site Sesudah']);

    $clockOut = $action->handle(
        $employee,
        AttendanceType::ClockOut,
        null,
        null,
        null,
        null,
        null,
        null,
        UploadedFile::fake()->createWithContent('clock-out.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/b1cAAAAASUVORK5CYII=')),
        SelfieSource::Camera,
    );

    expect($clockOut->fresh()->client_name_snapshot)->toBe('Client Sebelum')
        ->and($clockOut->fresh()->project_name_snapshot)->toBe('Project Sebelum')
        ->and($clockOut->fresh()->location_name_snapshot)->toBe('Site Sebelum')
        ->and($clockOut->fresh()->snapshot_source)->toBe('clock_in')
        ->and($client->fresh()->name)->toBe('Client Sesudah')
        ->and($project->fresh()->name)->toBe('Project Sesudah')
        ->and($location->fresh()->name)->toBe('Site Sesudah');

    $historyResponse = $this->actingAs($user)
        ->get(route('attendance-history.index'))
        ->assertOk();

    preg_match('/data-event="([^"]+)"/', $historyResponse->getContent(), $matches);
    expect($matches)->toHaveKey(1);

    $payload = json_decode(base64_decode($matches[1]), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['client'])->toContain('Client Sebelum')
        ->and($payload['project'])->toContain('Project Sebelum')
        ->and($payload['location'])->toBe('Site Sebelum')
        ->and($payload['client'])->not->toContain('Client Sesudah')
        ->and($payload['project'])->not->toContain('Project Sesudah')
        ->and($payload['location'])->not->toContain('Site Sesudah');

    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $this->actingAs($admin)
        ->get(route('admin.kehadiran'))
        ->assertOk()
        ->assertSee('Client Sebelum')
        ->assertSee('Project Sebelum')
        ->assertSee('Site Sebelum')
        ->assertDontSee('Client Sesudah')
        ->assertDontSee('Project Sesudah')
        ->assertDontSee('Site Sesudah');
});
