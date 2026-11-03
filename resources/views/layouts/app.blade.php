<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main inset class="lg:ms-0 lg:mt-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
