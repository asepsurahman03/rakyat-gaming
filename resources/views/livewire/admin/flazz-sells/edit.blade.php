<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\FlazzSell;
use App\Models\Product;
use App\Models\Game;
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Edit FlazzSell')] class extends Component {
    public FlazzSell $flazzSell;
    public $game_id = '';
    public $product_id = '';
    public $discount_percentage = '';
    public $start_at = '';
    public $end_at = '';
    public $is_active = true;
    public $quota = null;

    public function mount(FlazzSell $flazzSell)
    {
        $this->flazzSell = $flazzSell;
        $this->game_id = $flazzSell->product->game_id;
        $this->product_id = $flazzSell->product_id;
        $this->discount_percentage = $flazzSell->discount_percentage;
        $this->start_at = $flazzSell->start_at->format('Y-m-d\TH:i');
        $this->end_at = $flazzSell->end_at->format('Y-m-d\TH:i');
        $this->is_active = $flazzSell->is_active;
        $this->quota = $flazzSell->quota;
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
            'selectedProduct' => $this->product_id
                ? Product::find($this->product_id)
                : null,
        ];
    }

    public function updatedGameId()
    {
        $this->product_id = '';
    }

    public function save()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'discount_percentage' => 'required|numeric|min:1|max:99',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'quota' => 'nullable|integer|min:1',
        ], [
            'product_id.required' => 'Product harus dipilih',
            'discount_percentage.required' => 'Persentase diskon harus diisi',
            'discount_percentage.min' => 'Persentase diskon minimal 1%',
            'discount_percentage.max' => 'Persentase diskon maksimal 99%',
            'start_at.required' => 'Tanggal mulai harus diisi',
            'end_at.required' => 'Tanggal berakhir harus diisi',
            'end_at.after' => 'Tanggal berakhir harus setelah tanggal mulai',
            'quota.integer' => 'Quota harus berupa angka',
            'quota.min' => 'Quota minimal 1',
        ]);

        $this->flazzSell->update([
            'product_id' => $this->product_id,
            'discount_percentage' => $this->discount_percentage,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'is_active' => $this->is_active,
            'quota' => $this->quota,
        ]);

        Flux::toast('FlazzSell berhasil diupdate!');
        return redirect()->route('admin.flazz-sells.index');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.flazz-sells.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Edit FlazzSell</flux:heading>
            <flux:subheading>Update pengaturan flash sale produk</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Game Selection -->
                <div>
                    <flux:field>
                        <flux:label>Game <span class="text-red-600">*</span></flux:label>
                        <flux:select wire:model.live="game_id" placeholder="Pilih game...">
                            @foreach ($games as $game)
                                <option value="{{ $game->id }}">{{ $game->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="game_id" />
                    </flux:field>
                </div>

                <!-- Product Selection -->
                <div>
                    <flux:field>
                        <flux:label>Product <span class="text-red-600">*</span></flux:label>
                        <flux:select
                            wire:model.live="product_id"
                            placeholder="Pilih product..."
                            :disabled="!$game_id"
                        >
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} - Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="product_id" />
                    </flux:field>
                </div>

                <!-- Discount Percentage -->
                <div>
                    <flux:field>
                        <flux:label>Persentase Diskon (%) <span class="text-red-600">*</span></flux:label>
                        <flux:input
                            wire:model.live.debounce.300ms="discount_percentage"
                            type="number"
                            min="1"
                            max="99"
                            step="1"
                            placeholder="Masukkan persentase (1-99)"
                        />
                        <flux:error name="discount_percentage" />
                    </flux:field>
                </div>

                <!-- Quota -->
                <div>
                    <flux:field>
                        <flux:label>Quota (Opsional)</flux:label>
                        <flux:input
                            wire:model="quota"
                            type="number"
                            min="1"
                            placeholder="Kosongkan jika unlimited"
                        />
                        <flux:error name="quota" />
                        <flux:description>Batasan jumlah penjualan. Kosongkan untuk unlimited.</flux:description>
                    </flux:field>
                </div>
            </div>

                        <!-- Price Preview -->
                        @if ($selectedProduct && $discount_percentage)
                            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Preview Harga:</div>
                                <div class="mt-2 flex items-center gap-4">
                                    <div>
                                        <div class="text-xs text-gray-500">Harga Awal</div>
                                        <div class="text-lg font-medium text-gray-700 line-through dark:text-gray-300">
                                            Rp {{ number_format($selectedProduct->selling_price, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="text-2xl text-gray-400"></div>
                                    <div>
                                        <div class="text-xs text-gray-500">Harga Diskon</div>
                                        <div class="text-2xl font-bold text-red-600">
                                            Rp {{ number_format($selectedProduct->selling_price - ($selectedProduct->selling_price * $discount_percentage / 100), 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Hemat</div>
                                        <div class="text-lg font-semibold text-green-600">
                                            Rp {{ number_format($selectedProduct->selling_price * $discount_percentage / 100, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Start Date -->
                <div>
                    <flux:field>
                        <flux:label>Tanggal Mulai <span class="text-red-600">*</span></flux:label>
                        <flux:input
                            wire:model="start_at"
                            type="datetime-local"
                        />
                        <flux:error name="start_at" />
                    </flux:field>
                </div>

                <!-- End Date -->
                <div>
                    <flux:field>
                        <flux:label>Tanggal Berakhir <span class="text-red-600">*</span></flux:label>
                        <flux:input
                            wire:model="end_at"
                            type="datetime-local"
                        />
                        <flux:error name="end_at" />
                    </flux:field>
                </div>
            </div>

            <!-- Active Status -->
            <div>
                <flux:checkbox wire:model="is_active" label="Aktifkan FlazzSell" />
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <flux:button href="{{ route('admin.flazz-sells.index') }}" wire:navigate variant="ghost">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Update
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
