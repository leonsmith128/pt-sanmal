@props([
    'title',
    'status',
    'statusColor' => 'zinc',
])

<section {{ $attributes->class(['rounded-xl border border-neutral-200 p-3 dark:border-neutral-700']) }}>
    <div class="flex items-start justify-between gap-2">
        <flux:text class="text-sm">{{ $title }}</flux:text>
        <flux:badge :color="$statusColor">{{ $status }}</flux:badge>
    </div>

    <div class="mt-3 text-sm">
        {{ $slot }}
    </div>
</section>
