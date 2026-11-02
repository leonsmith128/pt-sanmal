@props([
    'label',
    'value',
    'href',
    'icon' => null,
])

<x-ui.card :href="$href" padding="p-5">
    <div class="flex items-center justify-between gap-3">
        <flux:text>{{ $label }}</flux:text>

        @if ($icon)
            <flux:icon :name="$icon" class="size-5 text-zinc-400" />
        @endif
    </div>

    <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
        {{ number_format($value) }}
    </p>
</x-ui.card>
