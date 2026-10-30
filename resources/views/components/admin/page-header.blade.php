@props(['title', 'description' => null])

<header class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
        @if ($description)
            <flux:text class="mt-1">{{ $description }}</flux:text>
        @endif
    </div>
    {{ $slot }}
</header>
