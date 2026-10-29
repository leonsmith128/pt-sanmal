<?php
namespace App\Support;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
final class AttendanceSelfie
{
    public const DISK = 'local';
    public const MAX_SIZE_KB = 200;
    public static function rules(string $attribute='selfie'): array
    {
        return [$attribute => ['required','image','mimes:jpg,jpeg,png,webp','max:'.self::MAX_SIZE_KB]];
    }
    public static function store(UploadedFile $file, int|string $employeeId, ?\DateTimeInterface $at=null): string
    {
        $at ??= now();
        $path = $file->store('selfies/'.$employeeId.'/'.$at->format('Y/m'), self::DISK);
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['selfie' => 'Foto gagal disimpan. Silakan ambil foto kembali.']);
        }
        return $path;
    }
    public static function delete(?string $path): void
    {
        if ($path) Storage::disk(self::DISK)->delete($path);
    }
}
