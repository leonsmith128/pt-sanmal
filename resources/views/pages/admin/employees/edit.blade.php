<x-layouts::app :title="__('Ubah Pegawai')">
    <x-ui.page class="!max-w-2xl gap-5">
        <x-admin.page-header
            :title="__('Pengelolaan Akun Pegawai')"
            :description="$employee->user->email"
        >
            <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline" icon="arrow-left">
                {{ __('Daftar pegawai') }}
            </x-ui.button>
        </x-admin.page-header>

        <x-admin.flash-message />

        <x-ui.card padding="p-5 sm:p-6" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">{{ __('Status akun') }}</h2>
                <x-admin.employee-status-badge :user="$employee->user" long />
            </div>

            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <x-ui.metadata-item
                    :label="__('Verifikasi email')"
                    label-class="text-sm text-[var(--sanmal-text-muted)]"
                    value-class="mt-1.5 font-medium"
                >
                    <x-ui.badge :color="$employee->user->hasVerifiedEmail() ? 'green' : 'zinc'">
                        {{ $employee->user->hasVerifiedEmail() ? __('Terverifikasi') : __('Belum terverifikasi') }}
                    </x-ui.badge>
                </x-ui.metadata-item>

                <x-ui.metadata-item
                    :label="__('Aktivasi akun')"
                    label-class="text-sm text-[var(--sanmal-text-muted)]"
                    value-class="mt-1.5 font-medium"
                >
                    @if (! $employee->user->must_change_password)
                        {{ __('Selesai') }}
                    @elseif ($employee->user->accountActivation?->consumed_at)
                        {{ __('Tautan sudah digunakan') }}
                    @elseif ($employee->user->accountActivation?->isUsable())
                        {{ __('Tautan berlaku sampai :date', ['date' => $employee->user->accountActivation->expires_at->format('d M Y H:i')]) }}
                    @elseif ($employee->user->is_active)
                        {{ __('Tautan belum tersedia atau sudah kedaluwarsa') }}
                    @else
                        {{ __('Akun dinonaktifkan') }}
                    @endif
                </x-ui.metadata-item>
            </dl>

            <p class="text-xs text-[var(--sanmal-text-muted)]">
                {{ __('Status aktivasi akun dan verifikasi email merupakan dua hal terpisah. Pegawai dapat menyelesaikan aktivasi dengan tautan email atau password sementara dari admin.') }}
            </p>
        </x-ui.card>

        <x-ui.card padding="p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <fieldset class="grid min-w-0 gap-4">
                    <legend class="mb-1 font-semibold text-[var(--sanmal-text-primary)]">
                        {{ __('Data pegawai') }}
                    </legend>

                    <x-ui.input
                        name="name"
                        :label="__('Nama lengkap')"
                        :value="old('name', $employee->user->name)"
                        required
                    />

                    <x-ui.input
                        name="email"
                        type="email"
                        :label="__('Email akun')"
                        :value="old('email', $employee->user->email)"
                        required
                    />
                </fieldset>

                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <x-ui.input
                        name="employee_number"
                        :label="__('Nomor pegawai')"
                        :description="__('Nomor pegawai dibuat otomatis dan tidak dapat diubah.')"
                        :value="$employee->employee_number"
                        readonly
                    />

                    <x-ui.input
                        name="phone"
                        :label="__('Nomor telepon')"
                        :value="old('phone', $employee->phone)"
                    />

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
                </div>

                <x-ui.select
                    name="is_active"
                    :label="__('Status akun')"
                    :description="__('Akun nonaktif tidak dapat menggunakan aplikasi. Data dan riwayat absensi tetap tersimpan.')"
                >
                    <flux:select.option
                        value="1"
                        :selected="(string) old('is_active', (int) $employee->user->is_active) === '1'"
                    >
                        {{ __('Aktif') }}
                    </flux:select.option>
                    <flux:select.option
                        value="0"
                        :selected="(string) old('is_active', (int) $employee->user->is_active) === '0'"
                    >
                        {{ __('Nonaktif') }}
                    </flux:select.option>
                </x-ui.select>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button href="{{ route('admin.employees.index') }}" variant="outline" class="w-full sm:w-auto">
                        {{ __('Batal') }}
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">
                        {{ __('Simpan perubahan') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($employee->user->is_active && $employee->user->must_change_password && $employee->user->accountCredential)
            <x-ui.card padding="p-5 sm:p-6" class="space-y-4">
                <div>
                    <h2 class="font-semibold">{{ __('Password sementara') }}</h2>
                    <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">
                        {{ __('Salin password ini dan sampaikan kepada pegawai melalui saluran yang aman. Password tersedia sampai pegawai berhasil menggantinya.') }}
                    </p>
                </div>

                <div
                    class="space-y-3"
                    x-data="temporaryPasswordCopy({
                        url: @js(route('admin.employees.temporary-password', $employee)),
                        csrf: @js(csrf_token()),
                    })"
                >
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            icon="clipboard-document"
                            class="w-full sm:w-auto"
                            x-on:click="copyTemporaryPassword()"
                            x-bind:disabled="copying"
                        >
                            <span x-show="!copying">{{ __('Salin password sementara') }}</span>
                            <span x-cloak x-show="copying">{{ __('Mengambil password...') }}</span>
                        </x-ui.button>

                        <span
                            x-cloak
                            x-show="copied"
                            class="text-sm font-medium text-green-700 dark:text-green-400"
                            aria-live="polite"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span aria-hidden="true">✓</span>{{ __('Tersalin') }}
                            </span>
                        </span>
                    </div>

                    <p
                        x-cloak
                        x-show="message"
                        x-text="message"
                        class="text-sm text-[var(--sanmal-text-muted)]"
                        role="status"
                        aria-live="polite"
                    ></p>

                    <div x-cloak x-show="manualPassword" class="space-y-2">
                        <label for="temporary-password-manual" class="text-sm font-medium">
                            {{ __('Password sementara') }}
                        </label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input
                                id="temporary-password-manual"
                                x-ref="manualPasswordInput"
                                x-model="manualPassword"
                                type="text"
                                readonly
                                autocomplete="off"
                                spellcheck="false"
                                class="min-w-0 flex-1 rounded-lg border border-sanmal-border bg-sanmal-surface-secondary px-3 py-2 font-mono text-sm text-[var(--sanmal-text-primary)] dark:border-sanmal-border-dark dark:bg-sanmal-surface-secondary-dark"
                            />
                            <x-ui.button
                                type="button"
                                variant="outline"
                                class="w-full sm:w-auto"
                                x-on:click="copyManualPassword()"
                            >
                                {{ __('Salin ulang') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if ($employee->user->is_active && $employee->user->must_change_password)
            <x-ui.card padding="p-5 sm:p-6" class="space-y-4">
                <div>
                    <h2 class="font-semibold">{{ __('Tindakan aktivasi') }}</h2>
                    <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">
                        {{ __('Gunakan kirim ulang jika tautan aktivasi kedaluwarsa atau email sebelumnya gagal dikirim. Rotasi akan mengganti password sementara yang lama.') }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <form
                        method="POST"
                        action="{{ route('admin.employees.resend-activation', $employee) }}"
                        class="w-full sm:w-auto"
                    >
                        @csrf
                        <x-ui.button type="submit" variant="outline" class="w-full sm:w-auto">
                            {{ __('Kirim ulang tautan aktivasi') }}
                        </x-ui.button>
                    </form>

                    @if ($employee->user->accountCredential)
                        <form
                            method="POST"
                            action="{{ route('admin.employees.rotate-temporary-password', $employee) }}"
                            class="w-full sm:w-auto"
                        >
                            @csrf
                            <x-ui.button type="submit" variant="outline" class="w-full sm:w-auto">
                                {{ __('Rotasi password sementara') }}
                            </x-ui.button>
                        </form>
                    @endif
                </div>
            </x-ui.card>
        @endif
    </x-ui.page>
</x-layouts::app>
