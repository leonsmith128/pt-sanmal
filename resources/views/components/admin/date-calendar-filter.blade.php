@props([
    'date',
    'exportOptions' => [],
    'exportWarning' => null,
    'exportConfirmTitle' => null,
])

@php
    $selected = \Illuminate\Support\Carbon::parse($date);
    $weekStart = $selected->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $today = now('Asia/Makassar')->toDateString();
    $weekDays = collect(range(0, 6))->map(fn (int $offset) => [
        'date' => $weekStart->copy()->addDays($offset)->toDateString(),
        'label' => $weekStart->copy()->addDays($offset)->translatedFormat('D'),
        'day' => $weekStart->copy()->addDays($offset)->format('j'),
        'today' => $weekStart->copy()->addDays($offset)->toDateString() === $today,
        'indicator' => null,
    ])->all();
@endphp

<form
    method="GET"
    action="{{ route('admin.daily-attendance') }}"
    x-data="{
        selected: @js($date),
        today: @js($today),
        select(value) {
            if (!value) return;
            this.selected = value;
            this.$nextTick(() => this.$root.requestSubmit());
        },
        shiftWeek(direction) {
            const date = new Date(this.selected + 'T00:00:00');
            date.setDate(date.getDate() + (direction * 7));
            const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            this.select(value);
        },
    }"
    x-on:admin-calendar-select.window="select($event.detail.date)"
    x-on:admin-calendar-week.window="shiftWeek($event.detail.direction)"
    class="w-full min-w-0"
>
    <input type="hidden" name="date" x-bind:value="selected">

    <div class="sm:hidden">
        <div class="flex min-w-0 items-end gap-2">
        <label for="admin-recap-date" class="mb-1.5 block text-sm font-medium text-[var(--sanmal-text-primary)]">
            {{ __('Tanggal rekap') }}
        </label>
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[var(--sanmal-text-secondary)]">
                <flux:icon name="calendar-days" variant="outline" class="size-4" />
            </span>
            <input
                id="admin-recap-date"
                type="date"
                value="{{ $date }}"
                x-on:change="select($event.target.value)"
                class="min-h-10 w-full min-w-0 rounded-lg border border-sanmal-border bg-sanmal-surface-primary py-2 pl-10 pr-3 text-sm text-[var(--sanmal-text-primary)] shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark"
            >
        </div>
    </div>

    <div class="hidden min-w-0 sm:block">
        <x-attendance.calendar
            :week-days="$weekDays"
            :selected-date="$date"
            :selected-month-label="$selected->translatedFormat('F Y')"
            :calendar-month-label="$selected->translatedFormat('F Y')"
            :today-date="$today"
            interaction="alpine"
        >
            @if ($exportOptions !== [])
                <x-slot:actions>
                    <x-ui.export-button
                        :options="$exportOptions"
                        :confirm-title="$exportConfirmTitle"
                        :warning="$exportWarning"
                        modal-id="admin-daily-export-desktop-dialog"
                        class="w-auto shrink-0"
                    />
                </x-slot:actions>
            @endif
        </x-attendance.calendar>
    </div>
</form>
