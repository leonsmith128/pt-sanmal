@props([
    'label',
    'value',
    'href',
    'icon' => null,
])

<x-ui.card :href="$href" padding="p-4" class="group">
    <div class="flex items-center gap-3">
        @if ($icon)
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sanmal-surface-inset text-sanmal-brand-blue dark:bg-sanmal-surface-inset-dark dark:text-sanmal-brand-yellow-dark">
                <flux:icon :name="$icon" class="size-4" />
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <p class="text-sm leading-5 text-[var(--sanmal-text-secondary)]">{{ $label }}</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums text-[var(--sanmal-text-primary)]">
                {{ number_format($value) }}
            </p>
        </div>

        <flux:icon name="chevron-right" class="size-4 shrink-0 text-[var(--sanmal-text-muted)] transition-transform motion-reduce:transition-none group-hover:translate-x-0.5" />
    </div>
</x-ui.card>
