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
                        class="relative isolate overflow-hidden rounded-none bg-zinc-900 shadow-2xl ring-1 ring-white/20 sm:rounded-[1.75rem]"
                        :class="isLandscape ? 'attendance-camera-landscape' : 'attendance-camera-portrait'"
                        :style="viewportStyle"
                    >
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
                            x-show="! capturedUrl"
                            class="pointer-events-none absolute bottom-[16%] left-1/2 z-10 max-w-[90%] -translate-x-1/2 rounded-full bg-black/55 px-3 py-1.5 text-center text-[11px] text-white shadow-sm backdrop-blur-sm"
                            role="status"
                            aria-live="polite"
                            x-text="gpsStatusText"
                        ></div>

                        <button
                            type="button"
                            x-show="! capturedUrl"
                            x-cloak
                            x-on:click="switchCamera()"
                            x-bind:disabled="busy || starting || ! canSwitchCamera"
                            x-bind:title="canSwitchCamera ? 'Ganti kamera' : 'Perangkat hanya memiliki satu kamera'"
                            class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-full bg-transparent text-white shadow-sm transition hover:bg-black/20 disabled:opacity-50"
                            aria-label="{{ __('Ganti kamera depan/belakang') }}"
                            title="{{ __('Ganti kamera') }}"
                        >
                            <flux:icon name="arrow-path" variant="outline" class="size-5" />
                        </button>

                        <div class="absolute inset-x-0 bottom-0 flex min-h-[14%] items-center justify-center gap-5 bg-black/90 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-2 sm:gap-7 sm:pb-5">
                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="remeasureLocation()"
                                x-bind:disabled="busy || starting"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50 sm:size-11"
                                aria-label="{{ __('Refresh lokasi') }}"
                                title="{{ __('Refresh lokasi') }}"
                            >
                                <flux:icon name="map-pin" variant="outline" class="size-5 sm:size-6" />
                            </button>

                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="capture()"
                                x-bind:disabled="busy || starting || ! videoReady"
                                class="flex size-16 shrink-0 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 transition active:scale-95 disabled:opacity-50 sm:size-[4.5rem]"
                                aria-label="{{ __('Ambil foto') }}"
                                title="{{ __('Ambil foto') }}"
                            >
                                <flux:icon name="camera" variant="outline" class="size-7 sm:size-8" />
                            </button>

                            <button
                                type="button"
                                x-show="! capturedUrl"
                                x-on:click="stop()"
                                x-bind:disabled="busy"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50 sm:size-11"
                                aria-label="{{ __('Tutup kamera') }}"
                                title="{{ __('Tutup kamera') }}"
                            >
                                <flux:icon name="x-mark" variant="outline" class="size-5 sm:size-6" />
                            </button>

                            <button
                                type="button"
                                x-show="capturedUrl"
                                x-on:click="retake()"
                                x-bind:disabled="busy"
                                class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 disabled:opacity-50"
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
                                class="flex size-16 items-center justify-center rounded-full border-[3px] border-white bg-white/15 text-white shadow-lg outline outline-2 outline-offset-2 outline-white/40 disabled:opacity-50"
                                aria-label="{{ __('Gunakan foto ini') }}"
                                title="{{ __('Gunakan foto ini') }}"
                            >
                                <flux:icon name="check" variant="outline" class="size-7" />
                            </button>
                            <button
                                type="button"
                                x-show="capturedUrl"
                                x-on:click="stop()"
                                x-bind:disabled="busy"
                                class="flex size-11 items-center justify-center rounded-full bg-transparent text-white transition hover:bg-white/15 disabled:opacity-50"
                                aria-label="{{ __('Tutup kamera') }}"
                                title="{{ __('Tutup kamera') }}"
                            >
                                <flux:icon name="x-mark" variant="outline" class="size-5" />
                            </button>
                        </div>

                        <div
                            x-show="error && active"
                            x-cloak
                            x-text="error"
                            role="alert"
                            class="absolute left-1/2 top-[18%] z-30 w-[90%] -translate-x-1/2 rounded-lg bg-red-950/90 px-3 py-2 text-center text-sm text-white shadow-lg"
                        ></div>

                        <div x-show="busy" x-cloak class="absolute inset-0 flex items-center justify-center bg-black/35 text-sm font-medium text-white">
                            {{ __('Memproses foto…') }}
                        </div>
                    </div>
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
