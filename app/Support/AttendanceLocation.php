<?php
namespace App\Support;
final class AttendanceLocation
{
    public const MAX_ACCURACY_METERS = 100;
    public const ADDRESS_MAX_LENGTH = 500;
    public static function rules(string $latitude='latitude', string $longitude='longitude', string $accuracy='accuracy', string $address='address_snapshot'): array
    {
        return [
            $latitude => ['required','numeric','between:-90,90'],
            $longitude => ['required','numeric','between:-180,180'],
            $accuracy => ['required','numeric','min:0','max:'.self::MAX_ACCURACY_METERS],
            $address => ['nullable','string','max:'.self::ADDRESS_MAX_LENGTH],
        ];
    }
    public static function messages(string $accuracy='accuracy'): array
    {
        $message = 'Akurasi GPS harus ±'.self::MAX_ACCURACY_METERS.' meter atau lebih baik. Tunggu GPS lebih presisi lalu coba lagi.';
        return [
            $accuracy.'.required' => 'Lokasi GPS belum memiliki data akurasi. Ambil ulang lokasi.',
            $accuracy.'.max' => $message,
            $accuracy.'.between' => $message,
        ];
    }
}
