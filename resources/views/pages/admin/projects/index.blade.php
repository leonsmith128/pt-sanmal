<x-layouts::app :title="__('Master Proyek')">
    <x-ui.page>
        <x-admin.page-header
            :title="__('Master Proyek')"
            :description="__('Kelola proyek jasa kalibrasi dan pelatihan beserta Client dan lokasi Site-nya.')"
        >
            <x-ui.button href="{{ route('admin.projects.create') }}" variant="primary" icon="plus">{{ __('Tambah proyek') }}</x-ui.button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <x-admin.filter-panel class="!p-3">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <x-ui.input size="sm" name="q" :value="request('q')" :label="__('Cari proyek')" placeholder="Kode atau nama proyek" />
                <x-ui.select size="sm" name="client_id" :label="__('Client')" :value="request('client_id')">
                    <flux:select.option value="">{{ __('Semua Client') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                    @endforeach
                </x-ui.select>
                <x-ui.select size="sm" name="project_type" :label="__('Jenis layanan')" :value="request('project_type')">
                    <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                    @foreach ($projectTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </x-ui.select>
                <x-ui.select size="sm" name="status" :label="__('Status')" :value="request('status')">
                    <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </x-ui.select>
                <x-ui.button size="sm" type="submit" variant="outline">{{ __('Filter') }}</x-ui.button>
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
                                        <x-ui.badge size="sm" color="blue">{{ $project->project_type->label() }}</x-ui.badge>
                                    </div>
                                </div>
                                <x-ui.badge size="sm" :color="$project->status === \App\Enums\ProjectStatus::Active ? 'green' : 'zinc'">{{ $project->status->label() }}</x-ui.badge>
                            </div>

                            <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <x-ui.metadata-item
                                    :label="__('Client')"
                                    label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                    value-class="mt-0.5 break-words font-medium"
                                >{{ $project->client->name }}</x-ui.metadata-item>
                                <x-ui.metadata-item
                                    :label="__('Lokasi Site')"
                                    label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                    value-class="mt-0.5 break-words font-medium"
                                >
                                    {{ $project->location->name }}
                                    @if (! $project->location->is_active)
                                        <span class="mt-1 block"><x-ui.badge size="sm" color="amber">{{ __('Lokasi nonaktif') }}</x-ui.badge></span>
                                    @endif
                                </x-ui.metadata-item>
                            </dl>

                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-zinc-100 pt-2 text-xs dark:border-zinc-800">
                                <span class="text-zinc-600 dark:text-zinc-300">{{ number_format($project->attendance_records_count) }} {{ __('record absensi') }}</span>
                                <span class="inline-flex items-center gap-1 font-medium text-blue-700 dark:text-blue-400">
                                    {{ __('Detail proyek') }}
                                    <flux:icon name="chevron-down" variant="micro" class="transition-transform group-open:rotate-180" />
                                </span>
                            </div>
                        </summary>

                        <dl class="mt-3 grid grid-cols-1 gap-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800/60 sm:grid-cols-2">
                            <x-ui.metadata-item
                                :label="__('Periode mulai')"
                                label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                value-class="mt-0.5 font-medium"
                            >{{ $project->start_date?->format('d M Y') ?? '—' }}</x-ui.metadata-item>
                            <x-ui.metadata-item
                                :label="__('Periode selesai')"
                                label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                value-class="mt-0.5 font-medium"
                            >{{ $project->end_date?->format('d M Y') ?? '—' }}</x-ui.metadata-item>
                            <x-ui.metadata-item
                                :label="__('Status Site')"
                                label-class="text-xs text-zinc-500 dark:text-zinc-400"
                                value-class="mt-0.5 font-medium"
                                class="sm:col-span-2"
                            >{{ $project->location->is_active ? __('Aktif') : __('Nonaktif') }}</x-ui.metadata-item>
                        </dl>
                    </details>

                    <div class="mt-3 flex justify-end border-t border-zinc-100 pt-2 dark:border-zinc-800">
                        <x-ui.button size="sm" variant="outline" href="{{ route('admin.projects.edit', $project) }}">{{ __('Ubah proyek') }}</x-ui.button>
                    </div>
                </article>
            @empty
                <x-ui.empty-state
                    class="col-span-full !p-6"
                    :message="__('Belum ada data proyek.')"
                />
            @endforelse
        </div>

        {{ $projects->links() }}
    </x-ui.page>
</x-layouts::app>
