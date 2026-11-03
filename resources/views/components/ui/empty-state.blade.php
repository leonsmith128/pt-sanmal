@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" {{ $attributes->class(['px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400']) }}>
            {{ $message }}
        </td>
    </tr>
@else
    <div {{ $attributes->class([
        'rounded-xl border border-sanmal-border bg-sanmal-surface-primary p-6 text-center text-sm text-slate-500',
        'dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark dark:text-slate-400',
    ]) }}>
        {{ $slot->isNotEmpty() ? $slot : $message }}
    </div>
@endif
