<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected from the employee profile page', function () {
    $this->get(route('employee-profile.edit'))
        ->assertRedirect(route('login'));
});

test('employees can update their phone number without changing official data', function () {
    $user = User::factory()->create(['name' => 'Official Name']);
    $employee = Employee::query()->create([
        'user_id' => $user->id,
        'employee_number' => 'SM-001',
        'phone' => '08111111111',
        'employment_status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::employee.employee-profile')
        ->set('phone', '08222222222')
        ->call('saveContactDetails')
        ->assertHasNoErrors();

    expect($employee->refresh()->phone)->toBe('08222222222')
        ->and($user->fresh()->name)->toBe('Official Name')
        ->and($employee->employee_number)->toBe('SM-001');
});

test('employees can upload and replace their profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Employee::query()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test('pages::employee.employee-profile')
        ->set('photo', UploadedFile::fake()->image('profile.jpg', 200, 200))
        ->call('saveProfilePhoto')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);

    $oldPath = $user->profile_photo_path;

    Livewire::test('pages::employee.employee-profile')
        ->set('photo', UploadedFile::fake()->image('new-profile.png', 200, 200))
        ->call('saveProfilePhoto')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->profile_photo_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('profile photo upload rejects non-image files', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::employee.employee-profile')
        ->set('photo', UploadedFile::fake()->create('script.php', 20, 'application/x-php'))
        ->call('saveProfilePhoto')
        ->assertHasErrors(['photo']);
});

test('employee profile can remove its current profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('profile.jpg')->store('profile-photos', 'public');
    $user->forceFill(['profile_photo_path' => $path])->save();

    $this->actingAs($user);

    Livewire::test('pages::employee.employee-profile')
        ->call('removeProfilePhoto')
        ->assertHasNoErrors();

    expect($user->fresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('profile photo upload rejects images over two megabytes', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::employee.employee-profile')
        ->set('photo', UploadedFile::fake()->image('large-profile.jpg')->size(3000))
        ->call('saveProfilePhoto')
        ->assertHasErrors(['photo']);
});
