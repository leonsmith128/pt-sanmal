<form method="POST" action="{{ $action }}" class="space-y-4">
    <x-ui.card padding="p-5 sm:p-6" class="space-y-4">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input
            name="code"
            :label="__('Kode proyek')"
            :value="old('code', $project?->code)"
            required
        />
        <x-ui.input
            name="name"
            :label="__('Nama proyek')"
            :value="old('name', $project?->name)"
            required
        />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @if ($project !== null)
            <x-ui.field name="client_id" :label="__('Client')">
                <flux:select disabled>
                    <flux:select.option value="{{ $project->client_id }}">{{ $project->client->name }} ({{ $project->client->code }})</flux:select.option>
                </flux:select>
                <input type="hidden" name="client_id" value="{{ $project->client_id }}">
            </x-ui.field>
        @else
            <x-ui.select name="client_id" :label="__('Client')" required>
                <flux:select.option value="">{{ __('Pilih Client') }}</flux:select.option>
                @foreach ($clients as $client)
                    <flux:select.option value="{{ $client->id }}" :selected="(string) old('client_id') === (string) $client->id">{{ $client->name }} ({{ $client->code }})</flux:select.option>
                @endforeach
            </x-ui.select>
        @endif

        <x-ui.select name="project_type" :label="__('Jenis layanan')" required>
            @foreach ($projectTypes as $type)
                <flux:select.option value="{{ $type->value }}" :selected="old('project_type', $project?->project_type?->value ?? 'calibration') === $type->value">{{ $type->label() }}</flux:select.option>
            @endforeach
        </x-ui.select>
    </div>

    <x-ui.select
        name="location_id"
        :label="__('Lokasi Site')"
        :description="__('Lokasi wajib merupakan Site milik Client proyek. Lokasi dapat diperbarui; riwayat absensi tetap menyimpan lokasi saat absensi dilakukan. Lokasi nonaktif hanya ditampilkan untuk mempertahankan data proyek lama.')"
        required
    >
        <flux:select.option value="">{{ __('Pilih lokasi Site') }}</flux:select.option>
        @foreach ($locations as $location)
            <flux:select.option value="{{ $location->id }}" :selected="(string) old('location_id', $project?->location_id) === (string) $location->id">
                {{ $location->name }} — {{ $location->client?->name }}{{ $location->is_active ? '' : ' (Nonaktif)' }}
            </flux:select.option>
        @endforeach
    </x-ui.select>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input
            name="start_date"
            type="date"
            :label="__('Tanggal mulai')"
            :value="old('start_date', $project?->start_date?->format('Y-m-d'))"
        />
        <x-ui.input
            name="end_date"
            type="date"
            :label="__('Tanggal selesai')"
            :value="old('end_date', $project?->end_date?->format('Y-m-d'))"
        />
    </div>

    <x-ui.textarea
        name="description"
        :label="__('Keterangan')"
        :rows="3"
    >{{ old('description', $project?->description) }}</x-ui.textarea>

    <x-ui.select name="status" :label="__('Status')" required>
        @foreach ($statuses as $status)
            <flux:select.option value="{{ $status->value }}" :selected="old('status', $project?->status?->value ?? 'pending') === $status->value">{{ $status->label() }}</flux:select.option>
        @endforeach
    </x-ui.select>

    <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
        <x-ui.button href="{{ route('admin.projects.index') }}" variant="ghost" class="w-full sm:w-auto">{{ __('Batal') }}</x-ui.button>
        <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">{{ $method === 'PUT' ? __('Simpan perubahan') : __('Simpan proyek') }}</x-ui.button>
    </div>
    </x-ui.card>
</form>
