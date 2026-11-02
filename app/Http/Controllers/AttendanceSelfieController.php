<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceSelfieDriveBackup;
use App\Support\AttendanceSelfie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceSelfieController extends Controller
{
    /**
     * Menampilkan selfie hanya kepada pemilik catatan absensi.
     */
    public function showForEmployee(
        Request $request,
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless(
            $user->is_active && $employee && $record->employee_id === $employee->id,
            403,
            'Anda tidak memiliki akses ke foto absensi ini.'
        );

        return $this->streamSelfie($record, $driveBackup);
    }

    /**
     * Menampilkan selfie dari disk private untuk admin/superadmin.
     * Route ini dilindungi middleware peran pada routes/web.php.
     */
    public function show(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        return $this->streamSelfie($record, $driveBackup);
    }

    /**
     * Mengunduh selfie hanya kepada pemilik catatan absensi.
     */
    public function downloadForEmployee(
        Request $request,
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless(
            $user->is_active && $employee && $record->employee_id === $employee->id,
            403,
            'Anda tidak memiliki akses ke foto absensi ini.'
        );

        return $this->downloadSelfie($record, $driveBackup);
    }

    /**
     * Mengunduh selfie untuk admin/superadmin melalui route yang dilindungi peran.
     */
    public function download(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        return $this->downloadSelfie($record, $driveBackup);
    }

    private function streamSelfie(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        $disk = Storage::disk(AttendanceSelfie::DISK);

        if ($record->selfie_path && $disk->exists($record->selfie_path)) {
            return $disk->response($record->selfie_path, null, $this->privateImageHeaders());
        }

        abort_unless($record->local_deleted_at && $record->drive_backup_status === 'verified', 404);

        $content = $driveBackup->downloadVerifiedContent($record);

        return response()->stream(
            function () use ($content): void {
                echo $content;
            },
            200,
            $this->privateImageHeaders($this->mimeType($record->selfie_path)),
        );
    }

    private function downloadSelfie(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $driveBackup,
    ): StreamedResponse {
        $disk = Storage::disk(AttendanceSelfie::DISK);
        $fileName = $this->downloadFileName($record);

        if ($record->selfie_path && $disk->exists($record->selfie_path)) {
            return $disk->download($record->selfie_path, $fileName, $this->privateImageHeaders());
        }

        abort_unless($record->local_deleted_at && $record->drive_backup_status === 'verified', 404);

        $content = $driveBackup->downloadVerifiedContent($record);

        return response()->streamDownload(
            function () use ($content): void {
                echo $content;
            },
            $fileName,
            $this->privateImageHeaders($this->mimeType($record->selfie_path)),
        );
    }

    private function downloadFileName(AttendanceRecord $record): string
    {
        $extension = strtolower(pathinfo((string) $record->selfie_path, PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';

        return 'attendance-'.$record->id.'.'.$extension;
    }

    private function mimeType(?string $path): string
    {
        return match (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private function privateImageHeaders(?string $contentType = null): array
    {
        return array_filter([
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
