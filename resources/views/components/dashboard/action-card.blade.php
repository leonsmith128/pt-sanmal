@props([
    'label',
    'value',
    'href',
    'icon' => null,
])

<a
    href="{{ $href }}"
    class="block rounded-xl border border-zinc-200 bg-white p-5 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
>
    <div class="flex items-center justify-between gap-3">
        <flux:text>{{ $label }}</flux:text>

        @if ($icon)
            <flux:icon :name="$icon" class="size-5 text-zinc-400" />
        @endif
    </div>

    <p class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
        {{ number_format($value) }}
    </p>
</a>
