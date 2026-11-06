<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Notifications\AccountSecurityNotification;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

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

        $oldEmail = $user->email;
        $newEmail = $validated['email'];

        DB::transaction(function () use ($user, $oldEmail, $newEmail): void {
            $user->forceFill([
                'email' => $newEmail,
                'email_verified_at' => null,
            ])->save();

            PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereNull('used_at')
                ->update([
                    'status' => 'expired',
                    'token_hash' => null,
                    'expires_at' => null,
                ]);

            AuditLog::record($user, 'account.email.changed', [
                'email' => $oldEmail,
            ], [
                'email' => $newEmail,
            ]);
        });

        $verificationSent = true;

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            $verificationSent = false;
            report($exception);
            Log::warning('Account email verification notification could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }

        try {
            Notification::route('mail', $oldEmail)->notify(new AccountSecurityNotification(
                'Email akun SanMal diubah',
                'Alamat email login akun SanMal Anda baru saja diubah menjadi '.$newEmail.'. Jika Anda tidak melakukan perubahan ini, segera hubungi administrator.',
            ));
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Account email-change security notification could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }

        $this->reset('email');
        $this->email = $user->email;

        if ($verificationSent) {
            Flux::toast(variant: 'success', text: __('Email akun diperbarui. Verifikasi alamat email baru Anda.'));
        } else {
            Flux::toast(variant: 'warning', text: __('Email akun diperbarui, tetapi tautan verifikasi gagal dikirim. Gunakan tombol kirim ulang.'));
        }
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        try {
            $sent = RateLimiter::attempt(
                'email-verification-resend:'.$user->getKey(),
                3,
                function () use ($user): bool {
                    $user->sendEmailVerificationNotification();

                    return true;
                },
                3600,
            );
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Email verification resend could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            Flux::toast(variant: 'warning', text: __('Tautan verifikasi gagal dikirim. Silakan coba kembali nanti.'));

            return;
        }

        if (! $sent) {
            Flux::toast(variant: 'warning', text: __('Batas pengiriman ulang tercapai. Coba kembali satu jam lagi.'));

            return;
        }

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Update the password and invalidate sessions on other devices.
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

        $user = Auth::user();

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password' => $validated['password'],
            ])->save();

            Auth::logoutOtherDevices($validated['password']);

            AuditLog::record($user, 'account.password.changed');
        });

        try {
            $user->notify(new AccountSecurityNotification(
                'Password akun SanMal diubah',
                'Password akun SanMal Anda baru saja diubah. Seluruh sesi pada perangkat lain telah dicabut. Jika Anda tidak melakukan perubahan ini, segera hubungi administrator.',
            ));
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Account password-change security notification could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Kata sandi berhasil diperbarui. Sesi perangkat lain telah dicabut.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = Auth::user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }
}; ?>

<x-ui.page max-width="3xl" gap="6">
    <x-ui.page-header
        :title="__('Keamanan Akun')"
        :description="__('Kelola email login dan kata sandi. Konfirmasi kata sandi diperlukan sebelum membuka halaman ini.')"
    />

    <x-ui.card padding="p-6">
        <flux:heading size="lg">{{ __('Alamat email') }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ __('Gunakan email yang dapat Anda akses. Email baru harus diverifikasi sebelum dianggap terverifikasi.') }}
        </flux:subheading>

        <form wire:submit="updateEmail" class="mt-5 space-y-4">
            <x-ui.input
                name="email"
                wire:model="email"
                :label="__('Email login')"
                type="email"
                required
                autocomplete="email"
            />

            <div class="flex justify-end">
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="updateEmail">
                    {{ __('Simpan email') }}
                </x-ui.button>
            </div>
        </form>

        @if ($this->hasUnverifiedEmail)
            <flux:callout icon="envelope" variant="warning" class="mt-5">
                <flux:callout.heading>{{ __('Email belum diverifikasi') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Periksa kotak masuk email baru Anda untuk menyelesaikan verifikasi.') }}
                </flux:callout.text>
                <x-slot name="actions">
                    <x-ui.button size="sm" variant="ghost" wire:click="resendVerificationNotification">
                        {{ __('Kirim ulang tautan verifikasi') }}
                    </x-ui.button>
                </x-slot>
            </flux:callout>
        @endif

        @if (session('status') === 'verification-link-sent')
            <flux:text class="mt-3 text-sm font-medium text-green-700 dark:text-green-400">
                {{ __('Tautan verifikasi telah dikirim ke alamat email Anda.') }}
            </flux:text>
        @endif
    </x-ui.card>

    <x-ui.card padding="p-6">
        <flux:heading size="lg">{{ __('Ubah kata sandi') }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ __('Gunakan kata sandi yang kuat dan tidak digunakan pada layanan lain.') }}
        </flux:subheading>

        <form wire:submit="updatePassword" class="mt-5 space-y-5">
            <x-ui.input
                name="current_password"
                wire:model="current_password"
                :label="__('Kata sandi saat ini')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <x-ui.input
                name="password"
                wire:model="password"
                :label="__('Kata sandi baru')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <x-ui.input
                name="password_confirmation"
                wire:model="password_confirmation"
                :label="__('Konfirmasi kata sandi baru')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex justify-end">
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                    {{ __('Perbarui kata sandi') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-ui.page>
