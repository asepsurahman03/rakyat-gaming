<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\FieldForm;
use App\Models\FlazzSell;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGateway;
use App\Services\IpaymuService;
use App\Services\OnoyStoreSupplierService;
use Illuminate\Support\Str;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest'), Title('Game Detail')] class extends Component {
    public $slug;
    public $game;
    public $selectedProduct = null;
    public $fields = [];
    public $products = [];
    public $productGroups = [];
    public $ungroupedProducts = [];
    public $flazzSellProducts = [];
    public $timeRemaining = [];
    public $flazzSell;
    public $email = '';
    public $whatsapp = '';
    public $selectedPaymentMethod = null;
    public $paymentMethods = [];
    public $paymentMethodsCached = false;
    public $paymentMethodsError = null;
    public $loading = false;
    public $playerName = null;
    public bool $showModal = false;
    public bool $dontShowAgain = false;

    public function mount()
    {
        $this->game = Game::with(['products.supplierProduct', 'fieldForms'])
            ->where('slug', $this->slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Check if user has disabled modal for this game
        $sessionKey = 'dont_show_modal_' . $this->game->slug;
        $dontShow = session()->get($sessionKey, false);

        $this->showModal = !$dontShow && $this->game->show_modal;
        $this->dontShowAgain = $dontShow;

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

        // Get product groups with their products (excluding FlazzSell products)
        $this->productGroups = ProductGroup::where('game_id', $this->game->id)
            ->where('is_active', true)
            ->with([
                'products' => function ($query) use ($activeFlazzSellProductIds) {
                    $query->with('supplierProduct')->where('is_active', true)->whereNotIn('id', $activeFlazzSellProductIds)->orderBy('sort_order')->orderBy('selling_price');
                },
            ])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($group) => $group->products->isNotEmpty()); // Only show groups with products

        // Get ungrouped products (products without product_group_id, excluding FlazzSell)
        $this->ungroupedProducts = $this->game->products()->with('supplierProduct')->where('is_active', true)->whereNull('product_group_id')->whereNotIn('id', $activeFlazzSellProductIds)->orderBy('sort_order')->orderBy('selling_price')->get();

        // All products for selection (for backward compatibility)
        $this->products = $this->game->products()->with('supplierProduct')->where('is_active', true)->whereNotIn('id', $activeFlazzSellProductIds)->orderBy('selling_price', 'asc')->get();
    }

    public function cekName()
    {
        foreach ($this->game->fieldForms as $fieldForm) {
            if ($fieldForm->is_required && empty($this->fields[$fieldForm->name])) {
                // Kirim event ke JS untuk scroll
                $this->dispatch('scroll-to-field', name: $fieldForm->name);
                Flux::toast(heading: 'Data player tidak lengkap!', text: "Field '{$fieldForm->label}' wajib diisi sebelum melanjutkan.", variant: 'error', position: 'top-right');
                return;
            }
        }

        // Panggil layanan untuk mendapatkan nama pemain
        $cekNameService = app(\App\Services\Suppliers\OnoyStoreSupplierService::class);
        $inquiry = strtolower($this->game->inquiry); // Contoh inquiry untuk cek nama Mobile Legends
        $result = $cekNameService->cekName($this->fields, $inquiry);

        if ($result['success']) {
            $this->playerName = $result['data']['nickname'] ?? null;
        } else {
            Flux::toast(heading: 'Gagal cek name!', text: $result['message'] ?? 'Terjadi kesalahan saat mengecek name.', variant: 'error', position: 'top-right');
        }
    }

    public function updatedDontShowAgain($value)
    {
        $sessionKey = 'dont_show_modal_' . $this->game->slug;

        if ($value) {
            // Save to session that user doesn't want to see modal for this game
            session()->put($sessionKey, true);
            // Close modal immediately
            $this->showModal = false;
        } else {
            // Remove from session
            session()->forget($sessionKey);
        }
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
        $this->paymentMethodsError = null;

        // Load payment methods when product is selected
        $product = $this->products->firstWhere('id', $productId) ?? $this->flazzSellProducts->firstWhere('id', $productId);
        if ($product) {
            $price = $product->selling_price;

            // If FlazzSell, get discounted price
            if ($product->hasActiveFlazzSell()) {
                $flazzSellProduct = $product->activeFlazzSellProduct();
                $price = $flazzSellProduct->getDiscountedPrice();
            }

            try {
                $ipaymuService = app(IpaymuService::class);

                // Define filters for payment methods
                $filters = [
                    'exclude' => ['cod'], // Example: exclude COD if needed

                    // 'min_amount' => 5000,  // Example: filter by minimum amount
                    // 'max_amount' => 10000000, // Example: filter by maximum amount
                ];

                // Get payment methods with caching enabled (default 15 minutes)
                $result = $ipaymuService->getPaymentMethods((int) $price, $filters, true, 15);

                if ($result['success']) {
                    $this->paymentMethods = collect($result['methods'])->toArray();
                    $this->paymentMethodsCached = $result['cached'] ?? false;

                    // Log for debugging
                    \Log::info('Payment methods loaded', [
                        'product_id' => $productId,
                        'price' => $price,
                        'methods_count' => count($this->paymentMethods),
                        'cached' => $this->paymentMethodsCached,
                    ]);
                } else {
                    $this->paymentMethodsError = $result['message'] ?? 'Gagal memuat metode pembayaran';
                    \Log::warning('Failed to load payment methods', [
                        'product_id' => $productId,
                        'error' => $this->paymentMethodsError,
                    ]);
                }
            } catch (\Exception $e) {
                $this->paymentMethodsError = 'Terjadi kesalahan saat memuat metode pembayaran';
                \Log::error('Exception loading payment methods', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
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

            // Check stock for manual products
            if ($product->product_type === 'manual') {
                $currentProduct = Product::lockForUpdate()->find($product->id);

                if ($currentProduct->stock === null || $currentProduct->stock < 1) {
                    throw new \Exception('Stok produk tidak tersedia');
                }
            }

            $price = $product->selling_price;

            // If FlazzSell, get discounted price
            if ($product->hasActiveFlazzSell()) {
                $flazzSellProduct = $product->activeFlazzSellProduct();
                $price = $flazzSellProduct->getDiscountedPrice();
            }

            // Get payment fee from selected method
            $paymentFee = 0;
            if (!empty($this->paymentMethods)) {
                $method = collect($this->paymentMethods)->firstWhere('code', $this->selectedPaymentMethod);
                $paymentFee = $method['fee'] ?? 0;
            }

            $totalAmount = $price + $paymentFee;

            // Create order
            $order = Order::create([
                'order_code' => 'RG-' . strtoupper(Str::random(10)),
                'user_id' => auth()->id() ?? null,
                'email' => $this->email,
                'whatsapp' => '+62' . $this->whatsapp,
                'game_id' => $this->game->id,
                'product_id' => $this->selectedProduct,
                'data_player' => $this->fields,
                'payment_method' => $this->selectedPaymentMethod,
                'price' => $totalAmount,
                'status' => 'pending',
            ]);

            // Get payment gateway (Ipaymu)
            $paymentGateway = PaymentGateway::where('code', 'ipaymu')->where('is_active', true)->first();

            if (!$paymentGateway) {
                throw new \Exception('Payment gateway tidak tersedia');
            }

            // Create order payment
            $orderPayment = OrderPayment::create([
                'order_id' => $order->id,
                'payment_gateway_id' => $paymentGateway->id,
                'method' => $this->selectedPaymentMethod,
                'payment_name' => $method['name'] ?? null,
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // Create payment request to Ipaymu
            $ipaymuService = app(IpaymuService::class);
            $result = $ipaymuService->createPayment($order, $method);

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Gagal membuat pembayaran');
            }

            // Store payment data in metadata for later display
            $paymentData = [
                'channel' => $result['channel'] ?? null,
                'payment_no' => $result['payment_no'] ?? null,
                'payment_url' => $result['payment_url'] ?? null,
                'qr_string' => $result['qr_string'] ?? null,
                'QrImage' => $result['QrImage'] ?? null,
                'amount' => $result['amount'] ?? $totalAmount,
                'expired' => $result['expired'] ?? null,
                'logo' => $method['logo'] ?? null,
            ];

            // Update order payment with payment data
            $orderPayment->update([
                'payment_reference' => $result['reference'],
                'pay_url' => $result['payment_url'],
                'metadata' => json_encode($paymentData),
            ]);

            \DB::commit();

            // Redirect to payment detail page instead of external URL
            return $this->redirect(route('payment.detail', ['order_code' => $order->order_code]), navigate: true);
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
        <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-1/2 sm:bottom-0 translate-y-1/2 w-full max-w-7xl px-2 md:px-6">
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
                                    <span class="text-xs">{{ __('messages.fast_process') }}</span>
                                </div>
                                <div class="flex gap-0.5 items-center">
                                    <flux:icon.messages-square class="w-5 h-5 text-yellow-400 animate-pulse" />
                                    <span class="text-xs">{{ __('messages.support_247') }}</span>
                                </div>
                                <div class="flex gap-0.5 items-center">
                                    <flux:icon.credit-card class="w-5 h-5 text-yellow-400 animate-pulse" />
                                    <span class="text-xs">{{ __('messages.secure_payment') }}</span>
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
                <span class="text-xs">{{ __('messages.fast_process') }}</span>
            </div>
            <div class="flex gap-0.5 items-center">
                <flux:icon.messages-square class="w-5 h-5 text-yellow-400 animate-pulse" />
                <span class="text-xs">{{ __('messages.support_247') }}</span>
            </div>
            <div class="flex gap-0.5 items-center">
                <flux:icon.credit-card class="w-5 h-5 text-yellow-400 animate-pulse" />
                <span class="text-xs">{{ __('messages.secure_payment') }}</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Product Selection -->
            <div class="lg:col-span-2">
                <flux:card class="mb-4">
                    <flux:heading size="lg" class="mb-4">{{ __('messages.player_data') }}</flux:heading>

                    @if ($game->fieldForms->isEmpty())
                        <flux:callout variant="info" icon="triangle-alert" class="mb-4">
                            {{ __('messages.no_data_required') }}
                        </flux:callout>
                    @else
                        @php
                            $totalFields = $game->fieldForms->count();
                            $isOdd = $totalFields % 2 !== 0;
                        @endphp
                        <div class="grid grid-cols-2 gap-4 items-start">
                            @foreach ($game->fieldForms as $index => $fieldForm)
                                @php
                                    $isLastItem = $index === $totalFields - 1;
                                    $shouldSpan = $isOdd && $isLastItem;
                                @endphp
                                <div class="form-control mb-4 {{ $shouldSpan ? 'col-span-2' : '' }}">
                                    @if ($fieldForm->type === 'textarea')
                                        <flux:textarea id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="{{ __('messages.enter') }} {{ strtolower($fieldForm->label) }}" />
                                    @elseif($fieldForm->type === 'select')
                                        <flux:select id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="{{ __('messages.select') }} {{ strtolower($fieldForm->label) }}"
                                            label="{{ $fieldForm->label }}">
                                            @if ($fieldForm->options && is_array($fieldForm->options))
                                                @foreach ($fieldForm->options as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            @endif
                                        </flux:select>
                                    @elseif($fieldForm->type === 'number')
                                        <flux:input type="number" id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="{{ __('messages.enter') }} {{ strtolower($fieldForm->label) }}"
                                            label="{{ $fieldForm->label }}" />
                                    @else
                                        <flux:input type="text" id="field-{{ $fieldForm->name }}"
                                            wire:model="fields.{{ $fieldForm->name }}"
                                            placeholder="{{ __('messages.enter') }} {{ strtolower($fieldForm->label) }}"
                                            label="{{ $fieldForm->label }}" />
                                    @endif
                                    @if ($fieldForm->description)
                                        <flux:subheading size="xs" class="mt-1">
                                            {{ $fieldForm->description }}
                                        </flux:subheading>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($game->inquiry)
                            <div class="mt-2 md:flex gap-2 w-full items-start">
                                <flux:button wire:click="cekName">{{ __('messages.check_name') }}</flux:button>
                                @if ($playerName)
                                    <flux:badge color="yellow" class="mt-2 md:mt-0">
                                        {{ __('messages.nickname') }}: <strong>{{ $playerName }}</strong>
                                    </flux:badge>
                                @endif
                            </div>
                        @endif
                    @endif
                </flux:card>

                <!-- FlazzSell Products Section -->
                @if ($flazzSellProducts->isNotEmpty())
                    <flux:card class="mb-4 border-2 border-red-500">
                        <div class="md:flex items-center justify-between gap-2 mb-4">
                            <div class="mb-2">
                                <flux:icon.fire class="text-red-500" />
                                <flux:heading size="lg" class="text-red-600">{{ __('messages.flazzsell_discount') }}
                                </flux:heading>
                            </div>
                            <!-- Countdown Timer -->
                            <div class="mt-3 pt-3">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">{{ __('messages.ends_in') }}:</div>
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
                                        <div class="text-xs text-gray-500">{{ __('messages.days') }}</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="hours"></div>
                                        <div class="text-xs text-gray-500">{{ __('messages.hours') }}</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="minutes"></div>
                                        <div class="text-xs text-gray-500">{{ __('messages.minutes') }}</div>
                                    </div>
                                    <div class="flex-1 bg-white rounded px-2 py-1">
                                        <div class="text-lg font-bold text-red-600" x-text="seconds"></div>
                                        <div class="text-xs text-gray-500">{{ __('messages.seconds') }}</div>
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

                                    <div class="flex items-start gap-2">
                                        <flux:heading size="sm" class="text-sm md:text-base line-clamp-2 flex-1">
                                            {{ $product->name }}</flux:heading>
                                        @if ($product->icon)
                                            <img src="{{ asset('storage/' . $product->icon) }}"
                                                alt="Icon"
                                                class="w-6 h-6 md:w-8 md:h-8 object-contain rounded flex-shrink-0">
                                        @endif
                                    </div>
                                    @if ($product->description)
                                        <flux:subheading size="sm" class="mt-1 text-xs line-clamp-2">
                                            <flux:icon.info variant="mini"/>
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
                                            {{ __('messages.save_amount') }} Rp
                                            {{ number_format($product->selling_price - $discountedPrice, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                @endif

                <!-- Grouped Products -->
                @if ($productGroups->isNotEmpty())
                    <flux:card class="mb-4">
                        @foreach ($productGroups as $group)
                            <div class="flex items-center gap-2 mb-4">
                                <flux:heading size="lg">{{ $group->name }}</flux:heading>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-2 md:mb-4">
                                @foreach ($group->products as $product)
                                    <div wire:click="selectProduct({{ $product->id }})"
                                        class="p-3 md:p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-yellow-400' : '' }}">
                                        <div class="flex items-start gap-2">
                                            <flux:heading size="sm" class="text-sm md:text-base line-clamp-2 flex-1">
                                                {{ $product->name }}</flux:heading>
                                            @if ($product->icon)
                                                <img src="{{ asset('storage/' . $product->icon) }}"
                                                    alt="Icon"
                                                    class="w-6 h-6 md:w-8 md:h-8 object-contain rounded flex-shrink-0">
                                            @endif
                                        </div>
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
                        @endforeach
                    </flux:card>
                @endif

                <!-- Ungrouped Products -->
                @if ($ungroupedProducts->isNotEmpty())
                    <flux:card class="mb-4">
                        <flux:heading size="lg" class="mb-4">{{ __('messages.select_nominal') }}</flux:heading>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($ungroupedProducts as $product)
                                <div wire:click="selectProduct({{ $product->id }})"
                                    class="p-3 md:p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all {{ $selectedProduct === $product->id ? 'ring-2 ring-yellow-400' : '' }}">
                                    <div class="flex items-start gap-2">
                                        <flux:heading size="sm" class="text-sm md:text-base line-clamp-2 flex-1">
                                            {{ $product->name }}</flux:heading>
                                        @if ($product->icon)
                                            <img src="{{ asset('storage/' . $product->icon) }}"
                                                alt="Icon"
                                                class="w-6 h-6 md:w-8 md:h-8 object-contain rounded flex-shrink-0">
                                        @endif
                                    </div>
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
                    </flux:card>
                @endif

                <!-- Empty State -->
                @if ($productGroups->isEmpty() && $ungroupedProducts->isEmpty())
                    <flux:card class="mb-4">
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            {{ __('messages.product_unavailable') }}
                        </flux:callout>
                    </flux:card>
                @endif

                <flux:card class="mb-4 hidden md:block">
                    <flux:heading class="mb-4">{{ __('messages.description_of') }} {{ $game->name }}</flux:heading>
                    <flux:text class="whitespace-pre-line text-justify">
                        {{ $game->description }}
                    </flux:text>
                </flux:card>
            </div>

            <!-- Right: Player Data Form -->
            <div class="lg:col-span-1 mb-4">
                <div class="p-4 rounded-xl mb-4 bg-zinc-900 border border-zinc-800 shadow-sm">
                    <flux:heading>{{ __('messages.reviews_rating') }}</flux:heading>
                    <div class="flex gap-2">
                        <h1 class="text-5xl font-bold">4.9</h1>
                        <div>
                            <div class="flex items-center gap-1">
                                @for ($i = 0; $i < 5; $i++)
                                    <flux:icon.star class="w-10 h-10 text-yellow-400" variant="solid" />
                                @endfor
                            </div>
                            <div class="text-sm text-zinc-300">{{ __('messages.based_on_reviews', ['count' => '3.421']) }}</div>
                        </div>
                    </div>
                </div>
                <flux:card class="sticky top-30">
                    <flux:heading size="lg" class="mb-4">{{ __('messages.contact_info') }}</flux:heading>

                    <flux:callout variant="info" icon="information-circle" class="mb-4 text-xs">
                        {{ __('messages.contact_notice') }}
                    </flux:callout>

                    <div class="space-y-4 mb-4">
                        <div>
                            <flux:input type="email" wire:model="email" label="{{ __('messages.email') }} *"
                                placeholder="email@example.com" />
                        </div>

                        <div>
                            <flux:label>{{ __('messages.whatsapp_number') }} *</flux:label>
                            <flux:input.group>
                                <flux:input.group.prefix>
                                    <span class="text-zinc-500 text-sm">+62</span>
                                </flux:input.group.prefix>
                                <flux:input type="text" wire:model="whatsapp" placeholder="81234567890" />
                            </flux:input.group>
                            <flux:error name="whatsapp" />
                            <flux:text class="text-xs text-zinc-300 mt-1 flex">
                                <flux:icon.triangle-alert variant="outline" class="w-4 h-4 text-yellow-600 inline" />
                                {{ __('messages.whatsapp_notice') }}
                            </flux:text>
                        </div>

                        <div x-data="{ openGroup: 'Virtual Account' }">
                            <flux:label>{{ __('messages.payment_method') }} *</flux:label>

                            @if ($paymentMethodsError)
                                <flux:callout variant="error" icon="exclamation-circle" class="text-sm mt-2">
                                    {{ $paymentMethodsError }}
                                </flux:callout>
                            @elseif (!$paymentMethods || count($paymentMethods) === 0)
                                <flux:callout variant="warning" icon="exclamation-triangle" class="text-sm mt-2">
                                    {{ __('messages.select_product_first') }}
                                </flux:callout>
                            @else
                                @php
                                    // Group payment methods by group_name
                                    $groupedMethods = collect($paymentMethods)->groupBy('group_name');

                                    // Sort groups by custom order
                                    $groupOrder = [
                                        'Virtual Account',
                                        'eWallet',
                                        'QRIS',
                                        'Convenience Store',
                                        'Credit Card',
                                        'Debit Online',
                                        'Pay Later',
                                        'COD',
                                    ];
                                    $orderedGroups = collect($groupOrder)->filter(function ($groupName) use (
                                        $groupedMethods,
                                    ) {
                                        return $groupedMethods->has($groupName);
                                    });
                                @endphp

                                <div class="mt-3 space-y-2">
                                    @foreach ($orderedGroups as $groupName)
                                        @php
                                            $methods = $groupedMethods[$groupName];
                                            $groupIcon = $groupIcons[$groupName] ?? 'credit-card';
                                        @endphp

                                        <div class="border border-zinc-700 rounded-lg overflow-hidden bg-zinc-900">
                                            <!-- Accordion Header -->
                                            <button
                                                @click="openGroup = openGroup === '{{ $groupName }}' ? null : '{{ $groupName }}'"
                                                type="button"
                                                class="w-full flex items-center justify-between p-3 hover:bg-zinc-800/50 transition-colors">
                                                <div class="flex w-full items-center gap-3">
                                                    <span class="font-semibold text-sm">{{ $groupName }}</span>
                                                </div>
                                                <flux:icon.chevron-down
                                                    class="w-5 h-5 text-zinc-400 transition-transform"
                                                    x-bind:class="{ 'rotate-180': openGroup === '{{ $groupName }}' }" />
                                            </button>

                                            <!-- Accordion Content -->
                                            <div x-show="openGroup === '{{ $groupName }}'" x-collapse
                                                class="border-t border-zinc-700">
                                                <div class="p-2 grid grid-cols-3 gap-2">
                                                    @foreach ($methods as $method)
                                                        <div class="bg-white rounded-lg ">
                                                            <label
                                                                class="relative p-3 border rounded-lg cursor-pointer transition-all aspect-[3/2] flex items-center justify-center
                                                            {{ $selectedPaymentMethod === $method['code'] ? 'border-yellow-500 bg-gray-100 ring-2 ring-yellow-500/50' : 'border-zinc-700 hover:border-yellow-400/50' }}">
                                                                <input type="radio" name="payment_method"
                                                                    value="{{ $method['code'] }}"
                                                                    wire:model.live="selectedPaymentMethod"
                                                                    class="sr-only" />

                                                                @if (!empty($method['logo']))
                                                                    <img src="{{ $method['logo'] }}"
                                                                        alt="{{ $method['name'] }}"
                                                                        class="w-full h-full object-contain p-1" />
                                                                @else
                                                                    <div
                                                                        class="w-full h-full bg-zinc-800 rounded flex items-center justify-center">
                                                                        <flux:icon.credit-card
                                                                            class="w-8 h-8 text-zinc-600" />
                                                                    </div>
                                                                @endif

                                                                @if ($selectedPaymentMethod === $method['code'])
                                                                    <div class="absolute top-1 right-1">
                                                                        <flux:icon.check-circle
                                                                            class="w-5 h-5 text-yellow-500" />
                                                                    </div>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($paymentMethodsCached)
                                    <flux:text class="text-xs text-zinc-400 mt-2 flex items-center gap-1">
                                        <flux:icon.information-circle class="w-3 h-3" />
                                        {{ __('messages.payment_methods_from_cache') }}
                                    </flux:text>
                                @endif
                            @endif

                            <flux:error name="selectedPaymentMethod" />
                        </div>
                    </div>

                    <flux:separator class="mb-4" />

                    <flux:heading size="lg" class="mb-4">{{ __('messages.payment_details') }}</flux:heading>

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
                                $method = collect($paymentMethods)->firstWhere('code', $selectedPaymentMethod);
                                $paymentFee = $method['fee'] ?? 0;
                                $totalAmount = $displayPrice + $paymentFee;
                            }
                        @endphp
                        @if ($product)
                            <div class="space-y-3">
                                @if ($isFlazzSell)
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                        <div class="flex items-center gap-2 text-red-600 text-sm font-semibold mb-2">
                                            <flux:icon.fire class="w-4 h-4" />
                                            <span>{{ __('messages.flazzsell_active') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-zinc-600 dark:text-zinc-300">{{ __('messages.normal_price') }}:</span>
                                            <span class="line-through text-zinc-300">Rp
                                                {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm text-green-600 font-semibold">
                                            <span>{{ __('messages.save_amount') }}:</span>
                                            <span>- Rp
                                                {{ number_format($product->selling_price - $displayPrice, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-300">{{ __('messages.product_price') }}:</span>
                                    <span class="font-medium">Rp
                                        {{ number_format($displayPrice, 0, ',', '.') }}</span>
                                </div>

                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-300">{{ __('messages.admin_fee') }}:</span>
                                    <span class="font-medium">Rp
                                        {{ number_format($paymentFee, 0, ',', '.') }}</span>
                                </div>

                                <div class="border-t border-zinc-300 dark:border-zinc-600 pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold">{{ __('messages.total_payment') }}:</span>
                                        <span
                                            class="text-2xl font-bold {{ $isFlazzSell ? 'text-red-600' : 'text-yellow-400' }}">
                                            Rp {{ number_format($totalAmount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <flux:text class="text-center text-zinc-300">{{ __('messages.select_product_first_simple') }}</flux:text>
                    @endif

                    <flux:button wire:click="createOrder" variant="primary" class="w-full mt-6"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createOrder">{{ __('messages.pay_now') }}</span>
                        <span wire:loading wire:target="createOrder">
                            <svg class="animate-spin h-5 w-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ __('messages.processing_order') }}
                        </span>
                    </flux:button>

                    <flux:text class="text-xs flex text-center text-zinc-300 mt-3">
                        {{ __('messages.order_auto_process') }}
                    </flux:text>
                </flux:card>
            </div>
        </div>
    </div>

    {{-- modal --}}
    <div x-data="{ showModal: @entangle('showModal') }">
        <template x-if="showModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @keydown.escape.window="showModal = false">
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-lg max-w-lg w-full p-6 mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <flux:heading class="text-xl font-bold text-yellow-500">{{ $game->name }}</flux:heading>
                        <button @click="showModal = false"
                            class="text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div
                        class="prose prose-zinc dark:prose-invert max-w-none
                        [&>p]:text-sm
                        [&>h1]:text-2xl [&>h2]:text-xl [&>h3]:text-lg
                            [&>p]:mb-4
                            [&>h1]:mt-6 [&>h1]:mb-4
                            [&>h2]:mt-6 [&>h2]:mb-4
                            [&>h3]:mt-6 [&>h3]:mb-4
                            [&>ul]:my-4 [&>ul]:list-disc [&>ul]:pl-8 [&>ul]:space-y-2
                            [&>ol]:my-4 [&>ol]:list-decimal [&>ol]:pl-8 [&>ol]:space-y-2
                            [&>ul>li]:ml-0 [&>ol>li]:ml-0
                            [&_ul]:list-disc [&_ol]:list-decimal">
                        {!! $game->modal_content !!}
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <flux:checkbox wire:model.live="dontShowAgain"></flux:checkbox>
                            <flux:label>{{ __('messages.dont_show_again') }}</flux:label>
                        </div>
                    </div>
                </div>
            </div>
        </template>
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
