<x-layouts.auth title="Forgot Password">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-100">Enter your email to receive a password reset link</p>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-6">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <flux:field>
            <label class="text-white">Email Address</label>
            <input class="text-white border focus:border-yellow-400 focus:outline-0 px-2 py-1 rounded-md" type="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" data-test="email-password-reset-link-button">
            Email Password Reset Link
        </flux:button>
    </form>

    <div class="divider text-white">or</div>

    <div class="text-center text-sm">
        <span class="text-zinc-100">Remember your password?</span>
        <flux:link href="{{ route('login') }}" wire:navigate class="font-semibold ml-1">Sign In</flux:link>
    </div>
</x-layouts.auth>
