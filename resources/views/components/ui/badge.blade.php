@props([
    'color' => 'zinc',
])

<flux:badge :color="$color" {{ $attributes }}>
    {{ $slot }}
</flux:badge>
