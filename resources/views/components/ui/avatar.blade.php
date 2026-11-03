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
        'lg' => 'size-14 text-base',
        'xl' => 'size-20 text-xl',
        default => 'size-10 text-sm',
    };
@endphp

<flux:avatar
    :name="$name"
    :initials="$initials"
    :src="$src"
    {{ $attributes->class(['shrink-0 rounded-full [&_img]:rounded-full', $sizeClass]) }}
/>
