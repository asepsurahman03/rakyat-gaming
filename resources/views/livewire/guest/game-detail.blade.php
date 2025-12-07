<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\FieldForm;
use App\Models\FlazzSell;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGateway;
use App\Services\DuitkuService;
use Illuminate\Support\Str;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest'), Title('Game Detail')] class extends Component {
    public $slug;
    public $game;
    public $selectedProduct = null;
    public $fields = [];
    public $products = [];
    public $flazzSellProducts = [];
    public $timeRemaining = [];
    public $flazzSell;
    public $email = '';
    public $whatsapp = '';
    public $selectedPaymentMethod = null;
    public $paymentMethods = [];
    public $loading = false;

    public function mount()
    {
        $this->game = Game::with(['products.supplierProduct', 'fieldForms'])
            ->where('slug', $this->slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->fields = $this->game->fieldForms->pluck('name')->mapWithKeys(fn($name) => [$name => ''])->toArray();

        // Set user data if authenticated
        if (auth()->check()) {
            $this->email = auth()->user()->email;
            $this->whatsapp = auth()->user()->profile->whatsapp ?? '';
        }

        // Get FlazzSell products (active flash sales from event system)
        $activeFlazzSellProductIds = \App\Models\FlazzSellProduct::whereHas('flazzSell', function ($query) {
            $query->where('is_active', true)->where('start_at', '<=', now())->where('end_at', '>=', now());
        })
            ->where(function ($q) {
                $q->whereNull('quota')->orWhereRaw('sold_count < quota');
            })
            ->pluck('product_id')
            ->toArray();

        $this->flazzSellProducts = Product::whereIn('id', $activeFlazzSellProductIds)->with('supplierProduct')->where('game_id', $this->game->id)->where('is_active', true)->orderBy('selling_price', 'asc')->get();
        if ($this->flazzSellProducts->isNotEmpty()) {
            $flazzSell = $this->flazzSellProducts->first()->activeFlazzSellProduct()->flazzSell;
            $this->timeRemaining = $flazzSell->getTimeRemaining();
            $this->flazzSell = $flazzSell;
        }
        // Get regular products (excluding FlazzSell products)
        $this->products = $this->game->products()->with('supplierProduct')->where('is_active', true)->whereNotIn('id', $activeFlazzSellProductIds)->orderBy('selling_price', 'asc')->get();
    }

    public function selectProduct($productId)
    {
        // Validate game fields
        foreach ($this->game->fieldForms as $fieldForm) {
            if ($fieldForm->is_required && empty($this->fields[$fieldForm->name])) {
                // Kirim event ke JS untuk scroll
                $this->dispatch('scroll-to-field', name: $fieldForm->name);
                Flux::toast(heading: 'Data player tidak lengkap!', text: "Field '{$fieldForm->label}' wajib diisi sebelum melanjutkan.", variant: 'error', position: 'top-right');
                $this->loading = false;
                return;
            }
        }

        $this->selectedProduct = $productId;

        // Load payment methods when product is selected
        $product = $this->products->firstWhere('id', $productId) ?? $this->flazzSellProducts->firstWhere('id', $productId);
        if ($product) {
            $price = $product->selling_price;

            // If FlazzSell, get discounted price
            if ($product->hasActiveFlazzSell()) {
                $flazzSellProduct = $product->activeFlazzSellProduct();
                $price = $flazzSellProduct->getDiscountedPrice();
            }

            $duitkuService = app(DuitkuService::class);
            $result = $duitkuService->getPaymentMethods((int) $price);

            if ($result['success']) {
                $this->paymentMethods = collect($result['methods'])->toArray();
            }
        }
    }

    public function createOrder()
    {
        $this->loading = true;

        // Validate required fields
        try {
            $this->validate(
                [
                    'email' => 'required|email',
                    'whatsapp' => 'required|numeric|min:10',
                    'selectedPaymentMethod' => 'required',
                ],
                [
                    'email.required' => 'Email harus diisi',
                    'email.email' => 'Email tidak valid',
                    'whatsapp.required' => 'Nomor WhatsApp harus diisi',
                    'whatsapp.numeric' => 'Nomor WhatsApp harus berupa angka',
                    'whatsapp.min' => 'Nomor WhatsApp minimal 10 digit',
                    'selectedPaymentMethod.required' => 'Silakan pilih metode pembayaran',
                ],
            );
        } catch (ValidationException $e) {
            $this->loading = false;
            throw $e;
        }

        if (!$this->selectedProduct) {
            Flux::toast(heading: 'Pilih produk terlebih dahulu!', text: 'Silakan pilih produk sebelum melanjutkan ke pembayaran.', variant: 'error', position: 'top-right');
            $this->loading = false;
            return;
        }

        // Validate game fields
        foreach ($this->game->fieldForms as $fieldForm) {
            if ($fieldForm->is_required && empty($this->fields[$fieldForm->name])) {
                Flux::toast(heading: 'Data player tidak lengkap!', text: "Field '{$fieldForm->label}' wajib diisi sebelum melanjutkan.", variant: 'error', position: 'top-right');
                $this->loading = false;
                return;
            }
        }

        try {
            \DB::beginTransaction();

            $product = $this->products->firstWhere('id', $this->selectedProduct) ?? $this->flazzSellProducts->firstWhere('id', $this->selectedProduct);
            $price = $product->selling_price;

            // If FlazzSell, get discounted price
            if ($product->hasActiveFlazzSell()) {
                $flazzSellProduct = $product->activeFlazzSellProduct();
                $price = $flazzSellProduct->getDiscountedPrice();
            }

            // Get payment fee from selected method
            $paymentFee = 0;
            if (!empty($this->paymentMethods)) {
                $method = collect($this->paymentMethods)->firstWhere('paymentMethod', $this->selectedPaymentMethod);
                $paymentFee = $method['totalFee'] ?? 0;
            }

            $totalAmount = $price + $paymentFee;

            // Create order
            $order = Order::create([
                'order_code' => 'RG-' . strtoupper(Str::random(10)),
                'user_id' => auth()->id() ?? null,
                'email' => $this->email,
                'whatsapp' => $this->whatsapp,
                'game_id' => $this->game->id,
                'product_id' => $this->selectedProduct,
                'data_player' => $this->fields,
                'payment_method' => $this->selectedPaymentMethod,
                'price' => $totalAmount,
                'status' => 'pending',
            ]);

            // Get payment gateway (Duitku)
            $paymentGateway = PaymentGateway::where('code', 'duitku')->where('is_active', true)->first();

            if (!$paymentGateway) {
                throw new \Exception('Payment gateway tidak tersedia');
            }

            // Create order payment
            $orderPayment = OrderPayment::create([
                'order_id' => $order->id,
                'payment_gateway_id' => $paymentGateway->id,
                'method' => $this->selectedPaymentMethod,
                'payment_name' => $method['paymentName'] ?? null,
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // Create payment request to Duitku
            $duitkuService = app(DuitkuService::class);
            $result = $duitkuService->createPayment($order, $this->selectedPaymentMethod);

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Gagal membuat pembayaran');
            }

            // Update order payment with payment URL
            $orderPayment->update([
                'payment_reference' => $result['reference'],
                'pay_url' => $result['pay_url'],
            ]);

            \DB::commit();

            // Redirect to payment page
            return redirect()->away($result['pay_url']);
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->loading = false;

            \Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Flux::toast(heading: 'Gagal membuat pesanan!', text: $e->getMessage(), variant: 'error', duration: 5000, position: 'top-right');
        }
    }
}; ?>

<div>
    <div class="relative">
        <img src="{{ asset('storage/' . $game->banner) }}" class="w-full" alt="banner">
        <div class="absolute left-1/2 -translate-x-1/2 -bottom-1/2 sm:bottom-0 translate-y-1/2 w-full max-w-7xl px-2 md:px-6">
            <div class="">
                <div class="mb-6 p-2 md:p-6">
                    <div class="flex items-start gap-6">
                        <img src="{{ asset('storage/' . $game->image) }}" alt="{{ $game->image }}"
                            class="w-16 sm:w-24 md:w-32 rounded-lg object-cover">
                        <div class="p-4 rounded-lg flex flex-col h-full">
                            <flux:heading size="xl">{{ $game->name }}</flux:heading>
                            <div class="flex">
                                <flux:badge class="mt-3" color="indigo">{{ $game->provider }}</flux:badge>
                            </div>
                            <flux:spacer class="my-6" />
                            <div class="items-end gap-2 hidden md:flex">
                                <div class="flex gap-0.5 items-center">
                                    <flux:icon.zap class="w-5 h-5 text-yellow-400 animate-pulse" />
                                    <span class="text-xs">Proses Cepat</span>
                                </div>
                                <div class="flex gap-0.5 items-center">
                                    <flux:icon.messages-square class="w-5 h-5 text-yellow-400 animate-pulse" />
                                    <span class="text-xs">Layanan Chat 24/7</span>
                                </div>
                                <div class="flex gap-0.5 items-center">
                                    <flux:icon.credit-card class="w-5 h-5 text-yellow-400 animate-pulse" />
                                    <span class="text-xs">Pembayaran Aman!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-2 md:px-6 py-16 md:py-25">
        <!-- Game Header -->
        <div
            class="flex items-center justify-center gap-2 md:hidden bg-white/70 dark:bg-black/70 px-4 py-2 rounded-full mb-4">
            <div class="flex gap-0.5 items-center">
                <flux:icon.zap class="w-5 h-5 text-yellow-400 animate-pulse" />
                <span class="text-xs">Proses Cepat</span>
            </div>
            <div class="flex gap-0.5 items-center">
                <flux:icon.messages-square class="w-5 h-5 text-yellow-400 animate-pulse" />
                <span class="text-xs">Layanan Chat 24/7</span>
            </div>
            <div class="flex gap-0.5 items-center">
                <flux:icon.credit-card class="w-5 h-5 text-yellow-400 animate-pulse" />
                <span class="text-xs">Pembayaran Aman!</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Product Selection -->
            <div class="lg:col-span-2">
                <flux:card class="mb-4">
                    <flux:heading size="lg" class="mb-4">Data Player</flux:heading>

                    @if ($game->fieldForms->isEmpty())
                        <flux:callout variant="info" icon="triangle-alert" class="mb-4">
                            Tidak ada data yang perlu diisi
                        </flux:callout>
                    @else
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach ($game->fieldForms as $fieldForm)
                                <div class="form-control mb-4">
                                    @if ($fieldForm->type === 'textarea')
                                        <flux:textarea id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="Masukkan {{ strtolower($fieldForm->label) }}" />
                                    @elseif($fieldForm->type === 'number')
                                        <flux:input type="number" id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="Masukkan {{ strtolower($fieldForm->label) }}"
                                            label="{{ $fieldForm->label }}" />
                                    @else
                                        <flux:input type="text" id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
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
                        <div class="md:flex items-center justify-between gap-2 mb-4">
                            <div class="mb-2">
                                <flux:icon.fire class="text-red-500" />
                                <flux:heading size="lg" class="text-red-600">FlazzSell - Diskon Terbatas!
                                </flux:heading>
                            </div>
                            <!-- Countdown Timer -->
                            <div class="mt-3 pt-3">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Berakhir dalam:</div>
                                <div class="flex gap-2 text-center" x-data="{
                                    days: {{ $timeRemaining['days'] }},
                                    hours: {{ $timeRemaining['hours'] }},
                                    minutes: {{ $timeRemaining['minutes'] }},
                                    seconds: {{ $timeRemaining['seconds'] }},
                                    endTime: new Date('{{ $flazzSell->end_at->toIso8601String() }}').getTime()
                                }" x-init="setInterval(() => {
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
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="days"></div>
                                        <div class="text-xs text-gray-500">Hari</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="hours"></div>
                                        <div class="text-xs text-gray-500">Jam</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="minutes"></div>
                                        <div class="text-xs text-gray-500">Menit</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="seconds"></div>
                                        <div class="text-xs text-gray-500">Detik</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($flazzSellProducts as $product)
                                @php
                                    $flazzSellProduct = $product->activeFlazzSellProduct();
                                    $discountedPrice = $flazzSellProduct->getDiscountedPrice();
                                @endphp
                                <div wire:click="selectProduct({{ $product->id }})"
                                    class="relative p-3 md:p-4 rounded-lg bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-2 border-red-300 dark:border-red-700 cursor-pointer hover:shadow-lg transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-red-600' : '' }}">

                                    <!-- Discount Badge -->
                                    <div class="absolute -top-3 -right-3">
                                        <div
                                            class="bg-red-600 text-white px-2 md:px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                            -{{ number_format($flazzSellProduct->discount_percentage, 0) }}%
                                        </div>
                                    </div>

                                    <flux:heading size="sm" class="text-sm md:text-base line-clamp-1">
                                        {{ $product->name }}</flux:heading>
                                    @if ($product->description)
                                        <flux:subheading size="sm" class="mt-1 text-xs line-clamp-2">
                                            {{ $product->description }}
                                        </flux:subheading>
                                    @endif

                                    <!-- Price Display -->
                                    <div class="mt-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs md:text-sm text-gray-500 line-through">
                                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between mt-1">
                                            <span class="text-lg md:text-2xl font-bold text-red-600">
                                                Rp {{ number_format($discountedPrice, 0, ',', '.') }}
                                            </span>
                                            @if ($selectedProduct === $product->id)
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 md:h-6 md:w-6 text-red-600" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="text-xs text-green-600 font-semibold mt-1">
                                            Hemat Rp
                                            {{ number_format($product->selling_price - $discountedPrice, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                @endif

                <!-- Regular Products -->
                <flux:card class="mb-4">
                    <flux:heading size="lg" class="mb-4">Pilih Nominal</flux:heading>

                    @if ($products->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
                            Produk tidak tersedia saat ini
                        </flux:callout>
                    @else
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($products as $product)
                                <div wire:click="selectProduct({{ $product->id }})"
                                    class="p-3 md:p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-yellow-400' : '' }}">
                                    <flux:heading size="sm" class="text-sm md:text-base line-clamp-1">
                                        {{ $product->name }}</flux:heading>
                                    @if ($product->description)
                                        <flux:subheading size="sm" class="mt-1 text-xs line-clamp-2">
                                            {{ $product->description }}
                                        </flux:subheading>
                                    @endif
                                    <div class="flex items-center justify-between mt-3">
                                        <span class="text-lg md:text-2xl font-bold text-yellow-400">Rp
                                            {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                        @if ($selectedProduct === $product->id)
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5 md:h-6 md:w-6 text-yellow-400" viewBox="0 0 20 20"
                                                fill="currentColor">
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

                <flux:card class="mb-4 hidden md:block">
                    <flux:heading class="mb-4">Deskripsi {{ $game->name }}</flux:heading>
                    <flux:text class="whitespace-pre-line text-justify">
                        {{ $game->description }}
                    </flux:text>
                </flux:card>
            </div>

            <!-- Right: Player Data Form -->
            <div class="lg:col-span-1 mb-4">
                <div class="p-4 rounded-xl mb-4 bg-zinc-900 border border-zinc-800 shadow-sm">
                    <flux:heading>Ulasan dan rating</flux:heading>
                    <div class="flex gap-2">
                        <h1 class="text-5xl font-bold">4.9</h1>
                        <div>
                            <div class="flex items-center gap-1">
                                @for ($i = 0; $i < 5; $i++)
                                <flux:icon.star class="w-10 h-10 text-yellow-400" variant="solid" />
                                @endfor
                            </div>
                            <div class="text-sm text-zinc-300">Berdasarkan 3.421 ulasan</div>
                        </div>
                    </div>
                </div>
                <flux:card class="sticky top-30">
                    <flux:heading size="lg" class="mb-4">Informasi Kontak</flux:heading>

                    <flux:callout variant="info" icon="information-circle" class="mb-4 text-xs">
                        Pastikan data yang Anda masukkan sudah benar. kontak ini akan digunakan untuk menghubungi
                        Anda jika
                        terjadi masalah pada pesanan.
                    </flux:callout>

                    <div class="space-y-4 mb-4">
                        <div>
                            <flux:input type="email" wire:model="email" label="Email *"
                                placeholder="email@example.com" />
                        </div>

                        <div>
                            <flux:label>WhatsApp *</flux:label>
                            <flux:input.group>
                                <flux:input.group.prefix>
                                    <span class="text-zinc-500 text-sm">+62</span>
                                </flux:input.group.prefix>
                                <flux:input type="text" wire:model="whatsapp" placeholder="81234567890" />
                            </flux:input.group>
                            <flux:error name="whatsapp" />
                            <flux:text class="text-xs text-zinc-300 mt-1 flex">
                                <flux:icon.triangle-alert variant="outline" class="w-4 h-4 text-yellow-600 inline" />
                                Tulis tanpa angka 0 di depan. Contoh: 81234567890
                            </flux:text>
                        </div>

                        <div>
                            <flux:select wire:model="selectedPaymentMethod" variant="listbox"
                                label="Metode Pembayaran *" placeholder="Pilih metode pembayaran" class="w-full">
                                @if (!$paymentMethods)
                                    <flux:select.option disabled>
                                        Pilih produk terlebih dahulu
                                    </flux:select.option>
                                @else
                                    @foreach ($paymentMethods as $method)
                                        <flux:select.option value="{{ $method['paymentMethod'] }}">
                                            {{ $method['paymentName'] }}
                                        </flux:select.option>
                                    @endforeach
                                @endif
                            </flux:select>
                        </div>
                    </div>

                    <flux:separator class="mb-4" />

                    <flux:heading size="lg" class="mb-4">Detail Pembayaran</flux:heading>

                    @if ($selectedProduct)
                        @php
                            $product =
                                $this->products->firstWhere('id', $selectedProduct) ??
                                $this->flazzSellProducts->firstWhere('id', $selectedProduct);
                            $displayPrice = $product->selling_price;
                            $isFlazzSell = false;
                            $flazzSellProduct = null;

                            if ($product && $product->hasActiveFlazzSell()) {
                                $flazzSellProduct = $product->activeFlazzSellProduct();
                                $displayPrice = $flazzSellProduct->getDiscountedPrice();
                                $isFlazzSell = true;
                            }

                            // Calculate payment fee
                            $paymentFee = 0;
                            $totalAmount = $displayPrice;
                            if ($selectedPaymentMethod && !empty($paymentMethods)) {
                                $method = collect($paymentMethods)->firstWhere('paymentMethod', $selectedPaymentMethod);
                                $paymentFee = $method['totalFee'] ?? 0;
                                $totalAmount = $displayPrice + $paymentFee;
                            }
                        @endphp
                        @if ($product)
                            <div class="space-y-3">
                                @if ($isFlazzSell)
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                        <div class="flex items-center gap-2 text-red-600 text-sm font-semibold mb-2">
                                            <flux:icon.fire class="w-4 h-4" />
                                            <span>FlazzSell Aktif!</span>
                                        </div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-zinc-600 dark:text-zinc-300">Harga Normal:</span>
                                            <span class="line-through text-zinc-300">Rp
                                                {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm text-green-600 font-semibold">
                                            <span>Hemat:</span>
                                            <span>- Rp
                                                {{ number_format($product->selling_price - $displayPrice, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-300">Harga Produk:</span>
                                    <span class="font-medium">Rp
                                        {{ number_format($displayPrice, 0, ',', '.') }}</span>
                                </div>

                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-300">Biaya Admin:</span>
                                    <span class="font-medium">Rp
                                        {{ number_format($paymentFee, 0, ',', '.') }}</span>
                                </div>

                                <div class="border-t border-zinc-300 dark:border-zinc-600 pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold">Total Pembayaran:</span>
                                        <span
                                            class="text-2xl font-bold {{ $isFlazzSell ? 'text-red-600' : 'text-yellow-400' }}">
                                            Rp {{ number_format($totalAmount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <flux:text class="text-center text-zinc-300">Pilih produk terlebih dahulu</flux:text>
                    @endif

                    <flux:button wire:click="createOrder" variant="primary" class="w-full mt-6"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createOrder">Bayar Sekarang</span>
                        <span wire:loading wire:target="createOrder">
                            <svg class="animate-spin h-5 w-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Memproses...
                        </span>
                    </flux:button>

                    <flux:text class="text-xs flex text-center text-zinc-300 mt-3">
                        Pesanan akan diproses otomatis setelah pembayaran terverifikasi
                    </flux:text>
                </flux:card>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('scroll-to-field', ({
            name
        }) => {
            const el = document.getElementById(`field-${name}`);
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                el.focus();
            }
        });
    });
</script>
