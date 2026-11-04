@props([
    'selfie' => null,
])

@if ($selfie && $selfie->isPreviewable())
    <div class="relative w-full" x-data="{ previewOpen: false }">
        <button
            type="button"
            x-on:click="previewOpen = ! previewOpen"
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
            x-transition
            x-on:click.outside="previewOpen = false"
            x-on:keydown.escape.window="previewOpen = false"
            id="attendance-photo-preview"
            role="dialog"
            aria-label="{{ __('Pratinjau foto absensi ukuran penuh') }}"
            class="absolute left-0 top-full z-40 mt-2 w-max max-w-[calc(100vw-2rem)] rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 shadow-xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark"
        >
            <div class="mb-2 flex items-center justify-between gap-4">
                <span class="text-sm font-medium">{{ __('Pratinjau foto') }}</span>
                <button
                    type="button"
                    x-on:click="previewOpen = false"
                    class="rounded-md p-1 text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-2 focus-visible:outline-blue-600"
                    aria-label="{{ __('Tutup pratinjau foto') }}"
                >
                    <flux:icon name="x-mark" variant="micro" />
                </button>
            </div>
            <img
                src="{{ $selfie->temporaryUrl() }}"
                alt="{{ __('Foto absensi dalam ukuran asli') }}"
                class="block h-auto w-auto max-h-[75vh] max-w-[calc(100vw-3.5rem)] rounded-lg object-contain"
            />
        </div>
        <div class="mt-2">
            <flux:button type="button" size="sm" icon="arrow-path" wire:click="retakeSelfie">{{ __('Foto ulang') }}</flux:button>
        </div>
    </div>
@endif
