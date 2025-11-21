<?php

use App\Models\Order;
use App\Models\Game;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;

new #[Layout('components.layouts.app.sidebar'), Title('Orders Management')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $gameFilter = '';

    public function getStatusBadge($status)
    {
        return match ($status) {
            'pending' => 'badge-warning',
            'process' => 'badge-info',
            'success' => 'badge-success',
            'failed' => 'badge-error',
            'refund' => 'badge-secondary',
            default => 'badge-ghost',
        };
    }

    public function with(): array
    {
        return [
            'orders' => Order::with(['user', 'game', 'product', 'orderPayment'])
                ->when($this->search, fn($q) => $q->where('order_code', 'like', "%{$this->search}%")->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when($this->gameFilter, fn($q) => $q->where('game_id', $this->gameFilter))
                ->latest()
                ->paginate(20),
            'games' => Game::orderBy('name')->get(),
            'statuses' => ['pending', 'process', 'success', 'failed', 'refund'],
        ];
    }
}; ?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li>Orders</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-accent badge-lg">ORDERS</span>
                Management
            </h1>
            <p class="text-base-content/60 mt-2">Monitor dan kelola semua transaksi</p>
        </div>
        <div class="stats shadow">
            <div class="stat">
                <div class="stat-title">Total</div>
                <div class="stat-value text-accent">{{ $orders->total() }}</div>
                <div class="stat-desc">orders</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <div class="input-group">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari order..."
                            class="input input-bordered w-full" />
                    </div>
                </div>
                <select wire:model.live="statusFilter" class="select select-bordered w-full">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="gameFilter" class="select select-bordered w-full">
                    <option value="">Semua Game</option>
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat bg-base-100 shadow-xl rounded-lg">
            <div class="stat-title">Total Orders</div>
            <div class="stat-value text-2xl">{{ Order::count() }}</div>
        </div>
        <div class="stat bg-base-100 shadow-xl rounded-lg">
            <div class="stat-title">Pending</div>
            <div class="stat-value text-warning text-2xl">{{ Order::where('status', 'pending')->count() }}</div>
        </div>
        <div class="stat bg-base-100 shadow-xl rounded-lg">
            <div class="stat-title">Success</div>
            <div class="stat-value text-success text-2xl">{{ Order::where('status', 'success')->count() }}</div>
        </div>
        <div class="stat bg-base-100 shadow-xl rounded-lg">
            <div class="stat-title">Failed</div>
            <div class="stat-value text-error text-2xl">{{ Order::where('status', 'failed')->count() }}</div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Game / Produk</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <div class="font-mono text-sm font-semibold">{{ $order->order_code }}</div>
                            </td>
                            <td>
                                <div class="text-sm">{{ $order->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-base-content/60">{{ $order->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                @if ($order->user)
                                    <div class="text-sm font-semibold">{{ $order->user->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ $order->user->email }}</div>
                                @else
                                    <span class="text-base-content/40">Guest</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm font-semibold">{{ $order->game->name ?? '-' }}</div>
                                <div class="text-xs text-base-content/60">{{ $order->product->name ?? '-' }}</div>
                            </td>
                            <td class="font-semibold">Rp {{ number_format($order->price, 0, ',', '.') }}</td>
                            <td>
                                <div class="badge {{ $this->getStatusBadge($order->status) }} badge-sm">
                                    {{ ucfirst($order->status) }}
                                </div>
                                @if ($order->orderPayment)
                                    <div class="text-xs text-base-content/60 mt-1">
                                        {{ ucfirst($order->orderPayment->status) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate
                                    class="btn btn-sm btn-ghost">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-base-content/30">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                    <span class="text-base-content/60">Belum ada pesanan</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination -->
    <div class="mt-6">
        {{ $orders->links() }}
    </div>

</div>
