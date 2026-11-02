<x-layouts::app :title="__('Master Proyek')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4">
        <x-admin.page-header
            :title="__('Master Proyek')"
            :description="__('Kelola proyek jasa kalibrasi dan pelatihan beserta Client dan lokasi Site-nya.')"
        >
            <flux:button href="{{ route('admin.projects.create') }}" variant="primary" icon="plus">{{ __('Tambah proyek') }}</flux:button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <x-admin.filter-panel class="!p-3">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <flux:input size="sm" name="q" value="{{ request('q') }}" :label="__('Cari proyek')" placeholder="Kode atau nama proyek" />
                <flux:select size="sm" name="client_id" :label="__('Client')" :value="request('client_id')">
                    <flux:select.option value="">{{ __('Semua Client') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select size="sm" name="project_type" :label="__('Jenis layanan')" :value="request('project_type')">
                    <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                    @foreach ($projectTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select size="sm" name="status" :label="__('Status')" :value="request('status')">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button size="sm" type="submit">{{ __('Filter') }}</flux:button>
            </form>
        </x-admin.filter-panel>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @forelse ($projects as $project)
                <article class="min-w-0 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <details class="group">
                        <summary class="list-none cursor-pointer rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-blue-500 [&::-webkit-details-marker]:hidden">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="break-words font-semibold text-zinc-900 dark:text-zinc-100">{{ $project->name }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $project->code }}</span>
                                        <flux:badge size="sm" color="blue">{{ $project->project_type->label() }}</flux:badge>
                                    </div>
                                </div>
                                <flux:badge size="sm" :color="$project->status === \App\Enums\ProjectStatus::Active ? 'green' : 'zinc'">{{ $project->status->label() }}</flux:badge>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Client') }}</p>
                                    <p class="break-words font-medium">{{ $project->client->name }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Lokasi Site') }}</p>
                                    <p class="break-words font-medium">{{ $project->location->name }}</p>
                                    @if (! $project->location->is_active)
                                        <div class="mt-1"><flux:badge size="sm" color="amber">{{ __('Lokasi nonaktif') }}</flux:badge></div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-zinc-100 pt-2 text-xs dark:border-zinc-800">
                                <span class="text-zinc-600 dark:text-zinc-300">{{ number_format($project->attendance_records_count) }} {{ __('record absensi') }}</span>
                                <span class="inline-flex items-center gap-1 font-medium text-blue-700 dark:text-blue-400">
                                    {{ __('Detail proyek') }}
                                    <flux:icon name="chevron-down" variant="micro" class="transition-transform group-open:rotate-180" />
                                </span>
                            </div>
                        </summary>

                        <div class="mt-3 grid grid-cols-1 gap-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800/60 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Periode mulai') }}</p>
                                <p class="font-medium">{{ $project->start_date?->format('d M Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Periode selesai') }}</p>
                                <p class="font-medium">{{ $project->end_date?->format('d M Y') ?? '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Status Site') }}</p>
                                <p class="font-medium">{{ $project->location->is_active ? __('Aktif') : __('Nonaktif') }}</p>
                            </div>
                        </div>
                    </details>

                    <div class="mt-3 flex justify-end border-t border-zinc-100 pt-2 dark:border-zinc-800">
                        <flux:button size="sm" variant="outline" href="{{ route('admin.projects.edit', $project) }}">{{ __('Ubah proyek') }}</flux:button>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                    {{ __('Belum ada data proyek.') }}
                </div>
            @endforelse
        </div>

        {{ $projects->links() }}
    </div>
</x-layouts::app>
