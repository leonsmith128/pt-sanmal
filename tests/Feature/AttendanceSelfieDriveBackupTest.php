<?php

use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceSelfieDriveBackup;
use App\Support\AttendanceSelfie;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function driveBackupRecord(string $content = 'private attendance photo'): AttendanceRecord
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();
    $employee = Employee::create([
        'user_id' => $user->id,
        'joined_at' => now()->toDateString(),
    ]);
    $path = 'selfies/'.$employee->id.'/2026/10/attendance.jpg';

    Storage::disk(AttendanceSelfie::DISK)->put($path, $content);

    return AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => now(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => now('Asia/Makassar')->toDateString(),
        'selfie_path' => $path,
        'selfie_source' => SelfieSource::Camera,
    ]);
}

function fakeGoogleDrive(string $content, array $metadataOverrides = []): void
{
    $metadata = array_merge([
        'id' => 'drive-file-123',
        'name' => 'attendance.jpg',
        'size' => (string) strlen($content),
        'md5Checksum' => md5($content),
    ], $metadataOverrides);

    Http::fake(function (Request $request) use ($metadata) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return Http::response(['access_token' => 'test-drive-token', 'expires_in' => 3600]);
        }

        if (str_contains($request->url(), '/upload/drive/v3/files')) {
            return Http::response(['id' => 'drive-file-123']);
        }

        if (str_contains($request->url(), '/drive/v3/files/drive-file-123') && $request->method() === 'GET') {
            if (str_contains($request->url(), 'alt=media')) {
                return Http::response('private attendance photo');
            }

            return Http::response($metadata);
        }

        return Http::response([], 404);
    });
}

beforeEach(function () {
    Storage::fake(AttendanceSelfie::DISK);
    Cache::flush();
    config([
        'services.google_drive.client_id' => 'test-client-id',
        'services.google_drive.client_secret' => 'test-client-secret',
        'services.google_drive.refresh_token' => 'test-refresh-token',
        'services.google_drive.folder_id' => 'test-folder-id',
    ]);
});

it('uploads and verifies attendance photos before marking the Drive backup complete', function () {
    $content = 'private attendance photo';
    $record = driveBackupRecord($content);
    fakeGoogleDrive($content);

    $updated = app(AttendanceSelfieDriveBackup::class)->backup($record);

    expect($updated->drive_backup_status)->toBe('verified')
        ->and($updated->drive_file_id)->toBe('drive-file-123')
        ->and($updated->drive_md5_checksum)->toBe(md5($content))
        ->and($updated->drive_size_bytes)->toBe(strlen($content))
        ->and($updated->drive_backed_up_at)->not->toBeNull();
});

it('deletes local photos only after matching the verified Drive checksum and size', function () {
    $content = 'private attendance photo';
    $record = driveBackupRecord($content);
    fakeGoogleDrive($content);

    $backup = app(AttendanceSelfieDriveBackup::class);
    $verified = $backup->backup($record);
    $deleted = $backup->deleteLocalAfterVerification($verified);

    expect(Storage::disk(AttendanceSelfie::DISK)->exists($record->selfie_path))->toBeFalse()
        ->and($deleted->local_deleted_at)->not->toBeNull()
        ->and($deleted->drive_backup_status)->toBe('verified')
        ->and($backup->downloadVerifiedContent($deleted))->toBe($content);
});

it('lets admins trigger a verified Drive backup and then delete the local copy', function () {
    $content = 'private attendance photo';
    $record = driveBackupRecord($content);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    fakeGoogleDrive($content);

    $this->actingAs($admin)
        ->post(route('admin.attendance.selfie.backup', $record))
        ->assertRedirect();

    $record->refresh();
    expect($record->drive_backup_status)->toBe('verified');

    $this->delete(route('admin.attendance.selfie.delete-local', $record))
        ->assertRedirect();

    expect(Storage::disk(AttendanceSelfie::DISK)->exists($record->selfie_path))->toBeFalse()
        ->and($record->fresh()->local_deleted_at)->not->toBeNull();
});

it('serves a locally deleted photo from its verified private Drive backup', function () {
    $content = 'private attendance photo';
    $record = driveBackupRecord($content);
    $record->forceFill([
        'drive_backup_status' => 'verified',
        'drive_file_id' => 'drive-file-123',
        'drive_md5_checksum' => md5($content),
        'drive_size_bytes' => strlen($content),
        'local_deleted_at' => now(),
    ])->save();
    Storage::disk(AttendanceSelfie::DISK)->delete($record->selfie_path);
    fakeGoogleDrive($content);

    $this->actingAs($record->employee->user)
        ->get(route('attendance-history.selfie', $record))
        ->assertOk()
        ->assertStreamedContent($content);

    $this->actingAs($record->employee->user)
        ->get(route('attendance-history.selfie.download', $record))
        ->assertOk()
        ->assertHeader('content-disposition')
        ->assertStreamedContent($content);
});

it('keeps local photos when the remote Drive checksum does not match', function () {
    $content = 'private attendance photo';
    $record = driveBackupRecord($content);
    $record->forceFill([
        'drive_backup_status' => 'verified',
        'drive_file_id' => 'drive-file-123',
        'drive_md5_checksum' => md5($content),
        'drive_size_bytes' => strlen($content),
    ])->save();

    fakeGoogleDrive($content, ['md5Checksum' => md5('different content')]);

    expect(fn () => app(AttendanceSelfieDriveBackup::class)->deleteLocalAfterVerification($record))
        ->toThrow(RuntimeException::class);

    expect(Storage::disk(AttendanceSelfie::DISK)->exists($record->selfie_path))->toBeTrue();
});
