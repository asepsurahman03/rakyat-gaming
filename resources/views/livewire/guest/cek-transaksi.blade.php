<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('components.layouts.guest'), Title('Rakyat Gaming - Cek Transaksi')] class extends Component {
    public $orderCode = '';
    public $order = null;
    public $notFound = false;
    public $paymentUrl;

    public function checkOrder()
    {
        $this->validate(
            [
                'orderCode' => 'required|string',
            ],
            [
                'orderCode.required' => __('messages.required'),
            ],
        );

        $this->notFound = false;

        // Cari order berdasarkan order_code saja
        $this->order = Order::with(['game', 'product', 'orderPayment.paymentGateway'])
            ->where('order_code', $this->orderCode)
            ->first();

        $this->paymentUrl = $this->order->orderPayment->pay_url;

        Log::info('Order: ' . json_encode($this->order));

        if (!$this->order) {
            $this->notFound = true;
        }
    }

    public function clearForm()
    {
        $this->orderCode = '';
        $this->order = null;
        $this->notFound = false;
    }

    public function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'badge-warning',
            'process' => 'badge-info',
            'success' => 'badge-success',
            'failed' => 'badge-error',
            'refund' => 'badge-ghost',
            default => 'badge-neutral',
        };
    }

    public function getStatusText($status)
    {
        return match ($status) {
            'pending' => __('messages.pending'),
            'process' => __('messages.processing'),
            'success' => __('messages.completed'),
            'failed' => __('messages.failed'),
            'refund' => 'Refund',
            default => ucfirst($status),
        };
    }

    public function getPaymentMethodName()
    {
        if (!$this->order) {
            return '-';
        }

        // Jika ada orderPayment dengan method code, cari payment gateway yang sesuai
        if ($this->order->orderPayment) {
            $paymentName = $this->order->orderPayment->payment_name;
            if ($paymentName) {
                return $paymentName;
            } else {
                return $this->order->orderPayment->paymentGateway->name ?? '-';
            }
        }

        return $this->order->payment_method ?? '-';
    }
}; ?>

