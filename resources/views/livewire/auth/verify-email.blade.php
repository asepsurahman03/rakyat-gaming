<x-layouts.auth title="Verify Email">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Please verify your email address by clicking on the link we just emailed to you.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <flux:banner variant="success" class="mb-6">
            A new verification link has been sent to the email address you provided during registration.
        </flux:banner>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <flux:button type="submit" variant="primary" class="w-full">
                Resend Verification Email
            </flux:button>
        </form>

        <flux:separator />

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="w-full" data-test="logout-button">
                Log Out
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
