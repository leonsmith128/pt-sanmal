@props(['status'])

<x-ui.badge :color="$status->badgeColor()">
    {{ $status->label() }}
</x-ui.badge>
