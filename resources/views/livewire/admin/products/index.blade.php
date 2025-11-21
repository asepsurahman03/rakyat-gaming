<?php

use App\Models\Product;
use App\Models\Game;
use function Livewire\Volt\{state, with};

state(['search' => '', 'gameFilter' => '']);

with(fn () => [
    'products' => Product::with(['game', 'supplierProduct'])
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
        ->when($this->gameFilter, fn($q) => $q->where('game_id', $this->gameFilter))
        ->latest()
        ->paginate(15),
    'games' => Game::where('is_active', true)->orderBy('name')->get(),
]);

$delete = function (Product $product) {
    $product->delete();
    $this->dispatch('success', message: 'Produk berhasil dihapus');
};

?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li>Products</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-secondary badge-lg">PRODUCTS</span>
                Management
            </h1>
            <p class="text-base-content/60 mt-2">Kelola produk digital untuk dijual</p>
        </div>
        <a href="{{ route('admin.products.create') }}" wire:navigate class="btn btn-secondary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Produk
        </a>
    </div>

    <!-- Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <div class="input-group">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari produk..."
                            class="input input-bordered w-full"
                        >
                    </div>
                </div>
                <select wire:model.live="gameFilter" class="select select-bordered w-full">
                    <option value="">Semua Game</option>
                    @foreach($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Game</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Margin</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $product->name }}</div>
                                @if($product->supplierProduct)
                                    <div class="text-xs text-base-content/60">{{ $product->supplierProduct->code }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="badge badge-outline">{{ $product->game->name }}</div>
                            </td>
                            <td>
                                @if($product->supplierProduct)
                                    Rp {{ number_format($product->supplierProduct->buyer_price, 0, ',', '.') }}
                                @else
                                    <span class="text-base-content/40">-</span>
                                @endif
                            </td>
                            <td class="font-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                            <td>
                                @if($product->supplierProduct)
                                    @php
                                        $margin = $product->selling_price - $product->supplierProduct->buyer_price;
                                    @endphp
                                    <span class="text-success">+Rp {{ number_format($margin, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-base-content/40">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <div class="badge {{ $product->is_active ? 'badge-success' : 'badge-error' }} badge-sm">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </div>
                                    @if($product->is_auto_price)
                                        <div class="badge badge-info badge-sm">Auto Price</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="btn btn-sm btn-ghost">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    <button wire:click="delete({{ $product->id }})" wire:confirm="Yakin ingin menghapus produk ini?" class="btn btn-sm btn-ghost text-error">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-base-content/30">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <span class="text-base-content/60">Belum ada produk</span>
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
        {{ $products->links() }}
    </div>
</div>
