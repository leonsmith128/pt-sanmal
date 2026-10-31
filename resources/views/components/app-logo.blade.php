@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo" class="flex h-8 w-auto items-center justify-center rounded-md ">
            <img
                src="{{ asset('images/logo-sanmal.png') }}"
                alt="SanMal"
                class="h-8 w-auto object-contain"
            />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo" class="flex h-8 w-auto items-center justify-center rounded-md">
            <img
                src="{{ asset('images/logo-sanmal.png') }}"
                alt="SanMal"
                class="h-8 w-auto object-contain"
            />
        </x-slot>
    </flux:brand>
@endif
