<x-layouts.auth title="Create Account">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Create your account and start gaming!</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label>Full Name</flux:label>
            <flux:input type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus autocomplete="name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Email Address</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required autocomplete="email" />
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

        <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
            Create Account
        </flux:button>
    </form>

    <flux:separator text="or" />

    <div class="text-center text-sm">
        <span class="text-zinc-600 dark:text-zinc-400">Already have an account?</span>
        <flux:link href="{{ route('login') }}" wire:navigate class="font-semibold ml-1">Sign In</flux:link>
    </div>

    <div class="text-center text-xs text-zinc-500 dark:text-zinc-400 mt-4">
        <p>By creating an account, you agree to our Terms of Service and Privacy Policy</p>
    </div>
</x-layouts.auth>
