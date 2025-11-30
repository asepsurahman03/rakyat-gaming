<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\FieldForm;
use App\Models\FlazzSell;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest'), Title('Game Detail')] class extends Component {
    public $slug;
    public $game;
    public $selectedProduct = null;
    public $fields = [];
    public $products = [];
    public $flazzSellProducts = [];

    public function mount()
    {
        $this->game = Game::with(['products.supplierProduct', 'fieldForms'])
            ->where('slug', $this->slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->fields = $this->game->fieldForms->pluck('name')->mapWithKeys(fn($name) => [$name => ''])->toArray();

        // Get FlazzSell products (active flash sales from event system)
        $activeFlazzSellProductIds = \App\Models\FlazzSellProduct::whereHas('flazzSell', function ($query) {
            $query->where('is_active', true)
                ->where('start_at', '<=', now())
                ->where('end_at', '>=', now());
        })
        ->where(function ($q) {
            $q->whereNull('quota')
                ->orWhereRaw('sold_count < quota');
        })
        ->pluck('product_id')
        ->toArray();

        $this->flazzSellProducts = Product::whereIn('id', $activeFlazzSellProductIds)
            ->with('supplierProduct')
            ->where('game_id', $this->game->id)
            ->where('is_active', true)
            ->orderBy('selling_price', 'asc')
            ->get();

        // Get regular products (excluding FlazzSell products)
        $this->products = $this->game->products()
            ->with('supplierProduct')
            ->where('is_active', true)
            ->whereNotIn('id', $activeFlazzSellProductIds)
            ->orderBy('selling_price', 'asc')
            ->get();
    }

    public function selectProduct($productId)
    {
        $this->selectedProduct = $productId;
    }

    public function proceedToCheckout()
    {
        if (!$this->selectedProduct) {
            $this->dispatch('error', message: 'Silakan pilih produk terlebih dahulu');
            return;
        }

        // Validate required fields
        foreach ($this->game->fieldForms as $fieldForm) {
            if ($fieldForm->is_required && empty($this->fields[$fieldForm->name])) {
                $this->dispatch('error', message: "Field {$fieldForm->label} harus diisi");
                return;
            }
        }

        // Store in session and redirect to checkout
        session([
            'checkout_data' => [
                'game_id' => $this->game->id,
                'product_id' => $this->selectedProduct,
                'fields' => $this->fields,
            ],
        ]);

        $this->redirect(route('checkout'), navigate: true);
    }
}; ?>

