<?php

use App\Models\Product;
use App\Models\Game;
use App\Models\SupplierProduct;
use Livewire\Attributes\{Layout, Title};
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

$games = computed(fn() => Game::where('is_active', true)->orderBy('name')->get());

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
            <flux:subheading>Hubungkan produk supplier dengan game</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Game -->
                <flux:field>
                    <flux:label>Game <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model="game_id">
                        <option value="">Pilih Game</option>
                        @foreach ($this->games as $game)
                            <option value="{{ $game->id }}">{{ $game->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="game_id" />
                </flux:field>

                <!-- Supplier Product -->
                <flux:field>
                    <flux:label>Produk Supplier <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model="supplier_product_id" wire:change="onSupplierProductChange">
                        <option value="">Pilih Produk Supplier</option>
                        @foreach ($this->supplierProducts as $sp)
                            <option value="{{ $sp->id }}">
                                {{ $sp->product_name }} - Rp {{ number_format($sp->buyer_price, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="supplier_product_id" />
                </flux:field>

                <!-- Name -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Nama Produk <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="e.g., 100 Diamonds" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <!-- Auto Price Toggle -->
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

                <!-- Selling Price -->
                <div class="{{ $is_auto_price ? 'self-start' : 'md:col-span-2 self-start' }}">
                    <flux:field>
                        <flux:label>Harga Jual <span class="text-red-600">*</span></flux:label>
                        <flux:input type="number" wire:model="selling_price" placeholder="10000" step="100"
                            :readonly="$is_auto_price" />
                        <flux:error name="selling_price" />
                        @if ($is_auto_price)
                            <flux:description>Dihitung otomatis</flux:description>
                        @endif
                    </flux:field>
                </div>
                <!-- Margin (if auto price) -->

                <!-- Description -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" rows="2"
                            placeholder="Keterangan tambahan produk..." />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <!-- Active Status -->
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model="is_active" />
                        <flux:label>{{ $is_active ? 'Aktif' : 'Nonaktif' }}</flux:label>
                    </div>
                </flux:field>
            </div>

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
