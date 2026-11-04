@props([
    'title',
    'status',
    'statusColor' => 'zinc',
])

<section {{ $attributes->class(['rounded-xl border border-sanmal-border bg-sanmal-surface-card p-3 dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark']) }}>
    <div class="flex items-start justify-between gap-2">
        <flux:text class="text-sm">{{ $title }}</flux:text>
        <flux:badge :color="$statusColor">{{ $status }}</flux:badge>
    </div>

    <div class="mt-3 text-sm">
        {{ $slot }}
    </div>
</section>
