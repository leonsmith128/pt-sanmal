<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Support\AttendanceSelfie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AttendanceSelfieDriveBackup
{
    private const TOKEN_CACHE_KEY = 'attendance-google-drive-access-token';

    public function backup(AttendanceRecord $record): AttendanceRecord
    {
        try {
            $disk = Storage::disk(AttendanceSelfie::DISK);

            if ($record->drive_file_id && $record->drive_md5_checksum && $record->drive_size_bytes !== null) {
                $metadata = $this->fileMetadata($record->drive_file_id);
                $this->assertRemoteFileMatches(
                    $metadata,
                    $record->drive_md5_checksum,
                    (int) $record->drive_size_bytes,
                );

                if ($record->selfie_path && $disk->exists($record->selfie_path)) {
                    $localContent = $disk->get($record->selfie_path);
                    if (! is_string($localContent)) {
                        throw new RuntimeException('Isi foto lokal tidak dapat dibaca.');
                    }

                    $this->assertContentMatches(
                        $localContent,
                        $record->drive_md5_checksum,
                        (int) $record->drive_size_bytes,
                    );
                }

                return $this->markVerified(
                    $record,
                    $record->drive_file_id,
                    $record->drive_md5_checksum,
                    (int) $record->drive_size_bytes,
                );
            }

            if (! $record->selfie_path || ! $disk->exists($record->selfie_path)) {
                throw new RuntimeException('Foto lokal tidak ditemukan untuk backup.');
            }

            $content = $disk->get($record->selfie_path);
            if (! is_string($content)) {
                throw new RuntimeException('Isi foto lokal tidak dapat dibaca.');
            }

            $checksum = md5($content);
            $size = strlen($content);

            $fileId = $this->upload($record, $content);

            try {
                $metadata = $this->fileMetadata($fileId);
                $this->assertRemoteFileMatches($metadata, $checksum, $size);
            } catch (Throwable $exception) {
                $this->deleteRemoteFile($fileId);
                throw $exception;
            }

            return $this->markVerified($record, $fileId, $checksum, $size);
        } catch (Throwable $exception) {
            $record->forceFill(['drive_backup_status' => 'failed'])->save();

            throw $exception;
        }
    }

    public function deleteLocalAfterVerification(AttendanceRecord $record): AttendanceRecord
    {
        if ($record->drive_backup_status !== 'verified' || ! $record->drive_file_id
            || ! $record->drive_md5_checksum || $record->drive_size_bytes === null
            || ! $record->selfie_path) {
            throw new RuntimeException('Foto belum memiliki backup Drive yang terverifikasi.');
        }

        $disk = Storage::disk(AttendanceSelfie::DISK);

        if ($record->local_deleted_at && ! $disk->exists($record->selfie_path)) {
            return $record;
        }

        if (! $disk->exists($record->selfie_path)) {
            throw new RuntimeException('Foto lokal tidak ditemukan; penghapusan dibatalkan.');
        }

        $content = $disk->get($record->selfie_path);
        if (! is_string($content)) {
            throw new RuntimeException('Isi foto lokal tidak dapat dibaca.');
        }

        $localChecksum = md5($content);
        $localSize = strlen($content);

        if ($localChecksum !== $record->drive_md5_checksum || $localSize !== (int) $record->drive_size_bytes) {
            throw new RuntimeException('Checksum atau ukuran foto lokal tidak cocok dengan metadata backup.');
        }

        $metadata = $this->fileMetadata($record->drive_file_id);
        $this->assertRemoteFileMatches($metadata, $localChecksum, $localSize);

        if (! $disk->delete($record->selfie_path)) {
            throw new RuntimeException('Foto lokal gagal dihapus.');
        }

        $record->forceFill(['local_deleted_at' => now()])->save();

        return $record->refresh();
    }

    public function downloadVerifiedContent(AttendanceRecord $record): string
    {
        if ($record->drive_backup_status !== 'verified' || ! $record->drive_file_id
            || ! $record->drive_md5_checksum || $record->drive_size_bytes === null) {
            throw new RuntimeException('Backup Drive foto tidak tersedia atau belum terverifikasi.');
        }

        $metadata = $this->fileMetadata($record->drive_file_id);
        $this->assertRemoteFileMatches(
            $metadata,
            $record->drive_md5_checksum,
            (int) $record->drive_size_bytes,
        );

        $response = Http::withToken($this->accessToken())
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($record->drive_file_id), [
                'alt' => 'media',
                'supportsAllDrives' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Foto backup tidak dapat diunduh dari Google Drive.');
        }

        $content = $response->body();
        $this->assertContentMatches($content, $record->drive_md5_checksum, (int) $record->drive_size_bytes);

        return $content;
    }

    private function markVerified(
        AttendanceRecord $record,
        string $fileId,
        string $checksum,
        int $size,
    ): AttendanceRecord {
        $record->forceFill([
            'drive_backup_status' => 'verified',
            'drive_file_id' => $fileId,
            'drive_md5_checksum' => $checksum,
            'drive_size_bytes' => $size,
            'drive_backed_up_at' => now(),
        ])->save();

        return $record->refresh();
    }

    private function upload(AttendanceRecord $record, string $content): string
    {
        $extension = strtolower(pathinfo((string) $record->selfie_path, PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $metadata = [
            'name' => 'attendance-'.$record->id.'.'.$extension,
            'mimeType' => $mimeType,
            'appProperties' => ['attendanceRecordId' => (string) $record->id],
        ];

        $folderId = config('services.google_drive.folder_id');
        if (is_string($folderId) && $folderId !== '') {
            $metadata['parents'] = [$folderId];
        }

        $boundary = 'sanmal_'.Str::random(24);
        $body = '--'.$boundary."\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata, JSON_THROW_ON_ERROR)."\r\n"
            .'--'.$boundary."\r\n"
            .'Content-Type: '.$mimeType."\r\n"
            ."Content-Transfer-Encoding: binary\r\n\r\n"
            .$content."\r\n"
            .'--'.$boundary."--\r\n";

        $response = Http::withToken($this->accessToken())
            ->withBody($body, 'multipart/related; boundary='.$boundary)
            ->timeout(30)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id&supportsAllDrives=true');

        if (! $response->successful() || ! is_string($response->json('id'))) {
            throw new RuntimeException('Upload foto ke Google Drive gagal.');
        }

        $fileId = $response->json('id');
        if (! is_string($fileId)) {
            throw new RuntimeException('Google Drive tidak mengembalikan ID file.');
        }

        return $fileId;
    }

    private function fileMetadata(string $fileId): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(10)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), [
                'fields' => 'id,name,size,md5Checksum',
                'supportsAllDrives' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Metadata foto di Google Drive tidak dapat diverifikasi.');
        }

        $metadata = $response->json();
        if (! is_array($metadata)) {
            throw new RuntimeException('Metadata Google Drive tidak valid.');
        }

        return $metadata;
    }

    private function assertRemoteFileMatches(array $metadata, string $checksum, int $size): void
    {
        if (($metadata['md5Checksum'] ?? null) !== $checksum
            || (int) ($metadata['size'] ?? -1) !== $size) {
            throw new RuntimeException('Verifikasi checksum atau ukuran foto Google Drive gagal.');
        }
    }

    private function assertContentMatches(string $content, string $checksum, int $size): void
    {
        if (md5($content) !== $checksum || strlen($content) !== $size) {
            throw new RuntimeException('Isi foto dari Google Drive tidak cocok dengan backup terverifikasi.');
        }
    }

    private function deleteRemoteFile(string $fileId): void
    {
        Http::withToken($this->accessToken())
            ->timeout(10)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId).'?supportsAllDrives=true');
    }

    private function accessToken(): string
    {
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if (! is_string($clientId) || $clientId === ''
            || ! is_string($clientSecret) || $clientSecret === ''
            || ! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException('Kredensial OAuth Google Drive belum dikonfigurasi.');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new RuntimeException('Token akses Google Drive tidak dapat diperbarui.');
        }

        $expiresIn = max(60, (int) $response->json('expires_in', 3600) - 60);
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn));

        return $token;
    }
}
