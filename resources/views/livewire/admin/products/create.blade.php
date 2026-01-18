<?php

use App\Models\Product;
use App\Models\Game;
use App\Models\ProductGroup;
use App\Models\SupplierProduct;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title};
use function Livewire\Volt\{state, computed, rules, uses};

uses([WithFileUploads::class]);

state([
    'product_type' => 'digiflazz',
    'game_id' => '',
    'product_group_id' => null,
    'sort_order' => 0,
    'supplier_product_id' => '',
    'name' => '',
    'icon' => null,
    'selling_price' => 0,
    'stock' => null,
    'is_auto_price' => false,
    'margin' => 1000,
    'is_active' => true,
    'description' => '',
]);

rules(function () {
    return [
        'product_type' => 'required|string|max:50',
        'game_id' => 'required|exists:games,id',
        'product_group_id' => 'nullable|exists:product_groups,id',
        'sort_order' => 'integer|min:0',
        'supplier_product_id' => [
            'required_unless:product_type,manual',
            'nullable',
            'exists:supplier_products,id',
            function ($attribute, $value, $fail) {
                if ($this->product_type !== 'manual' && $value && $this->game_id) {
                    $exists = Product::where('game_id', $this->game_id)
                        ->where('supplier_product_id', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Produk supplier ini sudah ada untuk game yang dipilih.');
                    }
                }
            },
        ],
        'name' => 'required|string|max:255',
        'icon' => 'nullable|image|max:2048',
        'selling_price' => 'required|numeric|min:0',
        'stock' => 'required_if:product_type,manual|nullable|integer|min:0',
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

$availableSuppliers = computed(function () {
    return \App\Models\Supplier::whereHas('supplierProducts', function ($query) {
        $query->where('status', 'active');
    })
    ->where('is_active', true)
    ->orderBy('name')
    ->get();
});

$updateSellingPrice = function () {
    if ($this->is_auto_price && $this->supplier_product_id) {
        $supplierProduct = SupplierProduct::find($this->supplier_product_id);
        if ($supplierProduct) {
            $margin = is_numeric($this->margin) ? (float) $this->margin : 0;
            $this->selling_price = $supplierProduct->buyer_price + $margin;
        }
    }
};

$updated = function ($property) {
    if ($property === 'margin' && $this->is_auto_price) {
        $this->updateSellingPrice();
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
    // Set supplier_product_id to null if empty (for manual products)
    if ($this->product_type === 'manual') {
        $this->supplier_product_id = null;
        // Stock is required for manual products
    } else {
        // Set stock to null for supplier products
        $this->stock = null;
    }

    $validated = $this->validate();

    // Handle icon upload
    if ($this->icon) {
        $validated['icon'] = $this->icon->store('products', 'public');
    }

    Product::create($validated);

    $this->redirect(route('admin.products.index'), navigate: true);
    Flux::toast(heading: 'Produk berhasil ditambahkan!', text: 'Produk baru telah disimpan ke sistem.', variant: 'success', duration: 3000, position: 'top-right');
};

?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.products.index') }}" variant="ghost" size="sm"
            icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Tambah Produk Baru</flux:heading>
            <flux:subheading>Tambahkan produk dari supplier atau stok manual sendiri</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <!-- Product Type / Supplier Selection -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Pilih Supplier <span class="text-red-600">*</span></flux:label>
                        <flux:select wire:model.live="product_type">
                            <option value="manual">Manual (Stok sendiri)</option>
                            @foreach ($this->availableSuppliers as $supplier)
                                <option value="{{ $supplier->code }}">{{ $supplier->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:description>
                            @if($product_type === 'manual')
                                Produk dengan stok yang dikelola sendiri (tidak melalui supplier)
                            @else
                                Produk dari supplier {{ ucfirst($product_type) }} - transaksi otomatis melalui API
                            @endif
                        </flux:description>
                        <flux:error name="product_type" />
                    </flux:field>
                </div>

                <!-- Game -->
                <flux:field>
                    <flux:label>Game <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model.live="game_id">
                        <option value="">Pilih Game</option>
                        @foreach ($this->games as $game)
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

                <!-- Supplier Product (Only for Supplier Products, not Manual) -->
                @if($product_type !== 'manual')
                    <flux:field class="md:col-span-2">
                        <flux:label>Produk Supplier <span class="text-red-600">*</span></flux:label>
                        <flux:select variant="listbox" searchable wire:model="supplier_product_id" wire:change="onSupplierProductChange">
                            <option value="">Pilih Produk dari {{ ucfirst($product_type) }}</option>
                            @foreach ($this->supplierProducts->where('supplier.code', $product_type) as $sp)
                                <flux:select.option value="{{ $sp->id }}">
                                    <div class="flex justify-between items-center">
                                        <span>{{ $sp->product_name }}</span>
                                        <span class="text-sm text-zinc-500">{{ ' - Rp ' . number_format($sp->buyer_price, 0, ',', '.') . ' - ' . $sp->brand . ' - ' . $sp->category }}</span>
                                    </div>
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:description>
                            Pilih produk dari katalog {{ ucfirst($product_type) }}
                            @if($this->supplierProducts->where('supplier.code', $product_type)->count() === 0)
                                <span class="text-orange-600">- Tidak ada produk tersedia. Lakukan sync produk terlebih dahulu.</span>
                            @endif
                        </flux:description>
                        <flux:error name="supplier_product_id" />
                    </flux:field>
                @else
                    <!-- Stock (Only for Manual) -->
                    <flux:field>
                        <flux:label>Stok <span class="text-red-600">*</span></flux:label>
                        <flux:input type="number" wire:model="stock" placeholder="0" />
                        <flux:description>Jumlah stok produk manual</flux:description>
                        <flux:error name="stock" />
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

                <!-- Icon -->
                <div class="md:col-span-2">
                    <flux:field x-data="{ iconPreview: null }">
                        <flux:label>Icon Produk (Opsional)</flux:label>
                        <input type="file" wire:model="icon" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => iconPreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" />
                        <flux:error name="icon" />
                        <flux:description>Icon untuk menandai produk (DM/Voucher). Max 2MB, format: JPG, PNG</flux:description>
                        <div x-show="iconPreview" class="mt-2">
                            <img :src="iconPreview" class="w-16 h-16 rounded object-cover" alt="Preview">
                        </div>
                    </flux:field>
                </div>

                <!-- Auto Price Toggle (For all suppliers except manual) -->
                @if($product_type !== 'manual')
                    <div class="md:col-span-2">
                        <flux:field>
                            <div class="flex items-center gap-3">
                                <flux:checkbox wire:model.live="is_auto_price" wire:change="updateSellingPrice" />
                                <flux:label>Harga jual otomatis = Harga beli + Margin</flux:label>
                            </div>
                        </flux:field>
                    </div>

                    @if ($is_auto_price)
                        <div class="md:col-span-2">
                             <!-- Margin -->
                            <flux:field>
                                <flux:label>Margin (Rp) <span class="text-red-600">*</span></flux:label>
                                <flux:input type="number" wire:model.live="margin"
                                    placeholder="1000" step="100" />
                                <flux:description>Masukkan margin dalam rupiah</flux:description>
                                <flux:error name="margin" />
                            </flux:field>
                        </div>
                    @endif
                @endif

                <!-- Selling Price -->
                <div class="{{ $is_auto_price && $product_type !== 'manual' ? 'self-start' : 'md:col-span-2 self-start' }}">
                    <flux:field>
                        <flux:label>Harga Jual <span class="text-red-600">*</span></flux:label>
                        <flux:input type="number" wire:model="selling_price" placeholder="10000" step="100"
                            :readonly="$is_auto_price && $product_type !== 'manual'" />
                        <flux:error name="selling_price" />
                        @if ($is_auto_price && $product_type !== 'manual')
                            <flux:description>Dihitung otomatis dari harga beli + margin</flux:description>
                        @endif
                    </flux:field>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" rows="2"
                            placeholder="Keterangan tambahan produk..." />
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
            </div>
            <flux:field>
                <div class="flex items-center gap-3">
                    <flux:switch wire:model="is_active" />
                    <flux:label>{{ $is_active ? 'Aktif' : 'Nonaktif' }}</flux:label>
                </div>
            </flux:field>

            @if ($supplier_product_id)
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
                    <span wire:loading.remove>Simpan</span>
                    <flux:icon.arrow-path wire:loading class="animate-spin" />
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
