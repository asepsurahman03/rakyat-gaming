<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\SupplierProduct;
use App\Models\Supplier;

new #[Layout('components.layouts.app.sidebar'), Title('Supplier Products')] class extends Component {
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
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Berhasil sync ' . $result['count'] . ' produk dari supplier',
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal sync produk: ' . ($result['body']['message'] ?? 'Unknown error'),
            ]);
        }
    }
}; ?>

<div>

    <!-- Page Header -->
    <div class="flex justify-between items-center my-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-info badge-lg">SUPPLIER</span>
                Products
            </h1>
            <p class="text-base-content/60 mt-2">Produk dari supplier untuk dijual kembali</p>
        </div>
        <button wire:click="syncProducts" class="btn btn-info gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Sync Products
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats bg-base-200/50 stats-vertical lg:stats-horizontal shadow w-full mb-6">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <div class="stat-title">Total Products</div>
            <div class="stat-value text-primary">{{ number_format($totalProducts) }}</div>
            <div class="stat-desc">From supplier</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">Active Products</div>
            <div class="stat-value text-success">{{ number_format($activeProducts) }}</div>
            <div class="stat-desc">Ready to sell</div>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="card bg-base-200/50 shadow-xl mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search by code, name, or brand..." class="input input-bordered w-full" />
                </div>

                <!-- Category Filter -->
                <select wire:model.live="categoryFilter" class="select select-bordered w-full md:w-48">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>

                <!-- Brand Filter -->
                <select wire:model.live="brandFilter" class="select select-bordered w-full md:w-48">
                    <option value="">All Brands</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter" class="select select-bordered w-full md:w-32">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card bg-base-200/50 shadow-xl">
        <div class="overflow-x-auto rounded-box border border-base-content/5">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Buyer Price</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplierProducts as $product)
                        <tr>
                            <td>
                                <span class="badge badge-ghost">{{ $product->code }}</span>
                            </td>
                            <td>
                                <div class="font-medium">{{ $product->product_name }}</div>
                                <div class="text-xs text-gray-400">{{ $product->supplier->name }}</div>
                            </td>
                            <td>
                                <span class="badge badge-outline">{{ $product->category }}</span>
                            </td>
                            <td>{{ $product->brand }}</td>
                            <td>
                                <span class="font-semibold text-primary">
                                    Rp {{ number_format($product->buyer_price, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if ($product->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-error">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-xs text-gray-400">
                                    {{ $product->last_sync_at?->diffForHumans() ?? '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8">
                                <div class="text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <div class="text-lg font-medium">No products found</div>
                                    <div class="text-sm">Click "Sync From Supplier" to load products</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($supplierProducts->hasPages())
        <div class="mt-6">
            {{ $supplierProducts->links() }}
        </div>
    @endif
</div>

