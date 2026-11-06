{{-- Reusable modal shell for filters and confirmation dialogs. --}}
@props([
    'id',
    'title',
    'model',
    'maxWidth' => 'max-w-xl',
])

<div
    x-show="{{ $model }}"
    x-cloak
    x-transition.opacity
    x-on:click.self="{{ $model }} = false"
    x-on:keydown.escape.window="{{ $model }} = false"
    class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-black/60 p-4"
    role="presentation"
>
    <section
        id="{{ $id }}"
        class="my-auto w-full {{ $maxWidth }} space-y-5 rounded-2xl border border-sanmal-border bg-sanmal-surface-card p-5 text-[var(--sanmal-text-primary)] shadow-2xl dark:border-sanmal-border-dark dark:bg-sanmal-surface-card-dark sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        tabindex="-1"
    >
        <header class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <x-ui.button type="button" variant="ghost" icon="x-mark" square aria-label="{{ __('Tutup') }}" x-on:click="{{ $model }} = false" />
        </header>

        <div class="min-w-0">{{ $slot }}</div>

        @isset($actions)
            <footer class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                {{ $actions }}
            </footer>
        @endisset
    </section>
</div>
