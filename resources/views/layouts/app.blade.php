<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="{{ auth()->user()->isEmployee() && ! auth()->user()->isManager() ? 'pb-[calc(4rem+env(safe-area-inset-bottom))] lg:pb-0' : '' }}">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
