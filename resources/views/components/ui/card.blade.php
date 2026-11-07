@props([
    'href' => null,
    'padding' => 'p-4',
])

@if ($href !== null)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'block min-w-0 rounded-xl border border-sanmal-border bg-sanmal-surface-card text-[var(--sanmal-text-primary)] transition-colors',
            'hover:border-accent hover:bg-sanmal-surface-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sanmal-focus)] focus-visible:ring-offset-2',
            'dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark dark:hover:bg-sanmal-surface-elevated-dark dark:focus-visible:ring-offset-sanmal-surface-page-dark',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        {{ $attributes->class([
            'min-w-0 rounded-xl border border-sanmal-border bg-sanmal-surface-card text-[var(--sanmal-text-primary)]',
            'dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark',
            $padding,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
