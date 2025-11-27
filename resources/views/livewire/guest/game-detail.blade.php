<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\FieldForm;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest'), Title('Game Detail')] class extends Component {
    public $slug;
    public $game;
    public $selectedProduct = null;
    public $fields = [];
    public $products = [];

    public function mount()
    {
        $this->game = Game::with(['products.supplierProduct', 'fieldForms'])
            ->where('slug', $this->slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->fields = $this->game->fieldForms->pluck('name')->mapWithKeys(fn($name) => [$name => ''])->toArray();

        $this->products = $this->game->products()->with('supplierProduct')->where('is_active', true)->orderBy('selling_price', 'asc')->get();
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
    <div class="bg-gradient-to-bl from-[#7E0000] to-[#470000] px-4 md:px-6 py-10 md:py-20">
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
                                $product = $this->products->firstWhere('id', $selectedProduct);
                            @endphp
                            @if ($product)
                                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm">Total Pembayaran:</span>
                                        <span class="text-xl font-bold text-yellow-400">Rp
                                            {{ number_format($product->selling_price, 0, ',', '.') }}</span>
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
