<?php

use App\Models\Game;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGateway;
use App\Services\DuitkuService;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, mount, computed};

state([
    'game',
    'product',
    'fields' => [],
    'selectedPaymentMethod' => null,
    'email' => '',
    'whatsapp' => '',
    'paymentMethods' => [],
    'loading' => false,
]);

mount(function () {
    $checkoutData = session('checkout_data');

    if (!$checkoutData) {
        return $this->redirect(route('home'), navigate: true);
    }

    $this->game = Game::findOrFail($checkoutData['game_id']);
    $this->product = Product::with('supplierProduct')->findOrFail($checkoutData['product_id']);
    $this->fields = $checkoutData['fields'];

    // Set user data if authenticated
    if (auth()->check()) {
        $this->email = auth()->user()->email;
        $this->whatsapp = auth()->user()->profile->whatsapp ?? '';
    }

    // Get payment methods from Duitku
    $duitkuService = app(DuitkuService::class);
    $result = $duitkuService->getPaymentMethods((int) $this->product->selling_price);

    if ($result['success']) {
        $this->paymentMethods = collect($result['methods'])->take(10)->toArray();
    }
});

$createOrder = function () {
    $this->loading = true;

    $this->validate([
        'email' => 'required|email',
        'whatsapp' => 'required|numeric|min:10',
        'selectedPaymentMethod' => 'required',
    ], [
        'email.required' => 'Email harus diisi',
        'email.email' => 'Email tidak valid',
        'whatsapp.required' => 'Nomor WhatsApp harus diisi',
        'whatsapp.numeric' => 'Nomor WhatsApp harus berupa angka',
        'whatsapp.min' => 'Nomor WhatsApp minimal 10 digit',
        'selectedPaymentMethod.required' => 'Silakan pilih metode pembayaran',
    ]);

    try {
        \DB::beginTransaction();

        // Create order
        $order = Order::create([
            'order_code' => 'WG-' . strtoupper(Str::random(10)),
            'user_id' => auth()->id(),
            'game_id' => $this->game->id,
            'product_id' => $this->product->id,
            'data_player' => $this->fields,
            'payment_method' => $this->selectedPaymentMethod,
            'price' => $this->product->selling_price,
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
            'amount' => $this->product->selling_price,
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

        // Clear session
        session()->forget('checkout_data');

        // Redirect to payment page
        return redirect()->away($result['pay_url']);

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

<div class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">Checkout</h1>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Forms -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Summary -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Ringkasan Pesanan</h2>

                            <div class="flex items-center gap-4 mt-4">
                                <img src="{{ $game->image }}" alt="{{ $game->name }}" class="w-16 h-16 rounded-lg">
                                <div class="flex-1">
                                    <h3 class="font-bold">{{ $game->name }}</h3>
                                    <p class="text-sm text-base-content/60">{{ $product->name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            @if(!empty($fields))
                                <div class="divider"></div>
                                <div class="space-y-2">
                                    <h3 class="font-semibold">Data Player:</h3>
                                    @foreach($fields as $key => $value)
                                        @if($value)
                                            <div class="flex justify-between text-sm">
                                                <span class="text-base-content/60">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                <span class="font-medium">{{ $value }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Informasi Kontak</h2>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">Email <span class="text-error">*</span></span>
                                </label>
                                <input
                                    type="email"
                                    wire:model="email"
                                    class="input input-bordered"
                                    placeholder="email@example.com"
                                    required
                                >
                                @error('email') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">WhatsApp <span class="text-error">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="whatsapp"
                                    class="input input-bordered"
                                    placeholder="08123456789"
                                    required
                                >
                                @error('whatsapp') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                                <label class="label">
                                    <span class="label-text-alt">Notifikasi status pesanan akan dikirim ke WhatsApp ini</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Metode Pembayaran</h2>

                            @if(empty($paymentMethods))
                                <div class="alert alert-warning">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    <span>Metode pembayaran tidak tersedia</span>
                                </div>
                            @else
                                <div class="space-y-2 mt-4">
                                    @foreach($paymentMethods as $method)
                                        <div
                                            wire:click="$set('selectedPaymentMethod', '{{ $method['paymentMethod'] }}')"
                                            class="card bg-base-200 cursor-pointer hover:bg-base-300 transition-all {{ $selectedPaymentMethod === $method['paymentMethod'] ? 'ring-2 ring-primary' : '' }}"
                                        >
                                            <div class="card-body p-4 flex-row items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    @if(isset($method['paymentImage']))
                                                        <img src="{{ $method['paymentImage'] }}" alt="{{ $method['paymentName'] }}" class="h-8">
                                                    @endif
                                                    <div>
                                                        <h3 class="font-semibold">{{ $method['paymentName'] }}</h3>
                                                        <p class="text-xs text-base-content/60">Fee: Rp {{ number_format($method['totalFee'], 0, ',', '.') }}</p>
                                                    </div>
                                                </div>
                                                @if($selectedPaymentMethod === $method['paymentMethod'])
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('selectedPaymentMethod') <span class="text-error text-sm mt-2">{{ $message }}</span> @enderror
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right: Summary & Checkout Button -->
                <div class="lg:col-span-1">
                    <div class="card bg-base-100 shadow-xl sticky top-4">
                        <div class="card-body">
                            <h2 class="card-title text-xl">Total Pembayaran</h2>

                            <div class="space-y-2 mt-4">
                                <div class="flex justify-between">
                                    <span class="text-base-content/60">Harga Produk</span>
                                    <span class="font-medium">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                </div>

                                @if($selectedPaymentMethod && !empty($paymentMethods))
                                    @php
                                        $method = collect($paymentMethods)->firstWhere('paymentMethod', $selectedPaymentMethod);
                                        $fee = $method['totalFee'] ?? 0;
                                        $total = $product->selling_price + $fee;
                                    @endphp
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">Biaya Admin</span>
                                        <span class="font-medium">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="divider my-2"></div>
                                    <div class="flex justify-between text-lg">
                                        <span class="font-bold">Total</span>
                                        <span class="font-bold text-primary">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div class="divider my-2"></div>
                                    <div class="flex justify-between text-lg">
                                        <span class="font-bold">Total</span>
                                        <span class="font-bold text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>

                            <button
                                wire:click="createOrder"
                                class="btn btn-primary btn-block mt-6"
                                {{ $loading ? 'disabled' : '' }}
                            >
                                @if($loading)
                                    <span class="loading loading-spinner"></span>
                                    Memproses...
                                @else
                                    Bayar Sekarang
                                @endif
                            </button>

                            <div class="alert alert-info mt-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="text-xs">
                                    <p>Pesanan Anda akan diproses otomatis setelah pembayaran terverifikasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
