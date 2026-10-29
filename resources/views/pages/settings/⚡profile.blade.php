<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengaturan Akun')] class extends Component {
    use ProfileValidationRules;

    public string $email = '';

    public function mount(): void
    {
        $this->email = Auth::user()->email;
    }

    /** Update only the login email; the official name is managed by an admin. */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'email' => $this->emailRules($user->id),
        ]);

        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
            $user->save();
        }

        Flux::toast(variant: 'success', text: __('Email akun berhasil diperbarui.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Pengaturan Akun') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Email akun')" :subheading="__('Ubah alamat email yang digunakan untuk masuk ke akun Anda.')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input
                wire:model="email"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email"
            />

            @if ($this->hasUnverifiedEmail)
                <div>
                    <flux:text class="mt-4">
                        {{ __('Alamat email Anda belum diverifikasi.') }}
                        <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                            {{ __('Kirim ulang email verifikasi.') }}
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium !text-green-600 dark:!text-green-400">
                            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </flux:text>
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Simpan email') }}
                </flux:button>
            </div>
        </form>

        <flux:callout icon="information-circle" class="mt-6">
            Nama resmi dan identitas kepegawaian hanya dapat diubah oleh admin.
        </flux:callout>
    </x-pages::settings.layout>
</section>
