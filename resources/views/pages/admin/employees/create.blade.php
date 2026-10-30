<x-layouts::app :title="__('Tambah Pegawai')">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Tambah Pegawai') }}</flux:heading>
            <flux:text>{{ __('Buat akun pegawai dan lengkapi data administrasinya.') }}</flux:text>
        </div>

        <form method="POST" action="{{ route('admin.employees.store') }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf
            <flux:input name="name" :label="__('Nama lengkap')" value="{{ old('name') }}" required />
            <flux:input name="email" type="email" :label="__('Email akun')" value="{{ old('email') }}" required />
            <flux:input name="employee_number" :label="__('Nomor pegawai')" value="{{ old('employee_number') }}" />
            <flux:input name="phone" :label="__('Nomor telepon')" value="{{ old('phone') }}" />

            <flux:select name="title_id" :label="__('Jabatan')">
                <flux:select.option value="">{{ __('Belum ditentukan') }}</flux:select.option>
                @foreach ($titles as $title)
                    <flux:select.option value="{{ $title->id }}">{{ $title->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input name="joined_at" type="date" :label="__('Tanggal bergabung')" value="{{ old('joined_at') }}" />

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
