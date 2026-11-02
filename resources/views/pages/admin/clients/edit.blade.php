<x-layouts::app :title="__('Ubah Client')">
    <div class="mx-auto w-full max-w-2xl space-y-5">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('admin.clients.show', $client) }}">{{ __('Kembali ke Detail Client') }}</flux:button>
            <flux:heading size="xl" class="mt-3">{{ __('Ubah Client') }}</flux:heading>
            <flux:text>{{ __('Perbarui identitas client.') }}</flux:text>
        </div>
        <x-admin.flash-message />
        <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf
            @method('PUT')
            <flux:field>
                <flux:input name="code" :label="__('Kode client')" value="{{ old('code', $client->code) }}" required />
                <flux:error name="code" />
            </flux:field>
            <flux:field>
                <flux:input name="name" :label="__('Nama client')" value="{{ old('name', $client->name) }}" required />
                <flux:error name="name" />
            </flux:field>
            <flux:field>
                <flux:textarea name="address" :label="__('Alamat')" rows="3">{{ old('address', $client->address) }}</flux:textarea>
                <flux:error name="address" />
            </flux:field>
            <flux:field>
                <flux:input name="phone" :label="__('Nomor telepon')" value="{{ old('phone', $client->phone) }}" />
                <flux:error name="phone" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.clients.show', $client) }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan perubahan') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
