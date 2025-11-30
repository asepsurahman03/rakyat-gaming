<x-layouts.guest>
    <div class="max-w-7xl mx-auto">
        <div class="flex w-full min-h-screen">
            <div class="w-64 p-4">
                <nav class="space-y-1" aria-label="Sidebar">
                    <a href="{{ route('user.dashboard') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.dashboard') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        Dashboard
                    </a>

                    <a href="{{ route('user.transactions') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.transactions*') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        Riwayat Transaksi
                    </a>

                    <a href="{{ route('user.profile') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.profile') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        Profil
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50">
                                <flux:icon name="arrow-right-start-on-rectangle" class="inline-block mr-2" />
                            Logout
                        </button>
                    </form>
                </nav>
            </div>
            <div class="flex-1 p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.guest>
