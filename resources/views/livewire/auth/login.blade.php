<x-layouts.auth title="Welcome Back!">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Sign in to continue to Warung Gaming</p>
    </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label>Email Address</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required autofocus autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <div class="flex items-center justify-between mb-2">
                <flux:label>Password</flux:label>
                @if (Route::has('password.request'))
                    <flux:link href="{{ route('password.request') }}" wire:navigate class="text-sm">Forgot password?</flux:link>
                @endif
            </div>
            <flux:input type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:checkbox name="remember" label="Keep me signed in" {{ old('remember') ? 'checked' : '' }} />

        <flux:button type="submit" variant="primary" class="w-full" data-test="login-button">
            Sign In
        </flux:button>
    </form>

    @if (Route::has('register'))
        <flux:separator text="or" />
        <div class="text-center text-sm">
            <span class="text-zinc-600 dark:text-zinc-400">Don't have an account?</span>
            <flux:link href="{{ route('register') }}" wire:navigate class="font-semibold ml-1">Create Account</flux:link>
        </div>
    @endif

    <div class="text-center text-xs text-zinc-500 dark:text-zinc-400 mt-4">
        <p>By signing in, you agree to our Terms of Service and Privacy Policy</p>
    </div>
</x-layouts.auth>
