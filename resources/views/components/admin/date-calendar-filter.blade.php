@props(['date'])

<form
    method="GET"
    action="{{ route('admin.daily-attendance') }}"
    x-data="{
        selected: @js($date),
        month: @js(substr($date, 0, 7).'-01'),
        open: false,
        today: @js(now('Asia/Makassar')->toDateString()),
        formatDate(value) {
            return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                .format(new Date(value + 'T00:00:00'));
        },
        dateString(value) {
            return value.getFullYear() + '-' + String(value.getMonth() + 1).padStart(2, '0') + '-' + String(value.getDate()).padStart(2, '0');
        },
        get monthLabel() {
            return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' })
                .format(new Date(this.month + 'T00:00:00'));
        },
        get days() {
            const [year, month] = this.month.split('-').map(Number);
            const first = new Date(year, month - 1, 1);
            const offset = (first.getDay() + 6) % 7;
            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(year, month - 1, index - offset + 1);
                const value = this.dateString(date);
                return {
                    value,
                    day: date.getDate(),
                    inMonth: date.getMonth() === month - 1,
                    today: value === this.today,
                    selected: value === this.selected,
                };
            });
        },
        shiftMonth(direction) {
            const [year, month] = this.month.split('-').map(Number);
            const next = new Date(year, month - 1 + direction, 1);
            this.month = this.dateString(next);
        },
        choose(value) {
            this.selected = value;
            this.open = false;
        },
    }"
    class="flex flex-wrap items-end gap-3"
>
    <input type="hidden" name="date" x-bind:value="selected">

    <div class="relative w-full sm:w-auto">
        <span class="mb-1.5 block text-sm font-medium text-[var(--sanmal-text-primary)]">
            {{ __('Tanggal rekap') }}
        </span>
        <button
            type="button"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open.toString()"
            aria-haspopup="dialog"
            aria-controls="admin-daily-attendance-calendar"
            class="flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border border-sanmal-border bg-sanmal-surface-primary px-3 py-2 text-left text-sm text-[var(--sanmal-text-primary)] shadow-xs transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:border-sanmal-border-dark dark:bg-sanmal-surface-primary-dark dark:hover:bg-sanmal-surface-elevated-dark sm:min-w-64"
        >
            <span class="inline-flex min-w-0 items-center gap-2">
                <flux:icon name="calendar-days" variant="outline" class="size-4 shrink-0 text-[var(--sanmal-text-secondary)]" />
                <span class="truncate" x-text="formatDate(selected)"></span>
            </span>
            <flux:icon name="chevron-down" variant="micro" class="shrink-0 text-[var(--sanmal-text-muted)]" />
        </button>

        <div
            id="admin-daily-attendance-calendar"
            x-cloak
            x-show="open"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="open = false"
            x-transition
            role="dialog"
            aria-label="{{ __('Pilih tanggal rekap') }}"
            class="absolute left-0 top-full z-40 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-2xl bg-sanmal-surface-card p-4 text-[var(--sanmal-text-primary)] shadow-xl ring-1 ring-black/5 dark:bg-sanmal-surface-card-dark dark:ring-white/10"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <span class="font-semibold capitalize" x-text="monthLabel"></span>
                <div class="flex items-center gap-1">
                    <button type="button" x-on:click="shiftMonth(-1)" aria-label="{{ __('Bulan sebelumnya') }}" class="flex size-9 items-center justify-center rounded-lg text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark">
                        <flux:icon name="chevron-left" variant="micro" />
                    </button>
                    <button type="button" x-on:click="shiftMonth(1)" aria-label="{{ __('Bulan berikutnya') }}" class="flex size-9 items-center justify-center rounded-lg text-[var(--sanmal-text-secondary)] transition hover:bg-sanmal-surface-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue dark:hover:bg-sanmal-surface-elevated-dark">
                        <flux:icon name="chevron-right" variant="micro" />
                    </button>
                </div>
            </div>

            <div class="mb-1 grid grid-cols-7 gap-1 text-center">
                @foreach ([__('Sen'), __('Sel'), __('Rab'), __('Kam'), __('Jum'), __('Sab'), __('Min')] as $weekday)
                    <span class="py-2 text-xs font-medium text-[var(--sanmal-text-muted)]">{{ $weekday }}</span>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                <template x-for="day in days" :key="day.value">
                    <button
                        type="button"
                        x-on:click="choose(day.value)"
                        x-bind:aria-pressed="day.selected.toString()"
                        x-bind:class="{
                            'bg-[#0E3B6B] font-semibold text-white dark:bg-[#D9A72E] dark:text-[#172033]': day.selected,
                            'text-[var(--sanmal-text-primary)] hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark': day.inMonth && !day.selected,
                            'text-[var(--sanmal-text-muted)] opacity-50 hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark': !day.inMonth && !day.selected,
                            'ring-1 ring-[#0E3B6B] dark:ring-[#D9A72E]': day.today && !day.selected,
                        }"
                        class="flex min-h-10 items-center justify-center rounded-lg text-sm tabular-nums transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sanmal-brand-blue"
                        x-text="day.day"
                    ></button>
                </template>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-sanmal-border pt-3 dark:border-sanmal-border-dark">
                <button type="button" x-on:click="choose(today); month = today.slice(0, 7)" class="text-sm font-medium text-sanmal-brand-blue hover:underline dark:text-sanmal-brand-yellow">
                    {{ __('Hari ini') }}
                </button>
                <button type="button" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--sanmal-text-secondary)] hover:bg-sanmal-surface-secondary dark:hover:bg-sanmal-surface-elevated-dark">
                    {{ __('Tutup') }}
                </button>
            </div>
        </div>
    </div>

    <x-ui.button type="submit" variant="primary">
        {{ __('Lihat') }}
    </x-ui.button>
</form>
