@props([
    'options' => [],
    'filterSummary',
    'warning',
])

<flux:dropdown position="bottom" align="end">
    <x-ui.button size="sm" variant="outline" class="w-full flex-row items-center justify-center gap-2 whitespace-nowrap sm:w-auto" icon="arrow-down-tray">
        <span class="inline-flex items-center gap-2 whitespace-nowrap">
            {{ __('Export') }}
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
    aria-labelledby="attendance-export-confirm-title"
>
    <div class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-5 shadow-xl dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-3">
            <h2 id="attendance-export-confirm-title" class="text-lg font-semibold">{{ __('Konfirmasi ekspor rekap absensi') }}</h2>
            <x-ui.button type="button" variant="ghost" icon="x-mark" square aria-label="{{ __('Tutup') }}" x-on:click="exportConfirmOpen = false" />
        </div>

        <p class="text-sm text-zinc-600 dark:text-zinc-300" x-text="exportDescription"></p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $filterSummary }}</p>
        <p class="text-sm text-amber-700 dark:text-amber-300">{{ $warning }}</p>

        <div class="flex justify-end gap-2">
            <x-ui.button type="button" variant="outline" x-on:click="exportConfirmOpen = false">{{ __('Batal') }}</x-ui.button>
            <x-ui.button href="#" x-bind:href="exportUrl" variant="primary" x-on:click="exportConfirmOpen = false">
                {{ __('Konfirmasi dan ekspor') }}
            </x-ui.button>
        </div>
    </div>
</div>
