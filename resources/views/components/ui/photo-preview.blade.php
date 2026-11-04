@props([
    'src',
    'alt' => __('Pratinjau foto'),
    'title' => __('Pratinjau foto'),
    'triggerLabel' => __('Perbesar foto'),
    'downloadUrl' => null,
    'downloadName' => 'foto.jpg',
])

@php
    $previewId = 'photo-preview-'.\\Illuminate\\Support\\Str::uuid();
    $titleId = $previewId.'-title';
@endphp

<div class="relative inline-flex" x-data="{ previewOpen: false }">
    <button
        type="button"
        x-on:click="previewOpen = true"
        x-bind:aria-expanded="previewOpen"
        aria-controls="{{ $previewId }}"
        aria-haspopup="dialog"
        aria-label="{{ $triggerLabel }}"
        {{ $attributes->class(['inline-flex max-w-full rounded-lg text-left outline-offset-2 focus-visible:outline-2 focus-visible:outline-blue-600']) }}
    >
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            <img
                src="{{ $src }}"
                alt="{{ $alt }}"
                class="block h-auto w-auto max-h-48 max-w-full rounded-lg object-contain"
            />
        @endif
    </button>

    <div
        x-show="previewOpen"
        x-cloak
        x-transition.opacity
        x-on:click.self="previewOpen = false"
        x-on:keydown.escape.window="previewOpen = false"
        id="{{ $previewId }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
    >
        <div
            x-on:click.stop
            class="flex max-h-[calc(100dvh-2rem)] w-fit max-w-full flex-col rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 shadow-2xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark"
        >
            <div class="mb-2 flex items-center justify-between gap-4">
                <span id="{{ $titleId }}" class="text-sm font-medium">{{ $title }}</span>
                <div class="flex items-center gap-2">
                    @if (filled($downloadUrl))
                        <a
                            href="{{ $downloadUrl }}"
                            download="{{ $downloadName }}"
                            class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-2 focus-visible:outline-blue-600"
                        >
                            <flux:icon name="arrow-down-tray" variant="micro" />
                            {{ __('Unduh') }}
                        </a>
                    @endif
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
                src="{{ $src }}"
                alt="{{ $alt }}"
                class="block h-auto w-auto max-h-[calc(100dvh-7rem)] max-w-[calc(100vw-3.5rem)] rounded-lg object-contain"
            />
        </div>
    </div>
</div>
