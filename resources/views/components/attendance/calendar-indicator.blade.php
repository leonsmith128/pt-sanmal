@props([
    'status' => null,
])

@php
    [$indicatorClass, $indicatorLabel] = match ($status) {
        'present' => ['bg-[#54B66F]', __('Hadir')],
        'review' => ['bg-[#B2640A]', __('Perlu ditinjau')],
        'none' => ['bg-slate-400/70 dark:bg-slate-400/60', __('Tidak ada catatan')],
        default => [null, null],
    };
@endphp

<span class="flex h-1.5 items-center justify-center">
    @if ($indicatorClass)
        <span class="size-1.5 rounded-full {{ $indicatorClass }}" aria-hidden="true"></span>
        <span class="sr-only">{{ $indicatorLabel }}</span>
    @endif
</span>
