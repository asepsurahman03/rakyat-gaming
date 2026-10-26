<x-layouts.auth title="Welcome Back!">
    <div class="text-center mb-6">
        <p class="text-sm text-zinc-100">Sign in to continue to Rakyat Gaming</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

        <flux:field>
            <label class="text-white">Email Address</label>
            <input class="text-white border focus:border-yellow-400 focus:outline-0 px-2 py-1 rounded-md" type="email" name="email" id="email-input" value="{{ old('email') }}" placeholder="your.email@example.com" required autofocus autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <div class="flex items-center justify-between mb-2">
                <label class="text-white">Password</label>
                @if (Route::has('password.request'))
                    <flux:link href="{{ route('password.request') }}" wire:navigate class="text-sm">Forgot password?</flux:link>
                @endif
            </div>
            <input class="text-white border focus:border-yellow-400 focus:outline-0 px-2 py-1 rounded-md" type="password" id="password-input" name="password" placeholder="••••••••" required autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:checkbox name="remember" label="Keep me signed in" {{ old('remember') ? 'checked' : '' }} />

        <flux:button type="submit" variant="primary" class="w-full" data-test="login-button">
            Sign In
        </flux:button>
    </form>

    @if (Route::has('register'))
        <div class="divider text-white">or</div>
        <div class="text-center text-sm">
            <span class="text-zinc-100">Don't have an account?</span>
            <flux:link href="{{ route('register') }}" wire:navigate class="font-semibold ml-1">Create Account</flux:link>
        </div>
    @endif

    <div class="text-center text-xs text-zinc-100 mt-4">
        <p>By signing in, you agree to our Terms of Service and Privacy Policy</p>
    </div>

    {{-- Demo Credentials for Promotion --}}
    <div class="mt-6">
        <p class="text-center text-xs text-zinc-400 mb-3 flex items-center gap-2">
            <span class="flex-1 h-px bg-white/10"></span>
            <span>Coba demo akun</span>
            <span class="flex-1 h-px bg-white/10"></span>
        </p>

        <div class="grid grid-cols-2 gap-2">
            {{-- Admin Card --}}
            <button type="button"
                onclick="fillLogin('admin@gmail.com', 'password')"
                class="group relative text-left rounded-xl border border-red-500/20 bg-red-500/5 hover:bg-red-500/15 hover:border-red-400/40 p-3 transition-all duration-200 cursor-pointer">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-red-400 text-xs font-bold">Admin</span>
                </div>
                <div class="space-y-0.5 text-xs">
                    <p class="text-zinc-300 truncate font-mono">admin@gmail.com</p>
                    <p class="text-zinc-500">password: <span class="text-zinc-300">password</span></p>
                </div>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </div>
            </button>

            {{-- User Card --}}
            <button type="button"
                onclick="fillLogin('user@gmail.com', 'password')"
                class="group relative text-left rounded-xl border border-blue-500/20 bg-blue-500/5 hover:bg-blue-500/15 hover:border-blue-400/40 p-3 transition-all duration-200 cursor-pointer">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-blue-400 text-xs font-bold">User</span>
                </div>
                <div class="space-y-0.5 text-xs">
                    <p class="text-zinc-300 truncate font-mono">user@gmail.com</p>
                    <p class="text-zinc-500">password: <span class="text-zinc-300">password</span></p>
                </div>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </div>
            </button>
        </div>

        <p class="text-center text-xs text-zinc-500 mt-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline mr-1 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Klik kartu untuk mengisi form secara otomatis
        </p>
    </div>

    <script>
        function fillLogin(email, password) {
            document.getElementById('email-input').value = email;
            document.getElementById('password-input').value = password;
            // flash highlight
            ['email-input', 'password-input'].forEach(id => {
                const el = document.getElementById(id);
                el.classList.add('border-yellow-400');
                setTimeout(() => el.classList.remove('border-yellow-400'), 1000);
            });
        }
    </script>
</x-layouts.auth>
