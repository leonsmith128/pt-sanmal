@props([
    'selfie' => null,
])

@if ($selfie && $selfie->isPreviewable())
    <div class="relative w-full" x-data="{ previewOpen: false }">
        <button
            type="button"
            x-on:click="previewOpen = true"
            x-bind:aria-expanded="previewOpen"
            aria-controls="attendance-photo-preview"
            aria-haspopup="dialog"
            aria-label="{{ __('Perbesar pratinjau foto absensi') }}"
            class="inline-flex max-w-full flex-col items-start rounded-lg text-left outline-offset-2 focus-visible:outline-2 focus-visible:outline-blue-600"
        >
            <img
                src="{{ $selfie->temporaryUrl() }}"
                alt="{{ __('Pratinjau foto absensi') }}"
                class="block h-auto w-auto max-h-48 max-w-full rounded-lg object-contain"
            />
        </button>

        <div
            x-show="previewOpen"
            x-cloak
            x-transition.opacity
            x-on:click.self="previewOpen = false"
            x-on:keydown.escape.window="previewOpen = false"
            id="attendance-photo-preview"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Pratinjau foto absensi ukuran penuh') }}"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        >
            <div
                x-on:click.stop
                class="flex max-h-[calc(100dvh-2rem)] w-fit max-w-full flex-col rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 shadow-2xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark"
            >
                <div class="mb-2 flex items-center justify-between gap-4">
                    <span class="text-sm font-medium">{{ __('Pratinjau foto') }}</span>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ $selfie->temporaryUrl() }}"
                            download="foto-absensi.{{ $selfie->guessExtension() ?: 'jpg' }}"
                            class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-2 focus-visible:outline-blue-600"
                        >
                            <flux:icon name="arrow-down-tray" variant="micro" />
                            {{ __('Unduh') }}
                        </a>
                        <button
                            type="button"
                            x-on:click="previewOpen = false"
                            class="rounded-md p-1 text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-2 focus-visible:outline-blue-600"
                            aria-label="{{ __('Tutup pratinjau foto') }}"
                        >
                            <flux:icon name="x-mark" variant="micro" />
                        </button>
                    </div>
                </div>
                <img
                    src="{{ $selfie->temporaryUrl() }}"
                    alt="{{ __('Foto absensi dalam ukuran asli') }}"
                    class="block h-auto w-auto max-h-[calc(100dvh-7rem)] max-w-[calc(100vw-3.5rem)] rounded-lg object-contain"
                />
            </div>
        </div>
        <div class="mt-2">
            <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
        </div>
    </div>
@endif
