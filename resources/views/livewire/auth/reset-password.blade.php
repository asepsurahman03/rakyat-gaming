<x-layouts.auth>
    <h2 class="text-2xl font-bold text-center mb-2">{{ __('Reset password') }}</h2>
    <p class="text-center text-sm opacity-70 mb-6">{{ __('Please enter your new password below') }}</p>

    <!-- Session Status -->
    <x-auth-session-status class="text-center mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <!-- Token -->
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Email') }}</span>
            </label>
            <input type="email" name="email" value="{{ request('email') }}"
                   class="input input-bordered w-full @error('email') input-error @enderror"
                   required autocomplete="email" readonly />
            @error('email')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Password') }}</span>
            </label>
            <input type="password" name="password" placeholder="{{ __('Password') }}"
                   class="input input-bordered w-full @error('password') input-error @enderror"
                   required autocomplete="new-password" />
            @error('password')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Confirm password') }}</span>
            </label>
            <input type="password" name="password_confirmation" placeholder="{{ __('Confirm password') }}"
                   class="input input-bordered w-full @error('password_confirmation') input-error @enderror"
                   required autocomplete="new-password" />
            @error('password_confirmation')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="reset-password-button">
            {{ __('Reset password') }}
        </button>
    </form>
    </div>
</x-layouts.auth>
