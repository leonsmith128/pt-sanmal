@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-8 text-center text-sm text-zinc-500">
            {{ $message }}
        </td>
    </tr>
@else
    <div {{ $attributes->class(['rounded-xl border border-neutral-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-neutral-700 dark:bg-zinc-900']) }}>
        {{ $message }}
    </div>
@endif
