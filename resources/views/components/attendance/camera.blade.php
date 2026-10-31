@props([
    'name',
    'eventLabel',
    'selfie' => null,
])

<div
                x-data="attendanceCamera()"
                data-attendance-name="{{ $name }}"
                data-attendance-event="{{ $eventLabel }}"
                data-reverse-geocode-url="{{ route('attendance.reverse-geocode') }}"
                x-on:livewire:navigating.window="stop()"
                x-on:attendance-camera-open.window="start()"
                class="flex flex-col gap-2"
            >
                @if ($selfie && $selfie->isPreviewable())
                    <img src="{{ $selfie->temporaryUrl() }}" alt="Pratinjau foto absensi" class="w-full max-w-xs rounded-lg object-cover" />
                    <div>
                        <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
                    </div>
                @endif

                <div
                    x-show="active"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-transparent"
                    :class="window.matchMedia('(max-width: 640px)').matches ? 'h-[100dvh] w-screen' : ''"
                    aria-label="{{ __('Kamera absensi') }}"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="relative isolate flex flex-col overflow-hidden rounded-none bg-zinc-900 shadow-2xl ring-1 ring-white/20 sm:rounded-[1.75rem]"
                        :class="isLandscape ? 'attendance-camera-landscape' : 'attendance-camera-portrait'"
                        :style="viewportStyle"
                    >
                        <div class="relative min-h-0 flex-1 overflow-hidden bg-zinc-900">
                            <video
                                x-ref="video"
                                x-show="! capturedUrl"
                                autoplay
                                playsinline
                                muted
                                class="absolute inset-0 h-full w-full bg-zinc-900 object-contain"
                                :style="facingMode === 'user' ? 'transform:scaleX(-1)' : 'transform:none'"
                            ></video>

                            <img
                                x-show="capturedUrl"
                                x-cloak
                                :src="capturedUrl"
                                alt="{{ __('Pratinjau akhir foto absensi') }}"
                                class="absolute inset-0 h-full w-full bg-zinc-900 object-contain"
                            />

                            <canvas
                                x-ref="watermarkPreview"
                                x-show="! capturedUrl"
                                aria-hidden="true"
                                class="pointer-events-none absolute inset-0 z-10 h-full w-full object-contain"
                            ></canvas>

                            <div
                                x-show="error && active"
                                x-cloak
                                x-text="error"
                                role="alert"
                                class="absolute left-1/2 top-[18%] z-30 w-[90%] -translate-x-1/2 rounded-lg bg-red-950/90 px-3 py-2 text-center text-sm text-white shadow-lg"
                            ></div>

                            <div x-show="busy" x-cloak class="absolute inset-0 z-30 flex items-center justify-center bg-black/35 text-sm font-medium text-white">
                                {{ __('Memproses foto…') }}
                            </div>
                        </div>

                        <div class="relative z-20 flex min-h-[76px] shrink-0 items-center justify-center gap-3 bg-zinc-950 px-4 py-2 text-white sm:min-h-[84px] sm:px-6">
                            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                                <button
                                    type="button"
                                    x-show="! capturedUrl"
                                    x-on:click="switchCamera()"
                                    x-bind:disabled="busy || starting || ! canSwitchCamera"
                                    x-bind:title="canSwitchCamera ? 'Ganti kamera' : 'Perangkat hanya memiliki satu kamera'"
                                    class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                                    aria-label="{{ __('Ganti kamera depan/belakang') }}"
                                    title="{{ __('Ganti kamera') }}"
                                >
                                    <flux:icon name="arrow-path" variant="outline" class="size-5" />
                                </button>
                                <button
                                    type="button"
                                    x-show="! capturedUrl"
                                    x-on:click="remeasureLocation()"
                                    x-bind:disabled="busy || starting"
                                    class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                                    aria-label="{{ __('Refresh lokasi') }}"
                                    title="{{ __('Refresh lokasi') }}"
                                >
                                    <flux:icon name="map-pin" variant="outline" class="size-5" />
                                </button>

                                <button
                                    type="button"
                                    x-show="! capturedUrl"
                                    x-on:click="capture()"
                                    x-bind:disabled="busy || starting || ! videoReady || ! locationAccessReady"
                                    class="flex size-14 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 transition active:scale-95 disabled:opacity-50 sm:size-16"
                                    aria-label="{{ __('Ambil foto') }}"
                                    title="{{ __('Ambil foto') }}"
                                >
                                    <flux:icon name="camera" variant="outline" class="size-6 sm:size-7" />
                                </button>

                                <button
                                    type="button"
                                    x-show="! capturedUrl"
                                    x-on:click="stop()"
                                    x-bind:disabled="busy"
                                    class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                                    aria-label="{{ __('Tutup kamera') }}"
                                    title="{{ __('Tutup kamera') }}"
                                >
                                    <flux:icon name="x-mark" variant="outline" class="size-5" />
                                </button>

                                <button
                                    type="button"
                                    x-show="capturedUrl"
                                    x-on:click="retake()"
                                    x-bind:disabled="busy"
                                    class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                                    aria-label="{{ __('Foto ulang') }}"
                                    title="{{ __('Foto ulang') }}"
                                >
                                    <flux:icon name="arrow-left" variant="outline" class="size-5" />
                                </button>
                                <button
                                    type="button"
                                    x-show="capturedUrl"
                                    x-on:click="useCapturedPhoto()"
                                    x-bind:disabled="busy || ! capturedBlob"
                                    class="flex size-14 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 disabled:opacity-50"
                                    aria-label="{{ __('Gunakan foto ini') }}"
                                    title="{{ __('Gunakan foto ini') }}"
                                >
                                    <flux:icon name="check" variant="outline" class="size-6" />
                                </button>
                                <button
                                    type="button"
                                    x-show="capturedUrl"
                                    x-on:click="stop()"
                                    x-bind:disabled="busy"
                                    class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                                    aria-label="{{ __('Tutup kamera') }}"
                                    title="{{ __('Tutup kamera') }}"
                                >
                                    <flux:icon name="x-mark" variant="outline" class="size-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    x-show="starting && ! active"
                    x-cloak
                    role="status"
                    aria-live="polite"
                    class="rounded-xl bg-zinc-100 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    {{ __('Memeriksa izin dan layanan lokasi. Proses pencarian lokasi dibatasi 5 detik setelah izin diberikan; koordinat dapat menyusul.') }}
                </div>

                <div x-show="fallback && ! active" x-cloak class="rounded-xl bg-zinc-100 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <p>{{ __('Kamera tidak tersedia. Pastikan izin kamera diberikan dan halaman dibuka melalui HTTPS atau localhost.') }}</p>
                    <p x-show="error" x-text="error" class="mt-1 text-red-600 dark:text-red-400"></p>
                </div>

                <flux:text x-show="$wire.latitude !== null" x-cloak class="text-sm text-green-600 dark:text-green-400">
                    {{ __('Lokasi terambil') }} &middot; {{ __('akurasi') }} &plusmn;<span x-text="Math.round($wire.accuracy ?? 0)"></span> m
                </flux:text>

                <flux:text x-show="error && ! active" x-cloak x-text="error" class="text-sm text-red-500"></flux:text>
                @error('selfie') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror
            </div>
