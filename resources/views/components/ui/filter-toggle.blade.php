{{--
    Tombol buka-tutup panel filter. Hanya UI: halaman yang mendefinisikan state Alpine
    (default `filterOpen`) dan menentukan panel mana yang dikontrol lewat `controls`.
--}}
@props([
    'controls',
    'count' => 0,
    'model' => 'filterOpen',
    'label' => null,
])

<x-ui.button
    type="button"
    variant="outline"
    icon="funnel"
    x-on:click="{{ $model }} = ! {{ $model }}"
    x-bind:aria-expanded="{{ $model }}.toString()"
    aria-controls="{{ $controls }}"
    {{ $attributes }}
>
    {{ $label ?? __('Filter') }}
    @if ($count > 0)
        <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-sanmal-brand-blue/15 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-sanmal-brand-blue dark:bg-sanmal-brand-yellow/20 dark:text-sanmal-brand-yellow">{{ $count }}</span>
    @endif
</x-ui.button>
