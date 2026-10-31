<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceSelfieDriveBackup;
use Illuminate\Http\RedirectResponse;
use Throwable;

class AttendanceSelfieDriveController extends Controller
{
    public function store(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $backup,
    ): RedirectResponse {
        abort_unless($record->selfie_path, 404);

        try {
            $backup->backup($record);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'drive_backup' => 'Backup Google Drive gagal. Periksa konfigurasi dan coba kembali.',
            ]);
        }

        return back()->with('status', 'Foto absensi berhasil diverifikasi di Google Drive.');
    }

    public function destroyLocal(
        AttendanceRecord $record,
        AttendanceSelfieDriveBackup $backup,
    ): RedirectResponse {
        try {
            $backup->deleteLocalAfterVerification($record);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'drive_backup' => 'Foto lokal tidak dihapus karena verifikasi backup belum berhasil.',
            ]);
        }

        return back()->with('status', 'Foto lokal dihapus setelah backup Google Drive terverifikasi.');
    }
}
