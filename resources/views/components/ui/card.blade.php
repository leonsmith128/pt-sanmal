@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block rounded-xl border border-zinc-200 bg-white text-zinc-900 transition',
            'hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500',
            'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'rounded-xl border border-zinc-200 bg-white text-zinc-900',
            'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
