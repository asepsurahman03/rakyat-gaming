<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <flux:sidebar sticky stashable class="bg-black dark:black border-r border-zinc-200 dark:border-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <img src="{{ asset('images/logo-rkgame-animation.gif') }}" alt="logo" class="mx-auto my-4">

        <flux:navlist variant="outline">
            <flux:navlist.item href="{{ route('dashboard') }}" wire:navigate :current="request()->routeIs('dashboard')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.home size="xs" /> <span>{{ __('messages.dashboard') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('carousel.index') }}" wire:navigate :current="request()->routeIs('carousel.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.photo size="xs" /> <span>{{ __('messages.carousel') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('admin.games.index') }}" wire:navigate :current="request()->routeIs('admin.games.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.puzzle size="xs" /> <span>{{ __('messages.games') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('admin.products.index') }}" wire:navigate :current="request()->routeIs('admin.products.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.cube size="xs" /> <span>{{ __('messages.products') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('admin.orders.index') }}" wire:navigate :current="request()->routeIs('admin.orders.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.shopping-bag size="xs" /> <span>{{ __('messages.orders') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('admin.supplier-products.index') }}" wire:navigate :current="request()->routeIs('admin.supplier-products.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.inbox-stack size="xs" /> <span>{{ __('messages.supplier_products') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.item href="{{ route('admin.users.index') }}" wire:navigate :current="request()->routeIs('admin.users.*')">
                <div class="text-yellow-400  flex items-center gap-2">
                    <flux:icon.users size="xs" /> <span>{{ __('messages.users') }}</span>
                </div>
            </flux:navlist.item>

            <flux:navlist.group expandable heading="{{ __('messages.configuration') }}" icon="cog">
                <flux:navlist.item href="{{ route('admin.settings.application') }}" wire:navigate :current="request()->routeIs('admin.settings.application')">
                    <div class="text-yellow-400  flex items-center gap-2">
                        <flux:icon.cog size="xs" /> <span>{{ __('messages.application') }}</span>
                    </div>
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.settings.suppliers') }}" wire:navigate :current="request()->routeIs('admin.settings.suppliers')">
                    <div class="text-yellow-400  flex items-center gap-2">
                        <flux:icon.truck size="xs" /> <span>{{ __('messages.suppliers') }}</span>
                    </div>
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.settings.payment-gateways') }}" wire:navigate :current="request()->routeIs('admin.settings.payment-gateways')">
                    <div class="text-yellow-400  flex items-center gap-2">
                        <flux:icon.credit-card size="xs" /> <span>{{ __('messages.payment_gateways') }}</span>
                    </div>
                </flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <flux:navlist variant="outline">
            @include('livewire.settings.appearance')
            <flux:navlist.item icon="user" href="{{ route('profile.edit') }}" wire:navigate
                :current="request()->routeIs('profile.edit')">Profile</flux:navlist.item>
        </flux:navlist>

        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:profile
                avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=random"
                name="{{ auth()->user()->name }}" />

            <flux:menu>
                <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    {{ __('messages.logout') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" alignEnd>
            <flux:profile
                avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=random" />

            <flux:menu>
                <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">
                    {{ __('messages.logout') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </flux:header>

    <flux:main class="p-6">
        {{ $slot }}
    </flux:main>

    @persist('toast')
        <flux:toast />
    @endpersist
</body>

</html>
