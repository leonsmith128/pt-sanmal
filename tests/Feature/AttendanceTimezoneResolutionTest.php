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
use App\Services\AttendanceTimezoneResolver;
use App\Services\DailyAttendanceStatusService;
use App\Support\AttendanceSelfie;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

afterEach(function (): void {
    Carbon::setTestNow();
});

function timezoneResolutionEmployee(): Employee
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    return Employee::query()->create([
        'user_id' => $user->id,
        'joined_at' => now()->toDateString(),
    ]);
}

function timezoneResolutionProject(string $locationTimezone = 'Asia/Jayapura'): Project
{
    $client = Client::query()->create([
        'code' => 'CL-TZ-TEST',
        'name' => 'Client Timezone Test',
    ]);
    $location = Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Site Timezone Test',
        'type' => 'site',
        'timezone' => $locationTimezone,
        'is_active' => true,
    ]);

    return Project::query()->create([
        'client_id' => $client->id,
        'code' => 'PRJ-TZ-TEST',
        'name' => 'Project Timezone Test',
        'project_type' => ProjectType::Service,
        'location_id' => $location->id,
        'status' => ProjectStatus::Active,
    ]);
}

function timezoneResolutionPhoto(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'attendance.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/b1cAAAAASUVORK5CYII='),
    );
}

it('resolves valid onsite GPS coordinates to the Indonesian IANA timezone', function (
    float $latitude,
    float $longitude,
    string $expectedTimezone,
) {
    $result = app(AttendanceTimezoneResolver::class)->resolveClockIn(
        WorkType::Onsite,
        $latitude,
        $longitude,
        15,
    );

    expect($result)->toBe([
        'timezone' => $expectedTimezone,
        'source' => 'gps_resolved',
    ]);
})->with([
    'WIB Jakarta' => [-6.2088, 106.8456, 'Asia/Jakarta'],
    'WIB Pontianak' => [-0.0263, 109.3425, 'Asia/Pontianak'],
    'WITA Makassar' => [-5.1477, 119.4327, 'Asia/Makassar'],
    'WIT Jayapura' => [-2.5916, 140.6690, 'Asia/Jayapura'],
]);

it('always uses the fixed company timezone for office attendance', function () {
    $result = app(AttendanceTimezoneResolver::class)->resolveClockIn(
        WorkType::Office,
        -6.2088,
        106.8456,
        10,
    );

    expect($result)->toBe([
        'timezone' => 'Asia/Makassar',
        'source' => 'office_fixed',
    ]);
});

it('uses an explicitly unverified WITA fallback when onsite GPS cannot resolve a timezone', function () {
    $resolver = app(AttendanceTimezoneResolver::class);

    expect($resolver->resolveClockIn(WorkType::Onsite, null, null, null))->toBe([
        'timezone' => 'Asia/Makassar',
        'source' => 'fallback',
    ])->and($resolver->resolveClockIn(WorkType::Onsite, 1.3521, 103.8198, 15))->toBe([
        'timezone' => 'Asia/Makassar',
        'source' => 'fallback',
    ])->and($resolver->resolveClockIn(WorkType::Onsite, 106.8456, -6.2088, 15))->toBe([
        'timezone' => 'Asia/Makassar',
        'source' => 'fallback',
    ]);
});

