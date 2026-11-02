<x-layouts::app :title="__('Tambah Client')">
    <x-ui.page class="max-w-2xl gap-5">
        <x-ui.page-header
            :title="__('Tambah Client')"
            :description="__('Masukkan identitas perusahaan atau organisasi.')"
        >
            <x-ui.button
                :href="route('admin.clients.index')"
                variant="outline"
                icon="arrow-left"
            >
                {{ __('Kembali ke Master Data Client') }}
            </x-ui.button>
        </x-ui.page-header>

        <x-admin.flash-message />

        <form
            method="POST"
            action="{{ route('admin.clients.store') }}"
            class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"
        >
            @csrf

            <x-ui.input
                name="code"
                :label="__('Kode client')"
                :value="old('code')"
                required
            />

            <x-ui.input
                name="name"
                :label="__('Nama client')"
                :value="old('name')"
                required
            />

            <x-ui.textarea
                name="address"
                :label="__('Alamat')"
                :rows="3"
            >{{ old('address') }}</x-ui.textarea>

            <x-ui.input
                name="phone"
                :label="__('Nomor telepon')"
                :value="old('phone')"
            />

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button
                    :href="route('admin.clients.index')"
                    variant="outline"
                >
                    {{ __('Batal') }}
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ __('Simpan client') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.page>
</x-layouts::app>
