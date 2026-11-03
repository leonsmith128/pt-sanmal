@props([
    'label',
    'value',
    'href',
    'icon' => null,
])

<x-ui.card :href="$href" padding="p-5">
    <div class="flex flex-col items-center gap-2 text-center">
        @if ($icon)
            <flux:icon :name="$icon" class="size-5 text-zinc-400" />
        @endif

        <flux:text>{{ $label }}</flux:text>
    </div>

    <p class="mt-2 text-center text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
        {{ number_format($value) }}
    </p>
</x-ui.card>
