<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Support\AttendanceSelfie;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceSelfieController extends Controller
{
    /**
     * Menampilkan selfie dari disk private. Hanya admin/superadmin,
     * dijaga oleh middleware pada route (lihat routes/web.php).
     */
    public function show(AttendanceRecord $record): StreamedResponse
    {
        $disk = Storage::disk(AttendanceSelfie::DISK);

        abort_unless($record->selfie_path && $disk->exists($record->selfie_path), 404);

        return $disk->response($record->selfie_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
