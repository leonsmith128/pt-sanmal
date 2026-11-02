@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
])

@if ($colspan)
    <tr>
        <td
            colspan="{{ $colspan }}"
            {{ $attributes->class(['px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400']) }}
        >
            {{ $message }}
        </td>
    </tr>
@else
    <div {{ $attributes->class([
        'rounded-xl border border-zinc-200 bg-white p-6 text-center text-sm text-zinc-500',
        'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400',
    ]) }}>
        {{ $slot->isNotEmpty() ? $slot : $message }}
    </div>
@endif
