<x-layouts.auth>
    <h2 class="text-2xl font-bold text-center mb-2">{{ __('Forgot password') }}</h2>
    <p class="text-center text-sm opacity-70 mb-6">{{ __('Enter your email to receive a password reset link') }}</p>

    <!-- Session Status -->
    <x-auth-session-status class="text-center mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Email Address') }}</span>
            </label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com"
                   class="input input-bordered w-full @error('email') input-error @enderror"
                   required autofocus />
            @error('email')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="email-password-reset-link-button">
            {{ __('Email password reset link') }}
        </button>
    </form>

    <div class="text-center text-sm mt-4">
        <span>{{ __('Or, return to') }}</span>
        <a href="{{ route('login') }}" class="link link-primary" wire:navigate>{{ __('log in') }}</a>
    </div>
</x-layouts.auth>
