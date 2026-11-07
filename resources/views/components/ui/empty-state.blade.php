@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'colspan' => null,
    'icon' => null,
    'title' => null,
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
        @if ($icon)
            <flux:icon :icon="$icon" class="mx-auto mb-3 size-6 text-[var(--sanmal-text-muted)]" aria-hidden="true" />
        @endif

        @if ($title)
            <p class="text-base font-semibold text-[var(--sanmal-text-primary)]">{{ $title }}</p>
        @endif

        <div @class(['mt-1' => $title])>{{ $slot->isNotEmpty() ? $slot : $message }}</div>

        @isset($action)
            <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $action }}</div>
        @endisset
    </div>
@endif
