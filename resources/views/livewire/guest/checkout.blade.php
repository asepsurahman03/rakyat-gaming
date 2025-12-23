<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGateway;
use App\Services\IpaymuService;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, mount, computed, layout, title};

layout('components.layouts.guest');
title('Checkout');

state(['game', 'product', 'fields' => [], 'selectedPaymentMethod' => null, 'email' => '', 'whatsapp' => '', 'paymentMethods' => [], 'paymentMethodsCached' => false, 'paymentMethodsError' => null, 'loading' => false]);

mount(function () {
    $checkoutData = session('checkout_data');

    if (!$checkoutData) {
        return $this->redirect(route('home'), navigate: true);
    }

    $this->game = Game::findOrFail($checkoutData['game_id']);
    $this->product = Product::with('supplierProduct')->findOrFail($checkoutData['product_id']);
    $this->fields = $checkoutData['fields'];

    // Check stock for manual products
    if ($this->product->product_type === 'manual') {
        if ($this->product->stock === null || $this->product->stock < 1) {
            session()->forget('checkout_data');
            return $this->redirect(route('home'), navigate: true);
        }
    }

    // Set user data if authenticated
    if (auth()->check()) {
        $this->email = auth()->user()->email;
        $this->whatsapp = auth()->user()->profile->whatsapp ?? '';
    }

    // Get payment methods from Ipaymu with caching
    try {
        $ipaymuService = app(IpaymuService::class);

        // Define filters if needed
        $filters = [
            // 'exclude' => ['cod'], // Example: exclude specific channels
        ];

        // Get payment methods with caching (15 minutes TTL)
        $result = $ipaymuService->getPaymentMethods((int) $this->product->selling_price, $filters, true, 15);

        if ($result['success']) {
            $this->paymentMethods = collect($result['methods'])->take(10)->toArray();
            $this->paymentMethodsCached = $result['cached'] ?? false;
        } else {
            $this->paymentMethodsError = $result['message'] ?? 'Gagal memuat metode pembayaran';
        }
    } catch (\Exception $e) {
        $this->paymentMethodsError = 'Terjadi kesalahan saat memuat metode pembayaran';
        \Log::error('Failed to load payment methods in checkout', [
            'error' => $e->getMessage(),
        ]);
    }
});

