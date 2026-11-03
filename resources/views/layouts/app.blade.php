<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main inset class="min-w-0 min-h-0 flex-1 p-0 lg:ms-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
