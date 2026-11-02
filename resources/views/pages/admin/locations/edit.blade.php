<x-layouts::app :title="__('Ubah Location')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-ui.page-header
            :title="__('Ubah Location')"
            :description="__('Perbarui data lokasi. Perubahan timezone hanya berlaku untuk sesi yang dibuat setelah perubahan.')"
        >
            <x-ui.button
                :href="$location->client_id ? route('admin.clients.show', $location->client_id) : route('admin.clients.index')"
                variant="outline"
                icon="arrow-left"
            >
                {{ __('Kembali ke Master Data Client') }}
            </x-ui.button>
        </x-ui.page-header>

        <x-admin.flash-message />

        <form
            method="POST"
            action="{{ route('admin.locations.update', $location) }}"
            class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"
        >
            @csrf
            @method('PUT')

            <x-ui.select name="client_id" :label="__('Pemilik lokasi')">
                <flux:select.option value="">{{ __('Internal Sanmal') }}</flux:select.option>
                @foreach ($clients as $client)
                    <flux:select.option
                        value="{{ $client->id }}"
                        :selected="(string) old('client_id', $location->client_id) === (string) $client->id"
                    >
                        {{ $client->name }} ({{ $client->code }})
                    </flux:select.option>
                @endforeach
            </x-ui.select>

            <x-ui.input
                name="name"
                :label="__('Nama lokasi')"
                :value="old('name', $location->name)"
                required
            />

            <x-ui.select name="type" :label="__('Jenis lokasi')">
                <flux:select.option value="office" :selected="old('type', $location->type) === 'office'">
                    {{ __('Kantor') }}
                </flux:select.option>
                <flux:select.option value="site" :selected="old('type', $location->type) === 'site'">
                    {{ __('Site / lapangan') }}
                </flux:select.option>
            </x-ui.select>

            <x-ui.textarea
                name="address"
                :label="__('Alamat lengkap')"
                :rows="3"
            >{{ old('address', $location->address) }}</x-ui.textarea>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input
                    name="latitude"
                    type="number"
                    step="any"
                    :label="__('Latitude')"
                    :value="old('latitude', $location->latitude)"
                />

                <x-ui.input
                    name="longitude"
                    type="number"
                    step="any"
                    :label="__('Longitude')"
                    :value="old('longitude', $location->longitude)"
                />
            </div>

            <x-ui.input
                name="timezone"
                :label="__('Timezone lokasi')"
                :value="old('timezone', $location->timezone)"
                placeholder="Asia/Makassar"
                required
                :description="__('Timezone baru hanya digunakan oleh sesi absensi berikutnya.')"
            />

            <x-ui.select name="is_active" :label="__('Status')">
                <flux:select.option
                    value="1"
                    :selected="(string) old('is_active', (int) $location->is_active) === '1'"
                >
                    {{ __('Aktif') }}
                </flux:select.option>
                <flux:select.option
                    value="0"
                    :selected="(string) old('is_active', (int) $location->is_active) === '0'"
                >
                    {{ __('Nonaktif') }}
                </flux:select.option>
            </x-ui.select>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button
                    :href="$location->client_id ? route('admin.clients.show', $location->client_id) : route('admin.clients.index')"
                    variant="outline"
                >
                    {{ __('Batal') }}
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ __('Simpan perubahan Location') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.page>
</x-layouts::app>
