<x-layouts::app :title="__('Tambah Location')">
    <div class="mx-auto w-full max-w-2xl space-y-5">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ $selectedClient ? route('admin.clients.show', $selectedClient) : route('admin.clients.index') }}">{{ __('Kembali') }}</flux:button>
            <flux:heading size="xl" class="mt-3">{{ __('Tambah Location') }}</flux:heading>
            <flux:text>
                @if ($selectedClient)
                    {{ __('Location Site akan langsung terhubung dengan Client :client.', ['client' => $selectedClient->name]) }}
                @elseif ($officeMode)
                    {{ __('Tambahkan kantor internal SanMal tanpa menghubungkannya ke Client.') }}
                @else
                    {{ __('Daftarkan Site milik Client atau kantor internal SanMal.') }}
                @endif
            </flux:text>
        </div>
        <x-admin.flash-message />
        <form method="POST" action="{{ route('admin.locations.store') }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
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
                <flux:field>
                    <flux:select name="type" :label="__('Jenis Location')" :value="old('type', 'site')">
                        <flux:select.option value="site">{{ __('Site / lapangan') }}</flux:select.option>
                        <flux:select.option value="office">{{ __('Office Internal') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>
                <flux:field>
                    <flux:select name="client_id" :label="__('Client pemilik Site')" :value="old('client_id', '')">
                        <flux:select.option value="">{{ __('— Tidak ada Client (Office) —') }}</flux:select.option>
                        @foreach ($clients as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }} ({{ $client->code }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                    <flux:description>{{ __('Site wajib memiliki Client. Office internal tidak boleh memiliki Client.') }}</flux:description>
                </flux:field>
            @endif
            <flux:field>
                <flux:input name="name" :label="__('Nama Location')" value="{{ old('name') }}" required />
                <flux:error name="name" />
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
                <flux:description>{{ __('Gunakan identifier zona waktu IANA, misalnya Asia/Makassar atau Asia/Jakarta.') }}</flux:description>
            </flux:field>
            <input type="hidden" name="is_active" value="1">
            <div class="flex justify-end gap-2">
                <flux:button href="{{ $selectedClient ? route('admin.clients.show', $selectedClient) : route('admin.clients.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan Location') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
