@props([
    'user',
    'long' => false,
])

@php
    [$label, $color] = match (true) {
        ! $user->is_active => [__('Nonaktif'), 'zinc'],
        $user->must_change_password => [$long ? __('Menunggu aktivasi / ganti password') : __('Menunggu aktivasi'), 'amber'],
        default => [__('Aktif'), 'green'],
    };
@endphp

<x-ui.badge :color="$color" {{ $attributes }}>
    {{ $label }}
</x-ui.badge>
