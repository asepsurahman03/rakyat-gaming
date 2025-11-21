<x-layouts.auth>
    <div class="text-center mb-8">
        <!-- Logo/Icon -->
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </div>
        </div>

        <h2 class="text-3xl font-bold mb-2 bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
            {{ __('Welcome Back!') }}
        </h2>
        <p class="text-base-content/70">{{ __('Sign in to continue to Warung Gaming') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

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
                class="input input-bordered w-full @error('email') input-error @else input-primary @enderror"
                required
                autofocus
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
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="label-text-alt link link-primary hover:link-secondary transition-colors" wire:navigate>
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </label>
            <input
                type="password"
                name="password"
                placeholder="••••••••"
                class="input input-bordered w-full @error('password') input-error @else input-primary @enderror"
                required
                autocomplete="current-password"
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

        <!-- Remember Me -->
        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-3 p-0">
                <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" {{ old('remember') ? 'checked' : '' }} />
                <span class="label-text">{{ __('Keep me signed in') }}</span>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-full gap-2 shadow-lg hover:shadow-xl transition-all" data-test="login-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            {{ __('Sign In') }}
        </button>

        <!-- Divider -->
        <div class="divider text-xs text-base-content/50">OR</div>

        <!-- Social Login Buttons (Optional) -->
        <div class="grid grid-cols-2 gap-3">
            <button type="button" class="btn btn-outline btn-sm gap-2" disabled>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Google
            </button>
            <button type="button" class="btn btn-outline btn-sm gap-2" disabled>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                GitHub
            </button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="text-center text-sm mt-6 p-4 bg-base-200 rounded-lg">
            <span class="text-base-content/70">{{ __('Don\'t have an account?') }}</span>
            <a href="{{ route('register') }}" class="link link-primary font-semibold hover:link-secondary transition-colors ml-1" wire:navigate>
                {{ __('Create Account') }}
            </a>
        </div>
    @endif

    <!-- Additional Info -->
    <div class="text-center text-xs text-base-content/50 mt-4">
        <p>{{ __('By signing in, you agree to our Terms of Service and Privacy Policy') }}</p>
    </div>
</x-layouts.auth>
