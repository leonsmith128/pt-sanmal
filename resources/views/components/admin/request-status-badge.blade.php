@props(['status'])

<flux:badge :color="$status->badgeColor()">
    {{ $status->label() }}
</flux:badge>
