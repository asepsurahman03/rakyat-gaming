<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-gradient-to-bl from-[#7E0000] to-[#470000] font-sans antialiased">
    <!-- Navbar -->
    <div class="bg-black border-b border-zinc-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Top Bar (Logo, Search, Language) -->
            <div class="flex w-full items-center justify-between gap-2 md:gap-4 py-2 md:py-0">
                <a href="/" wire:navigate class="flex-shrink-0">
                    <img src="{{ asset('images/logo-rkgame-animation.gif') }}" alt="logo" class="h-12 md:h-16 w-auto">
                </a>

                <flux:input class="hidden md:block w-full" icon="magnifying-glass"
                    placeholder="{{ __('messages.search_orders') }}" />

                <div class="flex items-center gap-2">
                    <!-- Language Selector with Flags -->
                    <div x-data="{ open: false, selected: '{{ app()->getLocale() }}' }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg w-20 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:border-yellow-400 transition-colors">
                            <template x-if="selected === 'en'">
                                <span class="flex items-center gap-2">
                                    <img src="{{ asset('images/eng.png') }}" alt="English" class="w-5 h-5 rounded-sm" />
                                    <span class="hidden md:inline text-sm font-medium">Eng</span>
                                </span>
                            </template>
                            <template x-if="selected === 'id'">
                                <span class="flex items-center gap-2">
                                    <img src="{{ asset('images/ind.png') }}" alt="Indonesia" class="w-5 h-5 rounded-sm" />
                                    <span class="hidden md:inline text-sm font-medium">Ind</span>
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
                            <a href="/lang/en"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                                :class="{ 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400': selected === 'en' }">
                                <img src="{{ asset('images/eng.png') }}" alt="English" class="w-5 h-5 rounded-sm" />
                                <div>
                                    <div class="font-medium text-sm">English</div>
                                    <div class="text-xs text-zinc-500">United Kingdom</div>
                                </div>
                            </a>
                            <a href="/lang/id"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                            :class="{ 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400': selected === 'id' }">
                            <img src="{{ asset('images/ind.png') }}" alt="Indonesia" class="w-5 h-5 rounded-sm" />
                                <div>
                                    <div class="font-medium text-sm">Indonesia</div>
                                    <div class="text-xs text-zinc-500">Bahasa Indonesia</div>
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
                <flux:input class="w-full text-sm" icon="magnifying-glass"
                    placeholder="{{ __('messages.search_orders') }}" />
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
                                        <div class="font-medium text-sm">Magic Whele</div>
                                        <div class="text-xs text-zinc-500 mt-0.5">
                                            Menghitung total diamond untuk skin legends
                                        </div>
                                    </div>
                                </a>
                                <a href="{{ route('winrate') }}" wire:navigate
                                    class="flex items-start gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <flux:icon.calculator class="size-4 mt-0.5" />
                                    <div>
                                        <div class="font-medium text-sm">Win Rate</div>
                                        <div class="text-xs text-zinc-500 mt-0.5">
                                            Menghitung persentase kemenangan
                                        </div>
                                    </div>
                                </a>
                                <a href="{{ route('zodiak') }}" wire:navigate
                                    class="flex items-start gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <flux:icon.calculator class="size-4 mt-0.5" />
                                    <div>
                                        <div class="font-medium text-sm">Zodiak</div>
                                        <div class="text-xs text-zinc-500 mt-0.5">
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

    @fluxScripts()
    @persist('toast')
        <flux:toast />
    @endpersist
</body>

</html>
