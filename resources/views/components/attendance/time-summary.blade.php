@props(['clockInTime' => null, 'clockOutTime' => null, 'workedMinutes' => null])

<div class="grid grid-cols-3 gap-2">
    <div class="rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20">
        <flux:text class="text-xs">{{ __('Masuk') }}</flux:text>
        <flux:heading size="lg">{{ $clockInTime ?? '—' }}</flux:heading>
    </div>
    <div class="rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20">
        <flux:text class="text-xs">{{ __('Pulang') }}</flux:text>
        <flux:heading size="lg">{{ $clockOutTime ?? '—' }}</flux:heading>
    </div>
    <div class="rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20">
        <flux:text class="text-xs">{{ __('Total kerja') }}</flux:text>
        <flux:heading size="sm">
            @if ($workedMinutes !== null)
                {{ intdiv($workedMinutes, 60) }}j {{ $workedMinutes % 60 }}m
            @else
                —
            @endif
        </flux:heading>
    </div>
</div>
