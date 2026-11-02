<x-layouts::app :title="__('Tambah Location')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-ui.page-header
            :title="__('Tambah Location')"
            :description="$selectedClient
                ? __('Location Site akan langsung terhubung dengan Client :client.', ['client' => $selectedClient->name])
                : ($officeMode
                    ? __('Tambahkan kantor internal SanMal tanpa menghubungkannya ke Client.')
                    : __('Daftarkan Site milik Client atau kantor internal SanMal.'))"
        >
            <x-ui.button
                :href="$selectedClient ? route('admin.clients.show', $selectedClient) : route('admin.clients.index')"
                variant="outline"
                icon="arrow-left"
            >
                {{ __('Kembali') }}
            </x-ui.button>
        </x-ui.page-header>

        <x-admin.flash-message />

        <form
            method="POST"
            action="{{ route('admin.locations.store') }}"
            class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"
        >
            @csrf

            @if ($selectedClient)
                <input type="hidden" name="client_id" value="{{ $selectedClient->id }}">
                <input type="hidden" name="type" value="site">

                <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500">{{ __('Client') }}</p>
                    <p class="font-medium">{{ $selectedClient->name }} ({{ $selectedClient->code }})</p>
                </div>
            @elseif ($officeMode)
                <input type="hidden" name="client_id" value="">
                <input type="hidden" name="type" value="office">

                <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500">{{ __('Jenis Location') }}</p>
                    <p class="font-medium">{{ __('Office Internal SanMal') }}</p>
                </div>
            @else
                <x-ui.select
                    name="type"
                    :label="__('Jenis Location')"
                    :value="old('type', 'site')"
                >
                    <flux:select.option value="site">{{ __('Site / lapangan') }}</flux:select.option>
                    <flux:select.option value="office">{{ __('Office Internal') }}</flux:select.option>
                </x-ui.select>

                <x-ui.select
                    name="client_id"
                    :label="__('Client pemilik Site')"
                    :value="old('client_id', '')"
                    :description="__('Site wajib memiliki Client. Office internal tidak boleh memiliki Client.')"
                >
                    <flux:select.option value="">{{ __('— Tidak ada Client (Office) —') }}</flux:select.option>
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client->id }}">
                            {{ $client->name }} ({{ $client->code }})
                        </flux:select.option>
                    @endforeach
                </x-ui.select>
            @endif

            <x-ui.input
                name="name"
                :label="__('Nama Location')"
                :value="old('name')"
                required
            />

            <x-ui.textarea
                name="address"
                :label="__('Alamat lengkap')"
                :rows="3"
            >{{ old('address') }}</x-ui.textarea>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input
                    name="latitude"
                    type="number"
                    step="any"
                    :label="__('Latitude')"
                    :value="old('latitude')"
                />

                <x-ui.input
                    name="longitude"
                    type="number"
                    step="any"
                    :label="__('Longitude')"
                    :value="old('longitude')"
                />
            </div>

            <x-ui.input
                name="timezone"
                :label="__('Timezone lokasi')"
                :value="old('timezone', 'Asia/Makassar')"
                placeholder="Asia/Makassar"
                required
                description="{{ __('Gunakan identifier zona waktu IANA, misalnya Asia/Makassar atau Asia/Jakarta.') }}"
            />

            <input type="hidden" name="is_active" value="1">

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button
                    :href="$selectedClient ? route('admin.clients.show', $selectedClient) : route('admin.clients.index')"
                    variant="outline"
                >
                    {{ __('Batal') }}
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ __('Simpan Location') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.page>
</x-layouts::app>
