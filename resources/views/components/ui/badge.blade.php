@props([
    'color' => 'zinc',
])

<flux:badge :color="$color" {{ $attributes->class(['max-w-full']) }}>
    {{ $slot }}
</flux:badge>
