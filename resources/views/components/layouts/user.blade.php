<x-layouts.guest>
    <div class="flex max-w-7xl mx-auto">
        <flux:sidebar sticky stashable class="bg-zinc-50">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
    
            <flux:brand href="{{ route('user.dashboard') }}" logo="{{ asset('storage/logo.png') }}" name="Warung Gaming"
                class="px-2 dark:hidden" />
            <flux:brand href="{{ route('user.dashboard') }}" logo="{{ asset('storage/logo.png') }}" name="Warung Gaming"
                class="px-2 hidden dark:flex" />
    
            <flux:navlist variant="outline">
                <flux:navlist.item icon="home" href="{{ route('user.dashboard') }}"
                    :current="request()->routeIs('user.dashboard')" wire:navigate>
                    Dashboard
                </flux:navlist.item>
    
                <flux:navlist.item icon="document-text" href="{{ route('user.transactions') }}"
                    :current="request()->routeIs('user.transactions')" wire:navigate>
                    Transaksi Saya
                </flux:navlist.item>
            </flux:navlist>
    
            <flux:spacer />
    
            <flux:navlist variant="outline">
                <flux:navlist.item icon="user" href="{{ route('user.profile') }}"
                    :current="request()->routeIs('user.profile')" wire:navigate>
                    {{ auth()->user()->name }}
                </flux:navlist.item>
    
                <flux:navlist.item icon="arrow-right-start-on-rectangle">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full text-left">
                            Logout
                        </button>
                    </form>
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>
    
        <flux:header class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
    
            <flux:spacer />
    
            <flux:profile
                avatar="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" />
        </flux:header>
    
        <flux:main>
            {{ $slot }}
    
            <flux:toast />
        </flux:main>
    </div>
</x-layouts.guest>
