<?php

use App\Models\Product;
use App\Models\Game;
use App\Models\SupplierProduct;
use function Livewire\Volt\{state, computed, rules};

state([
    'game_id' => '',
    'supplier_product_id' => '',
    'name' => '',
    'selling_price' => 0,
    'is_auto_price' => false,
    'margin' => 1000,
    'is_active' => true,
    'description' => '',
]);

rules([
    'game_id' => 'required|exists:games,id',
    'supplier_product_id' => 'required|exists:supplier_products,id',
    'name' => 'required|string|max:255',
    'selling_price' => 'required|numeric|min:0',
    'is_auto_price' => 'boolean',
    'margin' => 'required_if:is_auto_price,true|numeric|min:0',
    'is_active' => 'boolean',
    'description' => 'nullable|string',
]);

$games = computed(fn () => Game::where('is_active', true)->orderBy('name')->get());

$supplierProducts = computed(fn () => SupplierProduct::with('supplier')
    ->where('status', 'active')
    ->orderBy('product_name')
    ->get());

$updateSellingPrice = function () {
    if ($this->is_auto_price && $this->supplier_product_id) {
        $supplierProduct = SupplierProduct::find($this->supplier_product_id);
        if ($supplierProduct) {
            $this->selling_price = $supplierProduct->buyer_price + $this->margin;
        }
    }
};

$onSupplierProductChange = function () {
    $supplierProduct = SupplierProduct::find($this->supplier_product_id);
    if ($supplierProduct) {
        // Auto-fill name if empty
        if (empty($this->name)) {
            $this->name = $supplierProduct->product_name;
        }
        // Calculate price if auto-price is enabled
        if ($this->is_auto_price) {
            $this->selling_price = $supplierProduct->buyer_price + $this->margin;
        }
    }
};

$save = function () {
    $validated = $this->validate();

    Product::create($validated);

    $this->redirect(route('admin.products.index'), navigate: true);
    session()->flash('success', 'Produk berhasil ditambahkan');
};

?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.products.index') }}" wire:navigate class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>
        <div>
            <h1 class="text-2xl font-bold">Tambah Produk Baru</h1>
            <p class="text-base-content/60">Hubungkan produk supplier dengan game</p>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Game -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Game <span class="text-error">*</span></span>
                        </label>
                        <select wire:model="game_id" class="select select-bordered @error('game_id') select-error @enderror">
                            <option value="">Pilih Game</option>
                            @foreach($this->games as $game)
                                <option value="{{ $game->id }}">{{ $game->name }}</option>
                            @endforeach
                        </select>
                        @error('game_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Supplier Product -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Produk Supplier <span class="text-error">*</span></span>
                        </label>
                        <select
                            wire:model="supplier_product_id"
                            wire:change="onSupplierProductChange"
                            class="select select-bordered @error('supplier_product_id') select-error @enderror"
                        >
                            <option value="">Pilih Produk Supplier</option>
                            @foreach($this->supplierProducts as $sp)
                                <option value="{{ $sp->id }}">
                                    {{ $sp->product_name }} - Rp {{ number_format($sp->buyer_price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_product_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Name -->
                    <div class="form-control md:col-span-2">
                        <label class="label">
                            <span class="label-text font-semibold">Nama Produk <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            class="input input-bordered @error('name') input-error @enderror"
                            placeholder="e.g., 100 Diamonds"
                        >
                        @error('name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Auto Price Toggle -->
                    <div class="form-control md:col-span-2">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" wire:model.live="is_auto_price" wire:change="updateSellingPrice" class="toggle toggle-primary" />
                            <div>
                                <span class="label-text font-semibold">Auto Price</span>
                                <p class="text-xs text-base-content/60">Harga jual otomatis = Harga beli + Margin</p>
                            </div>
                        </label>
                    </div>

                    <!-- Margin (if auto price) -->
                    @if($is_auto_price)
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Margin <span class="text-error">*</span></span>
                            </label>
                            <input
                                type="number"
                                wire:model.live="margin"
                                wire:change="updateSellingPrice"
                                class="input input-bordered @error('margin') input-error @enderror"
                                placeholder="1000"
                                step="100"
                            >
                            @error('margin') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Selling Price -->
                    <div class="form-control {{ $is_auto_price ? '' : 'md:col-span-2' }}">
                        <label class="label">
                            <span class="label-text font-semibold">Harga Jual <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="number"
                            wire:model="selling_price"
                            class="input input-bordered @error('selling_price') input-error @enderror"
                            placeholder="10000"
                            step="100"
                            {{ $is_auto_price ? 'readonly' : '' }}
                        >
                        @error('selling_price') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        @if($is_auto_price)
                            <label class="label">
                                <span class="label-text-alt text-info">Dihitung otomatis</span>
                            </label>
                        @endif
                    </div>

                    <!-- Description -->
                    <div class="form-control md:col-span-2">
                        <label class="label">
                            <span class="label-text font-semibold">Deskripsi</span>
                        </label>
                        <textarea
                            wire:model="description"
                            class="textarea textarea-bordered @error('description') textarea-error @enderror"
                            rows="2"
                            placeholder="Keterangan tambahan produk..."
                        ></textarea>
                        @error('description') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Status</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" wire:model="is_active" class="toggle toggle-success" checked />
                            <span class="label-text">{{ $is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </label>
                    </div>
                </div>

                @if($supplier_product_id)
                    @php
                        $sp = $this->supplierProducts->firstWhere('id', $supplier_product_id);
                    @endphp
                    @if($sp)
                        <div class="alert alert-info mt-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="text-sm">
                                <p><strong>Harga Beli:</strong> Rp {{ number_format($sp->buyer_price, 0, ',', '.') }}</p>
                                @if($selling_price > 0)
                                    <p><strong>Harga Jual:</strong> Rp {{ number_format($selling_price, 0, ',', '.') }}</p>
                                    <p><strong>Keuntungan:</strong> Rp {{ number_format($selling_price - $sp->buyer_price, 0, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <div class="divider"></div>

                <div class="flex gap-2 justify-end">
                    <a href="{{ route('admin.products.index') }}" wire:navigate class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading.remove>Simpan</span>
                        <span wire:loading class="loading loading-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
