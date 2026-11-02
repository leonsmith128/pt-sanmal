<x-layouts::app :title="__('Ubah Location')">
    <div class="mx-auto w-full max-w-2xl space-y-5">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ $location->client_id ? route('admin.clients.show', $location->client_id) : route('admin.clients.index') }}">{{ __('Kembali ke Master Data Client') }}</flux:button>
            <flux:heading size="xl" class="mt-3">{{ __('Ubah Location') }}</flux:heading>
            <flux:text>{{ __('Perbarui data lokasi. Perubahan timezone hanya berlaku untuk sesi yang dibuat setelah perubahan.') }}</flux:text>
        </div>
        <x-admin.flash-message />
        <form method="POST" action="{{ route('admin.locations.update', $location) }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf
            @method('PUT')
            <flux:field>
                <flux:select name="client_id" :label="__('Pemilik lokasi')">
                    <flux:select.option value="">{{ __('Internal Sanmal') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}" :selected="(string) old('client_id', $location->client_id) === (string) $client->id">{{ $client->name }} ({{ $client->code }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="client_id" />
            </flux:field>
            <flux:field>
                <flux:input name="name" :label="__('Nama lokasi')" value="{{ old('name', $location->name) }}" required />
                <flux:error name="name" />
            </flux:field>
            <flux:field>
                <flux:select name="type" :label="__('Jenis lokasi')">
                    <flux:select.option value="office" :selected="old('type', $location->type) === 'office'">{{ __('Kantor') }}</flux:select.option>
                    <flux:select.option value="site" :selected="old('type', $location->type) === 'site'">{{ __('Site / lapangan') }}</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>
            <flux:field>
                <flux:textarea name="address" :label="__('Alamat lengkap')" rows="3">{{ old('address', $location->address) }}</flux:textarea>
                <flux:error name="address" />
            </flux:field>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:input name="latitude" type="number" step="any" :label="__('Latitude')" value="{{ old('latitude', $location->latitude) }}" />
                    <flux:error name="latitude" />
                </flux:field>
                <flux:field>
                    <flux:input name="longitude" type="number" step="any" :label="__('Longitude')" value="{{ old('longitude', $location->longitude) }}" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>
            <flux:field>
                <flux:input name="timezone" :label="__('Timezone lokasi')" value="{{ old('timezone', $location->timezone) }}" placeholder="Asia/Makassar" required />
                <flux:error name="timezone" />
                <flux:description>{{ __('Timezone baru hanya digunakan oleh sesi absensi berikutnya.') }}</flux:description>
            </flux:field>
            <flux:field>
                <flux:select name="is_active" :label="__('Status')">
                    <flux:select.option value="1" :selected="(string) old('is_active', (int) $location->is_active) === '1'">{{ __('Aktif') }}</flux:select.option>
                    <flux:select.option value="0" :selected="(string) old('is_active', (int) $location->is_active) === '0'">{{ __('Nonaktif') }}</flux:select.option>
                </flux:select>
                <flux:error name="is_active" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button href="{{ $location->client_id ? route('admin.clients.show', $location->client_id) : route('admin.clients.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan perubahan Location') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
