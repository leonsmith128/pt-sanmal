<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-5">
        <x-auth-header
            :title="__('Verify your email')"
            :description="__('Please verify your email address by clicking the link we sent to your inbox.')"
        />

        <x-auth-session-status :status="session('status')" />

        @if (session('status') == 'verification-link-sent')
            <flux:callout variant="success" icon="check-circle">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </flux:callout>
        @else
            <flux:callout variant="info" icon="envelope">
                {{ __('Open the message from SanMal and follow its verification link to continue.') }}
            </flux:callout>
        @endif

        <div class="flex flex-col gap-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button variant="ghost" type="submit" class="w-full" data-test="logout-button">
                    {{ __('Log out') }}
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts::auth>
