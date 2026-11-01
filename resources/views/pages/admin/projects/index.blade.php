<x-layouts::app :title="__('Master Proyek')">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Master Proyek') }}</flux:heading>
                <flux:text>{{ __('Kelola proyek jasa kalibrasi dan pelatihan beserta Client dan lokasi Site-nya.') }}</flux:text>
            </div>
            <flux:button href="{{ route('admin.projects.create') }}" variant="primary" icon="plus">{{ __('Tambah proyek') }}</flux:button>
        </div>

        <x-admin.flash-message />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <flux:input name="q" value="{{ request('q') }}" :label="__('Cari proyek')" placeholder="Kode atau nama proyek" />
            <flux:select name="client_id" :label="__('Client')" :value="request('client_id')">
                <flux:select.option value="">{{ __('Semua Client') }}</flux:select.option>
                @foreach ($clients as $client)
                    <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select name="project_type" :label="__('Jenis layanan')" :value="request('project_type')">
                <flux:select.option value="">{{ __('Semua jenis') }}</flux:select.option>
                @foreach ($projectTypes as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select name="status" :label="__('Status')" :value="request('status')">
                <flux:select.option value="">{{ __('Semua status') }}</flux:select.option>
                @foreach ($statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit">{{ __('Filter') }}</flux:button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                    <tr>
                        <th class="p-3">{{ __('Kode / Proyek') }}</th>
                        <th class="p-3">{{ __('Client') }}</th>
                        <th class="p-3">{{ __('Lokasi Site') }}</th>
                        <th class="p-3">{{ __('Jenis layanan') }}</th>
                        <th class="p-3">{{ __('Periode') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3">{{ __('Absensi') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($projects as $project)
                        <tr>
                            <td class="p-3">
                                <p class="font-mono text-xs">{{ $project->code }}</p>
                                <p class="font-medium">{{ $project->name }}</p>
                            </td>
                            <td class="p-3">{{ $project->client->name }}</td>
                            <td class="p-3">
                                <p>{{ $project->location->name }}</p>
                                @if (! $project->location->is_active)
                                    <flux:badge color="amber">{{ __('Lokasi nonaktif') }}</flux:badge>
                                @endif
                            </td>
                            <td class="p-3">{{ $project->project_type->label() }}</td>
                            <td class="p-3 text-xs">
                                {{ $project->start_date?->format('d M Y') ?? '—' }}
                                <span class="text-zinc-400">–</span>
                                {{ $project->end_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="p-3"><flux:badge :color="$project->status === \App\Enums\ProjectStatus::Active ? 'green' : 'zinc'">{{ $project->status->label() }}</flux:badge></td>
                            <td class="p-3">{{ $project->attendance_records_count }}</td>
                            <td class="p-3 text-right">
                                <flux:button size="sm" variant="ghost" href="{{ route('admin.projects.edit', $project) }}">{{ __('Ubah') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-8 text-center text-zinc-500">{{ __('Belum ada data proyek.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $projects->links() }}
    </div>
</x-layouts::app>
