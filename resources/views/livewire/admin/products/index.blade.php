<?php

use App\Models\Product;
use App\Models\Game;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Products Management')] class extends Component {
    use WithPagination;

    public $search = '';
    public $gameFilter = '';
    public $statusFilter = '';
    public $page = 1;

    public function with(): array
    {
        $query = Product::with(['game', 'supplierProduct']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->gameFilter) {
            $query->where('game_id', $this->gameFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter);
        }

        return [
            'products' => $query->latest()->paginate(15),
            'games' => Game::where('is_active', true)->orderBy('name')->get(),
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('is_active', true)->count(),
            'inactiveProducts' => Product::where('is_active', false)->count(),
        ];
    }

    public function resetPage()
    {
        $this->page = 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteProduct($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->delete();
            Flux::toast(heading: 'Produk berhasil dihapus!', text: 'Produk telah dihapus dari sistem.', variant: 'success', duration: 3000, position: 'top-right');
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Management Products</flux:heading>
            <flux:subheading>Kelola produk digital untuk dijual</flux:subheading>
        </div>
        <flux:button variant="primary" wire:navigate href="{{ route('admin.products.create') }}" icon="plus">
            Add Product
        </flux:button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Products
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalProducts) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">All products</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.cube class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Active Products
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($activeProducts) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Ready to sell</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Inactive Products
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($inactiveProducts) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Not available</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <flux:icon.x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filter Products</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari produk..."
                    icon="magnifying-glass" />
            </flux:field>

            <flux:field>
                <flux:label>Game</flux:label>
                <flux:select wire:model.live="gameFilter">
                    <option value="">Semua Game</option>
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <!-- Products Table -->
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">Product List</flux:heading>
            <flux:subheading>Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of
                {{ $products->total() }} products</flux:subheading>
        </div>

        <flux:table :paginate="$products">
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Produk</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Game</flux:table.column>
                <flux:table.column>Harga Beli</flux:table.column>
                <flux:table.column>Harga Jual</flux:table.column>
                <flux:table.column>Margin</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column class="text-center">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($products as $index => $product)
                    <flux:table.row wire:key="product-{{ $product->id }}">
                        <flux:table.cell>{{ $products->firstItem() + $index }}</flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="font-semibold">{{ $product->name }}</div>
                                @if ($product->supplierProduct)
                                    <div class="text-xs text-zinc-500">{{ $product->supplierProduct->code }}</div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($product->product_type === 'manual')
                                <flux:badge color="purple" size="sm" icon="cube">Manual</flux:badge>
                            @else
                                <flux:badge color="indigo" size="sm" icon="link">Digiflazz</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ $product->game->name }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($product->supplierProduct)
                                <span class="text-sm">Rp
                                    {{ number_format($product->supplierProduct->buyer_price, 0, ',', '.') }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-semibold">Rp
                                {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($product->supplierProduct)
                                @php
                                    $margin = $product->selling_price - $product->supplierProduct->buyer_price;
                                @endphp
                                <span class="text-green-600 font-semibold">+Rp
                                    {{ number_format($margin, 0, ',', '.') }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-col gap-1">
                                <flux:badge :color="$product->is_active ? 'green' : 'red'" size="sm">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                                @if ($product->is_auto_price)
                                    <flux:badge color="blue" size="sm">Auto Price</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2 justify-center">
                                <flux:button wire:navigate href="{{ route('admin.products.edit', $product) }}"
                                    variant="ghost" icon="pencil" />
                                <flux:modal.trigger name="delete-product-{{ $product->id }}">
                                    <flux:button variant="danger" icon="trash"></flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="delete-product-{{ $product->id }}" class="min-w-[22rem]">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete Product?</flux:heading>

                                            <flux:text class="mt-2">
                                                Apakah Anda yakin ingin menghapus produk ini?<br>
                                                Tindakan ini tidak dapat dibatalkan.
                                            </flux:text>
                                        </div>

                                        <div class="flex gap-2">
                                            <flux:spacer />

                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:modal.close>
                                                <flux:button wire:click="deleteProduct({{ $product->id }})" variant="danger">Delete</flux:button>
                                            </flux:modal.close>
                                        </div>
                                    </div>
                                </flux:modal>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.cube class="w-16 h-16 text-zinc-300" />
                                <div>
                                    <div class="font-semibold text-zinc-500">No Products Found</div>
                                    <div class="text-sm text-zinc-400 mt-1">Try adjusting your filters or add a new
                                        product</div>
                                </div>
                                <flux:button wire:navigate href="{{ route('admin.products.create') }}" size="sm"
                                    icon="plus">
                                    Add First Product
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
