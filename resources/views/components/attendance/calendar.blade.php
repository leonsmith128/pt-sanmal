@props([
    'weekDays' => [],
    'monthDays' => [],
    'selectedDate',
    'selectedMonthLabel',
    'calendarMonthLabel',
    'todayDate',
    'showMonthlyCalendar' => false,
])

<x-ui.card padding="p-4" class="rounded-2xl">
    <div class="mb-3 flex items-center justify-between gap-2">
        <div class="min-w-0">
            <button
                type="button"
                wire:click="openMonthlyCalendar"
                aria-haspopup="dialog"
                aria-expanded="{{ $showMonthlyCalendar ? 'true' : 'false' }}"
                aria-controls="attendance-month-calendar-dialog"
                class="inline-flex min-h-10 max-w-full items-center justify-center whitespace-nowrap rounded-lg px-2 -ml-2 text-center text-[var(--sanmal-text-primary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark"
            >
                <flux:heading size="sm">{{ $selectedMonthLabel }}</flux:heading>
            </button>
        </div>
        <div class="flex shrink-0 items-center gap-0.5">
            <x-ui.button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftWeek(-1)" aria-label="{{ __('Minggu sebelumnya') }}" />
            <x-ui.button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftWeek(1)" aria-label="{{ __('Minggu berikutnya') }}" />
            <x-ui.button size="sm" variant="ghost" wire:click="selectDate('{{ $todayDate }}')">{{ __('Hari ini') }}</x-ui.button>
        </div>
    </div>
    <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
        @foreach ($weekDays as $day)
            <button
                type="button"
                wire:key="attendance-week-day-{{ $day['date'] }}"
                wire:click="selectDate('{{ $day['date'] }}')"
                aria-pressed="{{ $selectedDate === $day['date'] ? 'true' : 'false' }}"
                class="flex min-h-12 min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-0.5 py-2 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue {{ $selectedDate === $day['date'] ? 'bg-[#0E3B6B] text-white dark:bg-[#D9A72E] dark:text-[#172033]' : 'text-[var(--sanmal-text-secondary)] hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark' }} {{ $day['today'] && $selectedDate !== $day['date'] ? 'ring-1 ring-[#0E3B6B] dark:ring-[#D9A72E]' : '' }}"
            >
                <span class="text-xs {{ $selectedDate === $day['date'] ? 'opacity-85' : 'text-[var(--sanmal-text-muted)]' }}">{{ $day['label'] }}</span>
                <span class="text-base font-semibold tabular-nums">{{ $day['day'] }}</span>
                <x-attendance.calendar-indicator :status="$day['indicator']" />
            </button>
        @endforeach
    </div>
</x-ui.card>

@if ($showMonthlyCalendar)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        role="presentation"
        wire:click.self="closeMonthlyCalendar"
        x-on:keydown.escape.window="$wire.closeMonthlyCalendar()"
    >
        <section id="attendance-month-calendar-dialog" role="dialog" aria-modal="true" aria-labelledby="attendance-month-calendar-title" class="w-full max-w-md rounded-2xl bg-sanmal-surface-card p-4 text-[var(--sanmal-text-primary)] shadow-2xl dark:bg-sanmal-surface-card-dark sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading id="attendance-month-calendar-title" size="lg">{{ $calendarMonthLabel }}</flux:heading>
                <div class="flex items-center gap-1">
                    <x-ui.button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftCalendarMonth(-1)" aria-label="{{ __('Bulan sebelumnya') }}" />
                    <x-ui.button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftCalendarMonth(1)" aria-label="{{ __('Bulan berikutnya') }}" />
                    <x-ui.button size="sm" variant="ghost" icon="x-mark" wire:click="closeMonthlyCalendar" aria-label="{{ __('Tutup kalender bulanan') }}" />
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center">
                @foreach ([__('Sen'), __('Sel'), __('Rab'), __('Kam'), __('Jum'), __('Sab'), __('Min')] as $weekday)
                    <span class="py-2 text-xs font-medium text-[var(--sanmal-text-muted)]">{{ $weekday }}</span>
                @endforeach
                @foreach ($monthDays as $day)
                    <button
                        type="button"
                        wire:key="attendance-month-day-{{ $day['date'] }}"
                        wire:click="selectCalendarDate('{{ $day['date'] }}')"
                        aria-pressed="{{ $selectedDate === $day['date'] ? 'true' : 'false' }}"
                        class="flex min-h-11 flex-col items-center justify-center gap-0.5 rounded-lg text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue {{ $selectedDate === $day['date'] ? 'bg-[#0E3B6B] font-semibold text-white dark:bg-[#D9A72E] dark:text-[#172033]' : ($day['inMonth'] ? 'text-[var(--sanmal-text-primary)] hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark' : 'text-[var(--sanmal-text-muted)] opacity-60 hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark') }} {{ $day['today'] && $selectedDate !== $day['date'] ? 'ring-1 ring-[#0E3B6B] dark:ring-[#D9A72E]' : '' }}"
                    >
                        <span class="tabular-nums">{{ $day['day'] }}</span>
                        <x-attendance.calendar-indicator :status="$day['indicator']" />
                    </button>
                @endforeach
            </div>
        </section>
    </div>
@endif
