@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block min-w-0 rounded-xl bg-sanmal-surface-card text-zinc-900 shadow-sm transition-shadow',
            'hover:bg-sanmal-surface-secondary hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue focus-visible:ring-offset-2',
            'dark:bg-sanmal-surface-card-dark dark:text-zinc-100 dark:shadow-black/20 dark:hover:bg-sanmal-surface-secondary-dark dark:focus-visible:ring-offset-sanmal-surface-page-dark',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'min-w-0 rounded-xl bg-sanmal-surface-card text-zinc-900 shadow-sm',
            'dark:bg-sanmal-surface-card-dark dark:text-zinc-100 dark:shadow-black/20',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
