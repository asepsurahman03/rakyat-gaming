<?php

use App\Models\Order;
use App\Models\Game;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Management Pesanan')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $gameFilter = '';
    public $fromDate = '';
    public $toDate = '';
    public $exportLoading = false;

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

    public function export($format = 'xlsx')
    {
        $this->exportLoading = true;

        try {
            // Hitung total data yang akan di-export
            $query = Order::query()
                ->when($this->search, fn($q) => $q->where('order_code', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when($this->gameFilter, fn($q) => $q->where('game_id', $this->gameFilter))
                ->when($this->fromDate, fn($q) => $q->whereDate('created_at', '>=', $this->fromDate))
                ->when($this->toDate, fn($q) => $q->whereDate('created_at', '<=', $this->toDate));

            $totalRecords = $query->count();

            // Validasi batas maksimal export
            if ($totalRecords > 5000) {
                Flux::toast(
                    heading: 'Data terlalu banyak!',
                    text: "Total data: {$totalRecords} rows. Maksimal export 5.000 rows. Gunakan filter tanggal untuk membatasi data.",
                    variant: 'warning',
                    duration: 5000,
                    position: 'top-right',
                );
                $this->exportLoading = false;
                return;
            }

            if ($totalRecords === 0) {
                \Flux::toast(
                    heading: 'Tidak ada data',
                    text: 'Tidak ada data pesanan yang sesuai dengan filter.',
                    variant: 'warning',
                    duration: 3000,
                    position: 'top-right',
                );
                $this->exportLoading = false;
                return;
            }

            $filename = 'orders-' . now()->format('Y-m-d-His') . '.' . $format;

            $this->exportLoading = false;

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\OrdersExport(
                    $this->search,
                    $this->statusFilter,
                    $this->gameFilter,
                    $this->fromDate,
                    $this->toDate
                ),
                $filename
            );
        } catch (\Exception $e) {
            $this->exportLoading = false;
            \Flux::toast(
                heading: 'Export gagal!',
                text: 'Terjadi kesalahan: ' . $e->getMessage(),
                variant: 'danger',
                duration: 5000,
                position: 'top-right',
            );
        }
    }

    public function with(): array
    {
        return [
            'orders' => Order::with(['user', 'game', 'product', 'orderPayment'])
                ->when($this->search, fn($q) => $q->where('order_code', 'like', "%{$this->search}%")->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when($this->gameFilter, fn($q) => $q->where('game_id', $this->gameFilter))
                ->when($this->fromDate, fn($q) => $q->whereDate('created_at', '>=', $this->fromDate))
                ->when($this->toDate, fn($q) => $q->whereDate('created_at', '<=', $this->toDate))
                ->latest()
                ->paginate(20),
            'games' => Game::orderBy('name')->get(),
            'statuses' => ['pending', 'process', 'success', 'failed', 'refund'],
        ];
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="/admin/dashboard" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Page Header -->
    <div class="mb-6">
        <flux:heading size="xl" class="mb-2">Management Pesanan</flux:heading>
        <flux:subheading>Monitor dan kelola semua transaksi</flux:subheading>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <flux:subheading class="mb-1">Total Orders</flux:subheading>
            <div class="text-2xl font-bold">{{ Order::count() }}</div>
        </flux:card>
        <flux:card>
            <flux:subheading class="mb-1">Pending</flux:subheading>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ Order::where('status', 'pending')->count() }}</div>
        </flux:card>
        <flux:card>
            <flux:subheading class="mb-1">Success</flux:subheading>
            <div class="text-2xl font-bold text-green-600 dark:text-green-500">{{ Order::where('status', 'success')->count() }}</div>
        </flux:card>
        <flux:card>
            <flux:subheading class="mb-1">Failed</flux:subheading>
            <div class="text-2xl font-bold text-red-600 dark:text-red-500">{{ Order::where('status', 'failed')->count() }}</div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search"
                        placeholder="Cari pesanan berdasarkan kode pesanan atau email..."
                        icon="magnifying-glass" />
                </div>
                <flux:select wire:model.live="statusFilter" placeholder="Semua Status" class="w-full sm:w-48">
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="gameFilter" placeholder="Semua Game" class="w-full sm:w-48">
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:separator />

            <div class="flex flex-col sm:flex-row items-end gap-4 w-full">
                <div class="flex-1 w-full">
                    <flux:field>
                        <flux:label>Dari Tanggal</flux:label>
                        <flux:input type="date" wire:model.live="fromDate" />
                    </flux:field>
                </div>
                <div class="flex-1 w-full">
                    <flux:field>
                        <flux:label>Sampai Tanggal</flux:label>
                        <flux:input type="date" wire:model.live="toDate" />
                    </flux:field>
                </div>
                <flux:dropdown position="bottom">
                    <flux:button
                        icon="arrow-down-tray"
                        variant="filled"
                        :disabled="$exportLoading"
                        class="whitespace-nowrap">
                        <span wire:loading.remove wire:target="export">Export Data</span>
                        <span wire:loading wire:target="export">
                            <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item wire:click="export('xlsx')" icon="document-text">
                            Export Excel (.xlsx)
                        </flux:menu.item>
                        <flux:menu.item wire:click="export('csv')" icon="table-cells">
                            Export CSV (.csv)
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </flux:card>

    <!-- Orders Table -->
    <flux:card>
        <flux:table :paginate="$orders">
            <flux:table.columns>
                <flux:table.column>Order Code</flux:table.column>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Game / Produk</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Harga</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($orders as $order)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-mono text-sm font-semibold">{{ $order->order_code }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">{{ $order->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->created_at->format('H:i') }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($order->user)
                                <div class="text-sm font-semibold">{{ $order->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->user->email }}</div>
                            @else
                                <span class="text-zinc-400">Guest</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm font-semibold">{{ $order->game->name ?? '-' }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->product->name ?? '-' }}</div>
                        </flux:table.cell>
                        <flux:table.cell class="font-semibold">
                            @if ($order->product->product_type === 'manual')
                                <flux:badge color="purple" size="sm" icon="cube">Manual</flux:badge>
                            @else
                                <flux:badge color="indigo" size="sm" icon="link">{{ $order->product->product_type }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="font-semibold">Rp {{ number_format($order->price, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$this->getStatusBadge($order->status)" size="sm">
                                {{ ucfirst($order->status) }}
                            </flux:badge>
                            @if ($order->orderPayment)
                                <div class="text-xs text-zinc-500 mt-1">
                                    {{ ucfirst($order->orderPayment->status) }}
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button href="{{ route('admin.orders.show', $order) }}" wire:navigate variant="ghost" size="sm" icon="eye" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <span class="text-zinc-500">Belum ada pesanan</span>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
