<x-layouts.auth title="Create Account">
    <div class="text-center mb-6">
        <p class="text-sm text-white">Create your account and start gaming!</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
        @csrf

        <flux:field>
            <label class="text-white">Full Name</label>
            <input class="px-2 text-white py-1 rounded-md border focus:border-yellow-400 focus:outline-0" type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus autocomplete="name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <label class="text-white">Email Address</label>
            <input class="px-2 text-white py-1 rounded-md border focus:border-yellow-400 focus:outline-0" type="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <label class="text-white">Password</label>
            <input class="px-2 text-white py-1 rounded-md border focus:border-yellow-400 focus:outline-0" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <label class="text-white">Confirm Password</label>
            <input class="px-2 text-white py-1 rounded-md border focus:border-yellow-400 focus:outline-0" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
            <flux:error name="password_confirmation" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
            Create Account
        </flux:button>
    </form>

    <div class="divider text-white">or</div>

    <div class="text-center text-sm">
        <span class="text-white">Already have an account?</span>
        <flux:link href="{{ route('login') }}" wire:navigate class="font-semibold ml-1">Sign In</flux:link>
    </div>

    <div class="text-center text-xs text-zinc-100 mt-4">
        <p>By creating an account, you agree to our Terms of Service and Privacy Policy</p>
    </div>
</x-layouts.auth>
