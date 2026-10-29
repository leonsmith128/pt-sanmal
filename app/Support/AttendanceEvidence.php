<?php
namespace App\Support;
use Illuminate\Support\Facades\Validator;
final class AttendanceEvidence
{
    public static function rules(array $fields=[]): array
    {
        $fields = array_merge([
            'latitude'=>'latitude','longitude'=>'longitude','accuracy'=>'accuracy',
            'address'=>'address_snapshot','selfie'=>'selfie',
        ], $fields);
        return array_merge(
            AttendanceLocation::rules($fields['latitude'],$fields['longitude'],$fields['accuracy'],$fields['address']),
            AttendanceSelfie::rules($fields['selfie']),
        );
    }
    public static function messages(string $accuracy='accuracy', string $selfie='selfie'): array
    {
        return array_merge(AttendanceLocation::messages($accuracy), [
            $selfie.'.required'=>'Foto selfie wajib diambil.',
            $selfie.'.image'=>'File harus berupa gambar.',
            $selfie.'.mimes'=>'Format foto harus jpg, png, atau webp.',
            $selfie.'.max'=>'Ukuran foto maksimal 200 KB. Coba ambil ulang foto.',
        ]);
    }
    public static function validate(array $data): array
    {
        return Validator::make($data,self::rules(),self::messages())->validate();
    }
}
