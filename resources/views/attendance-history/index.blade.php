<x-layouts::app :title="__('Riwayat Absensi')">
    <div class="mx-auto w-full max-w-5xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('Riwayat Absensi') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Lihat catatan masuk, mulai lembur, dan pulang yang tercatat pada akun Anda.') }}</flux:text>
            </div>
            <flux:button href="{{ route('attendance') }}" variant="primary" icon="clock" class="w-full sm:w-auto">
                {{ __('Buka halaman absensi') }}
            </flux:button>
        </header>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-5">
            <form method="GET" action="{{ route('attendance-history.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <flux:field>
                    <flux:label>{{ __('Dari tanggal') }}</flux:label>
                    <flux:input type="date" name="from" value="{{ $filters['from'] }}" />
                    <flux:error name="from" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Sampai tanggal') }}</flux:label>
                    <flux:input type="date" name="to" value="{{ $filters['to'] }}" />
                    <flux:error name="to" />
                </flux:field>
                <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-end lg:col-span-3 lg:justify-end">
                    <flux:button href="{{ route('attendance-history.index') }}" variant="ghost" class="w-full sm:w-auto">
                        {{ __('Reset') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="funnel" class="w-full sm:w-auto">
                        {{ __('Terapkan filter') }}
                    </flux:button>
                </div>
            </form>
        </section>

        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('Catatan absensi') }}</flux:heading>
            <flux:text class="text-sm">{{ number_format($records->total()) }} {{ __('catatan') }}</flux:text>
        </div>

        @if ($records->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:icon name="calendar-clock" class="mx-auto size-9 text-zinc-400" />
                <flux:heading size="md" class="mt-3">{{ __('Belum ada catatan absensi') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Coba rentang tanggal lain atau lakukan absensi terlebih dahulu.') }}</flux:text>
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($records as $record)
                    <article class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-zinc-500">{{ $record->recordedAtLocal()->translatedFormat('d M Y · H:i') }}</p>
                                <h2 class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $record->type->label() }}</h2>
                            </div>
                            <flux:badge color="{{ $record->type->value === 'clock_in' ? 'green' : ($record->type->value === 'clock_out' ? 'blue' : 'amber') }}">
                                {{ $record->type->label() }}
                            </flux:badge>
                        </div>
                        <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                            <p><span class="font-medium">{{ __('Jenis kerja') }}:</span> {{ $record->work_type->label() }}</p>
                            <p><span class="font-medium">{{ __('Lokasi') }}:</span> {{ $record->project?->name ?? $record->location?->name ?? '—' }}</p>
                            @if ($record->address_snapshot)
                                <p class="break-words">{{ $record->address_snapshot }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Waktu') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Aktivitas') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Jenis kerja') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Lokasi / proyek') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Alamat tercatat') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($records as $record)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap px-4 py-4">{{ $record->recordedAtLocal()->translatedFormat('d M Y · H:i') }}</td>
                                    <td class="px-4 py-4">
                                        <flux:badge color="{{ $record->type->value === 'clock_in' ? 'green' : ($record->type->value === 'clock_out' ? 'blue' : 'amber') }}">
                                            {{ $record->type->label() }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-4">{{ $record->work_type->label() }}</td>
                                    <td class="px-4 py-4">{{ $record->project?->name ?? $record->location?->name ?? '—' }}</td>
                                    <td class="max-w-sm break-words px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $record->address_snapshot ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-layouts::app>
