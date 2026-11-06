<x-layouts::app :title="__('Tambah Akun')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-admin.page-header
            :title="__('Tambah Akun')"
            :description="__('Buat akun Employee atau Admin beserta data kepegawaiannya.')"
        />

        <x-admin.flash-message />

        <x-ui.card padding="p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.employees.store') }}" class="space-y-6" x-data="{ accountRole: @js(old('role', 'employee')) }">
                @csrf

                <fieldset class="grid min-w-0 gap-4">
                    <legend class="mb-1 text-sm font-semibold text-[var(--sanmal-text-primary)]">
                        {{ __('Akun login') }}
                    </legend>

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
                    <x-ui.select
                        name="role"
                        :label="__('Jenis akun')"
                        :value="old('role', 'employee')"
                        x-on:change="accountRole = $event.target.value"
                    >
                        <flux:select.option value="employee">{{ __('Employee') }}</flux:select.option>
                        <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
                    </x-ui.select>
                </fieldset>

                <fieldset class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <legend class="mb-1 text-sm font-semibold text-[var(--sanmal-text-primary)] sm:col-span-2">
                        {{ __('Data kepegawaian') }}
                    </legend>

                    <x-ui.input
                        name="employee_number"
                        :label="__('Nomor pegawai')"
                        :description="__('Dibuat otomatis oleh sistem dan tidak dapat diubah.')"
                        :value="$employeeNumberPreview"
                        readonly
                    />

                    <x-ui.input
                        name="phone"
                        :label="__('Nomor telepon')"
                        :value="old('phone')"
                    />

                    <div x-show="accountRole === 'employee'" x-cloak>
                        <x-ui.select
                            name="position"
                            :label="__('Jabatan Employee')"
                            :value="old('position')"
                            x-bind:disabled="accountRole !== 'employee'"
                        >
                            @foreach ($employeePositions as $position)
                                <flux:select.option value="{{ $position->value }}">
                                    {{ $position->label() }}
                                </flux:select.option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div x-show="accountRole === 'admin'" x-cloak>
                        <x-ui.select
                            name="position"
                            :label="__('Jabatan Admin')"
                            :value="old('position')"
                            x-bind:disabled="accountRole !== 'admin'"
                        >
                            @foreach ($adminPositions as $position)
                                <flux:select.option value="{{ $position->value }}">
                                    {{ $position->label() }}
                                </flux:select.option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <x-ui.input
                        name="joined_at"
                        type="date"
                        :label="__('Tanggal bergabung')"
                        :value="old('joined_at')"
                    />
                </fieldset>

                <flux:callout variant="warning">
                    {{ __('Password sementara dibuat otomatis. Tautan aktivasi dikirim ke email akun; jika email gagal, admin dapat menyalin password sementara dari halaman detail akun.') }}
                </flux:callout>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline" class="w-full sm:w-auto">
                        {{ __('Batal') }}
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">
                        {{ __('Simpan akun') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.page>
</x-layouts::app>
