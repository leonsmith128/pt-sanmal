<x-layouts::app :title="__('Tambah Pegawai')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-admin.page-header
            :title="__('Tambah Pegawai')"
            :description="__('Buat akun pegawai dan lengkapi data administrasinya.')"
        />

        <x-admin.flash-message />

        <x-ui.card padding="p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.employees.store') }}" class="space-y-4">
            @csrf

            <x-ui.input
                name="name"
                :label="__('Nama lengkap')"
                :value="old('name')"
                required
            />

            <x-ui.input
                name="email"
                type="email"
                :label="__('Email akun')"
                :value="old('email')"
                required
            />

            <x-ui.input
                name="employee_number"
                :label="__('Nomor pegawai')"
                :value="old('employee_number')"
            />

            <x-ui.input
                name="phone"
                :label="__('Nomor telepon')"
                :value="old('phone')"
            />

            <x-ui.select name="title_id" :label="__('Jabatan')">
                <flux:select.option value="">{{ __('Belum ditentukan') }}</flux:select.option>
                @foreach ($titles as $title)
                    <flux:select.option value="{{ $title->id }}" :selected="(string) old('title_id') === (string) $title->id">
                        {{ $title->name }}
                    </flux:select.option>
                @endforeach
            </x-ui.select>

            <x-ui.input
                name="joined_at"
                type="date"
                :label="__('Tanggal bergabung')"
                :value="old('joined_at')"
            />

            <flux:callout variant="warning">
                {{ __('Password awal dibuat acak dan tidak ditampilkan. Admin perlu menjalankan prosedur reset password sebelum akun dapat digunakan.') }}
            </flux:callout>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline">{{ __('Batal') }}</x-ui.button>
                <x-ui.button type="submit" variant="primary">{{ __('Simpan pegawai') }}</x-ui.button>
            </div>
        </form>
        </x-ui.card>
    </x-ui.page>
</x-layouts::app>
