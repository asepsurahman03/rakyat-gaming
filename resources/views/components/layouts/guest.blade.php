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
                <select class="w-32 border-gray-50 rounded-lg px-2 py-2.5 bg-gray-50 focus:border-yellow-400" variant="listbox" onchange="window.location.href='/lang/'+this.value">
                    <option value="id" {{ app()->getLocale() === 'id' ? 'selected' : '' }}><span></span> Ind</option>
                    <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>Eng</option>
                </select>
            </div>
            <div class="flex gap-4 items-center">


                <a class="max-lg:hidden" href="/" wire:navigate>{{ __('messages.home') }}</a>
                <a class="max-lg:hidden" href="{{ route('cek-transaksi') }}" wire:navigate>{{ __('messages.check_transaction') }}</a>

                @if (Route::has('login'))
                    @auth
                        <flux:navbar.item icon="squares-2x2" href="{{ url('/dashboard') }}" wire:navigate>{{ __('messages.dashboard') }}
                        </flux:navbar.item>
                    @else
                        <flux:navbar.item icon="arrow-right-end-on-rectangle" href="{{ route('login') }}" wire:navigate>
                            {{ __('messages.login') }}</flux:navbar.item>

                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" wire:navigate variant="primary">{{ __('messages.register') }}
                            </flux:button>
                        @endif
                    @endauth
                @endif
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
</body>

</html>
