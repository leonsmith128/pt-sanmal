<?php

namespace App\Livewire\Pages;

use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Absensi')]
class Attendance extends Component {
    use WithFileUploads;

    public string $workType = 'office';
    public $locationId = null;
    public $projectId = null;

    public $latitude = null;
    public $longitude = null;
    public $accuracy = null;
    public $selfie = null;
    public string $selfieSource = 'camera';

    public function mount(): void
    {
        $user = Auth::user();

        // Superadmin (tanpa profil pegawai) dan akun nonaktif tidak boleh absen.
        abort_unless($user->is_active && $user->employee, 403);

        $this->locationId = $this->officeLocations->first()?->id;
        $this->projectId = $this->projects->first()?->id;
    }

    #[Computed]
    public function employee()
    {
        return Auth::user()->employee;
    }

    #[Computed]
    public function openSession(): ?AttendanceRecord
    {
        return RecordAttendance::openSession($this->employee);
    }

    #[Computed]
    public function officeLocations()
    {
        return RecordAttendance::officeLocations()->get();
    }

    #[Computed]
    public function projects()
    {
        return RecordAttendance::assignableProjects($this->employee)->get();
    }

    #[Computed]
    public function history()
    {
        return $this->employee->attendanceRecords()
            ->with(['location', 'project'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    public function retakeSelfie(): void
    {
        $this->reset('selfie', 'selfieSource', 'latitude', 'longitude', 'accuracy');
        $this->resetErrorBag();
    }

    private function attendanceType(string $type): AttendanceType
    {
        $attendanceType = AttendanceType::tryFrom($type);
        abort_if($attendanceType === null, 422);

        return $attendanceType;
    }

    private function validateInput(AttendanceType $type): void
    {
        $rules = [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:200'],
            'selfieSource' => ['required', Rule::enum(SelfieSource::class)],
        ];

        if ($type === AttendanceType::ClockIn) {
            $rules['workType'] = ['required', Rule::enum(WorkType::class)];
        }

        $this->validate($rules, [
            'latitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'longitude.required' => 'Lokasi belum terambil. Ambil ulang foto agar lokasi ikut terambil.',
            'selfie.required' => 'Foto selfie wajib diambil.',
            'selfie.image' => 'File harus berupa gambar.',
            'selfie.mimes' => 'Format foto harus jpg, png, atau webp.',
            'selfie.max' => 'Ukuran foto maksimal 200 KB. Coba ambil ulang foto.',
        ]);
    }

    /** Langkah 1: validasi dulu, lalu minta konfirmasi lewat modal. */
    public function confirm(string $type): void
    {
        $this->validateInput($this->attendanceType($type));

        Flux::modal('confirm-attendance')->show();
    }

    /**
     * Langkah 2: dijalankan setelah pegawai menekan "Ya" di modal.
     * Modal hanya kenyamanan; validasi dan aturan absensi tetap ditegakkan di server.
     */
    public function submit(string $type): void
    {
        $attendanceType = $this->attendanceType($type);

        try {
            $this->validateInput($attendanceType);

            app(RecordAttendance::class)->handle(
                employee: $this->employee,
                type: $attendanceType,
                workType: WorkType::tryFrom($this->workType),
                locationId: $this->locationId,
                projectId: $this->projectId,
                latitude: (float) $this->latitude,
                longitude: (float) $this->longitude,
                accuracy: $this->accuracy !== null ? (float) $this->accuracy : null,
                selfie: $this->selfie,
                selfieSource: SelfieSource::from($this->selfieSource),
            );
        } catch (ValidationException $e) {
            // Tutup modal supaya pesan error terlihat di formulir.
            Flux::modal('confirm-attendance')->close();

            throw $e;
        }

        $this->reset('latitude', 'longitude', 'accuracy', 'selfie', 'selfieSource');
        unset($this->openSession, $this->history);

        Flux::modal('confirm-attendance')->close();

        Flux::toast(
            variant: 'success',
            text: $attendanceType === AttendanceType::ClockIn ? 'Absensi masuk tercatat.' : 'Absensi pulang tercatat.',
        );
    }

}
