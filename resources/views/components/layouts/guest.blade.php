<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-[#6F1010] font-sans antialiased dark">
    <!-- Navbar -->
    <div class="bg-black border-b border-zinc-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Top Bar (Logo, Search, Language) -->
            <div class="flex w-full items-center justify-between gap-2 md:gap-4 py-2 md:py-0">
                <a href="/" wire:navigate class="flex-shrink-0">
                    <img src="{{ asset('images/logo-rkgame-animation.gif') }}" alt="logo" class="h-12 md:h-16 w-auto">
                </a>

                <!-- Game Search with Autocomplete -->
                <div x-data="{
                    searchQuery: '',
                    showResults: false,
                    searchResults: [],
                    loading: false,
                    async searchGames() {
                        if (this.searchQuery.length < 2) {
                            this.searchResults = [];
                            this.showResults = false;
                            return;
                        }

                        this.loading = true;
                        this.showResults = true;
                        try {
                            const response = await fetch(`/api/search-games?q=${encodeURIComponent(this.searchQuery)}`);
                            const data = await response.json();
                            this.searchResults = data.games || [];
                            console.log('Search results:', this.searchResults);
                        } catch (error) {
                            console.error('Search error:', error);
                            this.searchResults = [];
                        } finally {
                            this.loading = false;
                        }
                    },
                    goToGame(slug) {
                        this.showResults = false;
                        window.location.href = `/game/${slug}`;
                    }
                }"
                @click.away="showResults = false"
                class="hidden md:block w-full relative">
                    <div class="relative">
                        <flux:input
                            x-model="searchQuery"
                            @input.debounce.300ms="searchGames()"
                            @focus="if (searchQuery.length >= 2) { searchGames(); }"
                            icon="magnifying-glass"
                            placeholder="{{ __('messages.search_orders') }}"
                            class="w-full" />

                        <!-- Loading indicator -->
                        <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="animate-spin h-4 w-4 text-zinc-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div x-show="showResults && searchResults.length > 0"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-xl overflow-hidden z-[100] max-h-96 overflow-y-auto">
                        <template x-for="game in searchResults" :key="game.id">
                            <button
                                type="button"
                                @click="goToGame(game.slug)"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-left border-b border-zinc-200 dark:border-zinc-700 last:border-b-0">
                                <img :src="game.image_url" :alt="game.name" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100" x-text="game.name"></div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="game.category"></div>
                                </div>
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </template>
                    </div>

                    <!-- No Results -->
                    <div x-show="showResults && searchResults.length === 0 && searchQuery.length >= 2 && !loading"
                        x-cloak
                        x-transition
                        class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-xl p-4 z-[100]">
                        <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                            <svg class="w-12 h-12 mx-auto mb-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Tidak ada game ditemukan untuk "<span x-text="searchQuery"></span>"
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Language Selector with Flags -->
                    <div x-data="{ open: false, selected: '{{ app()->getLocale() }}' }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg w-20 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:border-yellow-400 transition-colors">
                            <template x-if="selected === 'id'">
                                <span class="flex items-center gap-2">
                                    <img src="{{ asset('images/ind.png') }}" alt="Indonesia" class="w-5 h-5 rounded-sm" />
                                    <span class="hidden md:inline text-sm font-medium text-amber-50">Ind</span>
                                </span>
                            </template>
                            <template x-if="selected === 'en'">
                                <span class="flex items-center gap-2">
                                    <img src="{{ asset('images/eng.png') }}" alt="English" class="w-5 h-5 rounded-sm" />
                                    <span class="hidden md:inline text-sm font-medium text-amber-50">Eng</span>
                                </span>
                            </template>
                            <svg class="w-4 h-4 ml-1" :class="{ 'rotate-180': open }" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg overflow-hidden z-50">
                            <a href="/lang/id"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                            :class="{ 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400': selected === 'id' }">
                            <img src="{{ asset('images/ind.png') }}" alt="Indonesia" class="w-5 h-5 rounded-sm" />
                                <div>
                                    <div class="font-medium text-sm text-zinc-200">Indonesia</div>
                                    <div class="text-xs text-zinc-400">Bahasa Indonesia</div>
                                </div>
                            </a>
                            <a href="/lang/en"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                :class="{ 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400': selected === 'en' }">
                                <img src="{{ asset('images/eng.png') }}" alt="English" class="w-5 h-5 rounded-sm" />
                                <div>
                                    <div class="font-medium text-sm text-zinc-200">English</div>
                                    <div class="text-xs text-zinc-400">United Kingdom</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button x-data x-on:click="$dispatch('toggle-mobile-menu')"
                        class="lg:hidden text-gray-300 hover:text-yellow-400 p-2">
                        <flux:icon.bars-3 class="size-6" />
                    </button>
                </div>
            </div>

            <!-- Mobile Search (visible on small screens) -->
            <div class="md:hidden pb-2">
                <div x-data="{
                    searchQuery: '',
                    showResults: false,
                    searchResults: [],
                    loading: false,
                    async searchGames() {
                        if (this.searchQuery.length < 2) {
                            this.searchResults = [];
                            this.showResults = false;
                            return;
                        }

                        this.loading = true;
                        this.showResults = true;
                        try {
                            const response = await fetch(`/api/search-games?q=${encodeURIComponent(this.searchQuery)}`);
                            const data = await response.json();
                            this.searchResults = data.games || [];
                        } catch (error) {
                            console.error('Search error:', error);
                            this.searchResults = [];
                        } finally {
                            this.loading = false;
                        }
                    },
                    goToGame(slug) {
                        this.showResults = false;
                        window.location.href = `/game/${slug}`;
                    }
                }"
                @click.away="showResults = false"
                class="relative">
                    <div class="relative">
                        <flux:input
                            x-model="searchQuery"
                            @input.debounce.300ms="searchGames()"
                            @focus="if (searchQuery.length >= 2) { searchGames(); }"
                            icon="magnifying-glass"
                            placeholder="{{ __('messages.search_orders') }}"
                            class="w-full text-sm" />

                        <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="animate-spin h-4 w-4 text-zinc-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <div x-show="showResults && searchResults.length > 0"
                        x-cloak
                        x-transition
                        class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-xl overflow-hidden z-[100] max-h-96 overflow-y-auto">
                        <template x-for="game in searchResults" :key="game.id">
                            <button
                                type="button"
                                @click="goToGame(game.slug)"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-left border-b border-zinc-200 dark:border-zinc-700 last:border-b-0">
                                <img :src="game.image_url" :alt="game.name" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100" x-text="game.name"></div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="game.category"></div>
                                </div>
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </template>
                    </div>

                    <div x-show="showResults && searchResults.length === 0 && searchQuery.length >= 2 && !loading"
                        x-cloak
                        x-transition
                        class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-xl p-4 z-[100]">
                        <div class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                            Tidak ada game ditemukan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex gap-4 items-center justify-between py-3 text-xs xl:text-sm">
                <div class="flex gap-3 xl:gap-6 items-center flex-wrap">
                    <a class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->is('/') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}"
                        href="/" wire:navigate>
                        <flux:icon.handbag class="size-4" /> <span>{{ __('messages.home') }}</span>
                    </a>

                    <a class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('cek-transaksi') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}"
                        href="{{ route('cek-transaksi') }}" wire:navigate>
                        <flux:icon.file-search class="size-4" /> <span>{{ __('messages.check_transaction') }}</span>
                    </a>

                    <a class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('leaderboard') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}"
                        href="{{ route('leaderboard') }}" wire:navigate>
                        <flux:icon.chart-no-axes-column class="size-4" /> <span>{{ __('messages.leaderboard') }}</span>
                    </a>

                    <a class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('guest.posts.index') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}"
                        href="{{ route('guest.posts.index') }}" wire:navigate>
                        <flux:icon.newspaper class="size-4" /> <span>{{ __('messages.artikel') }}</span>
                    </a>

                    <flux:dropdown>
                        <button
                            class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('magicwhele') || request()->routeIs('winrate') || request()->routeIs('zodiak') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}">
                            <flux:icon.calculator class="size-4" />
                            <span>{{ __('messages.calculator') }}</span>
                        </button>

                        <flux:menu>
                            <div class="w-65">
                                <a href="{{ route('magicwhele') }}" wire:navigate
                                    class="flex items-start gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <flux:icon.calculator class="size-4 mt-0.5" />
                                    <div>
                                        <div class="font-medium text-sm text-zinc-200">Magic Whele</div>
                                        <div class="text-xs text-zinc-400 mt-0.5">
                                            Menghitung total diamond untuk skin legends
                                        </div>
                                    </div>
                                </a>
                                <a href="{{ route('winrate') }}" wire:navigate
                                    class="flex items-start gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <flux:icon.calculator class="size-4 mt-0.5" />
                                    <div>
                                        <div class="font-medium text-sm text-zinc-200">Win Rate</div>
                                        <div class="text-xs text-zinc-400 mt-0.5">
                                            Menghitung persentase kemenangan
                                        </div>
                                    </div>
                                </a>
                                <a href="{{ route('zodiak') }}" wire:navigate
                                    class="flex items-start gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <flux:icon.calculator class="size-4 mt-0.5" />
                                    <div>
                                        <div class="font-medium text-sm text-zinc-200">Zodiak</div>
                                        <div class="text-xs text-zinc-400 mt-0.5">
                                            Menghitung total diamond untuk skin zodiac
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="flex gap-2 xl:gap-4 items-center flex-shrink-0">
                    @if (Route::has('login'))
                        @auth
                            @if (auth()->user()->role === 'admin')
                                <a class="inline-flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400 whitespace-nowrap"
                                    href="{{ route('dashboard') }}" wire:navigate>
                                    <flux:icon.squares-2x2 class="size-4" />
                                    <span class="hidden xl:inline">{{ __('messages.dashboard') }}</span>
                                </a>
                            @else
                                <a class="inline-flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400 whitespace-nowrap"
                                    href="{{ route('user.dashboard') }}" wire:navigate>
                                    <flux:icon.squares-2x2 class="size-4" />
                                    <span class="hidden xl:inline">{{ __('messages.dashboard') }}</span>
                                </a>
                            @endif
                        @else
                            <a class="inline-flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400"
                                href="{{ route('login') }}" wire:navigate>
                                <flux:icon.arrow-right-end-on-rectangle class="size-4" />
                                <span>{{ __('messages.login') }}</span>
                            </a>

                            @if (Route::has('register'))
                                <a class="inline-flex text-white bg-yellow-400 px-3 py-2 rounded-md font-medium items-center transition-colors duration-150 hover:bg-yellow-500 whitespace-nowrap"
                                    href="{{ route('register') }}" wire:navigate>
                                    {{ __('messages.register') }}
                                </a>
                            @endif
                        @endguest
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Sidebar -->
    <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 lg:hidden" style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>

        <!-- Sidebar -->
        <div x-show="open" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 w-64 bg-black border-r border-zinc-800 overflow-y-auto">

            <div class="p-4">
                <!-- Close Button -->
                <button x-on:click="open = false" class="absolute top-4 right-4 text-gray-300 hover:text-yellow-400">
                    <flux:icon.x-mark class="size-6" />
                </button>

                <!-- Logo -->
                <a href="/" wire:navigate x-on:click="open = false" class="block mb-6">
                    <img src="{{ asset('images/logo-rkgame-animation.gif') }}" alt="logo" class="h-16 w-auto">
                </a>

                <!-- Navigation Links -->
                <nav class="space-y-2">
                    <a href="/" wire:navigate x-on:click="open = false"
                        class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->is('/') ? 'text-yellow-400 bg-zinc-900' : '' }}">
                        <flux:icon.handbag class="size-5" />
                        <span>{{ __('messages.home') }}</span>
                    </a>

                    <a href="{{ route('cek-transaksi') }}" wire:navigate x-on:click="open = false"
                        class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('cek-transaksi') ? 'text-yellow-400 bg-zinc-900' : '' }}">
                        <flux:icon.file-search class="size-5" />
                        <span>{{ __('messages.check_transaction') }}</span>
                    </a>

                    <a href="{{ route('leaderboard') }}" wire:navigate x-on:click="open = false"
                        class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('leaderboard') ? 'text-yellow-400 bg-zinc-900' : '' }}">
                        <flux:icon.chart-no-axes-column class="size-5" />
                        <span>{{ __('messages.leaderboard') }}</span>
                    </a>

                    <a href="{{ route('guest.posts.index') }}" wire:navigate x-on:click="open = false"
                        class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('guest.posts.index') ? 'text-yellow-400 bg-zinc-900' : '' }}">
                        <flux:icon.newspaper class="size-5" />
                        <span>{{ __('messages.artikel') }}</span>
                    </a>

                    <!-- Calculator Submenu -->
                    <div x-data="{ calcOpen: false }" class="space-y-1">
                        <button x-on:click="calcOpen = !calcOpen"
                            class="flex items-center justify-between w-full gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('magicwhele') || request()->routeIs('winrate') || request()->routeIs('zodiak') ? 'text-yellow-400 bg-zinc-900' : '' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon.calculator class="size-5" />
                                <span>{{ __('messages.calculator') }}</span>
                            </div>
                            <flux:icon.chevron-down class="size-4 transition-transform"
                                ::class="{ 'rotate-180': calcOpen }" />
                        </button>

                        <div x-show="calcOpen" x-collapse class="pl-11 space-y-1">
                            <a href="{{ route('magicwhele') }}" wire:navigate x-on:click="open = false"
                                class="block px-3 py-2 text-sm text-gray-400 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('magicwhele') ? 'text-yellow-400' : '' }}">
                                Magic Whele
                            </a>
                            <a href="{{ route('winrate') }}" wire:navigate x-on:click="open = false"
                                class="block px-3 py-2 text-sm text-gray-400 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('winrate') ? 'text-yellow-400' : '' }}">
                                Win Rate
                            </a>
                            <a href="{{ route('zodiak') }}" wire:navigate x-on:click="open = false"
                                class="block px-3 py-2 text-sm text-gray-400 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors {{ request()->routeIs('zodiak') ? 'text-yellow-400' : '' }}">
                                Zodiak
                            </a>
                        </div>
                    </div>

                    <div class="border-t border-zinc-800 my-4"></div>

                    <!-- Auth Links -->
                    @if (Route::has('login'))
                        @auth
                            @if (auth()->user()->role === 'admin')
                                <a href="{{ route('dashboard') }}" wire:navigate x-on:click="open = false"
                                    class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors">
                                    <flux:icon.squares-2x2 class="size-5" />
                                    <span>{{ __('messages.dashboard') }}</span>
                                </a>
                            @else
                                <a href="{{ route('user.dashboard') }}" wire:navigate x-on:click="open = false"
                                    class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors">
                                    <flux:icon.squares-2x2 class="size-5" />
                                    <span>{{ __('messages.dashboard') }}</span>
                                </a>
                                <a href="{{ route('user.transactions') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors"><flux:icon.file-clock
                                        class="size-5" />
                                    <span>{{ __('messages.history_transaction') }}</span></a>
                                <a href="{{ route('user.profile') }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors"><flux:icon.circle-user-round
                                        class="size-5" />
                                    <span>{{ __('messages.profile') }}</span></a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50">
                                    <flux:icon name="arrow-right-start-on-rectangle" class="inline-block mr-2" />
                                    {{ __('messages.logout') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" wire:navigate x-on:click="open = false"
                                class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:text-yellow-400 hover:bg-zinc-900 rounded-lg transition-colors">
                                <flux:icon.arrow-right-end-on-rectangle class="size-5" />
                                <span>{{ __('messages.login') }}</span>
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" wire:navigate x-on:click="open = false"
                                    class="flex items-center justify-center gap-2 px-3 py-2 text-white bg-yellow-400 hover:bg-yellow-500 rounded-lg transition-colors font-medium">
                                    {{ __('messages.register') }}
                                </a>
                            @endif
                        @endguest
                    @endif
                </nav>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    @livewire('guest.footer')

    <style>
        @keyframes csMarqueeAnimation {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .animate-cs-marquee {
            display: inline-flex;
            white-space: nowrap;
            animation: csMarqueeAnimation 16s linear infinite;
        }
        .animate-cs-marquee:hover {
            animation-play-state: paused;
        }
    </style>

    <!-- Floating Chat Menu & Running Text Banner -->
    <div x-data="{ menuOpen: false }" class="fixed bottom-4 sm:bottom-6 right-3 sm:right-6 z-50 flex items-center gap-2 sm:gap-3 max-w-[calc(100vw-1.5rem)]">

        <!-- Running Text Banner (Kata-kata Berjalan) -->
        <a href="https://wa.me/6285351221602?text={{ urlencode('Halo Admin, saya tertarik dengan jasa pembuatan website top up game') }}"
           target="_blank"
           title="WhatsApp: Jasa Pembuatan Website Top Up Game (085351221602)"
           class="group flex items-center bg-zinc-950/90 hover:bg-zinc-900 border border-yellow-400/50 hover:border-yellow-400 text-white rounded-full py-2 px-3 sm:py-2.5 sm:px-4 shadow-2xl backdrop-blur-md transition-all duration-300 w-48 xs:w-56 sm:w-72 md:w-84 overflow-hidden flex-shrink cursor-pointer">

            <!-- WhatsApp Badge Icon -->
            <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white shadow-md mr-2 animate-pulse">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
            </div>

            <!-- Marquee Container -->
            <div class="relative overflow-hidden w-full select-none" style="mask-image: linear-gradient(to right, transparent, black 8px, black calc(100% - 8px), transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 8px, black calc(100% - 8px), transparent);">
                <div class="animate-cs-marquee text-xs sm:text-sm font-medium whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 pr-6">
                        <span class="text-yellow-400 font-semibold">jasa pembuatan website top up game</span>
                        <span class="text-zinc-500">•</span>
                        <span class="text-zinc-200">wa admin <span class="text-green-400 font-bold underline decoration-green-400/60">085351221602</span></span>
                        <span class="text-yellow-400 text-xs ml-1">✨</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 pr-6">
                        <span class="text-yellow-400 font-semibold">jasa pembuatan website top up game</span>
                        <span class="text-zinc-500">•</span>
                        <span class="text-zinc-200">wa admin <span class="text-green-400 font-bold underline decoration-green-400/60">085351221602</span></span>
                        <span class="text-yellow-400 text-xs ml-1">✨</span>
                    </span>
                </div>
            </div>
        </a>

        <!-- Main CS Button & Dropdown Options -->
        <div class="relative flex-shrink-0">
            <!-- Dropdown Options -->
            <div x-show="menuOpen"
                 x-transition:enter="transition ease-out duration-200 transform"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150 transform"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 @click.away="menuOpen = false"
                 class="absolute bottom-20 right-0 space-y-2">

                <!-- WhatsApp Button -->
                <a href="https://wa.me/+62{{ ltrim(preg_replace('/[^0-9]/', '', env('CONTACT_WHATSAPP', '85351221602')), '0') }}?text=Halo,%20saya%20ingin%20bertanya%20tentang%20produk%20Anda"
                   target="_blank"
                   class="flex items-center gap-3 bg-green-500 hover:bg-green-600 text-white rounded-xl py-3 px-5 shadow-lg hover:shadow-xl transition-all duration-300 group min-w-[180px]">
                    <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    <span class="font-semibold text-sm">WhatsApp</span>
                </a>

                <!-- Live Chat Button -->
                <button @click="menuOpen = false; Livewire.dispatch('toggle-chat')"
                        class="flex items-center gap-3 bg-red-700 hover:bg-red-800 text-white rounded-xl py-3 px-5 shadow-lg hover:shadow-xl transition-all duration-300 group w-full min-w-[180px]">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <span class="font-semibold text-sm">Live Chat</span>
                </button>
            </div>

            <!-- Main Toggle Button -->
            <button @click="menuOpen = !menuOpen"
                    class="bg-yellow-400 hover:bg-yellow-500 text-white rounded-full p-4 shadow-xl hover:shadow-2xl transition-all duration-300 group relative">
                <svg class="w-7 h-7 transition-transform duration-300"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center animate-pulse shadow-lg">
                    2
                </span>
            </button>
        </div>
    </div>

    <!-- Live Chat Widget (Hidden, controlled by chat-widget component) -->
    @livewire('guest.chat-widget', key('chat-widget'))

    @fluxScripts()
    @persist('toast')
        <flux:toast />
    @endpersist
</body>

</html>
