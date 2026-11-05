@props([
    'label',
    'value',
    'icon',
])

<x-ui.card padding="p-5">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:icon :name="$icon" class="size-5 text-zinc-400" />
        <flux:text>{{ $label }}</flux:text>
    </div>

    <p class="mt-3 text-center text-3xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
        {{ number_format($value) }}
    </p>
</x-ui.card>
