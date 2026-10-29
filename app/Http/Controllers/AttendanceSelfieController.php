<?php

namespace App\\Http\\Controllers;

use App\\Models\\AttendanceRecord;
use App\\Support\\AttendanceSelfie;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Storage;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

class AttendanceSelfieController extends Controller
{
    /**
     * Menampilkan selfie hanya kepada pemilik catatan absensi.
     */
    public function showForEmployee(Request $request, AttendanceRecord $record): StreamedResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless(
            $user->is_active && $employee && $record->employee_id === $employee->id,
            403,
            'Anda tidak memiliki akses ke foto absensi ini.'
        );

        return $this->streamSelfie($record);
    }

    /**
     * Menampilkan selfie dari disk private untuk admin/superadmin.
     * Route ini dilindungi middleware peran pada routes/web.php.
     */
    public function show(AttendanceRecord $record): StreamedResponse
    {
        return $this->streamSelfie($record);
    }

    private function streamSelfie(AttendanceRecord $record): StreamedResponse
    {
        $disk = Storage::disk(AttendanceSelfie::DISK);

        abort_unless($record->selfie_path && $disk->exists($record->selfie_path), 404);

        return $disk->response($record->selfie_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
