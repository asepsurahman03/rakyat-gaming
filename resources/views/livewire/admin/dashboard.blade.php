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
    public $totalDigiflazzOrders = 0;
    public $totalManualOrders = 0;
    public $digiflazzRevenue = 0;
    public $manualRevenue = 0;
    public $digiflazzBalance = 0;
    public $onoystoreBalance = 0;
    public $byxstoreBalance = 0;
    public $recentActivities = [];

    public function mount()
    {
        $this->loadStats();
        $this->loadRecentActivities();
        $this->loadDigiflazzBalance();
        $this->loadOnoystoreBalance();
        $this->loadByxstoreBalance();
    }

    public function loadDigiflazzBalance()
    {
        try {
            $supplier = \App\Models\Supplier::where('code', 'digiflazz')->first();

            if (!$supplier) {
                $this->digiflazzBalance = 0;
                return;
            }

            $username = $supplier->username ?? env('DIGIFLAZZ_USERNAME');
            $apiKey = $supplier->api_key ?? env('DIGIFLAZZ_API_KEY');
            $apiUrl = $supplier->api_url ?? env('DIGIFLAZZ_URL');

            // Generate signature: md5(username + apiKey + depo)
            $sign = md5($username . $apiKey . 'depo');

            $payload = [
                'username' => $username,
                'cmd' => 'deposit',
                'sign' => $sign,
            ];

            $endpoint = rtrim($apiUrl, '/') . '/cek-saldo';

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->asJson()
                ->post($endpoint, $payload);

            $body = $response->json();

            if ($response->ok() && isset($body['data']['deposit'])) {
                $this->digiflazzBalance = (float) $body['data']['deposit'];
            } else {
                $this->digiflazzBalance = 0;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load Digiflazz balance', [
                'error' => $e->getMessage()
            ]);
            $this->digiflazzBalance = 0;
        }
    }

    public function loadOnoystoreBalance()
    {
        try {
            $supplier = \App\Models\Supplier::where('code', 'onoystore')->first();

            if (!$supplier || !$supplier->api_key) {
                $this->onoystoreBalance = 0;
                return;
            }

            $service = app(\App\Services\Suppliers\OnoyStoreSupplierService::class);
            $balance = $service->getBalance();
            $this->onoystoreBalance = $balance ?? 0;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load Onoystore balance', [
                'error' => $e->getMessage()
            ]);
            $this->onoystoreBalance = 0;
        }
    }

    public function loadByxstoreBalance()
    {
        try {
            $supplier = \App\Models\Supplier::where('code', 'byxstore')->first();

            if (!$supplier || !$supplier->api_key) {
                $this->byxstoreBalance = 0;
                return;
            }

            $service = app(\App\Services\Suppliers\ByxStoreSupplierService::class);
            $balance = $service->getBalance();
            $this->byxstoreBalance = $balance ?? 0;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load Byxstore balance', [
                'error' => $e->getMessage()
            ]);
            $this->byxstoreBalance = 0;
        }
    }

    public function loadStats()
    {
        $this->totalUsers = User::count();
        $this->totalOrders = Order::count();
        $this->totalRevenue = Order::where('status', 'success')->sum('price');

        // Count orders by product type
        $this->totalDigiflazzOrders = Order::whereHas('product', function($query) {
            $query->where('product_type', 'digiflazz');
        })->count();

        $this->totalManualOrders = Order::whereHas('product', function($query) {
            $query->where('product_type', 'manual');
        })->count();

        // Calculate revenue by product type
        $this->digiflazzRevenue = Order::where('status', 'success')
            ->whereHas('product', function($query) {
                $query->where('product_type', 'digiflazz');
            })->sum('price');

        $this->manualRevenue = Order::where('status', 'success')
            ->whereHas('product', function($query) {
                $query->where('product_type', 'manual');
            })->sum('price');
    }

    public function getStatusBadge($status)
    {
        return match ($status) {
            'pending' => 'yellow',
            'process' => 'blue',
            'success' => 'green',
            'failed' => 'red',
            'refund' => 'zinc',
            default => 'zinc',
        };
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
        $this->loadDigiflazzBalance();
        $this->loadOnoystoreBalance();
        $this->loadByxstoreBalance();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">
        Dashboard
        <flux:subheading>Welcome back, {{ auth()->user()->name }}! 👋</flux:subheading>
    </flux:heading>

    <!-- Main Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Users
                    </flux:heading>
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
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Revenue
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($totalRevenue / 1000, 0) }}K
                    </flux:heading>
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
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Orders
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">All time transactions</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <flux:icon.shopping-bag class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Supplier Balances -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo Digiflazz
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($digiflazzBalance / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">Current balance</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.wallet class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo Onoystore
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($onoystoreBalance / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">Current balance</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.wallet class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo Byxstore
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($byxstoreBalance / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">Current balance</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                    <flux:icon.wallet class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Product Type Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Suppliers Orders
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalDigiflazzOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Supplier products</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.globe-alt class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Manual Orders
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalManualOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Custom stock products</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.cube class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Digiflazz Revenue
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($digiflazzRevenue / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">From supplier orders</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.currency-dollar class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Manual Revenue
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($manualRevenue / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">From custom stock</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.currency-dollar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
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
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentActivities as $activity)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-xs">{{ Str::limit($activity->order_code, 8) }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $activity->user->name ?? 'Guest' }}</flux:table.cell>
                            <flux:table.cell>{{ $activity->game->name ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($activity->product && $activity->product->product_type === 'manual')
                                    <flux:badge color="purple" size="sm" icon="cube">
                                        Manual
                                    </flux:badge>
                                @else
                                    <flux:badge color="indigo" size="sm" icon="globe-alt">
                                        {{ $activity->product->product_type }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $this->getStatusBadge($activity->status) }}" size="sm">
                                    {{ ucfirst($activity->status) }}
                                </flux:badge>
                                @if ($activity->orderPayment)
                                    <div class="text-xs text-zinc-500 mt-1">
                                        {{ ucfirst($activity->orderPayment->status) }}
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">No recent orders
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <!-- Quick Actions -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:button href="{{ route('carousel.index') }}" wire:navigate variant="outline" icon="photo"
                    class="justify-start">
                    Carousel
                </flux:button>
                <flux:button href="{{ route('carousel.create') }}" wire:navigate variant="outline" icon="plus"
                    class="justify-start">
                    New Carousel
                </flux:button>
                <flux:button href="{{ route('profile.edit') }}" wire:navigate variant="outline" icon="cog"
                    class="justify-start">
                    Settings
                </flux:button>
                <flux:button wire:click="refreshStats" variant="outline" icon="arrow-path" class="justify-start">
                    Refresh Stats
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
