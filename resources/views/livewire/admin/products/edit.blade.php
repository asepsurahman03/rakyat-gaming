<?php

use App\Models\Product;
use App\Models\Game;
use App\Models\ProductGroup;
use App\Models\SupplierProduct;
use function Livewire\Volt\{state, mount, computed, rules, Layout, Title};

Layout('components.layouts.app');
Title('Edit Produk');

state([
    'product',
    'game_id' => '',
    'product_group_id' => null,
    'sort_order' => 0,
    'product_type' => 'digiflazz',
    'supplier_product_id' => '',
    'stock' => null,
    'name' => '',
    'selling_price' => 0,
    'is_auto_price' => false,
    'margin' => 1000,
    'margin_percentage' => 0,
    'use_percentage' => false,
    'is_active' => true,
    'description' => '',
]);

mount(function (Product $product) {
    $this->product = $product;
    $this->game_id = $product->game_id;
    $this->product_group_id = $product->product_group_id;
    $this->sort_order = $product->sort_order ?? 0;
    $this->product_type = $product->product_type ?? 'digiflazz';
    $this->supplier_product_id = $product->supplier_product_id;
    $this->stock = $product->stock;
    $this->name = $product->name;
    $this->selling_price = $product->selling_price;
    $this->is_auto_price = $product->is_auto_price;
    $this->margin = $product->margin ?? 1000;
    $this->is_active = $product->is_active;
    $this->description = $product->description;
});

rules(function () {
    return [
        'game_id' => 'required|exists:games,id',
        'product_group_id' => 'nullable|exists:product_groups,id',
        'sort_order' => 'integer|min:0',
        'product_type' => 'required|string|max:50',
        'supplier_product_id' => [
            'required_unless:product_type,manual',
            'nullable',
            'exists:supplier_products,id',
            function ($attribute, $value, $fail) {
                if ($this->product_type !== 'manual' && $value && $this->game_id) {
                    $exists = Product::where('game_id', $this->game_id)
                        ->where('supplier_product_id', $value)
                        ->where('id', '!=', $this->product->id)
                        ->exists();
                    if ($exists) {
                        $fail('Produk Digiflazz ini sudah ada untuk game yang dipilih.');
                    }
                }
            },
        ],
        'stock' => 'required_if:product_type,manual|nullable|integer|min:0',
        'name' => 'required|string|max:255',
        'selling_price' => 'required|numeric|min:0',
        'is_auto_price' => 'boolean',
        'margin' => 'required_if:is_auto_price,true|numeric|min:0',
        'is_active' => 'boolean',
        'description' => 'nullable|string',
    ];
});

$games = computed(fn() => Game::where('is_active', true)->orderBy('name')->get());

$productGroups = computed(function () {
    if (!$this->game_id) {
        return collect([]);
    }
    return ProductGroup::where('game_id', $this->game_id)
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();
});

$supplierProducts = computed(fn() => SupplierProduct::with('supplier')->where('status', 'active')->orderBy('product_name')->get());

$updateSellingPrice = function () {
    if ($this->is_auto_price && $this->supplier_product_id) {
        $supplierProduct = SupplierProduct::find($this->supplier_product_id);
        if ($supplierProduct) {
            $this->selling_price = $supplierProduct->buyer_price + $this->margin;
        }
    }
};

$calculateMarginFromPercentage = function () {
    if ($this->use_percentage && $this->supplier_product_id && $this->margin_percentage > 0) {
        $supplierProduct = SupplierProduct::find($this->supplier_product_id);
        if ($supplierProduct) {
            $this->margin = ($supplierProduct->buyer_price * $this->margin_percentage) / 100;
            $this->updateSellingPrice();
        }
    }
};

$calculatePercentageFromMargin = function () {
    if (!$this->use_percentage && $this->supplier_product_id && $this->margin > 0) {
        $supplierProduct = SupplierProduct::find($this->supplier_product_id);
        if ($supplierProduct && $supplierProduct->buyer_price > 0) {
            $this->margin_percentage = ($this->margin / $supplierProduct->buyer_price) * 100;
        }
    }
};

$onSupplierProductChange = function () {
    $supplierProduct = SupplierProduct::find($this->supplier_product_id);
    if ($supplierProduct) {
        // Calculate price if auto-price is enabled
        if ($this->is_auto_price) {
            $this->selling_price = $supplierProduct->buyer_price + $this->margin;
        }
    }
};

