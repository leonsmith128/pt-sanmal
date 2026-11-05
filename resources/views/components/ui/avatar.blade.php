@props([
    'name' => null,
    'initials' => null,
    'src' => null,
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'size-7 text-[10px]',
        'sm' => 'size-8 text-xs',
        'compact' => 'size-12 text-sm',
        'lg' => 'size-14 text-base',
        'xl' => 'size-20 text-xl',
        default => 'size-10 text-sm',
    };
@endphp

<flux:avatar
    :name="$name"
    :initials="$initials"
    :src="$src"
    {{ $attributes->class(['aspect-square shrink-0 overflow-hidden rounded-full [&_img]:h-full [&_img]:w-full [&_img]:rounded-full [&_img]:object-cover', $sizeClass]) }}
/>
