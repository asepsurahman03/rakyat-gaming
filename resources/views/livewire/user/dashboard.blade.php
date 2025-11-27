<?php

use App\Models\Order;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.user'), Title('Dashboard')] class extends Component {
    public function with(): array
    {
        $userId = auth()->id();

        // Get today's transactions
        $todayOrders = Order::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->with(['product.game', 'paymentGateway'])
            ->latest()
            ->get();

        // Statistics
        $totalOrdersToday = $todayOrders->count();
        $successOrdersToday = $todayOrders->where('status', 'success')->count();
        $pendingOrdersToday = $todayOrders->where('status', 'pending')->count();
        $failedOrdersToday = $todayOrders->where('status', 'failed')->count();

        $totalSpentToday = $todayOrders->where('status', 'success')->sum('price');

        return [
            'todayOrders' => $todayOrders,
            'totalOrdersToday' => $totalOrdersToday,
            'successOrdersToday' => $successOrdersToday,
            'pendingOrdersToday' => $pendingOrdersToday,
            'failedOrdersToday' => $failedOrdersToday,
            'totalSpentToday' => $totalSpentToday,
        ];
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'success' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'zinc',
        };
    }

    public function getStatusText($status)
    {
        return match ($status) {
            'success' => 'Berhasil',
            'pending' => 'Menunggu',
            'failed' => 'Gagal',
            default => ucfirst($status),
        };
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:subheading>Selamat datang, {{ auth()->user()->name }}!</flux:subheading>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Transaksi Hari Ini
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalOrdersToday) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">{{ now()->format('d M Y') }}</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.shopping-cart class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Berhasil
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($successOrdersToday) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Transaksi sukses</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Pending
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($pendingOrdersToday) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Menunggu pembayaran</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <flux:icon.clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Pengeluaran
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($totalSpentToday / 1000, 0) }}K</flux:heading>
                    <flux:subheading class="text-xs mt-1">Hari ini</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.currency-dollar class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Today's Transactions -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Transaksi Hari Ini</flux:heading>

        @if ($todayOrders->isEmpty())
            <div class="text-center py-12">
                <flux:icon.shopping-cart class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto" />
                <div class="mt-4">
                    <flux:heading size="lg" class="text-zinc-500 dark:text-zinc-400">Belum ada transaksi hari ini</flux:heading>
                    <flux:subheading class="mt-2">Mulai berbelanja sekarang!</flux:subheading>
                </div>
                <flux:button href="{{ route('home') }}" wire:navigate class="mt-4" variant="primary">
                    Mulai Belanja
                </flux:button>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order ID</flux:table.column>
                    <flux:table.column>Game & Produk</flux:table.column>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="text-center">Detail</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($todayOrders as $order)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-mono text-sm">#{{ $order->order_number }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <div class="font-semibold">{{ $order->product->game->name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $order->product->name }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm">{{ $order->created_at->format('H:i') }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-semibold">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$this->getStatusColor($order->status)" size="sm">
                                    {{ $this->getStatusText($order->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-center">
                                <flux:button href="{{ route('user.transactions') }}" wire:navigate size="sm" variant="ghost">
                                    Lihat Detail
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4 text-center">
                <flux:button href="{{ route('user.transactions') }}" wire:navigate variant="ghost">
                    Lihat Semua Transaksi
                </flux:button>
            </div>
        @endif
    </flux:card>
</div>
