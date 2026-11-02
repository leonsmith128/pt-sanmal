@props([
    'title',
    'status',
    'statusColor' => 'zinc',
])

<x-ui.card padding="p-3" {{ $attributes }}>
    <div class="flex items-start justify-between gap-2">
        <flux:text class="text-sm">{{ $title }}</flux:text>
        <flux:badge :color="$statusColor">{{ $status }}</flux:badge>
    </div>

    <div class="mt-3 text-sm">
        {{ $slot }}
    </div>
</x-ui.card>