<div class="py-6 md:py-12">
    <!-- Hero Section -->
    <div class="container mx-auto px-3 sm:px-4 max-w-4xl text-center mb-6 md:mb-8">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-12 h-12 md:w-16 md:h-16 mx-auto mb-3 md:mb-4 text-yellow-400">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
        </svg>
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2 md:mb-3 text-yellow-400">{{ __('messages.check_transaction_title') }}
        </h1>
        <p class="text-base sm:text-lg text-zinc-200">{{ __('messages.check_transaction_subtitle') }}</p>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-3 sm:px-4 max-w-4xl pb-8 md:pb-12">
        @if (!$order)
            <!-- Search Form -->
            <flux:card class="shadow-xl">
                <div class="p-4 sm:p-6 md:p-8">
                    <h2 class="text-lg sm:text-xl md:text-2xl font-semibold mb-4 sm:mb-6 text-zinc-800 dark:text-white">
                        {{ __('messages.transaction_code') }}
                    </h2>

                    <form wire:submit="checkOrder" class="space-y-4 sm:space-y-6">
                        <!-- Order Code -->
                        <div>
                            <flux:input type="text" wire:model="orderCode" :label="__('messages.transaction_code')"
                                :placeholder="__('messages.transaction_code_placeholder')" class="w-full" />
                            @error('orderCode')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </div>

                        @if ($notFound)
                            <flux:callout variant="danger" icon="x-circle">
                                {{ __('messages.transaction_not_found') }}.
                                {{ __('messages.transaction_not_found_desc') }}.
                            </flux:callout>
                        @endif

                        <div class="flex justify-end pt-2">
                            <flux:button type="submit" variant="primary" class="w-full md:w-auto"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="checkOrder" class="flex gap-0.5">
                                    <flux:icon.magnifying-glass class="w-5 h-5" />
                                    {{ __('messages.check_status') }}
                                </span>
                                <span wire:loading wire:target="checkOrder">
                                    <svg class="animate-spin h-5 w-5 inline-block mr-2"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ __('messages.loading') }}
                                </span>
                            </flux:button>
                        </div>
                    </form>

                    <flux:separator class="my-4 sm:my-6" />

                    <flux:callout variant="info" icon="information-circle" class="text-xs sm:text-sm text-zinc-400">
                        <strong class="text-zinc-200">Tips:</strong> Kode transaksi dapat ditemukan di email konfirmasi atau notifikasi
                        WhatsApp yang Anda terima setelah melakukan pemesanan.
                    </flux:callout>
                </div>
            </flux:card>
        @else
            <!-- Order Details -->
            <div class="space-y-4 sm:space-y-6">
                <!-- Success Alert -->
                <flux:callout variant="success" icon="check-circle">
                    <strong>Transaksi ditemukan!</strong> Berikut adalah detail transaksi Anda.
                </flux:callout>

                <!-- Order Information Card -->
                <flux:card class="shadow-xl">
                    <div class="p-4 sm:p-6 md:p-8">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 sm:gap-4 mb-4 sm:mb-6">
                            <h2
                                class="text-lg sm:text-xl md:text-2xl font-semibold text-zinc-800 dark:text-white flex items-center gap-2">
                                <flux:icon.document-text class="w-5 h-5 sm:w-6 sm:h-6" />
                                {{ __('messages.transaction_details') }}
                            </h2>

                            @php
                                $statusVariant = match ($order->status) {
                                    'pending' => 'yellow',
                                    'process' => 'blue',
                                    'success' => 'green',
                                    'failed' => 'red',
                                    'refund' => 'zinc',
                                    default => 'zinc',
                                };
                            @endphp

                            <flux:badge color="$statusVariant" size="lg">
                                {{ $this->getStatusText($order->status) }}
                            </flux:badge>
                        </div>

                        <flux:separator class="my-4 sm:my-6" />

                        <!-- Order Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">
                                    {{ __('messages.transaction_code') }}</p>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-base sm:text-lg text-zinc-800 dark:text-white break-all">{{ $order->order_code }}
                                    </p>
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $order->order_code }}'); this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' class=\'w-4 h-4 sm:w-5 sm:h-5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M4.5 12.75l6 6 9-13.5\' /></svg>'; setTimeout(() => { this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' class=\'w-4 h-4 sm:w-5 sm:h-5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184\' /></svg>'; }, 2000)"
                                        class="p-1 sm:p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors flex-shrink-0"
                                        title="Salin kode transaksi">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-4 h-4 sm:w-5 sm:h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Tanggal Pemesanan</p>
                                <p class="font-semibold text-zinc-800 dark:text-white">
                                    {{ $order->created_at->format('d M Y, H:i') }} WIB</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Game</p>
                                <p class="font-semibold text-zinc-800 dark:text-white">{{ $order->game->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Produk</p>
                                <p class="font-semibold text-zinc-800 dark:text-white">{{ $order->product->name }}</p>
                            </div>

                            @if (!empty($order->data_player))
                                <div class="md:col-span-2">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">Data Player</p>
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 bg-zinc-50 dark:bg-zinc-800 p-3 sm:p-4 rounded-lg">
                                        @foreach ($order->data_player as $key => $value)
                                            @if ($value)
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ ucwords(str_replace('_', ' ', $key)) }}</p>
                                                    <p class="font-semibold text-sm text-zinc-800 dark:text-white">
                                                        {{ $value }}</p>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <flux:separator class="my-4 sm:my-6" />

                        <!-- Price -->
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 p-3 sm:p-4 md:p-6 rounded-lg border-2 border-yellow-400">
                            <span class="text-base sm:text-lg md:text-xl font-semibold text-zinc-800 dark:text-white">Total
                                Pembayaran</span>
                            <span class="text-xl sm:text-xl md:text-2xl font-bold text-yellow-600 dark:text-yellow-400">Rp
                                {{ number_format($order->price, 0, ',', '.') }}</span>
                        </div>

                        @if ($order->orderPayment)
                            <flux:separator class="my-4 sm:my-6" />

                            <!-- Payment Info -->
                            <div class="bg-zinc-50 dark:bg-zinc-800 p-3 sm:p-4 md:p-6 rounded-lg">
                                <h3
                                    class="font-bold text-base sm:text-lg mb-3 sm:mb-4 text-zinc-800 dark:text-white flex items-center gap-2">
                                    <flux:icon.credit-card class="w-5 h-5" />
                                    Informasi Pembayaran
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Metode Pembayaran</p>
                                        <p class="font-semibold text-zinc-800 dark:text-white">
                                            {{ $this->getPaymentMethodName() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Status Pembayaran</p>
                                        @php
                                            $payVariant =
                                                $order->orderPayment->status === 'paid' ? 'success' : 'warning';
                                        @endphp
                                        <flux:badge color="$payVariant">
                                            {{ ucfirst($order->orderPayment->status) }}
                                        </flux:badge>
                                    </div>
                                    @if ($order->orderPayment->payment_reference)
                                        <div class="md:col-span-2">
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Referensi
                                                Pembayaran</p>
                                            <p
                                                class="font-mono text-xs bg-white dark:bg-zinc-900 p-2 sm:p-3 rounded border break-all text-zinc-800 dark:text-white">
                                                {{ $order->orderPayment->payment_reference }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Transaction Status Information -->
                        <flux:separator class="my-4 sm:my-6" />

                        @if ($order->status === 'success')
                            <flux:callout variant="success" icon="check-circle">
                                <strong>Transaksi Berhasil!</strong> Pesanan Anda telah berhasil diproses dan dikirim.
                                Silakan cek email atau game Anda.
                            </flux:callout>
                        @elseif($order->status === 'process')
                            <flux:callout variant="info" icon="clock">
                                <strong>Sedang Diproses</strong> Transaksi Anda sedang dalam proses. Harap tunggu
                                beberapa saat, biasanya 1-5 menit.
                            </flux:callout>
                        @elseif($order->status === 'failed')
                            @php
                                $noteData = is_string($order->note) ? json_decode($order->note, true) : $order->note;
                                $errorMessage = $noteData['message'] ?? 'Transaksi gagal diproses';
                                $responseCode = $noteData['rc'] ?? null;
                            @endphp
                            <flux:callout variant="danger" icon="x-circle">
                                <strong>Transaksi Gagal!</strong> {{ $errorMessage }}
                                <br><span class="text-sm mt-2 block">Silakan hubungi customer service jika ada
                                    pertanyaan atau coba lagi nanti.</span>
                            </flux:callout>
                        @elseif($order->status === 'pending')
                            <flux:callout variant="warning" icon="exclamation-triangle">
                                <strong>Menunggu Pembayaran</strong> Silakan selesaikan pembayaran Anda untuk
                                melanjutkan pesanan.
                            </flux:callout>
                        @elseif($order->status === 'refund')
                            <flux:callout variant="default" icon="arrow-path">
                                <strong>Refund</strong> Transaksi telah di-refund. Dana akan dikembalikan sesuai metode
                                pembayaran yang digunakan.
                            </flux:callout>
                        @endif

                        <!-- Actions -->
                        <flux:separator class="my-4 sm:my-6" />

                        @if ($order->orderPayment && $order->orderPayment->status === 'pending')
                            <div class="mb-4 sm:mb-6">
                                    Klik tombol di bawah untuk menyelesaikan
                                    pembayaran Anda.
                            </div>
                        @endif

                        <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-2 sm:gap-3">
                            @if ($order->orderPayment && $order->orderPayment->status === 'pending')
                                <flux:button href="{{ $paymentUrl }}" target="_blank"
                                    variant="primary">
                                    <flux:icon.credit-card class="w-5 h-5" />
                                    Bayar Sekarang
                                </flux:button>
                            @endif
                            <flux:button wire:click="clearForm" variant="ghost" icon="arrow-path">
                                Cek Transaksi Lain
                            </flux:button>
                            <flux:button href="/" wire:navigate variant="primary">
                                <flux:icon.home class="w-5 h-5" />
                                Kembali ke Home
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</div>
