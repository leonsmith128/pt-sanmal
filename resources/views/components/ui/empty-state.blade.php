@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" {{ $attributes->class(['px-4 py-8 text-center text-sm text-[var(--sanmal-text-muted)]']) }}>
            {{ $message }}
        </td>
    </tr>
@else
    <div {{ $attributes->class([
        'rounded-xl border border-sanmal-border bg-sanmal-surface-card p-6 text-center text-sm text-[var(--sanmal-text-muted)]',
        'dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark',
    ]) }}>
        {{ $slot->isNotEmpty() ? $slot : $message }}
    </div>
@endif
