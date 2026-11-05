<x-layouts::app :title="__('Ubah Pegawai')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-admin.page-header
            :title="__('Ubah Data Pegawai')"
            :description="$employee->user->email"
        />

        <x-admin.flash-message />

        <x-ui.card padding="p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-ui.input name="name" :label="__('Nama lengkap')" :value="old('name', $employee->user->name)" required />
                <x-ui.input name="email" type="email" :label="__('Email akun')" :value="old('email', $employee->user->email)" required />
                <x-ui.input name="employee_number" :label="__('Nomor pegawai')" :value="old('employee_number', $employee->employee_number)" />
                <x-ui.input name="phone" :label="__('Nomor telepon')" :value="old('phone', $employee->phone)" />

                <x-ui.select name="title_id" :label="__('Jabatan')">
                    <flux:select.option value="">{{ __('Belum ditentukan') }}</flux:select.option>
                    @foreach ($titles as $title)
                        <flux:select.option
                            value="{{ $title->id }}"
                            :selected="(string) old('title_id', $employee->title_id) === (string) $title->id"
                        >
                            {{ $title->name }}
                        </flux:select.option>
                    @endforeach
                </x-ui.select>

                <x-ui.input
                    name="joined_at"
                    type="date"
                    :label="__('Tanggal bergabung')"
                    :value="old('joined_at', $employee->joined_at?->format('Y-m-d'))"
                />

                <x-ui.select name="is_active" :label="__('Status akun')">
                    <flux:select.option value="1" :selected="(string) old('is_active', (int) $employee->user->is_active) === '1'">{{ __('Aktif') }}</flux:select.option>
                    <flux:select.option value="0" :selected="(string) old('is_active', (int) $employee->user->is_active) === '0'">{{ __('Nonaktif') }}</flux:select.option>
                </x-ui.select>

                <div class="flex justify-end gap-2">
                    <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline">{{ __('Batal') }}</x-ui.button>
                    <x-ui.button type="submit" variant="primary">{{ __('Simpan perubahan') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($employee->user->accountCredential)
            <x-ui.card padding="p-4" class="space-y-3">
                <div>
                    <h2 class="font-medium">{{ __('Password sementara') }}</h2>
                    <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">
                        {{ __('Tersedia sampai pegawai mengganti password pertamanya. Nilai password tidak ditampilkan di halaman.') }}
                    </p>
                </div>
                <div
                    class="flex flex-wrap items-center gap-2"
                    x-data="{
                        copying: false,
                        copied: false,
                        async copyTemporaryPassword() {
                            this.copying = true;
                            this.copied = false;
                            try {
                                const response = await fetch(@js(route('admin.employees.temporary-password', $employee)), {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                        'Accept': 'application/json',
                                    },
                                });
                                if (!response.ok) throw new Error('Request failed');
                                const data = await response.json();
                                await navigator.clipboard.writeText(data.password);
                                this.copied = true;
                            } catch (error) {
                                window.alert('Password tidak dapat disalin. Pastikan browser mengizinkan akses clipboard.');
                            } finally {
                                this.copying = false;
                            }
                        }
                    }"
                >
                    <x-ui.button type="button" variant="outline" x-on:click="copyTemporaryPassword()" x-bind:disabled="copying">
                        <span x-show="!copying">{{ __('Salin password sementara') }}</span>
                        <span x-show="copying">{{ __('Memproses...') }}</span>
                    </x-ui.button>
                    <span x-cloak x-show="copied" class="text-sm text-green-700">{{ __('Password berhasil disalin.') }}</span>
                </div>
            </x-ui.card>
        @endif

        @if ($employee->user->must_change_password)
            <form method="POST" action="{{ route('admin.employees.resend-activation', $employee) }}">
                @csrf
                <x-ui.button type="submit" variant="outline">{{ __('Kirim ulang tautan aktivasi') }}</x-ui.button>
            </form>
        @endif
    </x-ui.page>
</x-layouts::app>
