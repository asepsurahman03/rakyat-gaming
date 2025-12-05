<?php

use App\Models\Order;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.user'), Title('Transaksi Saya')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';

    public function with(): array
    {
        $userId = auth()->id();

        $query = Order::where('user_id', $userId)->with(['product.game', 'paymentGateway']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")->orWhereHas('product', function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%");
                });
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFilter) {
            switch ($this->dateFilter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
            }
        }

        $orders = $query->latest()->paginate(15);

        // Statistics
        $totalOrders = Order::where('user_id', $userId)->count();
        $successOrders = Order::where('user_id', $userId)->where('status', 'success')->count();
        $totalSpent = Order::where('user_id', $userId)->where('status', 'success')->sum('price');

        return [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'successOrders' => $successOrders,
            'totalSpent' => $totalSpent,
        ];
    }

    public function resetPage()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
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
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Transaksi Saya</flux:heading>
            <flux:subheading>Riwayat semua transaksi Anda</flux:subheading>
        </div>
        <flux:button href="{{ route('home') }}" wire:navigate variant="primary" icon="plus">
            Transaksi Baru
        </flux:button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Transaksi
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Semua waktu</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.shopping-cart class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Transaksi Berhasil
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($successOrders) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Sukses diproses</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Pengeluaran
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">Rp {{ number_format($totalSpent / 1000, 0) }}K
                    </flux:heading>
                    <flux:subheading class="text-xs mt-1">Semua waktu</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.currency-dollar class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filter Transaksi</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>Cari Transaksi</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Order ID atau nama produk..."
                    icon="magnifying-glass" />
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="success">Berhasil</option>
                    <option value="failed">Gagal</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Periode</flux:label>
                <flux:select wire:model.live="dateFilter">
                    <option value="">Semua Waktu</option>
                    <option value="today">Hari Ini</option>
                    <option value="week">Minggu Ini</option>
                    <option value="month">Bulan Ini</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <!-- Transactions Table -->
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">Daftar Transaksi</flux:heading>
            <flux:subheading>Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of
                {{ $orders->total() }} transaksi</flux:subheading>
        </div>

        <flux:table :paginate="$orders">
            <flux:table.columns>
                <flux:table.column>Order ID</flux:table.column>
                <flux:table.column>Game & Produk</flux:table.column>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Payment</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column class="text-center">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($orders as $order)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-mono text-sm">#{{ $order->order_code }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="font-semibold">{{ $order->product->game->name }}</div>
                                <div class="text-sm text-zinc-500">{{ $order->product->name }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="text-sm">{{ $order->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->created_at->format('H:i') }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ $order->paymentGateway->name ?? 'N/A' }}
                            </flux:badge>
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
                            <flux:modal.trigger :name="'order-detail-' . $order->id">
                                <flux:button size="sm" variant="ghost" icon="eye">Detail</flux:button>
                            </flux:modal.trigger>

                            <flux:modal :name="'order-detail-' . $order->id" class="min-w-[40rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Detail Transaksi</flux:heading>
                                        <flux:subheading>Order #{{ $order->order_code }}</flux:subheading>
                                    </div>

                                    <div class="space-y-4">
                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Game</span>
                                            <span class="font-semibold">{{ $order->product->game->name }}</span>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Produk</span>
                                            <span class="font-semibold">{{ $order->product->name }}</span>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Tanggal</span>
                                            <span
                                                class="font-semibold">{{ $order->created_at->format('d M Y H:i') }}</span>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Metode
                                                Pembayaran</span>
                                            <span
                                                class="font-semibold">{{ $order->paymentGateway->name ?? 'N/A' }}</span>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Total
                                                Pembayaran</span>
                                            <span class="font-semibold text-lg">Rp
                                                {{ number_format($order->price, 0, ',', '.') }}</span>
                                        </div>

                                        <div
                                            class="flex justify-between items-center pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Status</span>
                                            <flux:badge :color="$this->getStatusColor($order->status)">
                                                {{ $this->getStatusText($order->status) }}
                                            </flux:badge>
                                        </div>

                                        @if ($order->additional_data)
                                            <div class="mt-4">
                                                <flux:heading size="sm" class="mb-2">Data Player</flux:heading>
                                                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                                                    @foreach (json_decode($order->additional_data, true) as $key => $value)
                                                        <div class="flex justify-between">
                                                            <span
                                                                class="text-sm text-zinc-600 dark:text-zinc-400">{{ $key }}</span>
                                                            <span class="font-semibold">{{ $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Tutup</flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.shopping-cart class="w-16 h-16 text-zinc-300" />
                                <div>
                                    <div class="font-semibold text-zinc-500">Tidak ada transaksi</div>
                                    <div class="text-sm text-zinc-400 mt-1">Belum ada riwayat transaksi</div>
                                </div>
                                <flux:button href="{{ route('home') }}" wire:navigate size="sm" icon="plus">
                                    Mulai Belanja
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
