@props([
    'row',
    'date',
])

@php
    $employee = $row['employee'];
    $daily = $row['daily'];
    $status = \App\Enums\DailyAttendanceStatus::tryFrom($daily['status']);
    $badgeColor = $status?->badgeColor() ?? 'zinc';
    $employeeName = $employee->user?->name ?? 'Pegawai #'.$employee->id;
    $modalName = 'daily-correction-'.$employee->id;

    // Buka kembali modal milik pegawai ini jika validasi koreksi gagal.
    $reopenModal = (string) old('employee_id') === (string) $employee->id
        && $errors->hasAny(['status', 'reason']);

    // Aksen sisi kiri hanya penanda cepat saat memindai; makna utama tetap pada teks badge.
    $accentClass = match ($status) {
        \App\Enums\DailyAttendanceStatus::Present => 'bg-sanmal-success',
        \App\Enums\DailyAttendanceStatus::Incomplete, \App\Enums\DailyAttendanceStatus::Sick => 'bg-sanmal-warning',
        \App\Enums\DailyAttendanceStatus::Permission => 'bg-sanmal-info',
        \App\Enums\DailyAttendanceStatus::Leave => 'bg-sanmal-brand-blue dark:bg-sanmal-brand-blue-light',
        \App\Enums\DailyAttendanceStatus::Absent, \App\Enums\DailyAttendanceStatus::NeedsReview => 'bg-sanmal-danger',
        default => 'bg-sanmal-border dark:bg-sanmal-border-dark',
    };
@endphp

<x-ui.card padding="py-5 pr-5 pl-6" class="relative flex h-full flex-col overflow-hidden">
    <span aria-hidden="true" class="absolute inset-y-0 left-0 w-1 {{ $accentClass }}"></span>

    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <x-ui.avatar :name="$employeeName" size="sm" />

            <div class="min-w-0">
                <flux:heading level="2" size="base" class="truncate">
                    {{ $employeeName }}
                </flux:heading>
                <flux:text class="text-xs text-[var(--sanmal-text-muted)]">
                    {{ __('ID Pegawai:') }} {{ $employee->employee_number ?? $employee->id }}
                </flux:text>
            </div>
        </div>

        <x-ui.badge :color="$badgeColor" class="shrink-0">
            {{ $daily['label'] }}
        </x-ui.badge>
    </div>

    <div class="mt-4 min-h-14 flex-1 space-y-2">
        @if ($daily['events']->isNotEmpty())
            <ul class="flex flex-wrap gap-2" aria-label="{{ __('Catatan waktu absensi') }}">
                @foreach ($daily['events'] as $event)
                    <li class="inline-flex items-center gap-2 rounded-lg bg-sanmal-surface-secondary px-2.5 py-1.5 dark:bg-sanmal-surface-inset-dark">
                        <span
                            aria-hidden="true"
                            class="size-1.5 rounded-full {{ $event->type === \App\Enums\AttendanceType::ClockIn ? 'bg-sanmal-success' : 'bg-sanmal-info' }}"
                        ></span>
                        <span class="text-xs text-[var(--sanmal-text-muted)]">{{ $event->type->label() }}</span>
                        <span class="text-sm font-semibold tabular-nums text-[var(--sanmal-text-primary)]">
                            {{ $event->effectiveRecordedAtLocal()->format('H:i') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-[var(--sanmal-text-muted)]">
                {{ __('Tidak ada catatan clock in/out.') }}
            </p>
        @endif

        @if ($daily['reason'])
            <p class="text-sm text-sanmal-warning dark:text-amber-400">
                {{ $daily['reason'] }}
            </p>
        @endif

        @if ($daily['needs_review'])
            <p role="note" class="sanmal-badge-danger rounded-lg px-3 py-2 text-xs">
                {{ __('Perlu koreksi eksplisit oleh admin untuk menyelesaikan konflik.') }}
            </p>
        @endif
    </div>

    <div class="mt-4 border-t border-sanmal-border pt-4 dark:border-sanmal-border-dark">
        <flux:modal.trigger :name="$modalName">
            <x-ui.button variant="outline" icon="pencil-square" class="w-full">
                {{ __('Koreksi status harian') }}
            </x-ui.button>
        </flux:modal.trigger>
    </div>

    <flux:modal :name="$modalName" class="max-w-md">
        <form method="POST" action="{{ route('admin.daily-attendance.correct') }}" class="grid gap-4">
            @csrf

            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">

            <div>
                <flux:heading size="lg">{{ __('Koreksi status harian') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ $employeeName }} · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('j F Y') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-sanmal-surface-secondary px-3 py-2.5 text-sm dark:bg-sanmal-surface-inset-dark">
                <span class="text-xs text-[var(--sanmal-text-muted)]">{{ __('Status saat ini') }}</span>
                <x-ui.badge :color="$badgeColor">{{ $daily['label'] }}</x-ui.badge>
            </div>

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

            @if ($daily['correction'])
                <p class="text-xs text-[var(--sanmal-text-muted)]">
                    {{ __('Koreksi terakhir dibuat oleh :name.', [
                        'name' => $daily['correction']->createdBy?->name ?? 'admin',
                    ]) }}
                </p>
            @endif

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <x-ui.button type="button" variant="outline" class="w-full sm:w-auto">{{ __('Batal') }}</x-ui.button>
                </flux:modal.close>
                <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">{{ __('Simpan koreksi') }}</x-ui.button>
            </div>
        </form>
    </flux:modal>

    @if ($reopenModal)
        <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: @js($modalName) }))"></div>
    @endif
</x-ui.card>
