@props([
    'title',
    'status',
    'statusColor' => 'zinc',
])

<section {{ $attributes->class(['flex h-full min-h-36 flex-col rounded-xl bg-sanmal-surface-card p-3 shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20']) }}>
    <div class="flex min-w-0 items-center justify-between gap-2">
        <flux:text class="min-w-0 text-sm">{{ $title }}</flux:text>
        <x-ui.badge :color="$statusColor" class="shrink-0 px-2 py-0.5 text-xs leading-4">{{ $status }}</x-ui.badge>
    </div>

    <div class="mt-3 min-h-16 flex-1 text-sm">
        {{ $slot }}
    </div>
</section>
