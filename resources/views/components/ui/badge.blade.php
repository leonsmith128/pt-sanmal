@props([
    'color' => 'zinc',
])

@php
    $semanticBadgeClasses = [
        'sanmal-badge-success' => in_array($color, ['green', 'emerald'], true),
        'sanmal-badge-warning' => in_array($color, ['yellow', 'amber', 'orange'], true),
        'sanmal-badge-danger' => in_array($color, ['red', 'rose'], true),
        'sanmal-badge-info' => in_array($color, ['blue', 'sky', 'cyan'], true),
    ];
@endphp

<flux:badge :color="$color" {{ $attributes->class($semanticBadgeClasses) }}>
    {{ $slot }}
</flux:badge>
