@props([
    'variant' => 'outline',
    'size' => 'sm',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'disabled' => false,
])

@if ($href)
    <flux:button
        :href="$href"
        :variant="$variant"
        :size="$size"
        :icon="$icon"
        :disabled="$disabled"
        {{ $attributes }}
    >
        {{ $slot }}
    </flux:button>
@else
    <flux:button
        :type="$type"
        :variant="$variant"
        :size="$size"
        :icon="$icon"
        :disabled="$disabled"
        {{ $attributes }}
    >
        {{ $slot }}
    </flux:button>
@endif
