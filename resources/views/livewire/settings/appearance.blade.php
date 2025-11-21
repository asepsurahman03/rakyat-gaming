<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.sidebar'), Title('Appearance Settings')]
class extends Component {
    public $theme = 'dark';

    public function mount()
    {
        // Load from localStorage via JavaScript, fallback to session
        $this->theme = session('theme', 'dark');
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;

        // Save to session
        session()->put('theme', $theme);
        session()->save();

        // Dispatch event to update localStorage
        $this->dispatch('theme-changed', theme: $theme);
    }
}; ?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a>Settings</a></li>
            <li>Appearance</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <span class="badge badge-secondary badge-lg">SETTINGS</span>
            Appearance
        </h1>
        <p class="text-base-content/60 mt-2">Customize the theme and appearance of the application</p>
    </div>

    <div class="card bg-base-100 shadow-xl max-w-2xl">
        <div class="card-body">
            <h2 class="card-title mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                Theme Selection
            </h2>
            <div class="divider"></div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Choose your theme</span>
                </label>

                <div class="join join-vertical w-full gap-3">
                    <!-- Light Theme -->
                    <label class="label cursor-pointer border rounded-lg p-4 hover:bg-base-200 transition-colors {{ $theme === 'light' ? 'border-primary bg-primary/5' : 'border-base-300' }}">
                        <div class="flex items-center gap-4">
                            <input
                                type="radio"
                                name="theme"
                                value="light"
                                wire:click="setTheme('light')"
                                class="radio radio-primary"
                                {{ $theme === 'light' ? 'checked' : '' }}
                            />
                            <div class="flex items-center gap-3">
                                <div class="bg-base-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold">Light Mode</div>
                                    <div class="text-sm text-base-content/60">Bright theme for daytime use</div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Dark Theme -->
                    <label class="label cursor-pointer border rounded-lg p-4 hover:bg-base-200 transition-colors {{ $theme === 'dark' ? 'border-primary bg-primary/5' : 'border-base-300' }}">
                        <div class="flex items-center gap-4">
                            <input
                                type="radio"
                                name="theme"
                                value="dark"
                                wire:click="setTheme('dark')"
                                class="radio radio-primary"
                                {{ $theme === 'dark' ? 'checked' : '' }}
                            />
                            <div class="flex items-center gap-3">
                                <div class="bg-base-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold">Dark Mode</div>
                                    <div class="text-sm text-base-content/60">Comfortable theme for low light</div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- System Theme -->
                    <label class="label cursor-pointer border rounded-lg p-4 hover:bg-base-200 transition-colors {{ $theme === 'system' ? 'border-primary bg-primary/5' : 'border-base-300' }}">
                        <div class="flex items-center gap-4">
                            <input
                                type="radio"
                                name="theme"
                                value="system"
                                wire:click="setTheme('system')"
                                class="radio radio-primary"
                                {{ $theme === 'system' ? 'checked' : '' }}
                            />
                            <div class="flex items-center gap-3">
                                <div class="bg-base-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold">System Default</div>
                                    <div class="text-sm text-base-content/60">Follows your system preference</div>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="alert alert-info mt-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Your theme preference is saved automatically and will persist across sessions.</span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('theme-changed', (event) => {
            const theme = event.theme;

            // Save to localStorage
            localStorage.setItem('theme', theme);

            // Apply theme
            if (theme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
            } else {
                document.documentElement.setAttribute('data-theme', theme);
            }
        });
    });
</script>
