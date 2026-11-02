@props([
    'label',
    'value',
    'icon',
])

<x-ui.card padding="p-5">
    <div class="flex items-center justify-between gap-3">
        <flux:text>{{ $label }}</flux:text>
        <flux:icon :name="$icon" class="size-5 text-zinc-400" />
    </div>

    <p class="mt-3 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
        {{ number_format($value) }}
    </p>
</x-ui.card>
