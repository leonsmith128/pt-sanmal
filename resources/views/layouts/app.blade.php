<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main inset data-sanmal-main-scroll class="sanmal-main-scroll min-w-0 min-h-0 flex-1 overflow-y-auto overscroll-contain bg-sanmal-surface-page p-0 text-zinc-900 lg:ms-0 dark:bg-sanmal-surface-page-dark dark:text-slate-100">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
