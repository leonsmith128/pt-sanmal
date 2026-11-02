@props(['title', 'description' => null])

<header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div class="min-w-0 flex-1">
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
        @if ($description)
            <flux:text class="mt-1">{{ $description }}</flux:text>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex w-full flex-wrap items-center gap-2 md:w-auto md:shrink-0 md:justify-end">
            {{ $slot }}
        </div>
    @endif
</header>