$createOrder = function () {
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

    try {
        \DB::beginTransaction();

        // Check stock again for manual products (double-check before creating order)
        if ($this->product->product_type === 'manual') {
            $currentProduct = Product::lockForUpdate()->find($this->product->id);

            if ($currentProduct->stock === null || $currentProduct->stock < 1) {
                throw new \Exception('Stok produk tidak tersedia');
            }
        }

        // Create order
        $order = Order::create([
            'order_code' => 'RG-' . strtoupper(Str::random(10)),
            'user_id' => auth()->id() ?? null,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'game_id' => $this->game->id,
            'product_id' => $this->product->id,
            'data_player' => $this->fields,
            'payment_method' => $this->selectedPaymentMethod['code'] ?? $this->selectedPaymentMethod,
            'price' => $this->product->selling_price,
            'status' => 'pending',
        ]);

        // Get payment gateway (Ipaymu)
        $paymentGateway = PaymentGateway::where('code', 'ipaymu')->where('is_active', true)->first();

        if (!$paymentGateway) {
            throw new \Exception('Payment gateway tidak tersedia');
        }

        // Calculate total amount (product price + payment fee)
        $paymentFee = $this->selectedPaymentMethod['fee'] ?? 0;
        $totalAmount = $this->product->selling_price + $paymentFee;

        // Create order payment
        $orderPayment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_gateway_id' => $paymentGateway->id,
            'method' => $this->selectedPaymentMethod['code'] ?? $this->selectedPaymentMethod,
            'payment_name' => $this->selectedPaymentMethod['name'] ?? null,
            'amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Create payment request to Ipaymu
        $ipaymuService = app(IpaymuService::class);
        $result = $ipaymuService->createPayment($order, $this->selectedPaymentMethod);

        if (!$result['success']) {
            throw new \Exception($result['message'] ?? 'Gagal membuat pembayaran');
        }

        // Store payment data in metadata for later display
        $paymentData = [
            'payment_no' => $result['payment_no'] ?? null,
            'payment_url' => $result['payment_url'] ?? null,
            'qr_string' => $result['qr_string'] ?? null,
            'amount' => $result['amount'] ?? $totalAmount,
            'expired' => $result['expired'] ?? null,
            'logo' => $this->selectedPaymentMethod['logo'] ?? null,
        ];

        // Update order payment with payment data
        $orderPayment->update([
            'payment_reference' => $result['reference'],
            'pay_url' => $result['payment_url'],
            'metadata' => json_encode($paymentData),
        ]);

        \DB::commit();

        // Clear session
        session()->forget('checkout_data');

        // Redirect to payment detail page instead of external URL
        return $this->redirect(route('payment.detail', ['order_code' => $order->order_code]), navigate: true);
    } catch (\Exception $e) {
        \DB::rollBack();
        $this->loading = false;

        \Log::error('Order creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->dispatch('error', message: 'Gagal membuat pesanan: ' . $e->getMessage());
    }
};

?>

<div class="px-4 md:px-6 py-10 md:py-20">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-yellow-400 shadow mb-6">Checkout</h1>

        <div class="grid lg:grid-cols-3 gap-6 bg-zinc-100 shadow p-6 rounded-lg">
            <!-- Left: Forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Summary -->
                <flux:card class="">
                    <div class="card-body">
                        <h2 class="card-title">Ringkasan Pesanan</h2>

                        <div class="flex items-center gap-4 mt-4">
                            <img src="{{ asset('storage/' . $game->image) }}" alt="{{ $game->name }}"
                                class="w-16 h-16 rounded-lg">
                            <div class="flex-1">
                                <h3 class="font-bold">{{ $game->name }}</h3>
                                <p class="text-sm text-base-content/60">{{ $product->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-primary">Rp
                                    {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        @if (!empty($fields))
                            <div class="divider"></div>
                            <div class="space-y-2">
                                <h3 class="font-semibold">Data Player:</h3>
                                @foreach ($fields as $key => $value)
                                    @if ($value)
                                        <div class="flex justify-between text-sm">
                                            <span
                                                class="text-base-content/60">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                            <span class="font-medium">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:card>

                <!-- Contact Information -->
                <flux:card class="">
                    <div class="card-body">
                        <h2 class="card-title">Informasi Kontak</h2>

                        <form>
                            <div class="form-control mt-4">
                                <flux:label for="email" class="label">
                                    <span class="label-text font-semibold">Email <span
                                            class="text-error">*</span></span>
                                </flux:label>
                                <flux:input id="email" type="email" wire:model="email"
                                    class="input input-bordered" placeholder="email@example.com" required />
                                <flux:error name="email" />
                            </div>

                            <div class="form-control mt-4">
                                <flux:label for="whatsapp" class="label">
                                    <span class="label-text font-semibold">WhatsApp <span
                                            class="text-error">*</span></span>
                                </flux:label>
                                <flux:input id="whatsapp" mask="81234567890" placeholder="81234567890" type="text"
                                    wire:model="whatsapp" required />
                                <flux:error name="whatsapp" />
                                <flux:text class="label mt-4 flex gap-2 items-start" variant="warning">
                                    <flux:icon.triangle-alert class="inline-block w-4 h-4 mr-1" />
                                    Pastikan Email dan WhatsApp yang di masukan benar. agar kami bisa menghubungi jika
                                    transaksi bermasalah. Tulis tanpa angka 0 di depan. Contoh: 8960xxxx>
                                </flux:text>
                            </div>
                        </form>
                    </div>
                </flux:card>
            </div>

            <!-- Right: Summary & Checkout Button -->
            <div class="lg:col-span-1">
                <flux:card class="sticky top-30">
                    <div class="card-body">
                        <h2 class="card-title text-xl">Detail Transaction</h2>

                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between">
                                <span class="text-base-content/60">Harga Produk</span>
                                <span class="font-medium">Rp
                                    {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                            </div>

                            <div class="divider my-2"></div>
                            <div class="flex justify-between text-lg">
                                <span class="font-bold">Total</span>
                                <span class="font-bold text-primary">Rp
                                    {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <flux:button variant="primary" class="w-full" wire:click="createOrder">
                            @if ($loading)
                                <span class="loading loading-spinner"></span>
                                Memproses...
                            @else
                                Bayar Sekarang
                            @endif
                        </flux:button>

                        <div class="alert alert-info mt-4 flex items-start gap-3">
                            <flux:icon.info class="mt-1" />
                            <flux:text>Pesanan Anda akan diproses otomatis setelah pembayaran terverifikasi</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
