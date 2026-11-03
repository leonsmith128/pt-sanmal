<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="m-2 min-w-0 flex-1 rounded-xl bg-white p-0 dark:bg-zinc-800 lg:m-4">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
