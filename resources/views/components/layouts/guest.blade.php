<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Warung Gaming') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white dark:bg-zinc-900 font-sans antialiased">
    <!-- Navbar -->
    <div class="bg-black border-b border-zinc-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex w-full items-center justify-between gap-4">
                <a href="/" wire:navigate>
                    <img src="{{ asset('images/logo-rkgame-animation.gif') }}" alt="logo" class="h-16 w-auto">
                </a>
                <flux:input class="w-full" icon="magnifying-glass" placeholder="{{ __('messages.search_orders') }}" />
                <select class="w-32 border-gray-50 rounded-lg px-2 py-2.5 bg-gray-50 focus:border-yellow-400"
                    variant="listbox" onchange="window.location.href='/lang/'+this.value">
                    <option value="id" {{ app()->getLocale() === 'id' ? 'selected' : '' }}><span></span> Ind
                    </option>
                    <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>Eng</option>
                </select>
            </div>
            <div class="flex gap-4 items-center justify-between py-3 text-xs">
                <div class="flex gap-4 md:gap-6 items-center">
                    <a class="max-lg:hidden inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->is('/') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}" href="/" wire:navigate>
                        <flux:icon.handbag class="size-4" /> <span>{{ __('messages.home') }}</span>
                    </a>

                    <a class="max-lg:hidden inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('cek-transaksi') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}" href="{{ route('cek-transaksi') }}" wire:navigate>
                        <flux:icon.file-search class="size-4" /> <span>{{ __('messages.check_transaction') }}</span>
                    </a>

                    <a class="max-lg:hidden inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('leaderboard') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}" href="{{ route('leaderboard') }}" wire:navigate>
                        <flux:icon.chart-no-axes-column class="size-4" /> <span>{{ __('messages.leaderboard') }}</span>
                    </a>

                    <a class="max-lg:hidden inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('guest.posts.index') ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}" href="{{ route('guest.posts.index') }}" wire:navigate>
                        <flux:icon.newspaper class="size-4" /> <span>{{ __('messages.artikel') }}</span>
                    </a>

                    <flux:dropdown>
                        <button class="inline-flex items-center gap-0.5 pb-1 border-b-2 border-transparent transition-colors duration-150 {{ (request()->routeIs('magicwhele') || request()->routeIs('winrate') || request()->routeIs('zodiak')) ? 'text-yellow-400 border-yellow-400' : 'text-gray-300 hover:text-yellow-400 hover:border-yellow-400' }}">
                            <div class="flex items-center">
                                <flux:icon.calculator class="size-4" />
                                <span>{{ __('messages.calculator') }}</span>
                            </div>
                        </button>

                        <flux:menu>
                            <div class="w-65">
                                <div class="rounded-md group">
                                    <a href="{{ route('magicwhele') }}" wire:navigate class="inline-flex items-start gap-1 p-2 w-full pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('magicwhele') ? 'text-yellow-600 border-yellow-600' : 'text-gray-700 hover:text-yellow-600 hover:border-yellow-600' }}">
                                        <flux:icon.calculator class="size-4" />
                                        <div>
                                            <h1 class="group-hover:text-yellow-600">Magic Whele</h1>
                                            <flux:text class="text-xs">
                                                Digunnakan untuk menghitung total maksimal diamond yang dibutukan untuk mendapatkan skin legends.
                                            </flux:text>
                                        </div>
                                    </a>
                                </div>
                                <div class="rounded-md group">
                                    <a href="{{ route('winrate') }}" wire:navigate class="inline-flex items-start gap-1 p-2 w-full pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('winrate') ? 'text-yellow-600 border-yellow-600' : 'text-gray-700 hover:text-yellow-600 hover:border-yellow-600' }}">
                                        <flux:icon.calculator class="size-4" />
                                        <div>
                                            <h1 class="group-hover:text-yellow-600">Win Rate</h1>
                                            <flux:text class="text-xs">
                                                Digunakan untuk menghitung persentase kemenangan berdasarkan jumlah kemenangan dan total permainan.
                                            </flux:text>
                                        </div>
                                    </a>
                                </div>
                                <div class="rounded-md group">
                                    <a href="{{ route('zodiak') }}" wire:navigate class="inline-flex items-start gap-1 p-2 w-full pb-1 border-b-2 border-transparent transition-colors duration-150 {{ request()->routeIs('zodiak') ? 'text-yellow-600 border-yellow-600' : 'text-gray-700 hover:text-yellow-600 hover:border-yellow-600' }}">
                                        <flux:icon.calculator class="size-4" />
                                        <div>
                                            <h1 class="group-hover:text-yellow-600">Zodiak</h1>
                                            <flux:text class="text-xs">
                                                Digunnakan untuk menghitung total maksimal diamond yang dibutukan untuk mendapatkan skin zodiacs.
                                            </flux:text>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </flux:menu>
                    </flux:dropdown>

                </div>

                <div class="flex gap-2 md:gap-4 items-center">
                    <!-- Authentication Links -->
                    @if (Route::has('login'))
                        @auth
                            @if (auth()->user()->role === 'admin')
                                <a class="flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400" href="{{ route('dashboard') }}" wire:navigate>
                                    <flux:icon.squares-2x2 class="size-4" />
                                    {{ __('messages.dashboard') }}
                                </a>
                            @else
                                <a class="flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400" href="{{ route('user.dashboard') }}" wire:navigate>
                                    <flux:icon.squares-2x2 class="size-4" />
                                    {{ __('messages.dashboard') }}
                                </a>
                            @endif
                        @else
                            <a class="flex text-gray-300 items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:text-yellow-400 hover:border-yellow-400" href="{{ route('login') }}" wire:navigate>
                                <flux:icon.arrow-right-end-on-rectangle class="size-4" />
                                {{ __('messages.login') }}
                            </a>

                            @if (Route::has('register'))
                                <a class="inline-flex text-white bg-yellow-400 px-3 py-2 rounded-md font-medium items-center gap-1 pb-1 border-b-2 border-transparent transition-colors duration-150 hover:bg-yellow-500" href="{{ route('register') }}" wire:navigate>
                                    {{ __('messages.register') }}
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    @livewire('guest.footer')

    @persist('toast')
        <flux:toast />
    @endpersist

    @stack('scripts')
</body>

</html>
