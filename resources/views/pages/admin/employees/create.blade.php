<x-layouts::app :title="__('Tambah Pegawai')">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Tambah Pegawai') }}</flux:heading>
            <flux:text>{{ __('Buat akun pegawai dan lengkapi data administrasinya.') }}</flux:text>
        </div>

        <x-admin.flash-message />

        <form method="POST" action="{{ route('admin.employees.store') }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf

            <flux:field>
                <flux:input name="name" :label="__('Nama lengkap')" value="{{ old('name') }}" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:input name="email" type="email" :label="__('Email akun')" value="{{ old('email') }}" required />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:input name="employee_number" :label="__('Nomor pegawai')" value="{{ old('employee_number') }}" />
                <flux:error name="employee_number" />
            </flux:field>

            <flux:field>
                <flux:input name="phone" :label="__('Nomor telepon')" value="{{ old('phone') }}" />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:select name="title_id" :label="__('Jabatan')">
                    <flux:select.option value="">{{ __('Belum ditentukan') }}</flux:select.option>
                    @foreach ($titles as $title)
                        <flux:select.option value="{{ $title->id }}" :selected="(string) old('title_id') === (string) $title->id">
                            {{ $title->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="title_id" />
            </flux:field>

            <flux:field>
                <flux:input name="joined_at" type="date" :label="__('Tanggal bergabung')" value="{{ old('joined_at') }}" />
                <flux:error name="joined_at" />
            </flux:field>

            <flux:callout variant="warning">
                {{ __('Password awal dibuat acak dan tidak ditampilkan. Admin perlu menjalankan prosedur reset password sebelum akun dapat digunakan.') }}
            </flux:callout>

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.employees.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan pegawai') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
