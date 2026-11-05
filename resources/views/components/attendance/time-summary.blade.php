@props(['clockInTime' => null, 'clockOutTime' => null, 'workedMinutes' => null])

<div class="grid grid-cols-3 gap-2">
    <div class="min-w-0 rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20 sm:p-4">
        <flux:text class="text-xs">{{ __('Masuk') }}</flux:text>
        <flux:heading size="lg" class="tabular-nums">{{ $clockInTime ?? '—' }}</flux:heading>
    </div>
    <div class="min-w-0 rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20 sm:p-4">
        <flux:text class="text-xs">{{ __('Pulang') }}</flux:text>
        <flux:heading size="lg" class="tabular-nums">{{ $clockOutTime ?? '—' }}</flux:heading>
    </div>
    <div class="min-w-0 rounded-xl bg-sanmal-surface-card p-3 text-center shadow-sm dark:bg-sanmal-surface-card-dark dark:shadow-black/20 sm:p-4">
        <flux:text class="text-xs">{{ __('Total kerja') }}</flux:text>
        <flux:heading size="sm" class="tabular-nums">
            @if ($workedMinutes !== null)
                {{ intdiv($workedMinutes, 60) }}j {{ $workedMinutes % 60 }}m
            @else
                —
            @endif
        </flux:heading>
    </div>
</div>
