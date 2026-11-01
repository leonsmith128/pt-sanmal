<?php

use App\Enums\AttendanceType;
use App\Enums\ProjectStatus;
use App\Enums\WorkType;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecord;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

function clientLocationAdminUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

it('keeps client as a persistent identity without an activation column', function () {
    expect(Schema::hasColumn('clients', 'is_active'))->toBeFalse();
});

it('allows administrators to manage client master data', function () {
    $admin = clientLocationAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.clients.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.clients.store'), [
            'code' => 'CL-001',
            'name' => 'PT Contoh Client',
            'address' => 'Makassar',
            'phone' => '0411000000',
        ])
        ->assertRedirect(route('admin.clients.index'));

    $client = Client::query()->where('code', 'CL-001')->firstOrFail();

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'PT Contoh Client',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.clients.update', $client), [
            'code' => 'CL-001',
            'name' => 'PT Contoh Client Updated',
            'address' => 'Gowa',
            'phone' => '0411222222',
        ])
        ->assertRedirect(route('admin.clients.index'));

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'PT Contoh Client Updated',
    ]);
});

it('allows administrators to manage client and internal locations with valid timezone coordinates', function () {
    $admin = clientLocationAdminUser();
    $client = Client::query()->create([
        'code' => 'CL-LOC',
        'name' => 'Client Lokasi',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'client_id' => $client->id,
            'name' => 'Site Makassar',
            'type' => 'site',
            'address' => 'Jl. Contoh No. 1',
            'latitude' => '-5.147665',
            'longitude' => '119.432732',
            'timezone' => 'Asia/Makassar',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.locations.index'));

    $this->assertDatabaseHas('locations', [
        'client_id' => $client->id,
        'name' => 'Site Makassar',
        'timezone' => 'Asia/Makassar',
        'latitude' => '-5.1476650',
        'longitude' => '119.4327320',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'client_id' => '',
            'name' => 'Kantor Internal',
            'type' => 'office',
            'address' => 'Kantor Sanmal',
            'latitude' => '',
            'longitude' => '',
            'timezone' => 'Asia/Jakarta',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.locations.index'));

    $this->assertDatabaseHas('locations', [
        'client_id' => null,
        'name' => 'Kantor Internal',
        'timezone' => 'Asia/Jakarta',
    ]);
});

it('requires location coordinates to be provided as a pair and validates IANA timezones', function () {
    $admin = clientLocationAdminUser();

    $this->actingAs($admin)
        ->from(route('admin.locations.create'))
        ->post(route('admin.locations.store'), [
            'client_id' => '',
            'name' => 'Lokasi Tidak Valid',
            'type' => 'site',
            'latitude' => '-5.1',
            'longitude' => '',
            'timezone' => 'Not/A_Timezone',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.locations.create'))
        ->assertSessionHasErrors(['client_id', 'longitude', 'timezone']);

    $this->assertDatabaseMissing('locations', ['name' => 'Lokasi Tidak Valid']);
});

it('denies employees access to client and location master data', function () {
    $employee = tap(User::factory()->create(), fn (User $user) => $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save());

    $this->actingAs($employee)
        ->get(route('admin.clients.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.locations.index'))
        ->assertForbidden();
});

it('enforces location ownership and requires every project to have a location', function () {
    $client = Client::query()->create([
        'code' => 'CL-RULE',
        'name' => 'Client Aturan',
    ]);

    expect(fn () => Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Office dengan Client',
        'type' => 'office',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]))->toThrow(QueryException::class);

    expect(fn () => Location::query()->create([
        'client_id' => null,
        'name' => 'Site tanpa Client',
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]))->toThrow(QueryException::class);

    $site = Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Site Valid',
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]);

    expect(fn () => DB::table('projects')->insert([
        'code' => 'PRJ-NOLOC',
        'name' => 'Project tanpa Location',
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    $office = Location::query()->create([
        'client_id' => null,
        'name' => 'Office Valid',
        'type' => 'office',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]);

    expect(fn () => Project::query()->create([
        'code' => 'PRJ-OFFICE',
        'name' => 'Project dengan Office',
        'location_id' => $office->id,
    ]))->toThrow(ValidationException::class);

    $inactiveSite = Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Site Nonaktif',
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => false,
    ]);

    expect(fn () => Project::query()->create([
        'code' => 'PRJ-INACTIVE',
        'name' => 'Project dengan Site Nonaktif',
        'location_id' => $inactiveSite->id,
    ]))->toThrow(ValidationException::class);

    $project = Project::query()->create([
        'code' => 'PRJ-RULE',
        'name' => 'Project Valid',
        'location_id' => $site->id,
    ]);

    expect($project->fresh()->status)->toBe(ProjectStatus::Pending);

    $employee = Employee::query()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Onsite,
        'location_id' => $site->id,
        'project_id' => $project->id,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
    ]);

    $otherSite = Location::query()->create([
        'client_id' => $client->id,
        'name' => 'Site Kedua',
        'type' => 'site',
        'timezone' => 'Asia/Makassar',
        'is_active' => true,
    ]);

    expect(fn () => $project->update([
        'location_id' => $otherSite->id,
    ]))->toThrow(ValidationException::class);
});
