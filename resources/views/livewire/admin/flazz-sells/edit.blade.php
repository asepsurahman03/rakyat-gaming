<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithFileUploads;
use App\Models\FlazzSell;
use App\Models\FlazzSellProduct;
use App\Models\Product;
use App\Models\Game;
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Edit FlazzSell Event')] class extends Component {
    use WithFileUploads;

    public FlazzSell $flazzSell;

    public $name = '';
    public $description = '';
    public $banner = null;
    public $start_at = '';
    public $end_at = '';
    public $is_active = true;

    // Selected products with their discount and quota
    public $selectedProducts = [];

    // For adding products
    public $game_id = '';
    public $product_id = '';
    public $discount_percentage = '';
    public $quota = null;

    public function mount(FlazzSell $flazzSell)
    {
        $this->flazzSell = $flazzSell->load('flazzSellProducts.product.game');

        $this->name = $flazzSell->name;
        $this->description = $flazzSell->description;
        $this->start_at = $flazzSell->start_at->format('Y-m-d\TH:i');
        $this->end_at = $flazzSell->end_at->format('Y-m-d\TH:i');
        $this->is_active = $flazzSell->is_active;

        // Load existing products
        foreach ($flazzSell->flazzSellProducts as $fsp) {
            $this->selectedProducts[$fsp->product_id] = [
                'product' => $fsp->product,
                'discount_percentage' => $fsp->discount_percentage,
                'quota' => $fsp->quota,
            ];
        }
    }

    public function with(): array
    {
        return [
            'games' => Game::where('is_active', true)->orderBy('name')->get(),
            'products' => $this->game_id
                ? Product::where('game_id', $this->game_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                : collect(),
        ];
    }

    public function updatedGameId()
    {
        $this->product_id = '';
    }

    public function addProduct()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'discount_percentage' => 'required|numeric|min:1|max:99',
            'quota' => 'nullable|integer|min:1',
        ], [
            'product_id.required' => 'Product harus dipilih',
            'discount_percentage.required' => 'Persentase diskon harus diisi',
            'discount_percentage.min' => 'Persentase diskon minimal 1%',
            'discount_percentage.max' => 'Persentase diskon maksimal 99%',
            'quota.integer' => 'Quota harus berupa angka',
            'quota.min' => 'Quota minimal 1',
        ]);

        // Check if product already added
        if (isset($this->selectedProducts[$this->product_id])) {
            Flux::toast('Product sudah ditambahkan!', variant: 'warning');
            return;
        }

        $product = Product::find($this->product_id);

        $this->selectedProducts[$this->product_id] = [
            'product' => $product,
            'discount_percentage' => $this->discount_percentage,
            'quota' => $this->quota,
        ];

        // Reset form
        $this->product_id = '';
        $this->discount_percentage = '';
        $this->quota = null;
        $this->game_id = '';
    }

    public function removeProduct($productId)
    {
        unset($this->selectedProducts[$productId]);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|max:2048',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ], [
            'name.required' => 'Nama event harus diisi',
            'start_at.required' => 'Tanggal mulai harus diisi',
            'end_at.required' => 'Tanggal berakhir harus diisi',
            'end_at.after' => 'Tanggal berakhir harus setelah tanggal mulai',
            'banner.image' => 'Banner harus berupa gambar',
            'banner.max' => 'Banner maksimal 2MB',
        ]);

        if (empty($this->selectedProducts)) {
            Flux::toast('Minimal tambahkan 1 product!', variant: 'warning');
            return;
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'is_active' => $this->is_active,
        ];

        if ($this->banner) {
            // Delete old banner if exists
            if ($this->flazzSell->banner) {
                \Storage::disk('public')->delete($this->flazzSell->banner);
            }
            $data['banner'] = $this->banner->store('flazz-sells', 'public');
        }

        $this->flazzSell->update($data);

        // Delete existing product relations
        $this->flazzSell->flazzSellProducts()->delete();

        // Attach new products
        foreach ($this->selectedProducts as $productId => $data) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $this->flazzSell->id,
                'product_id' => $productId,
                'discount_percentage' => $data['discount_percentage'],
                'quota' => $data['quota'],
            ]);
        }

        Flux::toast('FlazzSell Event berhasil diupdate!');
        return redirect()->route('admin.flazz-sells.index');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.flazz-sells.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Edit FlazzSell Event</flux:heading>
            <flux:subheading>Update event flash sale dengan multiple produk</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <!-- Event Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Event Name -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Nama Event <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="e.g., Flash Sale Akhir Tahun" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" placeholder="Deskripsi event..." rows="3" />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <!-- Banner -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Banner Event</flux:label>
                        <input type="file" wire:model="banner" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" />
                        <flux:error name="banner" />
                        <flux:description>Max 2MB, format: JPG, PNG. Kosongkan jika tidak ingin mengubah.</flux:description>
                    </flux:field>

                    @if ($banner)
                        <div class="mt-2">
                            <div wire:loading wire:target="banner" class="h-32 flex items-center justify-center border border-dashed rounded-lg">
                                <flux:icon.arrow-path class="w-6 h-6 animate-spin" />
                            </div>
                            <div wire:loading.remove wire:target="banner">
                                <img src="{{ $banner->temporaryUrl() }}" class="w-full h-32 object-cover rounded-lg" alt="Preview">
                            </div>
                        </div>
                    @elseif($flazzSell->banner)
                        <div class="mt-2">
                            <img src="{{ Storage::url($flazzSell->banner) }}" class="w-full h-32 object-cover rounded-lg" alt="Current Banner">
                        </div>
                    @endif
                </div>

                <!-- Start Date -->
                <div>
                    <flux:field>
                        <flux:label>Tanggal Mulai <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="start_at" type="datetime-local" />
                        <flux:error name="start_at" />
                    </flux:field>
                </div>

                <!-- End Date -->
                <div>
                    <flux:field>
                        <flux:label>Tanggal Berakhir <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="end_at" type="datetime-local" />
                        <flux:error name="end_at" />
                    </flux:field>
                </div>
            </div>

            <!-- Active Status -->
            <div>
                <flux:checkbox wire:model="is_active" label="Aktifkan Event" />
            </div>

            <flux:separator />

            <!-- Add Products Section -->
            <div class="space-y-4">
                <flux:heading size="lg">Tambah Produk ke Event</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Game Selection -->
                    <div>
                        <flux:field>
                            <flux:label>Game</flux:label>
                            <flux:select wire:model.live="game_id" placeholder="Pilih game...">
                                @foreach ($games as $game)
                                    <option value="{{ $game->id }}">{{ $game->name }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Product Selection -->
                    <div>
                        <flux:field>
                            <flux:label>Product</flux:label>
                            <flux:select wire:model="product_id" placeholder="Pilih product..." :disabled="!$game_id">
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Discount Percentage -->
                    <div>
                        <flux:field>
                            <flux:label>Diskon (%)</flux:label>
                            <flux:input wire:model="discount_percentage" type="number" min="1" max="99" placeholder="50" />
                        </flux:field>
                    </div>

                    <!-- Quota -->
                    <div>
                        <flux:field>
                            <flux:label>Quota</flux:label>
                            <flux:input wire:model="quota" type="number" min="1" placeholder="Unlimited" />
                        </flux:field>
                    </div>
                </div>

                <flux:button type="button" wire:click="addProduct" variant="primary" icon="plus" size="sm">
                    Tambah Product
                </flux:button>
            </div>

            <!-- Selected Products List -->
            @if(count($selectedProducts) > 0)
                <div class="space-y-4">
                    <flux:heading size="lg">Produk Terpilih ({{ count($selectedProducts) }})</flux:heading>

                    <div class="grid gap-4">
                        @foreach($selectedProducts as $productId => $data)
                            <flux:card class="bg-zinc-50 dark:bg-zinc-900">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="flex-1">
                                            <div class="font-semibold">{{ $data['product']->name }}</div>
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $data['product']->game->name }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-zinc-600">Harga Awal</div>
                                            <div class="font-medium line-through">
                                                Rp {{ number_format($data['product']->selling_price, 0, ',', '.') }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <flux:badge color="red">{{ $data['discount_percentage'] }}% OFF</flux:badge>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-zinc-600">Harga Diskon</div>
                                            <div class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($data['product']->selling_price - ($data['product']->selling_price * $data['discount_percentage'] / 100), 0, ',', '.') }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-zinc-600">Quota</div>
                                            <div class="font-medium">
                                                @if($data['quota'])
                                                    {{ number_format($data['quota']) }}
                                                @else
                                                    <span class="text-zinc-500">Unlimited</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <flux:button type="button" wire:click="removeProduct({{ $productId }})" variant="ghost" icon="trash" size="sm" color="red" />
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                </div>
            @else
                <flux:card variant="info">
                    <div class="text-center py-8 text-zinc-500">
                        <flux:icon.shopping-cart class="w-12 h-12 mx-auto mb-2" />
                        <p>Belum ada produk ditambahkan. Tambahkan minimal 1 produk untuk event ini.</p>
                    </div>
                </flux:card>
            @endif

            <flux:separator />

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <flux:button href="{{ route('admin.flazz-sells.index') }}" wire:navigate variant="ghost">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Update Event
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
