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
        'product_type' => 'required|in:digiflazz,manual',
        'supplier_product_id' => [
            'required_if:product_type,digiflazz',
            'nullable',
            'exists:supplier_products,id',
            function ($attribute, $value, $fail) {
                if ($this->product_type === 'digiflazz' && $value && $this->game_id) {
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

                <!-- Product Type -->
                <flux:field>
                    <flux:label>Tipe Produk <span class="text-red-600">*</span></flux:label>
                    <flux:radio.group wire:model.live="product_type" variant="segmented">
                        <flux:radio value="digiflazz" label="Produk Digiflazz" />
                        <flux:radio value="manual" label="Produk Manual" />
                    </flux:radio.group>
                    <flux:error name="product_type" />
                </flux:field>

                <!-- Supplier Product (Only for Digiflazz) -->
                @if ($product_type === 'digiflazz')
                <flux:field>
                    <flux:label>Produk Supplier <span class="text-red-600">*</span></flux:label>
                    <flux:select variant="listbox" searchable wire:model="supplier_product_id" wire:change="onSupplierProductChange">
                        @foreach ($this->supplierProducts as $sp)
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

                <!-- Auto Price Toggle (Only for Digiflazz) -->
                @if ($product_type === 'digiflazz')
                <div class="md:col-span-2">
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:checkbox wire:model.live="is_auto_price" wire:change="updateSellingPrice" />
                            <flux:label>Harga jual otomatis = Harga beli + Margin</flux:label>
                        </div>
                    </flux:field>
                </div>

                @if ($is_auto_price)
                    <div class="self-start">
                        <flux:field>
                            <flux:label>Margin <span class="text-red-600">*</span></flux:label>
                            <flux:input type="number" wire:model.live="margin" wire:change="updateSellingPrice"
                                placeholder="1000" step="100" />
                            <flux:error name="margin" />
                        </flux:field>
                    </div>
                @endif
                @endif

                <!-- Selling Price -->
                <div class="{{ ($product_type === 'digiflazz' && $is_auto_price) ? 'self-start' : 'md:col-span-2 self-start' }}">
                    <flux:field>
                        <flux:label>Harga Jual <span class="text-red-600">*</span></flux:label>
                        <flux:input type="number" wire:model="selling_price" placeholder="10000" step="100"
                            :readonly="$product_type === 'digiflazz' && $is_auto_price" />
                        <flux:error name="selling_price" />
                        @if ($product_type === 'digiflazz' && $is_auto_price)
                            <flux:description>Dihitung otomatis</flux:description>
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
