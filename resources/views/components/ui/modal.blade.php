{{-- Reusable modal shell for filters and confirmation dialogs. --}}
@props([
    'id',
    'title',
    'model',
    'maxWidth' => 'max-w-xl',
    'describedby' => null,
    'dismissibleBackdrop' => true,
])

<div
    x-show="{{ $model }}"
    x-cloak
    x-transition:enter="transition ease-out duration-150 motion-reduce:duration-75"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @if ($dismissibleBackdrop) x-on:click.self="{{ $model }} = false" @endif
    x-on:keydown.escape.window="{{ $model }} = false"
    class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-black/60 p-4"
    role="presentation"
>
    <section
        id="{{ $id }}"
        class="my-auto flex max-h-[calc(100dvh-2rem)] w-full {{ $maxWidth }} flex-col gap-5 rounded-xl border border-sanmal-border bg-sanmal-surface-card p-5 text-[var(--sanmal-text-primary)] shadow-xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        @if ($describedby) aria-describedby="{{ $describedby }}" @endif
        tabindex="-1"
        x-show="{{ $model }}"
        x-trap.noscroll="{{ $model }}"
        x-transition:enter="transition ease-out duration-150 motion-reduce:duration-75"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95 motion-reduce:translate-y-0 motion-reduce:scale-100"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <header class="flex shrink-0 items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <x-ui.button type="button" variant="ghost" icon="x-mark" square aria-label="{{ __('Tutup') }}" x-on:click="{{ $model }} = false" />
        </header>

        <div class="min-h-0 min-w-0 overflow-y-auto">{{ $slot }}</div>

        @isset($actions)
            <footer class="flex shrink-0 flex-col-reverse justify-end gap-2 sm:flex-row">
                {{ $actions }}
            </footer>
        @endisset
    </section>
</div>