<div>
    <div class="px-4 md:px-6 py-10 md:py-20">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Game Header -->
            <flux:card class="mb-6">
                <div class="flex items-start gap-6">
                    <img src="{{ asset('storage/' . $game->image) }}" alt="{{ $game->image }}"
                        class="w-32 h-32 rounded-lg object-cover">
                    <div class="flex-1">
                        <flux:heading size="xl">{{ $game->name }}</flux:heading>
                        <flux:subheading class="mt-2">{{ $game->description }}</flux:subheading>
                        <flux:badge class="mt-3" color="indigo">{{ $game->provider }}</flux:badge>
                    </div>
                </div>
            </flux:card>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Product Selection -->
                <div class="lg:col-span-2">
                    <flux:card class="sticky top-4 mb-4">
                        <flux:heading size="lg" class="mb-4">Data Player</flux:heading>

                        @if ($game->fieldForms->isEmpty())
                            <flux:callout variant="info" icon="information-circle" class="mb-4">
                                Tidak ada data yang perlu diisi
                            </flux:callout>
                        @else
                            <div class="grid md:grid-cols-2 gap-4">
                                @foreach ($game->fieldForms as $fieldForm)
                                    <div class="form-control mb-4">
                                        @if ($fieldForm->type === 'textarea')
                                            <flux:textarea wire:model="fields.{{ $fieldForm->name }}"
                                                placeholder="Masukkan {{ strtolower($fieldForm->label) }}" />
                                        @elseif($fieldForm->type === 'number')
                                            <flux:input type="number" wire:model="fields.{{ $fieldForm->name }}"
                                                placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                                label="{{ $fieldForm->label }}" />
                                        @else
                                            <flux:input type="text" wire:model="fields.{{ $fieldForm->name }}"
                                                placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                                label="{{ $fieldForm->label }}" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </flux:card>

                    <!-- FlazzSell Products Section -->
                    @if ($flazzSellProducts->isNotEmpty())
                        <flux:card class="mb-4 border-2 border-red-500">
                            <div class="flex items-center gap-2 mb-4">
                                <flux:icon.fire class="text-red-500" />
                                <flux:heading size="lg" class="text-red-600">FlazzSell - Diskon Terbatas!</flux:heading>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                @foreach ($flazzSellProducts as $product)
                                    @php
                                        $flazzSellProduct = $product->activeFlazzSellProduct();
                                        $discountedPrice = $flazzSellProduct->getDiscountedPrice();
                                        $flazzSell = $flazzSellProduct->flazzSell;
                                        $timeRemaining = $flazzSell->getTimeRemaining();
                                    @endphp
                                    <div wire:click="selectProduct({{ $product->id }})"
                                        class="relative p-4 rounded-lg bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-2 border-red-300 dark:border-red-700 cursor-pointer hover:shadow-lg transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-red-600' : '' }}">

                                        <!-- Discount Badge -->
                                        <div class="absolute -top-3 -right-3">
                                            <div class="bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                                -{{ number_format($flazzSellProduct->discount_percentage, 0) }}%
                                            </div>
                                        </div>

                                        <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                        @if ($product->description)
                                            <flux:subheading size="sm" class="mt-1">{{ $product->description }}
                                            </flux:subheading>
                                        @endif

                                        <!-- Price Display -->
                                        <div class="mt-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-500 line-through">
                                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="text-2xl font-bold text-red-600">
                                                    Rp {{ number_format($discountedPrice, 0, ',', '.') }}
                                                </span>
                                                @if ($selectedProduct === $product->id)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="text-xs text-green-600 font-semibold mt-1">
                                                Hemat Rp {{ number_format($product->selling_price - $discountedPrice, 0, ',', '.') }}
                                            </div>
                                        </div>

                                        <!-- Countdown Timer -->
                                        <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-800">
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Berakhir dalam:</div>
                                            <div class="flex gap-2 text-center"
                                                x-data="{
                                                    days: {{ $timeRemaining['days'] }},
                                                    hours: {{ $timeRemaining['hours'] }},
                                                    minutes: {{ $timeRemaining['minutes'] }},
                                                    seconds: {{ $timeRemaining['seconds'] }},
                                                    endTime: new Date('{{ $flazzSell->end_at->toIso8601String() }}').getTime()
                                                }"
                                                x-init="setInterval(() => {
                                                    let now = new Date().getTime();
                                                    let distance = endTime - now;

                                                    if (distance < 0) {
                                                        days = hours = minutes = seconds = 0;
                                                        return;
                                                    }

                                                    days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                                    hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                                    seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                                }, 1000)">
                                                <div class="flex-1 bg-white dark:bg-zinc-800 rounded px-2 py-1">
                                                    <div class="text-lg font-bold text-red-600" x-text="days"></div>
                                                    <div class="text-xs text-gray-500">Hari</div>
                                                </div>
                                                <div class="flex-1 bg-white dark:bg-zinc-800 rounded px-2 py-1">
                                                    <div class="text-lg font-bold text-red-600" x-text="hours"></div>
                                                    <div class="text-xs text-gray-500">Jam</div>
                                                </div>
                                                <div class="flex-1 bg-white dark:bg-zinc-800 rounded px-2 py-1">
                                                    <div class="text-lg font-bold text-red-600" x-text="minutes"></div>
                                                    <div class="text-xs text-gray-500">Menit</div>
                                                </div>
                                                <div class="flex-1 bg-white dark:bg-zinc-800 rounded px-2 py-1">
                                                    <div class="text-lg font-bold text-red-600" x-text="seconds"></div>
                                                    <div class="text-xs text-gray-500">Detik</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </flux:card>
                    @endif

                    <!-- Regular Products -->
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Pilih Nominal</flux:heading>

                        @if ($products->isEmpty())
                            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
                                Produk tidak tersedia saat ini
                            </flux:callout>
                        @else
                            <div class="grid md:grid-cols-2 gap-4">
                                @foreach ($products as $product)
                                    <div wire:click="selectProduct({{ $product->id }})"
                                        class="p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-indigo-600' : '' }}">
                                        <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                        @if ($product->description)
                                            <flux:subheading size="sm" class="mt-1">{{ $product->description }}
                                            </flux:subheading>
                                        @endif
                                        <div class="flex items-center justify-between mt-3">
                                            <span class="text-2xl font-bold text-indigo-600">Rp
                                                {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                            @if ($selectedProduct === $product->id)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </flux:card>
                </div>

                <!-- Right: Player Data Form -->
                <div class="lg:col-span-1">
                    <flux:card class="sticky top-4">
                        <flux:heading size="lg" class="mb-4">Detail Pembayaran</flux:heading>

                        @if ($selectedProduct)
                            @php
                                $product = $this->products->firstWhere('id', $selectedProduct) ?? $this->flazzSellProducts->firstWhere('id', $selectedProduct);
                                $displayPrice = $product->selling_price;
                                $isFlazzSell = false;
                                $flazzSellProduct = null;

                                if ($product && $product->hasActiveFlazzSell()) {
                                    $flazzSellProduct = $product->activeFlazzSellProduct();
                                    $displayPrice = $flazzSellProduct->getDiscountedPrice();
                                    $isFlazzSell = true;
                                }
                            @endphp
                            @if ($product)
                                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                                    @if ($isFlazzSell)
                                        <div class="mb-2">
                                            <div class="flex items-center gap-2 text-red-600 text-sm font-semibold mb-1">
                                                <flux:icon.fire class="w-4 h-4" />
                                                <span>FlazzSell Aktif!</span>
                                            </div>
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Harga Normal:</span>
                                                <span class="line-through text-gray-500">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-sm text-green-600 font-semibold">
                                                <span>Diskon:</span>
                                                <span>- Rp {{ number_format($product->selling_price - $displayPrice, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="border-t border-gray-300 dark:border-gray-600 my-2"></div>
                                        </div>
                                    @endif
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm">Total Pembayaran:</span>
                                        <span class="text-xl font-bold {{ $isFlazzSell ? 'text-red-600' : 'text-yellow-400' }}">
                                            Rp {{ number_format($displayPrice, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <button wire:click="proceedToCheckout" class="bg-yellow-400 text-white w-full py-3 rounded-lg mt-6 hover:bg-yellow-400-focus transition-all"
                            {{ !$selectedProduct || $this->products->isEmpty() ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="proceedToCheckout">Lanjut Pembayaran</span>
                            <span wire:loading wire:target="proceedToCheckout" class="loading loading-spinner"></span>
                        </button>
                    </flux:card>
                </div>
            </div>
        </div>
    </div>
</div>
