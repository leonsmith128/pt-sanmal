<x-layouts::app :title="__('Tambah Lokasi')">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Tambah Lokasi') }}</flux:heading>
            <flux:text>{{ __('Pilih client pemilik lokasi atau kosongkan untuk lokasi internal Sanmal.') }}</flux:text>
        </div>
        <x-admin.flash-message />
        <form method="POST" action="{{ route('admin.locations.store') }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf
            <flux:field>
                <flux:select name="client_id" :label="__('Pemilik lokasi')" :value="old('client_id', '')">
                    <flux:select.option value="">{{ __('Internal Sanmal') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }} ({{ $client->code }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="client_id" />
            </flux:field>
            <flux:field>
                <flux:input name="name" :label="__('Nama lokasi')" value="{{ old('name') }}" required />
                <flux:error name="name" />
            </flux:field>
            <flux:field>
                <flux:select name="type" :label="__('Jenis lokasi')" :value="old('type', 'site')">
                    <flux:select.option value="office">{{ __('Kantor') }}</flux:select.option>
                    <flux:select.option value="site">{{ __('Site / lapangan') }}</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>
            <flux:field>
                <flux:textarea name="address" :label="__('Alamat lengkap')" rows="3">{{ old('address') }}</flux:textarea>
                <flux:error name="address" />
            </flux:field>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:input name="latitude" type="number" step="any" :label="__('Latitude')" value="{{ old('latitude') }}" />
                    <flux:error name="latitude" />
                </flux:field>
                <flux:field>
                    <flux:input name="longitude" type="number" step="any" :label="__('Longitude')" value="{{ old('longitude') }}" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>
            <flux:field>
                <flux:input name="timezone" :label="__('Timezone lokasi')" value="{{ old('timezone', 'Asia/Makassar') }}" placeholder="Asia/Makassar" required />
                <flux:error name="timezone" />
                <flux:description>{{ __('Masukkan identifier zona waktu IANA, misalnya Asia/Makassar atau Asia/Jakarta.') }}</flux:description>
            </flux:field>
            <input type="hidden" name="is_active" value="1">
            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.locations.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan lokasi') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
