<form method="POST" action="{{ $action }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:field>
            <flux:input name="code" :label="__('Kode proyek')" value="{{ old('code', $project?->code) }}" required />
            <flux:error name="code" />
        </flux:field>
        <flux:field>
            <flux:input name="name" :label="__('Nama proyek')" value="{{ old('name', $project?->name) }}" required />
            <flux:error name="name" />
        </flux:field>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:field>
            @if ($project !== null)
                <flux:select :label="__('Client')" disabled>
                    <flux:select.option value="{{ $project->client_id }}">{{ $project->client->name }} ({{ $project->client->code }})</flux:select.option>
                </flux:select>
                <input type="hidden" name="client_id" value="{{ $project->client_id }}">
            @else
                <flux:select name="client_id" :label="__('Client')" required>
                    <flux:select.option value="">{{ __('Pilih Client') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}" :selected="(string) old('client_id') === (string) $client->id">{{ $client->name }} ({{ $client->code }})</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
            <flux:error name="client_id" />
        </flux:field>
        <flux:field>
            <flux:select name="project_type" :label="__('Jenis layanan')" required>
                @foreach ($projectTypes as $type)
                    <flux:select.option value="{{ $type->value }}" :selected="old('project_type', $project?->project_type?->value ?? 'calibration') === $type->value">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="project_type" />
        </flux:field>
    </div>

    <flux:field>
        <flux:select name="location_id" :label="__('Lokasi Site')" required>
            <flux:select.option value="">{{ __('Pilih lokasi Site') }}</flux:select.option>
            @foreach ($locations as $location)
                <flux:select.option value="{{ $location->id }}" :selected="(string) old('location_id', $project?->location_id) === (string) $location->id">
                    {{ $location->name }} — {{ $location->client?->name }}{{ $location->is_active ? '' : ' (Nonaktif)' }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:description>{{ __('Lokasi wajib merupakan Site milik Client proyek. Lokasi dapat diperbarui; riwayat absensi tetap menyimpan lokasi saat absensi dilakukan. Lokasi nonaktif hanya ditampilkan untuk mempertahankan data proyek lama.') }}</flux:description>
        <flux:error name="location_id" />
    </flux:field>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:field>
            <flux:input name="start_date" type="date" :label="__('Tanggal mulai')" value="{{ old('start_date', $project?->start_date?->format('Y-m-d')) }}" />
            <flux:error name="start_date" />
        </flux:field>
        <flux:field>
            <flux:input name="end_date" type="date" :label="__('Tanggal selesai')" value="{{ old('end_date', $project?->end_date?->format('Y-m-d')) }}" />
            <flux:error name="end_date" />
        </flux:field>
    </div>

    <flux:field>
        <flux:textarea name="description" :label="__('Keterangan')" rows="3">{{ old('description', $project?->description) }}</flux:textarea>
        <flux:error name="description" />
    </flux:field>

    <flux:field>
        <flux:select name="status" :label="__('Status')" required>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}" :selected="old('status', $project?->status?->value ?? 'pending') === $status->value">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="status" />
    </flux:field>

    <div class="flex justify-end gap-2">
        <flux:button href="{{ route('admin.projects.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
        <flux:button type="submit" variant="primary">{{ $method === 'PUT' ? __('Simpan perubahan') : __('Simpan proyek') }}</flux:button>
    </div>
</form>
