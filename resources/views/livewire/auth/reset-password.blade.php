<x-layouts.auth title="Reset Password">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Please enter your new password below</p>
    </div>

    @if (session('status'))
        <flux:banner variant="success" class="mb-6">
            {{ session('status') }}
        </flux:banner>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" name="email" value="{{ request('email') }}" required autocomplete="email" readonly />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirm Password</flux:label>
            <flux:input type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
            <flux:error name="password_confirmation" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
            Reset Password
        </flux:button>
    </form>
</x-layouts.auth>
