<x-layouts::app :title="__('Ubah Pegawai')">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <flux:heading size="xl">{{ __('Ubah Data Pegawai') }}</flux:heading>
            <flux:text>{{ $employee->user->email }}</flux:text>
        </div>

        <x-admin.flash-message />

        <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @csrf
            @method('PUT')

            <flux:field>
                <flux:input name="name" :label="__('Nama lengkap')" value="{{ old('name', $employee->user->name) }}" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:input name="email" type="email" :label="__('Email akun')" value="{{ old('email', $employee->user->email) }}" required />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:input name="employee_number" :label="__('Nomor pegawai')" value="{{ old('employee_number', $employee->employee_number) }}" />
                <flux:error name="employee_number" />
            </flux:field>

            <flux:field>
                <flux:input name="phone" :label="__('Nomor telepon')" value="{{ old('phone', $employee->phone) }}" />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:select name="title_id" :label="__('Jabatan')">
                    <flux:select.option value="">{{ __('Belum ditentukan') }}</flux:select.option>
                    @foreach ($titles as $title)
                        <flux:select.option
                            value="{{ $title->id }}"
                            :selected="(string) old('title_id', $employee->title_id) === (string) $title->id"
                        >
                            {{ $title->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="title_id" />
            </flux:field>

            <flux:field>
                <flux:input name="joined_at" type="date" :label="__('Tanggal bergabung')" value="{{ old('joined_at', $employee->joined_at?->format('Y-m-d')) }}" />
                <flux:error name="joined_at" />
            </flux:field>

            <flux:field>
                <flux:select name="is_active" :label="__('Status akun')">
                    <flux:select.option value="1" :selected="(string) old('is_active', (int) $employee->user->is_active) === '1'">{{ __('Aktif') }}</flux:select.option>
                    <flux:select.option value="0" :selected="(string) old('is_active', (int) $employee->user->is_active) === '0'">{{ __('Nonaktif') }}</flux:select.option>
                </flux:select>
                <flux:error name="is_active" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('admin.employees.index') }}" variant="ghost">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan perubahan') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
