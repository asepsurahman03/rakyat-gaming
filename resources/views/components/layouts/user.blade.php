<x-layouts.guest>
    <div class="max-w-7xl mx-auto px-2 sm:px-4">
        <div class="flex w-full min-h-screen">
            <flux:sidebar sticky stashable class="w-64 p-4">
                <nav class="space-y-1" aria-label="Sidebar">
                    <a href="{{ route('user.dashboard') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.dashboard') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        {{ __('messages.dashboard') }}
                    </a>

                    <a href="{{ route('user.transactions') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.transactions*') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        {{ __('messages.history_transaction') }}
                    </a>

                    <a href="{{ route('user.profile') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('user.profile') ? 'bg-yellow-400 text-zinc-50' : 'text-zinc-200 hover:text-gray-600 hover:bg-gray-50' }}" wire:navigate>
                        {{ __('messages.profile') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50">
                                <flux:icon name="arrow-right-start-on-rectangle" class="inline-block mr-2" />
                            {{ __('messages.logout') }}
                        </button>
                    </form>
                </nav>
            </flux:sidebar>

            <div class="flex-1 p-4 sm:p-6 pb-20 lg:pb-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.guest>
