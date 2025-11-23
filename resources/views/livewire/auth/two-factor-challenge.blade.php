<x-layouts.auth title="Two-Factor Authentication">
    <div
        class="relative w-full h-auto"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;

                this.code = '';
                this.recovery_code = '';

                $dispatch('clear-2fa-auth-code');

                $nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : $dispatch('focus-2fa-auth-code');
                });
            },
        }"
    >
        <div x-show="!showRecoveryInput" class="text-center mb-6">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Enter the authentication code provided by your authenticator application.</p>
        </div>

        <div x-show="showRecoveryInput" class="text-center mb-6">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Please confirm access to your account by entering one of your emergency recovery codes.</p>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-6">
            @csrf

            <div x-show="!showRecoveryInput">
                <div class="flex items-center justify-center mb-4">
                    <x-input-otp
                        name="code"
                        digits="6"
                        autocomplete="one-time-code"
                        x-model="code"
                    />
                </div>

                @error('code')
                    <p class="text-sm text-red-600 dark:text-red-400 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="showRecoveryInput">
                <flux:field>
                    <flux:input
                        type="text"
                        name="recovery_code"
                        x-ref="recovery_code"
                        x-bind:required="showRecoveryInput"
                        autocomplete="one-time-code"
                        x-model="recovery_code"
                        placeholder="Enter recovery code"
                    />
                </flux:field>

                @error('recovery_code')
                    <p class="text-sm text-red-600 dark:text-red-400 text-center mt-2">{{ $message }}</p>
                @enderror
            </div>

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
            >
                Continue
            </flux:button>

            <div class="text-center text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">or you can</span>
                <flux:link @click="toggleInput()" class="font-semibold ml-1 cursor-pointer">
                    <span x-show="!showRecoveryInput">login using a recovery code</span>
                    <span x-show="showRecoveryInput">login using an authentication code</span>
                </flux:link>
            </div>
        </form>
    </div>
</x-layouts.auth>
