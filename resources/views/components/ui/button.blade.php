@props([
    'variant' => 'outline',
    'size' => 'sm',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconTrailing' => null,
    'disabled' => false,
    'square' => false,
])

@if ($href !== null)
    <flux:button
        :href="$href"
        :variant="$variant"
        :size="$size"
        :icon="$icon"
        :icon-trailing="$iconTrailing"
        :disabled="$disabled"
        :square="$square"
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
        :icon-trailing="$iconTrailing"
        :disabled="$disabled"
        :square="$square"
        {{ $attributes }}
    >
        {{ $slot }}
    </flux:button>
@endif
