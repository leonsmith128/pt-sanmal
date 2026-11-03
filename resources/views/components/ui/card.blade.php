@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block min-w-0 rounded-xl bg-white text-zinc-900 shadow-sm transition-shadow',
            'hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-focus focus-visible:ring-offset-2',
            'dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-black/20 dark:focus-visible:ring-offset-zinc-950',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'min-w-0 rounded-xl bg-white text-zinc-900 shadow-sm',
            'dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-black/20',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
