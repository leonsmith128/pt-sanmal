@props([
    'maxWidth' => '7xl',
    'gap' => '4',
])

@php
    $maxWidthClass = match ($maxWidth) {
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        'full' => 'max-w-full',
        default => 'max-w-7xl',
    };

    $gapClass = match ((string) $gap) {
        '3' => 'gap-3',
        '5' => 'gap-5',
        '6' => 'gap-6',
        default => 'gap-4',
    };
@endphp

<div {{ $attributes->class(["mx-auto flex w-full {$maxWidthClass} flex-col {$gapClass}"]) }}>
    {{ $slot }}
</div>
