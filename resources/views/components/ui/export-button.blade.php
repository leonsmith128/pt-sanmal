{{--
    Tombol ekspor seragam. Hanya UI: halaman menentukan rute, rentang, dan teks konfirmasi.

    - `href`    : satu tombol link unduhan.
    - `options` : dropdown pilihan rentang dengan dialog konfirmasi; tiap opsi memuat
                  label, href, description, icon, dan optional divider_before.
                  `summary` dan `warning` ditampilkan di dialog konfirmasi.
    - tanpa keduanya: tombol submit untuk form yang disediakan halaman.
--}}
@props([
    'href' => null,
    'options' => [],
    'summary' => null,
    'warning' => null,
    'label' => null,
    'confirmTitle' => null,
])

@php
    $label ??= __('Ekspor');
@endphp

@if ($options !== [])
    <div
        x-data="{ exportConfirmOpen: false, exportUrl: '', exportDescription: '' }"
        x-on:keydown.escape.window="exportConfirmOpen = false"
        {{ $attributes->class(['w-full sm:w-auto']) }}
    >
        <flux:dropdown position="bottom" align="end">
            <x-ui.button size="sm" variant="outline" class="w-full flex-row items-center justify-center gap-2 whitespace-nowrap sm:w-auto" icon="arrow-down-tray">
                <span class="inline-flex items-center gap-2 whitespace-nowrap">
                    {{ $label }}
                    <flux:icon name="chevron-down" variant="micro" />
                </span>
            </x-ui.button>

            <flux:menu>
                @foreach ($options as $option)
                    @if ($option['divider_before'] ?? false)
                        <flux:menu.separator />
                    @endif

                    <flux:menu.item
                        :href="$option['href']"
                        x-on:click.prevent="exportUrl = $el.href; exportDescription = @js($option['description']); exportConfirmOpen = true"
                        :icon="$option['icon']"
                    >
                        {{ $option['label'] }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <div
            x-show="exportConfirmOpen"
            x-cloak
            x-transition.opacity
            x-on:click.self="exportConfirmOpen = false"
            class="fixed inset-0 z-[110] flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="export-confirm-title"
        >
            <div class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-5 shadow-xl dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="export-confirm-title" class="text-lg font-semibold">{{ $confirmTitle ?? __('Konfirmasi ekspor') }}</h2>
                    <x-ui.button type="button" variant="ghost" icon="x-mark" square aria-label="{{ __('Tutup') }}" x-on:click="exportConfirmOpen = false" />
                </div>

                <p class="text-sm text-zinc-600 dark:text-zinc-300" x-text="exportDescription"></p>
                @if ($summary)
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $summary }}</p>
                @endif
                @if ($warning)
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $warning }}</p>
                @endif

                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" x-on:click="exportConfirmOpen = false">{{ __('Batal') }}</x-ui.button>
                    <x-ui.button href="#" x-bind:href="exportUrl" variant="primary" x-on:click="exportConfirmOpen = false">
                        {{ __('Konfirmasi dan ekspor') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
@elseif ($href !== null)
    <x-ui.button
        :href="$href"
        variant="outline"
        icon="arrow-down-tray"
        {{ $attributes }}
    >
        {{ $label }}
    </x-ui.button>
@else
    <x-ui.button
        type="submit"
        variant="outline"
        icon="arrow-down-tray"
        {{ $attributes }}
    >
        {{ $label }}
    </x-ui.button>
@endif
