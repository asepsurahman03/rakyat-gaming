<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\FieldForm;
use function Livewire\Volt\{state, mount, computed};

state(['slug', 'game', 'selectedProduct' => null, 'fields' => []]);

mount(function () {
    $this->game = Game::with(['products.supplierProduct', 'fieldForms'])
        ->where('slug', $this->slug)
        ->where('is_active', true)
        ->firstOrFail();

    $this->fields = $this->game->fieldForms->pluck('name')->mapWithKeys(fn($name) => [$name => ''])->toArray();
});

$products = computed(function () {
    return $this->game->products()
        ->with('supplierProduct')
        ->where('is_active', true)
        ->orderBy('selling_price', 'asc')
        ->get();
});

$selectProduct = function ($productId) {
    $this->selectedProduct = $productId;
};

$proceedToCheckout = function () {
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
        ]
    ]);

    $this->redirect(route('checkout'), navigate: true);
};

?>

<div class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <!-- Game Header -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="flex items-start gap-6">
                    <img src="{{ $game->image }}" alt="{{ $game->name }}" class="w-32 h-32 rounded-lg object-cover">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold">{{ $game->name }}</h1>
                        <p class="text-base-content/60 mt-2">{{ $game->description }}</p>
                        <div class="badge badge-primary mt-3">{{ $game->provider }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Product Selection -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title text-2xl mb-4">Pilih Nominal</h2>

                        @if($this->products->isEmpty())
                            <div class="alert alert-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>Produk tidak tersedia saat ini</span>
                            </div>
                        @else
                            <div class="grid md:grid-cols-2 gap-4">
                                @foreach($this->products as $product)
                                    <div
                                        wire:click="selectProduct({{ $product->id }})"
                                        class="card bg-base-200 cursor-pointer hover:bg-base-300 transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-primary' : '' }}"
                                    >
                                        <div class="card-body p-4">
                                            <h3 class="font-bold text-lg">{{ $product->name }}</h3>
                                            @if($product->description)
                                                <p class="text-sm text-base-content/60">{{ $product->description }}</p>
                                            @endif
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-2xl font-bold text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                                @if($selectedProduct === $product->id)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Player Data Form -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl sticky top-4">
                    <div class="card-body">
                        <h2 class="card-title text-xl mb-4">Data Player</h2>

                        @if($game->fieldForms->isEmpty())
                            <div class="alert alert-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Tidak ada data yang perlu diisi</span>
                            </div>
                        @else
                            @foreach($game->fieldForms as $fieldForm)
                                <div class="form-control mb-4">
                                    <label class="label">
                                        <span class="label-text font-semibold">
                                            {{ $fieldForm->label }}
                                            @if($fieldForm->is_required)
                                                <span class="text-error">*</span>
                                            @endif
                                        </span>
                                    </label>

                                    @if($fieldForm->type === 'textarea')
                                        <textarea
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            class="textarea textarea-bordered"
                                            placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                            {{ $fieldForm->is_required ? 'required' : '' }}
                                        ></textarea>
                                    @elseif($fieldForm->type === 'number')
                                        <input
                                            type="number"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            class="input input-bordered"
                                            placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                            {{ $fieldForm->is_required ? 'required' : '' }}
                                        >
                                    @else
                                        <input
                                            type="text"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            class="input input-bordered"
                                            placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                            {{ $fieldForm->is_required ? 'required' : '' }}
                                        >
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        <div class="divider"></div>

                        <button
                            wire:click="proceedToCheckout"
                            class="btn btn-primary btn-block"
                            {{ !$selectedProduct || $this->products->isEmpty() ? 'disabled' : '' }}
                        >
                            <span wire:loading.remove wire:target="proceedToCheckout">Lanjut Pembayaran</span>
                            <span wire:loading wire:target="proceedToCheckout" class="loading loading-spinner"></span>
                        </button>

                        @if($selectedProduct)
                            @php
                                $product = $this->products->firstWhere('id', $selectedProduct);
                            @endphp
                            @if($product)
                                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm">Total Pembayaran:</span>
                                        <span class="text-xl font-bold text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
