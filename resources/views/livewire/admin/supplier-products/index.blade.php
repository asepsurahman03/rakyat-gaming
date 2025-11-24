<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\SupplierProduct;
use App\Models\Supplier;

new #[Layout('components.layouts.app'), Title('Supplier Products')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $brandFilter = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $query = SupplierProduct::query()->with('supplier')->orderBy('last_sync_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                    ->orWhere('product_name', 'like', '%' . $this->search . '%')
                    ->orWhere('brand', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->brandFilter) {
            $query->where('brand', $this->brandFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'supplierProducts' => $query->paginate(25),
            'categories' => SupplierProduct::distinct()->pluck('category')->filter(),
            'brands' => SupplierProduct::distinct()->pluck('brand')->filter(),
            'totalProducts' => SupplierProduct::count(),
            'activeProducts' => SupplierProduct::where('status', 'active')->count(),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBrandFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function syncProducts(): void
    {
        $service = app(\App\Services\DigiflazzService::class);
        $result = $service->syncPriceList();

        if ($result['success']) {
            $service->applyAutoPricing();
            Flux::toast(
                heading: 'Berhasil sync ' . $result['count'] . ' produk dari supplier',
                text: 'Produk telah diperbarui dari supplier.',
                variant: 'success',
                duration: 3000,
                position: 'top-right',
            );
        } else {
            Flux::toast(
                heading: 'Gagal sync produk',
                text: 'Error: ' . ($result['body']['message'] ?? 'Unknown error'),
                variant: 'error',
                duration: 3000,
                position: 'top-right',
            );
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="/admin/dashboard" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Supplier Products</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl" class="mb-2">
                <flux:badge color="info" size="lg" class="mr-2">SUPPLIER</flux:badge>
                Products
            </flux:heading>
            <flux:subheading>Produk dari supplier untuk dijual kembali</flux:subheading>
        </div>
        <flux:button wire:click="syncProducts" variant="primary" icon="arrow-path">
            Sync Products
        </flux:button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div>
                    <flux:subheading class="mb-1">Total Products</flux:subheading>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalProducts) }}</div>
                    <div class="text-sm text-zinc-500">From supplier</div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <flux:subheading class="mb-1">Active Products</flux:subheading>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($activeProducts) }}</div>
                    <div class="text-sm text-zinc-500">Ready to sell</div>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters & Actions -->
    <flux:card class="mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search"
                    placeholder="Search by code, name, or brand..."
                    icon="magnifying-glass" />
            </div>

            <!-- Category Filter -->
            <flux:select wire:model.live="categoryFilter" placeholder="All Categories" class="w-full md:w-48">
                @foreach ($categories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </flux:select>

            <!-- Brand Filter -->
            <flux:select wire:model.live="brandFilter" placeholder="All Brands" class="w-full md:w-48">
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </flux:select>

            <!-- Status Filter -->
            <flux:select wire:model.live="statusFilter" placeholder="All Status" class="w-full md:w-32">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </flux:select>
        </div>
    </flux:card>

    <!-- Products Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Product Name</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Brand</flux:table.column>
                <flux:table.column>Buyer Price</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Last Sync</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($supplierProducts as $product)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ $product->code }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $product->product_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $product->supplier->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="outline" size="sm">{{ $product->category }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->brand }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($product->buyer_price, 0, ',', '.') }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($product->status === 'active')
                                <flux:badge color="success" size="sm">Active</flux:badge>
                            @else
                                <flux:badge color="danger" size="sm">Inactive</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs text-zinc-500">
                                {{ $product->last_sync_at?->diffForHumans() ?? '-' }}
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2 text-zinc-400">
                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <div class="text-lg font-medium">No products found</div>
                                <div class="text-sm">Click "Sync Products" to load products</div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @if ($supplierProducts->hasPages())
        <div class="mt-6">
            {{ $supplierProducts->links() }}
        </div>
    @endif
</div>