$save = function () {
    // Set supplier_product_id to null if empty (for manual products)
    if ($this->product_type === 'manual') {
        $this->supplier_product_id = null;
    }

    // Set stock to null if empty (for digiflazz products)
    if ($this->product_type === 'digiflazz') {
        $this->stock = null;
    }

    $validated = $this->validate();

    $this->product->update($validated);

    $this->redirect(route('admin.products.index'), navigate: true);
    Flux::toast(heading: 'Produk berhasil diperbarui!', text: 'Perubahan pada produk telah disimpan ke sistem.', variant: 'success', duration: 3000, position: 'top-right');
};

?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.products.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Edit Produk</flux:heading>
            <flux:subheading>Edit informasi produk dari supplier atau stok manual sendiri</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Game -->
                <flux:field>
                    <flux:label>Game <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model.live="game_id">
                        <option value="">Pilih Game</option>
                        @foreach($this->games as $game)
                            <option value="{{ $game->id }}">{{ $game->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="game_id" />
                </flux:field>

                <!-- Product Group -->
                <flux:field>
                    <flux:label>Product Group</flux:label>
                    <flux:select wire:model="product_group_id" :disabled="!$game_id">
                        <option value="">-- Tidak ada group --</option>
                        @foreach ($this->productGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:description>Pilih game terlebih dahulu untuk melihat group yang tersedia</flux:description>
                    <flux:error name="product_group_id" />
                </flux:field>

                <!-- Product Type / Supplier Selection -->
                <flux:field>
                    <flux:label>Tipe Produk <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model.live="product_type">
                        <optgroup label="Supplier Products">
                            <option value="digiflazz">⚡ Digiflazz - Produk Digital & Pulsa</option>
                            <option value="onoystore" disabled>⭐ OnoyStore - Game Voucher (Coming Soon)</option>
                            <option value="byxstore" disabled>🛒 ByxStore - Top-up & Voucher (Coming Soon)</option>
                        </optgroup>
                        <optgroup label="Manual">
                            <option value="manual">📦 Produk Manual - Stok Sendiri</option>
                        </optgroup>
                    </flux:select>
                    <flux:description>
                        @if($product_type === 'manual')
                            Produk dengan stok yang dikelola sendiri
                        @else
                            Produk dari supplier {{ ucfirst($product_type) }}
                        @endif
                    </flux:description>
                    <flux:error name="product_type" />
                </flux:field>

                <!-- Supplier Product (Only for Supplier Products, not Manual) -->
                @if ($product_type !== 'manual')
                <flux:field>
                    <flux:label>Produk Supplier <span class="text-red-600">*</span></flux:label>
                    <flux:select variant="listbox" searchable wire:model="supplier_product_id" wire:change="onSupplierProductChange">
                        @foreach ($this->supplierProducts->where('supplier.code', $product_type) as $sp)
                            <flux:select.option value="{{ $sp->id }}">
                                {{ $sp->product_name }} - Rp {{ number_format($sp->buyer_price, 0, ',', '.') }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="supplier_product_id" />
                </flux:field>
                @endif

                <!-- Stock (Only for Manual Products) -->
                @if ($product_type === 'manual')
                <flux:field>
                    <flux:label>Stok <span class="text-red-600">*</span></flux:label>
                    <flux:input type="number" wire:model="stock" placeholder="100" min="0" />
                    <flux:error name="stock" />
                    <flux:description>Jumlah stok yang tersedia untuk produk manual</flux:description>
                </flux:field>
                @endif

                <!-- Name -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Nama Produk <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="e.g., 100 Diamonds" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <!-- Auto Price Toggle (For all suppliers except manual) -->
                @if ($product_type !== 'manual')
                <div class="md:col-span-2">
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:checkbox wire:model.live="is_auto_price" wire:change="updateSellingPrice" />
                            <flux:label>Harga jual otomatis = Harga beli + Margin</flux:label>
                        </div>
                    </flux:field>
                </div>

                @if ($is_auto_price)
                    <div class="md:col-span-2 space-y-4">
                        <!-- Toggle between Nominal and Percentage -->
                        <flux:field>
                            <div class="flex items-center gap-4">
                                <flux:radio.group wire:model.live="use_percentage">
                                    <flux:radio value="0" label="Margin Nominal (Rp)" />
                                    <flux:radio value="1" label="Margin Persentase (%)" />
                                </flux:radio.group>
                            </div>
                        </flux:field>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if (!$use_percentage)
                                <!-- Margin Nominal -->
                                <flux:field>
                                    <flux:label>Margin (Rp) <span class="text-red-600">*</span></flux:label>
                                    <flux:input type="number" wire:model.live="margin"
                                        wire:change="updateSellingPrice,calculatePercentageFromMargin"
                                        placeholder="1000" step="100" />
                                    <flux:description>Masukkan margin dalam rupiah</flux:description>
                                    <flux:error name="margin" />
                                </flux:field>

                                @if ($supplier_product_id && $margin > 0)
                                    @php
                                        $sp = $this->supplierProducts->firstWhere('id', $supplier_product_id);
                                        $calculatedPercentage = $sp && $sp->buyer_price > 0 ? ($margin / $sp->buyer_price) * 100 : 0;
                                    @endphp
                                    <flux:field>
                                        <flux:label>Persentase</flux:label>
                                        <flux:input type="text" value="{{ number_format($calculatedPercentage, 2) }}%" readonly />
                                        <flux:description>Otomatis dihitung dari margin nominal</flux:description>
                                    </flux:field>
                                @endif
                            @else
                                <!-- Margin Percentage -->
                                <flux:field>
                                    <flux:label>Margin (%) <span class="text-red-600">*</span></flux:label>
                                    <flux:input type="number" wire:model.live="margin_percentage"
                                        wire:change="calculateMarginFromPercentage"
                                        placeholder="10" step="0.5" min="0" max="100" />
                                    <flux:description>Masukkan persentase margin (contoh: 10 untuk 10%)</flux:description>
                                    <flux:error name="margin_percentage" />
                                </flux:field>

                                @if ($supplier_product_id && $margin_percentage > 0)
                                    @php
                                        $sp = $this->supplierProducts->firstWhere('id', $supplier_product_id);
                                        $calculatedMargin = $sp ? ($sp->buyer_price * $margin_percentage) / 100 : 0;
                                    @endphp
                                    <flux:field>
                                        <flux:label>Margin Nominal</flux:label>
                                        <flux:input type="text" value="Rp {{ number_format($calculatedMargin, 0, ',', '.') }}" readonly />
                                        <flux:description>Otomatis dihitung dari persentase</flux:description>
                                    </flux:field>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
                @endif

                <!-- Selling Price -->
                <div class="{{ ($product_type !== 'manual' && $is_auto_price) ? 'self-start' : 'md:col-span-2 self-start' }}">
                    <flux:field>
                        <flux:label>Harga Jual <span class="text-red-600">*</span></flux:label>
                        <flux:input type="number" wire:model="selling_price" placeholder="10000" step="100"
                            :readonly="$product_type !== 'manual' && $is_auto_price" />
                        <flux:error name="selling_price" />
                        @if ($product_type !== 'manual' && $is_auto_price)
                            <flux:description>Dihitung otomatis dari harga beli + margin</flux:description>
                        @endif
                    </flux:field>
                </div>
                <!-- Margin (if auto price) -->

                <!-- Description -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" rows="2" placeholder="Keterangan tambahan produk..." />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <!-- Sort Order -->
                <flux:field>
                    <flux:label>Urutan Tampilan</flux:label>
                    <flux:input type="number" wire:model="sort_order" placeholder="0" min="0" />
                    <flux:description>Semakin kecil angka, semakin atas posisinya</flux:description>
                    <flux:error name="sort_order" />
                </flux:field>

                <!-- Active Status -->
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model="is_active" />
                        <flux:label>{{ $is_active ? 'Aktif' : 'Nonaktif' }}</flux:label>
                    </div>
                </flux:field>
            </div>

            @if ($product_type === 'digiflazz' && $supplier_product_id)
                @php
                    $sp = $this->supplierProducts->firstWhere('id', $supplier_product_id);
                @endphp
                @if ($sp)
                    <flux:callout icon="bell" variant="info" icon="information-circle">
                        <flux:callout.heading class="text-sm space-y-1">
                            <p><strong>Harga Beli:</strong> Rp {{ number_format($sp->buyer_price, 0, ',', '.') }}</p>
                            @if ($selling_price > 0)
                                <p><strong>Harga Jual:</strong> Rp {{ number_format($selling_price, 0, ',', '.') }}</p>
                                <p><strong>Keuntungan:</strong> Rp
                                    {{ number_format($selling_price - $sp->buyer_price, 0, ',', '.') }}</p>
                            @endif
                        </flux:callout.heading>

                        <x-slot name="controls">
                            <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
                        </x-slot>
                    </flux:callout>
                @endif
            @endif

            <flux:separator />

            <div class="flex gap-2 justify-end">
                <flux:button wire:navigate href="{{ route('admin.products.index') }}" variant="ghost">Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove>Simpan Perubahan</span>
                    <flux:icon.arrow-path wire:loading class="animate-spin" />
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
