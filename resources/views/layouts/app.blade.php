<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-h-0 bg-sanmal-surface-page text-zinc-900 dark:bg-sanmal-surface-page-dark dark:text-slate-100">
        <div class="{{ auth()->user()->isEmployee() && ! auth()->user()->isManager() ? 'pb-[calc(4rem+env(safe-area-inset-bottom))] lg:pb-0' : '' }}">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
