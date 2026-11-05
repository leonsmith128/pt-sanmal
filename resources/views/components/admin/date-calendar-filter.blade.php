@props(['date'])

<form
    method="GET"
    action="{{ route('admin.daily-attendance') }}"
    x-data="{
        selected: @js($date),
        today: @js(now('Asia/Makassar')->toDateString()),
        dateString(value) {
            return value.getFullYear() + '-' + String(value.getMonth() + 1).padStart(2, '0') + '-' + String(value.getDate()).padStart(2, '0');
        },
        parse(value) {
            return new Date(value + 'T00:00:00');
        },
        get weekStart() {
            const date = this.parse(this.selected);
            const mondayOffset = (date.getDay() + 6) % 7;
            date.setDate(date.getDate() - mondayOffset);
            return date;
        },
        get monthLabel() {
            return new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(this.weekStart);
        },
        get days() {
            return Array.from({ length: 7 }, (_, index) => {
                const date = new Date(this.weekStart);
                date.setDate(date.getDate() + index);
                const value = this.dateString(date);
                return {
                    value,
                    day: date.getDate(),
                    label: new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date),
                    selected: value === this.selected,
                    today: value === this.today,
                };
            });
        },
        select(value) {
            this.selected = value;
            this.$nextTick(() => this.$el.requestSubmit());
        },
        shiftWeek(direction) {
            const date = this.parse(this.selected);
            date.setDate(date.getDate() + (direction * 7));
            this.select(this.dateString(date));
        },
    }"
    class="w-full min-w-0"
>
    <input type="hidden" name="date" x-bind:value="selected">

    <div class="sm:hidden">
        <label for="admin-recap-date" class="mb-1.5 block text-sm font-medium text-[var(--sanmal-text-primary)]">
            {{ __('Tanggal rekap') }}
        </label>
        <input
            id="admin-recap-date"
            type="date"
            value="{{ $date }}"
            x-on:change="select($event.target.value)"
            class="min-h-10 w-full min-w-0 rounded-lg border border-sanmal-border bg-sanmal-surface-primary px-3 py-2 text-sm text-[var(--sanmal-text-primary)] shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark"
        >
    </div>

    <div class="hidden min-w-0 sm:block">
        <div class="mb-2 flex items-center justify-between gap-2">
            <span class="truncate text-sm font-semibold text-[var(--sanmal-text-primary)]" x-text="monthLabel"></span>
            <div class="flex shrink-0 items-center gap-1">
                <button type="button" x-on:click="shiftWeek(-1)" aria-label="{{ __('Minggu sebelumnya') }}" class="flex size-8 items-center justify-center rounded-lg text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark">
                    <flux:icon name="chevron-left" variant="micro" />
                </button>
                <button type="button" x-on:click="shiftWeek(1)" aria-label="{{ __('Minggu berikutnya') }}" class="flex size-8 items-center justify-center rounded-lg text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark">
                    <flux:icon name="chevron-right" variant="micro" />
                </button>
                <button type="button" x-on:click="select(today)" class="rounded-lg px-2 py-1.5 text-xs font-medium text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark">
                    {{ __('Hari ini') }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <template x-for="day in days" :key="day.value">
                <button
                    type="button"
                    x-on:click="select(day.value)"
                    x-bind:aria-pressed="day.selected.toString()"
                    x-bind:class="{
                        'bg-[#0E3B6B] text-white dark:bg-[#D9A72E] dark:text-[#172033]': day.selected,
                        'text-[var(--sanmal-text-secondary)] hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark': !day.selected,
                        'ring-1 ring-[#0E3B6B] dark:ring-[#D9A72E]': day.today && !day.selected,
                    }"
                    class="flex min-h-[4.25rem] min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue"
                >
                    <span class="text-[11px] leading-4 opacity-80" x-text="day.label"></span>
                    <span class="text-sm font-semibold tabular-nums" x-text="day.day"></span>
                </button>
            </template>
        </div>
    </div>
</form>
