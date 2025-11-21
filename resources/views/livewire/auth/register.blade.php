<x-layouts.auth>
    <div class="text-center mb-8">
        <!-- Logo/Icon -->
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-secondary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
        </div>

        <h2 class="text-3xl font-bold mb-2 bg-gradient-to-r from-secondary to-primary bg-clip-text text-transparent">
            {{ __('Join Warung Gaming') }}
        </h2>
        <p class="text-base-content/70">{{ __('Create your account and start gaming!') }}</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-semibold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ __('Full Name') }}
                </span>
            </label>
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                placeholder="John Doe"
                class="input input-bordered w-full @error('name') input-error @else input-secondary @enderror"
                required
                autofocus
                autocomplete="name"
            />
            @error('name')
                <label class="label">
                    <span class="label-text-alt text-error flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </span>
                </label>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-semibold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                    {{ __('Email Address') }}
                </span>
            </label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="your.email@example.com"
                class="input input-bordered w-full @error('email') input-error @else input-secondary @enderror"
                required
                autocomplete="email"
            />
            @error('email')
                <label class="label">
                    <span class="label-text-alt text-error flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </span>
                </label>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-semibold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    {{ __('Password') }}
                </span>
            </label>
            <input
                type="password"
                name="password"
                placeholder="••••••••"
                class="input input-bordered w-full @error('password') input-error @else input-secondary @enderror"
                required
                autocomplete="new-password"
            />
            @error('password')
                <label class="label">
                    <span class="label-text-alt text-error flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </span>
                </label>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-semibold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Confirm Password') }}
                </span>
            </label>
            <input
                type="password"
                name="password_confirmation"
                placeholder="••••••••"
                class="input input-bordered w-full @error('password_confirmation') input-error @else input-secondary @enderror"
                required
                autocomplete="new-password"
            />
            @error('password_confirmation')
                <label class="label">
                    <span class="label-text-alt text-error flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </span>
                </label>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-secondary w-full gap-2 shadow-lg hover:shadow-xl transition-all" data-test="register-user-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
            </svg>
            {{ __('Create Account') }}
        </button>
    </form>

    <div class="text-center text-sm mt-6 p-4 bg-base-200 rounded-lg">
        <span class="text-base-content/70">{{ __('Already have an account?') }}</span>
        <a href="{{ route('login') }}" class="link link-secondary font-semibold hover:link-primary transition-colors ml-1" wire:navigate>
            {{ __('Sign In') }}
        </a>
    </div>

    <!-- Additional Info -->
    <div class="text-center text-xs text-base-content/50 mt-4">
        <p>{{ __('By creating an account, you agree to our Terms of Service and Privacy Policy') }}</p>
    </div>
</x-layouts.auth>