it('stores the GPS-resolved session timezone and makes clock out inherit its anchor', function () {
    Storage::fake(AttendanceSelfie::DISK);
    Carbon::setTestNow('2026-11-03 16:30:00 UTC');

    $employee = timezoneResolutionEmployee();
    $project = timezoneResolutionProject();
    $action = app(RecordAttendance::class);

    $clockIn = $action->handle(
        employee: $employee,
        type: AttendanceType::ClockIn,
        workType: WorkType::Onsite,
        locationId: null,
        projectId: $project->id,
        latitude: -6.2088,
        longitude: 106.8456,
        accuracy: 15,
        selfie: timezoneResolutionPhoto(),
        selfieSource: SelfieSource::Camera,
    );

    expect($clockIn->timezone)->toBe('Asia/Jakarta')
        ->and($clockIn->timezone_source)->toBe('gps_resolved')
        ->and($clockIn->attendance_date->toDateString())->toBe('2026-11-03');

    Carbon::setTestNow('2026-11-03 16:31:00 UTC');

    $clockOut = $action->handle(
        employee: $employee,
        type: AttendanceType::ClockOut,
        workType: null,
        locationId: null,
        projectId: null,
        latitude: -2.5916,
        longitude: 140.6690,
        accuracy: 15,
        selfie: timezoneResolutionPhoto(),
        selfieSource: SelfieSource::Camera,
    );

    expect($clockOut->timezone)->toBe('Asia/Jakarta')
        ->and($clockOut->timezone_source)->toBe('session_anchor')
        ->and($clockOut->attendance_date->toDateString())->toBe('2026-11-03')
        ->and($clockOut->latitude)->toBe('-2.5916000');
});

it('ignores an office location timezone and derives its attendance date in WITA', function () {
    Storage::fake(AttendanceSelfie::DISK);
    Carbon::setTestNow('2026-11-03 16:30:00 UTC');

    $employee = timezoneResolutionEmployee();
    $office = Location::query()->create([
        'name' => 'Kantor dengan timezone lama',
        'type' => 'office',
        'timezone' => 'Asia/Jakarta',
        'is_active' => true,
    ]);

    $record = app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockIn,
        workType: WorkType::Office,
        locationId: $office->id,
        projectId: null,
        latitude: -6.2088,
        longitude: 106.8456,
        accuracy: 15,
        selfie: timezoneResolutionPhoto(),
        selfieSource: SelfieSource::Camera,
    );

    expect($record->timezone)->toBe('Asia/Makassar')
        ->and($record->timezone_source)->toBe('office_fixed')
        ->and($record->attendance_date->toDateString())->toBe('2026-11-04');
});

it('flags an onsite session for admin review when it uses the WITA fallback', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $employee = timezoneResolutionEmployee();
    $project = timezoneResolutionProject();

    $record = app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockIn,
        workType: WorkType::Onsite,
        locationId: null,
        projectId: $project->id,
        latitude: null,
        longitude: null,
        accuracy: null,
        selfie: timezoneResolutionPhoto(),
        selfieSource: SelfieSource::Camera,
    );

    $status = app(DailyAttendanceStatusService::class)->forEmployeeDate(
        $employee,
        $record->attendance_date->toDateString(),
    );

    expect($record->timezone)->toBe('Asia/Makassar')
        ->and($record->timezone_source)->toBe('fallback')
        ->and($status['status'])->toBe('needs_review')
        ->and($status['reason'])->toContain('fallback Asia/Makassar');
});

it('flags office attendance for review when GPS evidence is unavailable even though WITA is fixed', function () {
    Storage::fake(AttendanceSelfie::DISK);

    $employee = timezoneResolutionEmployee();
    $office = Location::query()->create([
        'name' => 'Kantor tanpa GPS',
        'type' => 'office',
        'timezone' => 'Asia/Jakarta',
        'is_active' => true,
    ]);

    $record = app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockIn,
        workType: WorkType::Office,
        locationId: $office->id,
        projectId: null,
        latitude: null,
        longitude: null,
        accuracy: null,
        selfie: timezoneResolutionPhoto(),
        selfieSource: SelfieSource::Camera,
    );

    $status = app(DailyAttendanceStatusService::class)->forEmployeeDate(
        $employee,
        $record->attendance_date->toDateString(),
    );

    expect($record->timezone)->toBe('Asia/Makassar')
        ->and($record->timezone_source)->toBe('office_fixed')
        ->and($record->location_status)->toBe('unavailable')
        ->and($status['status'])->toBe('needs_review')
        ->and($status['reason'])->toContain('Bukti GPS');
});
