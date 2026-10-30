<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Keamanan Akun')] class extends Component {
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $email = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->email = Auth::user()->email;
    }

    /**
     * Update the login email after the route's password-confirmation check.
     * A changed email must be verified again.
     */
    public function updateEmail(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'email' => $this->emailRules($user->id),
        ]);

        if ($user->email === $validated['email']) {
            Flux::toast(variant: 'info', text: __('Alamat email tidak berubah.'));

            return;
        }

        $user->email = $validated['email'];
        $user->email_verified_at = null;
        $user->save();
        $user->sendEmailVerificationNotification();

        $this->reset('email');
        $this->email = $user->email;

        Flux::toast(variant: 'success', text: __('Email akun diperbarui. Verifikasi alamat email baru Anda.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $exception;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Kata sandi berhasil diperbarui.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = Auth::user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Keamanan Akun') }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ __('Kelola email login dan kata sandi. Konfirmasi kata sandi diperlukan sebelum membuka halaman ini.') }}
        </flux:subheading>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Alamat email') }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ __('Gunakan email yang dapat Anda akses. Email baru harus diverifikasi sebelum dianggap terverifikasi.') }}
        </flux:subheading>

        <form wire:submit="updateEmail" class="mt-5 space-y-4">
            <flux:input
                wire:model="email"
                :label="__('Email login')"
                type="email"
                required
                autocomplete="email"
            />

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="updateEmail">
                    {{ __('Simpan email') }}
                </flux:button>
            </div>
        </form>

        @if ($this->hasUnverifiedEmail)
            <flux:callout icon="envelope" variant="warning" class="mt-5">
                <flux:callout.heading>{{ __('Email belum diverifikasi') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Periksa kotak masuk email baru Anda untuk menyelesaikan verifikasi.') }}
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:button size="sm" variant="ghost" wire:click="resendVerificationNotification">
                        {{ __('Kirim ulang tautan verifikasi') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        @if (session('status') === 'verification-link-sent')
            <flux:text class="mt-3 text-sm font-medium text-green-700 dark:text-green-400">
                {{ __('Tautan verifikasi telah dikirim ke alamat email Anda.') }}
            </flux:text>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Ubah kata sandi') }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ __('Gunakan kata sandi yang kuat dan tidak digunakan pada layanan lain.') }}
        </flux:subheading>

        <form wire:submit="updatePassword" class="mt-5 space-y-5">
            <flux:input
                wire:model="current_password"
                :label="__('Kata sandi saat ini')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('Kata sandi baru')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Konfirmasi kata sandi baru')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                    {{ __('Perbarui kata sandi') }}
                </flux:button>
            </div>
        </form>
    </div>
</section>
