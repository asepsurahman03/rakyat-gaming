<x-layouts.auth title="Forgot Password">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Enter your email to receive a password reset link</p>
    </div>

    @if (session('status'))
        <flux:banner variant="success" class="mb-6">
            {{ session('status') }}
        </flux:banner>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label>Email Address</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" data-test="email-password-reset-link-button">
            Email Password Reset Link
        </flux:button>
    </form>

    <flux:separator text="or" />

    <div class="text-center text-sm">
        <span class="text-zinc-600 dark:text-zinc-400">Remember your password?</span>
        <flux:link href="{{ route('login') }}" wire:navigate class="font-semibold ml-1">Sign In</flux:link>
    </div>
</x-layouts.auth>
