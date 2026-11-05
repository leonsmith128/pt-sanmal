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

        @php
            $accountStatus = ! $employee->user->is_active
                ? __('Nonaktif')
                : ($employee->user->must_change_password ? __('Menunggu aktivasi / ganti password') : __('Aktif'));
        @endphp

        <x-ui.card padding="p-4" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">{{ __('Status akun') }}</h2>
                <x-ui.badge :color="! $employee->user->is_active ? 'zinc' : ($employee->user->must_change_password ? 'amber' : 'green')">
                    {{ $accountStatus }}
                </x-ui.badge>
            </div>
            <div class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-[var(--sanmal-text-muted)]">{{ __('Verifikasi email') }}</p>
                    <p class="mt-1 font-medium">{{ $employee->user->hasVerifiedEmail() ? __('Terverifikasi') : __('Belum terverifikasi') }}</p>
                </div>
                <div>
                    <p class="text-[var(--sanmal-text-muted)]">{{ __('Aktivasi akun') }}</p>
                    <p class="mt-1 font-medium">
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
                    </p>
                </div>
            </div>
            <p class="text-xs text-[var(--sanmal-text-muted)]">
                {{ __('Status aktivasi akun dan verifikasi email merupakan dua hal terpisah. Pegawai dapat menyelesaikan aktivasi dengan tautan email atau password sementara dari admin.') }}
            </p>
        </x-ui.card>

        <x-ui.card padding="p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <h2 class="font-semibold">{{ __('Data pegawai') }}</h2>
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

        @if ($employee->user->is_active && $employee->user->accountCredential)
            <x-ui.card padding="p-4" class="space-y-3">
                <div>
                    <h2 class="font-semibold">{{ __('Password sementara') }}</h2>
                    <p class="mt-1 text-sm text-[var(--sanmal-text-muted)]">
                        {{ __('Salin password ini dan sampaikan kepada pegawai melalui saluran yang aman. Password tersedia sampai pegawai berhasil menggantinya.') }}
                    </p>
                </div>

                <div
                    class="space-y-3"
                    x-data="{
                        copying: false,
                        copied: false,
                        manualPassword: '',
                        message: '',
                        async copyText(value) {
                            if (navigator.clipboard && window.isSecureContext) {
                                try {
                                    await navigator.clipboard.writeText(value);
                                    return true;
                                } catch (error) {
                                    // Continue to the browser-compatible fallback below.
                                }
                            }

                            const textarea = document.createElement('textarea');
                            textarea.value = value;
                            textarea.setAttribute('readonly', '');
                            textarea.style.position = 'fixed';
                            textarea.style.opacity = '0';
                            textarea.style.pointerEvents = 'none';
                            document.body.appendChild(textarea);
                            textarea.select();
                            textarea.setSelectionRange(0, textarea.value.length);
                            let success = false;
                            try {
                                success = document.execCommand('copy');
                            } finally {
                                textarea.remove();
                            }

                            return success;
                        },
                        async copyTemporaryPassword() {
                            this.copying = true;
                            this.copied = false;
                            this.message = '';
                            this.manualPassword = '';

                            try {
                                const response = await fetch(@js(route('admin.employees.temporary-password', $employee)), {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    cache: 'no-store',
                                    headers: {
                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                        'Accept': 'application/json',
                                    },
                                });

                                if (!response.ok) {
                                    throw new Error('Password sementara tidak dapat diambil.');
                                }

                                const data = await response.json();
                                if (typeof data.password !== 'string' || data.password.length === 0) {
                                    throw new Error('Respons password tidak valid.');
                                }

                                const password = data.password;
                                const success = await this.copyText(password);

                                if (success) {
                                    this.copied = true;
                                    this.message = 'Password berhasil disalin ke clipboard.';
                                } else {
                                    this.manualPassword = password;
                                    this.message = 'Clipboard tidak tersedia. Salin password dari kolom berikut secara manual.';
                                    this.$nextTick(() => {
                                        this.$refs.manualPasswordInput?.focus();
                                        this.$refs.manualPasswordInput?.select();
                                    });
                                }
                            } catch (error) {
                                this.message = error.message || 'Password gagal disalin. Silakan coba kembali.';
                            } finally {
                                this.copying = false;
                            }
                        },
                        async copyManualPassword() {
                            if (!this.manualPassword) return;
                            const success = await this.copyText(this.manualPassword);
                            if (success) {
                                this.copied = true;
                                this.message = 'Password berhasil disalin ke clipboard.';
                                this.manualPassword = '';
                            } else {
                                this.$refs.manualPasswordInput?.focus();
                                this.$refs.manualPasswordInput?.select();
                                this.message = 'Tekan Ctrl+C atau gunakan Salin pada menu browser.';
                            }
                        }
                    }"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            icon="clipboard"
                            x-on:click="copyTemporaryPassword()"
                            x-bind:disabled="copying"
                        >
                            <span x-show="!copying">{{ __('Salin password sementara') }}</span>
                            <span x-cloak x-show="copying">{{ __('Mengambil password...') }}</span>
                        </x-ui.button>
                        <span x-cloak x-show="copied" class="text-sm font-medium text-green-700 dark:text-green-400" aria-live="polite">
                            <span class="inline-flex items-center gap-1"><span aria-hidden="true">✓</span>{{ __('Tersalin') }}</span>
                        </span>
                    </div>

                    <p x-cloak x-show="message" x-text="message" class="text-sm text-[var(--sanmal-text-muted)]" role="status" aria-live="polite"></p>

                    <div x-cloak x-show="manualPassword" class="space-y-2">
                        <label for="temporary-password-manual" class="text-sm font-medium">{{ __('Password sementara') }}</label>
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
                            <x-ui.button type="button" variant="outline" x-on:click="copyManualPassword()">
                                {{ __('Salin ulang') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if ($employee->user->is_active && $employee->user->must_change_password)
            <x-ui.card padding="p-4" class="space-y-3">
                <div>
                    <h2 class="font-semibold">{{ __('Tindakan aktivasi') }}</h2>
                    <p class="text-sm text-[var(--sanmal-text-muted)]">
                        {{ __('Gunakan kirim ulang jika tautan aktivasi kedaluwarsa atau email sebelumnya gagal dikirim. Rotasi akan mengganti password sementara yang lama.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.employees.resend-activation', $employee) }}">
                        @csrf
                        <x-ui.button type="submit" variant="outline" icon="mail">{{ __('Kirim ulang tautan aktivasi') }}</x-ui.button>
                    </form>
                    @if ($employee->user->accountCredential)
                        <form method="POST" action="{{ route('admin.employees.rotate-temporary-password', $employee) }}">
                            @csrf
                            <x-ui.button type="submit" variant="outline" icon="refresh-cw">{{ __('Rotasi password sementara') }}</x-ui.button>
                        </form>
                    @endif
                </div>
            </x-ui.card>
        @endif
    </x-ui.page>
</x-layouts::app>
