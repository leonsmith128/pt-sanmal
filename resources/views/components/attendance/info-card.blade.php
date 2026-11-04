@props([
    'title',
    'status',
    'statusColor' => 'zinc',
])

<section {{ $attributes->class(['flex h-full min-h-36 flex-col rounded-xl bg-sanmal-surface-card p-3 shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20']) }}>
    <div class="flex items-start justify-between gap-2">
        <flux:text class="text-sm">{{ $title }}</flux:text>
        <flux:badge :color="$statusColor">{{ $status }}</flux:badge>
    </div>

    <div class="mt-3 min-h-16 flex-1 text-sm">
        {{ $slot }}
    </div>
</section>
