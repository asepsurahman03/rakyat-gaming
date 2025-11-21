<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.sidebar'), Title('Dashboard')]
class extends Component {
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

<div>
    <!-- Hero Section -->
    <div class="hero bg-gradient-to-r from-primary to-secondary rounded-box mb-6 mt-10">
        <div class="px-4 md:px-10 text-neutral-content w-full py-12">
            <div class="w-full flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-5xl font-bold">Dashboard</h1>
                        <p class="py-2">Selamat datang, <span class="font-semibold">{{ auth()->user()->name }}</span>! 👋</p>
                        <div class="badge badge-neutral badge-lg">{{ now()->format('l, d F Y') }}</div>
                    </div>
                </div>
                <button wire:click="refreshStats" class="btn btn-neutral btn-lg gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats stats-vertical lg:stats-horizontal shadow w-full mb-6 gap-2 md:gap-5">
        <div class="stat bg-white/50 dark:bg-base-200/50 shadow-lg rounded-lg">
            <div class="stat-figure text-info">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="stat-title">Total Users</div>
            <div class="stat-value text-info">{{ number_format($totalUsers) }}</div>
            <div class="stat-desc">Registered members</div>
        </div>

        <div class="stat bg-white/50 dark:bg-base-200/50 shadow-lg rounded-lg">
            <div class="stat-figure text-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">Total Revenue</div>
            <div class="stat-value text-success">Rp {{ number_format($totalRevenue / 1000, 0) }}K</div>
            <div class="stat-desc">From successful orders</div>
        </div>

        <div class="stat bg-white/50 dark:bg-base-200/50 shadow-lg rounded-lg">
            <div class="stat-figure text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div class="stat-title">Total Orders</div>
            <div class="stat-value text-warning">{{ number_format($totalOrders) }}</div>
            <div class="stat-desc">All time transactions</div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Activity Card -->
        <div class="card bg-base-200/50 shadow-xl border border-primary/20">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="card-title">Recent Orders</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead class="bg-primary/10">
                            <tr>
                                <th>Order Code</th>
                                <th>User</th>
                                <th>Game</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentActivities as $activity)
                                <tr>
                                    <td class="font-mono text-xs">{{ Str::limit($activity->order_code, 8) }}</td>
                                    <td>{{ $activity->user->name ?? 'N/A' }}</td>
                                    <td>{{ $activity->game->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($activity->status === 'success')
                                            <span class="badge badge-success badge-sm">Success</span>
                                        @elseif($activity->status === 'pending')
                                            <span class="badge badge-warning badge-sm">Pending</span>
                                        @else
                                            <span class="badge badge-error badge-sm">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-base-content/50">
                                        No recent orders
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card bg-base-200/50 shadow-xl border border-secondary/20">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-secondary rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('carousel.index') }}" wire:navigate class="btn btn-outline btn-lg gap-2 hover:btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Carousel
                    </a>
                    <a href="{{ route('carousel.create') }}" wire:navigate class="btn btn-outline btn-lg gap-2 hover:btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Carousel
                    </a>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="btn btn-outline btn-lg gap-2 hover:btn-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                    <button wire:click="refreshStats" class="btn btn-outline btn-lg gap-2 hover:btn-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        View Stats
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Area -->
    <div class="card bg-base-200/50 shadow-xl border border-accent/20">
        <div class="card-body">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="card-title">Statistics Overview</h2>
            </div>
            <div class="flex items-center justify-center h-64 bg-gradient-to-br from-primary/5 to-secondary/5 rounded-lg border-2 border-dashed border-primary/20">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-primary/40 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <p class="text-base-content/50 font-semibold">📈 Chart Area</p>
                    <p class="text-base-content/30 text-sm mt-2">Add your chart component here</p>
                </div>
            </div>
        </div>
    </div>
</div>
