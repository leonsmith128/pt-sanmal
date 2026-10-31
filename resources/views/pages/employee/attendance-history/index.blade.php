<x-layouts::app :title="__('Riwayat Absensi')">
    <div class="mx-auto w-full max-w-5xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('Riwayat Absensi') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Lihat catatan masuk, mulai lembur, dan pulang yang tercatat pada akun Anda.') }}</flux:text>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <flux:button href="{{ route('attendance-history.export', request()->query()) }}" variant="outline" icon="arrow-down-tray" class="w-full sm:w-auto">
                    {{ __('Ekspor Excel (CSV)') }}
                </flux:button>
                <flux:button href="{{ route('attendance') }}" variant="primary" icon="clock" class="w-full sm:w-auto">
                    {{ __('Buka halaman absensi') }}
                </flux:button>
            </div>
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
                <flux:icon name="clock" class="mx-auto size-9 text-zinc-400" />
                <flux:heading size="md" class="mt-3">{{ __('Belum ada catatan absensi') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Coba rentang tanggal lain atau lakukan absensi terlebih dahulu.') }}</flux:text>
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($records as $record)
                    <article class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-zinc-500">{{ $record->effectiveRecordedAtLocal()->translatedFormat('d M Y · H:i') }}</p>
                                <h2 class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $record->type->label() }}</h2>
                            </div>
                            <flux:badge color="{{ $record->type->value === 'clock_in' ? 'green' : ($record->type->value === 'clock_out' ? 'blue' : 'amber') }}">
                                {{ $record->type->label() }}
                            </flux:badge>
                        </div>

                        <div class="flex items-start gap-3">
                            @if ($record->selfie_path)
                                <a href="{{ route('attendance-history.selfie', $record) }}" target="_blank" rel="noopener noreferrer" class="shrink-0" aria-label="{{ __('Buka foto absensi') }}">
                                    <img src="{{ route('attendance-history.selfie', $record) }}" alt="{{ __('Foto absensi') }}" loading="lazy" class="size-20 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                                </a>
                            @else
                                <div class="flex size-20 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                    <flux:icon name="image-off" class="size-6" />
                                </div>
                            @endif

                            <div class="min-w-0 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                                <p><span class="font-medium">{{ __('Jenis kerja') }}:</span> {{ $record->work_type->label() }}</p>
                                <p><span class="font-medium">{{ __('Lokasi') }}:</span> {{ $record->project?->name ?? $record->location?->name ?? '—' }}</p>
                                @if (($record->latitude !== null && $record->longitude !== null) || $record->address_snapshot)
                                    <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($record->latitude !== null && $record->longitude !== null ? $record->latitude . ',' . $record->longitude : $record->address_snapshot) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:underline dark:text-blue-400" title="{{ $record->address_snapshot }}">
                                        <flux:icon name="map-pin" class="size-4" />
                                        {{ $record->address_snapshot ? \Illuminate\Support\Str::limit($record->address_snapshot, 48) : __('Buka peta') }}
                                    </a>
                                @else
                                    <p class="text-zinc-400">—</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('attendance-history.index', array_merge(request()->query(), ['direction' => $filters['direction'] === 'desc' ? 'asc' : 'desc', 'page' => null])) }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                        {{ __('Waktu') }}
                                        <flux:icon name="{{ $filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="size-3.5" />
                                    </a>
                                </th>
                                <th scope="col" class="px-4 py-3">{{ __('Aktivitas') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Jenis kerja') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Lokasi / proyek') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Foto') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Peta') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($records as $record)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap px-4 py-4">{{ $record->effectiveRecordedAtLocal()->translatedFormat('d M Y · H:i') }}</td>
                                    <td class="px-4 py-4">
                                        <flux:badge color="{{ $record->type->value === 'clock_in' ? 'green' : ($record->type->value === 'clock_out' ? 'blue' : 'amber') }}">
                                            {{ $record->type->label() }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-4">{{ $record->work_type->label() }}</td>
                                    <td class="px-4 py-4">{{ $record->project?->name ?? $record->location?->name ?? '—' }}</td>
                                    <td class="px-4 py-4">
                                        @if ($record->selfie_path)
                                            <a href="{{ route('attendance-history.selfie', $record) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Buka foto absensi') }}">
                                                <img src="{{ route('attendance-history.selfie', $record) }}" alt="{{ __('Foto absensi') }}" loading="lazy" class="size-16 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                                            </a>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if (($record->latitude !== null && $record->longitude !== null) || $record->address_snapshot)
                                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($record->latitude !== null && $record->longitude !== null ? $record->latitude . ',' . $record->longitude : $record->address_snapshot) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:underline dark:text-blue-400" title="{{ $record->address_snapshot }}">
                                                <flux:icon name="map-pin" class="size-4" />
                                                {{ $record->address_snapshot ? \Illuminate\Support\Str::limit($record->address_snapshot, 36) : __('Buka peta') }}
                                            </a>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
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
