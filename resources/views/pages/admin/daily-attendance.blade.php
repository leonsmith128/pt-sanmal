<x-layouts::app :title="'Rekap Absensi Harian'">
    <div class="mx-auto max-w-7xl space-y-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('Rekap Absensi Harian') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Status cuti, sakit, dan izin dihitung dari permohonan yang disetujui tanpa membuat event absensi sintetis.') }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.leave-requests.index') }}" variant="filled" icon="calendar">
                {{ __('Daftar permohonan') }}
            </flux:button>
        </div>


        <x-admin.flash-message />


        <div class="flex flex-wrap items-end justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
            <form method="GET" action="{{ route('admin.daily-attendance') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <flux:input type="date" name="date" :label="__('Tanggal rekap')" value="{{ $date }}" required />
                </div>
                <flux:button type="submit" variant="primary">{{ __('Lihat') }}</flux:button>
            </form>

            <form method="GET" action="{{ route('admin.daily-attendance.export') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <flux:input type="month" name="month" :label="__('Ekspor bulan')" value="{{ \Illuminate\Support\Carbon::parse($date)->format('Y-m') }}" required />
                </div>
                <flux:button type="submit" variant="filled" icon="arrow-down-tray">{{ __('Unduh CSV') }}</flux:button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($rows as $row)
                @php
                    $dailyStatusEnum = \App\Enums\DailyAttendanceStatus::tryFrom($row['daily']['status']);
                    $badgeColor = $dailyStatusEnum?->badgeColor() ?? 'zinc';
                @endphp
                <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <flux:heading level="2" size="base">
                                {{ $row['employee']->user?->name ?? 'Pegawai #'.$row['employee']->id }}
                            </flux:heading>
                            <flux:text class="text-xs text-zinc-500">
                                {{ __('ID Pegawai:') }} {{ $row['employee']->employee_number ?? $row['employee']->id }}
                            </flux:text>
                        </div>
                        <flux:badge :color="$badgeColor">
                            {{ $row['daily']['label'] }}
                        </flux:badge>
                    </div>

                    @if ($row['daily']['events']->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            @foreach ($row['daily']['events'] as $event)
                                <flux:badge size="sm" :color="$event->type === \App\Enums\AttendanceType::ClockIn ? 'green' : 'blue'">
                                    {{ $event->type->label() }}: {{ $event->recordedAtLocal()->format('H:i') }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @if ($row['daily']['reason'])
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">{{ $row['daily']['reason'] }}</p>
                    @endif

                    @if ($row['daily']['needs_review'])
                        <div class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                            {{ __('Perlu koreksi eksplisit oleh admin untuk menyelesaikan konflik.') }}
                        </div>
                    @endif

                    <details class="mt-4 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                        <summary class="cursor-pointer text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                            {{ __('Koreksi status harian') }}
                        </summary>

                        <form method="POST" action="{{ route('admin.daily-attendance.correct') }}" class="mt-4 grid gap-4 rounded-lg bg-zinc-50 p-4 sm:grid-cols-2 dark:bg-zinc-800/40">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $row['employee']->id }}">
                            <input type="hidden" name="attendance_date" value="{{ $date }}">

                            <div>
                                <flux:select name="status" :label="__('Status final')" required>
                                    @foreach (\App\Enums\DailyAttendanceStatus::correctionOptions() as $option)
                                        <flux:select.option value="{{ $option->value }}" :selected="$row['daily']['status'] === $option->value">
                                            {{ $option->label() }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div>
                                <flux:input name="reason" :label="__('Alasan wajib')" required minlength="5" maxlength="2000" value="{{ $row['daily']['correction']?->reason }}" placeholder="{{ __('Jelaskan alasan koreksi...') }}" />
                            </div>

                            <div class="sm:col-span-2 flex items-center justify-between pt-1">
                                <flux:button type="submit" size="sm" variant="primary">
                                    {{ __('Simpan koreksi') }}
                                </flux:button>
                                @if ($row['daily']['correction'])
                                    <span class="text-xs text-zinc-500">
                                        {{ __('Koreksi terakhir dibuat oleh :name.', ['name' => $row['daily']['correction']->createdBy?->name ?? 'admin']) }}
                                    </span>
                                @endif
                            </div>
                        </form>
                    </details>
                </section>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-8 text-center text-zinc-500 dark:border-neutral-700 dark:bg-zinc-900">
                    {{ __('Belum ada data pegawai.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-layouts::app>
