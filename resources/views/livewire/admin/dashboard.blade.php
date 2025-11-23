<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Dashboard')] class extends Component {
    public $totalUsers = 0;
    public $totalRevenue = 0;
    public $totalOrders = 0;
    public $recentActivities = [];

    public function mount()
    {
        $this->loadStats();
        $this->loadRecentActivities();
    }

    public function loadStats()
    {
        $this->totalUsers = User::count();
        $this->totalOrders = Order::count();
        $this->totalRevenue = Order::where('status', 'success')->sum('price');
    }

    public function loadRecentActivities()
    {
        $this->recentActivities = Order::with(['user', 'game', 'product'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function refreshStats()
    {
        $this->loadStats();
        $this->loadRecentActivities();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">
        Dashboard
        <flux:subheading>Welcome back, {{ auth()->user()->name }}! 👋</flux:subheading>
    </flux:heading>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Users</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalUsers) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Registered members</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Revenue</flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($totalRevenue / 1000, 0) }}K</flux:heading>
                    <flux:subheading class="text-xs mt-1">From successful orders</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Orders</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">All time transactions</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <flux:icon.shopping-bag class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Recent Orders</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order Code</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Game</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentActivities as $activity)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-xs">{{ Str::limit($activity->order_code, 8) }}</flux:table.cell>
                            <flux:table.cell>{{ $activity->user->name ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>{{ $activity->game->name ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>
                                @if($activity->status === 'success')
                                    <flux:badge color="green" size="sm">Success</flux:badge>
                                @elseif($activity->status === 'pending')
                                    <flux:badge color="yellow" size="sm">Pending</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">Failed</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500">No recent orders</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <!-- Quick Actions -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:button href="{{ route('carousel.index') }}" wire:navigate variant="outline" icon="photo" class="justify-start">
                    Carousel
                </flux:button>
                <flux:button href="{{ route('carousel.create') }}" wire:navigate variant="outline" icon="plus" class="justify-start">
                    New Carousel
                </flux:button>
                <flux:button href="{{ route('profile.edit') }}" wire:navigate variant="outline" icon="cog" class="justify-start">
                    Settings
                </flux:button>
                <flux:button wire:click="refreshStats" variant="outline" icon="arrow-path" class="justify-start">
                    Refresh Stats
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
