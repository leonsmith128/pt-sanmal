<?php

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

new #[Title('Absensi')] class extends Component {
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
}; ?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Absensi') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Catat kehadiran masuk dan pulang Anda.') }}</flux:text>
        </div>

        @php
            $session = $this->openSession;
            $mode = $session ? 'clock_out' : 'clock_in';
        @endphp

        {{-- Status saat ini --}}
        <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            @if ($session)
                <flux:badge color="green">{{ __('Sudah masuk') }}</flux:badge>
                <flux:text class="mt-3">
                    {{ __('Masuk pukul') }} {{ $session->recordedAtLocal()->format('H:i T') }}
                    &middot; {{ $session->work_type->label() }}
                    @if ($session->project) &middot; {{ $session->project->name }} @endif
                    @if (! $session->project && $session->location) &middot; {{ $session->location->name }} @endif
                </flux:text>
            @else
                <flux:badge color="zinc">{{ __('Belum clock-in') }}</flux:badge>
                <flux:text class="mt-3">{{ __('Pilih lokasi kerja, ambil lokasi dan selfie, lalu tekan Clock In.') }}</flux:text>
            @endif
        </div>

        {{-- Form absensi --}}
        <form wire:submit="confirm('{{ $mode }}')" class="flex flex-col gap-5 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            @if ($mode === 'clock_in')
                <flux:select wire:model.live="workType" :label="__('Jenis lokasi kerja')">
                    <flux:select.option value="office">{{ __('Kantor') }}</flux:select.option>
                    <flux:select.option value="onsite">{{ __('Onsite (Proyek)') }}</flux:select.option>
                </flux:select>

                @if ($workType === 'office')
                    @if ($this->officeLocations->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada lokasi kantor aktif. Hubungi admin.') }}</flux:text>
                    @else
                        <flux:select wire:model="locationId" :label="__('Kantor')">
                            @foreach ($this->officeLocations as $office)
                                <flux:select.option value="{{ $office->id }}">{{ $office->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    @error('locationId') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @else
                    @if ($this->projects->isEmpty())
                        <flux:text class="text-red-500">{{ __('Belum ada proyek aktif yang ditugaskan kepada Anda. Hubungi admin.') }}</flux:text>
                    @else
                        <flux:select wire:model="projectId" :label="__('Proyek')">
                            @foreach ($this->projects as $project)
                                <flux:select.option value="{{ $project->id }}">{{ $project->code }} &ndash; {{ $project->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    @error('projectId') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @endif
            @else
                <flux:text>{{ __('Clock-out memakai lokasi dan proyek yang sama dengan clock-in.') }}</flux:text>
            @endif

            {{-- Foto selfie + lokasi: keduanya terambil bersamaan saat tombol Ambil Foto ditekan --}}
            <div
                x-data="{
                    stream: null,
                    active: false,
                    busy: false,
                    fallback: false,
                    error: null,
                    async openCamera() {
                        // Resolusi tidak dipaksa (foto toh dikecilkan ke 800 px); beberapa driver kamera
                        // menghasilkan layar hitam kalau diminta resolusi tertentu.
                        const attempts = [
                            { video: { facingMode: 'user' }, audio: false },
                            { video: true, audio: false }
                        ];
                        let lastError = null;
                        for (const constraints of attempts) {
                            try {
                                return await navigator.mediaDevices.getUserMedia(constraints);
                            } catch (e) {
                                lastError = e;
                                if (e.name === 'NotAllowedError' || e.name === 'SecurityError') { break; }
                            }
                        }
                        throw lastError;
                    },
                    async start() {
                        this.error = null;
                        if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
                            this.error = 'Kamera tidak tersedia. Buka lewat HTTPS atau localhost, atau unggah foto manual.';
                            this.fallback = true;
                            return;
                        }
                        try {
                            this.stream = await this.openCamera();
                            this.active = true;
                            await this.$nextTick();
                            const video = this.$refs.video;
                            video.muted = true;
                            video.srcObject = this.stream;
                            try { await video.play(); } catch (playError) { /* autoplay akan menjalankannya */ }
                            this.warmUpLocation();
                            this.checkFrames(video);
                        } catch (e) {
                            this.stop();
                            if (e.name === 'NotAllowedError') {
                                this.error = 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser.';
                            } else if (e.name === 'NotFoundError') {
                                this.error = 'Kamera tidak ditemukan di perangkat ini.';
                            } else if (e.name === 'NotReadableError') {
                                this.error = 'Kamera tidak bisa dibuka. Mungkin sedang dipakai aplikasi atau tab lain.';
                            } else {
                                this.error = 'Kamera tidak dapat dibuka. Coba muat ulang halaman.';
                            }
                            this.fallback = true;
                        }
                    },
                    checkFrames(video) {
                        setTimeout(() => {
                            if (this.active && video.videoWidth === 0) {
                                this.error = 'Kamera terbuka tetapi tidak mengirim gambar. Pastikan kamera tidak dipakai aplikasi atau tab lain, lalu tutup dan buka kembali.';
                                this.fallback = true;
                            }
                        }, 3000);
                    },
                    stop() {
                        if (this.stream) { this.stream.getTracks().forEach((t) => t.stop()); }
                        this.stream = null;
                        this.active = false;
                    },
                    warmUpLocation() {
                        if (! navigator.geolocation) { return; }
                        navigator.geolocation.getCurrentPosition(
                            () => {},
                            (err) => {
                                if (err.code === 1) {
                                    this.error = 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.';
                                }
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    },
                    getPosition() {
                        return new Promise((resolve, reject) => {
                            if (! navigator.geolocation) {
                                reject(new Error('no-geolocation'));
                                return;
                            }
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 15000,
                                maximumAge: 10000
                            });
                        });
                    },
                    async compress(source, w, h) {
                        const limit = 190 * 1024;
                        let scale = Math.min(1, 800 / Math.max(w, h));
                        let blob = null;
                        for (let round = 0; round < 4; round++) {
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(w * scale);
                            canvas.height = Math.round(h * scale);
                            canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
                            for (const q of [0.8, 0.7, 0.6, 0.5, 0.4]) {
                                blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', q));
                                if (blob && blob.size <= limit) { return blob; }
                            }
                            scale *= 0.8;
                        }
                        return blob;
                    },
                    async send(source, w, h, kind) {
                        this.error = null;
                        this.busy = true;
                        try {
                            const [pos, blob] = await Promise.all([this.getPosition(), this.compress(source, w, h)]);
                            if (! blob) { throw new Error('no-photo'); }
                            $wire.latitude = pos.coords.latitude;
                            $wire.longitude = pos.coords.longitude;
                            $wire.accuracy = pos.coords.accuracy;
                            $wire.selfieSource = kind;
                            const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                            await new Promise((resolve, reject) => $wire.upload('selfie', file, resolve, reject));
                            this.stop();
                        } catch (e) {
                            if (typeof GeolocationPositionError !== 'undefined' && e instanceof GeolocationPositionError) {
                                this.error = e.code === 1
                                    ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.'
                                    : 'Lokasi tidak dapat diambil. Coba lagi di tempat yang lebih terbuka.';
                            } else if (e && e.message === 'no-geolocation') {
                                this.error = 'Browser ini tidak mendukung GPS.';
                            } else {
                                this.error = 'Gagal memproses atau mengunggah foto. Coba lagi.';
                            }
                        } finally {
                            this.busy = false;
                        }
                    },
                    capture() {
                        const video = this.$refs.video;
                        if (! video || ! video.videoWidth || this.busy) { return; }
                        this.send(video, video.videoWidth, video.videoHeight, 'camera');
                    },
                    async onFile(event) {
                        const file = event.target.files[0];
                        if (! file || this.busy) { return; }
                        try {
                            const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                            await this.send(bitmap, bitmap.width, bitmap.height, 'upload');
                        } catch (e) {
                            this.error = 'File gambar tidak dapat dibaca.';
                        }
                        event.target.value = '';
                    },
                    destroy() { this.stop(); }
                }"
                x-on:livewire:navigating.window="stop()"
                class="flex flex-col gap-2"
            >
                <flux:text class="font-medium">{{ __('Foto selfie') }}</flux:text>
                <flux:text class="text-sm">{{ __('Lokasi GPS akan ikut terambil otomatis saat foto diambil.') }}</flux:text>

                @if ($selfie && $selfie->isPreviewable())
                    <img src="{{ $selfie->temporaryUrl() }}" alt="Pratinjau selfie" class="w-full max-w-xs rounded-lg object-cover" />
                    <div>
                        <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
                    </div>
                @else
                    <div x-show="active" x-cloak class="flex flex-col gap-2">
                        <video x-ref="video" autoplay playsinline muted class="w-full max-w-xs rounded-lg bg-black" style="transform: scaleX(-1)"></video>
                        <div class="flex gap-2">
                            <flux:button type="button" variant="primary" icon="camera" x-on:click="capture()" x-bind:disabled="busy">{{ __('Ambil Foto') }}</flux:button>
                            <flux:button type="button" x-on:click="stop()" x-bind:disabled="busy">{{ __('Tutup Kamera') }}</flux:button>
                        </div>
                    </div>

                    {{-- x-if: tombol dihapus dari halaman (bukan sekadar disembunyikan) saat kamera aktif --}}
                    <template x-if="! active">
                        <div>
                            <flux:button type="button" icon="camera" x-on:click="start()">{{ __('Buka Kamera') }}</flux:button>
                        </div>
                    </template>

                    <div x-show="fallback && ! active" x-cloak class="flex flex-col gap-1">
                        <flux:text class="text-sm">{{ __('Atau unggah foto secara manual:') }}</flux:text>
                        <input
                            type="file"
                            accept="image/*"
                            capture="user"
                            x-on:change="onFile($event)"
                            class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-4 file:py-2 file:text-sm file:font-medium dark:text-zinc-300 dark:file:bg-zinc-700"
                        />
                    </div>
                @endif

                <flux:text x-show="busy" x-cloak>{{ __('Memproses foto dan lokasi...') }}</flux:text>
                <div wire:loading wire:target="selfie">
                    <flux:text>{{ __('Mengunggah foto...') }}</flux:text>
                </div>

                <flux:text x-show="$wire.latitude !== null" x-cloak class="text-sm text-green-600 dark:text-green-400">
                    {{ __('Lokasi terambil') }} &middot; {{ __('akurasi') }} &plusmn;<span x-text="Math.round($wire.accuracy ?? 0)"></span> m
                </flux:text>

                <flux:text x-show="error" x-cloak x-text="error" class="text-red-500"></flux:text>
                @error('selfie') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
                @error('latitude') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            </div>

            @error('form') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            @error('workType') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror

            <flux:button
                type="submit"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="confirm,submit,selfie"
            >
                {{ $mode === 'clock_in' ? __('Clock In') : __('Clock Out') }}
            </flux:button>
        </form>

        {{-- Riwayat --}}
        <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading level="2">{{ __('Riwayat terakhir') }}</flux:heading>

            @forelse ($this->history as $record)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-3 last:border-0 dark:border-neutral-800">
                    <div>
                        <flux:text class="font-medium">{{ $record->recordedAtLocal()->format('d M Y, H:i T') }}</flux:text>
                        <flux:text class="text-sm">
                            {{ $record->work_type->label() }}
                            @if ($record->project) &middot; {{ $record->project->name }}
                            @elseif ($record->location) &middot; {{ $record->location->name }} @endif
                        </flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($record->selfie_source === \App\Enums\SelfieSource::Upload)
                            <flux:badge color="amber" size="sm">{{ __('Foto unggahan') }}</flux:badge>
                        @endif
                        <flux:badge :color="$record->type === \App\Enums\AttendanceType::ClockIn ? 'green' : 'blue'">
                            {{ $record->type->label() }}
                        </flux:badge>
                    </div>
                </div>
            @empty
                <flux:text class="mt-3">{{ __('Belum ada catatan absensi.') }}</flux:text>
            @endforelse
        </div>

        {{-- Modal konfirmasi sebelum absensi dikirim --}}
        @php
            $confirmWork = $session
                ? $session->work_type->label()
                : \App\Enums\WorkType::tryFrom($workType)?->label();

            $confirmPlace = match (true) {
                (bool) $session => $session->project?->name ?? $session->location?->name,
                $workType === 'onsite' => $this->projects->firstWhere('id', (int) $projectId)?->name,
                default => $this->officeLocations->firstWhere('id', (int) $locationId)?->name,
            };
        @endphp
        <flux:modal name="confirm-attendance" class="max-w-md">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">
                        {{ $mode === 'clock_in' ? __('Konfirmasi Clock In') : __('Konfirmasi Clock Out') }}
                    </flux:heading>
                    <flux:subheading>{{ __('Periksa data berikut sebelum mengirim absensi.') }}</flux:subheading>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Jenis absensi') }}</dt>
                        <dd class="font-medium">{{ $mode === 'clock_in' ? __('Masuk') : __('Pulang') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Lokasi kerja') }}</dt>
                        <dd class="font-medium">{{ $confirmWork ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Tempat / proyek') }}</dt>
                        <dd class="text-right font-medium">{{ $confirmPlace ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Akurasi GPS') }}</dt>
                        <dd class="font-medium">
                            @if ($accuracy !== null) &plusmn;{{ round((float) $accuracy) }} m @else — @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Sumber foto') }}</dt>
                        <dd class="font-medium">{{ $selfieSource === 'upload' ? __('Unggah manual') : __('Kamera') }}</dd>
                    </div>
                </dl>

                @if ($selfie && $selfie->isPreviewable())
                    <img src="{{ $selfie->temporaryUrl() }}" alt="Pratinjau selfie" class="h-32 w-32 rounded-lg object-cover" />
                @endif

                <flux:text class="text-sm">
                    {{ __('Waktu absensi dicatat otomatis oleh sistem saat Anda menekan tombol konfirmasi.') }}
                </flux:text>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                    </flux:modal.close>

                    <flux:button
                        variant="primary"
                        wire:click="submit('{{ $mode }}')"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        {{ $mode === 'clock_in' ? __('Ya, Clock In') : __('Ya, Clock Out') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</section>
