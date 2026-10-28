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
                x-data="attendanceCamera"
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
