@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block min-w-0 rounded-xl border border-zinc-200 bg-white text-zinc-900 transition-colors',
            'hover:border-zinc-300 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-focus focus-visible:ring-offset-2',
            'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:focus-visible:ring-offset-zinc-950',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'min-w-0 rounded-xl border border-zinc-200 bg-white text-zinc-900',
            'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
