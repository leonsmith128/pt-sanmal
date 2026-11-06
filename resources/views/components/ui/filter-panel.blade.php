{{--
    Panel filter yang dapat dilipat. Hanya UI: kolom filter, aksi, dan form diisi halaman lewat slot.
    State Alpine (`model`, default `filterOpen`) didefinisikan oleh halaman.
--}}
@props([
    'id',
    'title',
    'description' => null,
    'model' => 'filterOpen',
])

<x-ui.card
    padding="p-4 sm:p-5"
    x-show="{{ $model }}"
    x-cloak
    x-transition
    id="{{ $id }}"
    {{ $attributes }}
>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
            @if ($description)
                <flux:text class="mt-1 text-sm">{{ $description }}</flux:text>
            @endif
        </div>
        @isset($aside)
            {{ $aside }}
        @endisset
    </div>

    {{ $slot }}
</x-ui.card>
