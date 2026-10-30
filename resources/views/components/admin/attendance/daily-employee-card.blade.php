@props([
    'row',
    'date',
])

@php
    $employee = $row['employee'];
    $daily = $row['daily'];
    $status = \App\Enums\DailyAttendanceStatus::tryFrom($daily['status']);
    $badgeColor = $status?->badgeColor() ?? 'zinc';
@endphp

<section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading level="2" size="base">
                {{ $employee->user?->name ?? 'Pegawai #'.$employee->id }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500">
                {{ __('ID Pegawai:') }} {{ $employee->employee_number ?? $employee->id }}
            </flux:text>
        </div>

        <flux:badge :color="$badgeColor">
            {{ $daily['label'] }}
        </flux:badge>
    </div>

    @if ($daily['events']->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-2 text-sm">
            @foreach ($daily['events'] as $event)
                <flux:badge
                    size="sm"
                    :color="$event->type === \App\Enums\AttendanceType::ClockIn ? 'green' : 'blue'"
                >
                    {{ $event->type->label() }}: {{ $event->effectiveRecordedAtLocal()->format('H:i') }}
                </flux:badge>
            @endforeach
        </div>
    @endif

    @if ($daily['reason'])
        <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">
            {{ $daily['reason'] }}
        </p>
    @endif

    @if ($daily['needs_review'])
        <flux:callout class="mt-3" variant="danger">
            {{ __('Perlu koreksi eksplisit oleh admin untuk menyelesaikan konflik.') }}
        </flux:callout>
    @endif

    <details class="mt-4 border-t border-neutral-100 pt-3 dark:border-neutral-800">
        <summary class="cursor-pointer text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
            {{ __('Koreksi status harian') }}
        </summary>

        <form
            method="POST"
            action="{{ route('admin.daily-attendance.correct') }}"
            class="mt-4 grid gap-4 rounded-lg bg-zinc-50 p-4 sm:grid-cols-2 dark:bg-zinc-800/40"
        >
            @csrf

            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">

            <flux:field>
                <flux:label>{{ __('Status final') }}</flux:label>
                <flux:select name="status" required>
                    @foreach (\App\Enums\DailyAttendanceStatus::correctionOptions() as $option)
                        <flux:select.option
                            value="{{ $option->value }}"
                            :selected="$daily['status'] === $option->value"
                        >
                            {{ $option->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Alasan wajib') }}</flux:label>
                <flux:input
                    name="reason"
                    required
                    minlength="5"
                    maxlength="2000"
                    value="{{ old('reason', $daily['correction']?->reason) }}"
                    placeholder="{{ __('Jelaskan alasan koreksi...') }}"
                />
                <flux:error name="reason" />
            </flux:field>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 sm:col-span-2">
                <flux:button type="submit" size="sm" variant="primary">
                    {{ __('Simpan koreksi') }}
                </flux:button>

                @if ($daily['correction'])
                    <span class="text-xs text-zinc-500">
                        {{ __('Koreksi terakhir dibuat oleh :name.', [
                            'name' => $daily['correction']->createdBy?->name ?? 'admin',
                        ]) }}
                    </span>
                @endif
            </div>
        </form>
    </details>
</section>
