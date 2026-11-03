@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block rounded-xl border border-sanmal-border bg-sanmal-surface-primary text-zinc-900 transition',
            'hover:bg-sanmal-surface-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue',
            'dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark dark:text-slate-100 dark:hover:bg-sanmal-surface-secondary-dark',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'rounded-xl border border-sanmal-border bg-sanmal-surface-primary text-zinc-900',
            'dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark dark:text-slate-100',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
