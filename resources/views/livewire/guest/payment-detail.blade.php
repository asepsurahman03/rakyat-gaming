<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Services\IpaymuService;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.guest')] class extends Component {
    public $settings;
    public $orderCode = '';
    public $order = null;
    public $paymentData = null;
    public $notFound = false;
    public $countdown = '';
    public $paymentMethod = '';

    public function mount()
    {
        // Get order code from URL parameter
        $this->orderCode = request()->get('order_code') ?? (request()->get('merchantOrderId') ?? '');
        if ($this->orderCode) {
            $this->loadOrder();
        }

        // Load settings
        $this->settings = [
            'url_whatsapp_cs' => env('CONTACT_WHATSAPP') ? 'https://wa.me/+62' . preg_replace('/\D/', '', env('CONTACT_WHATSAPP')) : null,
        ];
    }

    public function loadOrder()
    {
        $this->notFound = false;

        // Find order by order_code
        $this->order = Order::with(['game', 'product', 'orderPayment.paymentGateway'])
            ->where('order_code', $this->orderCode)
            ->first();

        if (!$this->order) {
            $this->notFound = true;
            return;
        }

        // Load payment data from orderPayment metadata
        if ($this->order->orderPayment && $this->order->orderPayment->metadata) {
            $this->paymentData = json_decode($this->order->orderPayment->metadata, true);
            $this->paymentMethod = $this->order->payment_method;
        }

        // Debug: Log payment method and data
        Log::info('Payment Detail - Payment Info', [
            'order_code' => $this->orderCode,
            'payment_method' => $this->paymentMethod,
            'payment_data' => $this->paymentData,
            'has_qr_string' => isset($this->paymentData['qr_string']),
        ]);

        // Debug: Log data player
        Log::info('Payment Detail - Data Player', [
            'order_code' => $this->orderCode,
            'data_player' => $this->order->data_player,
            'is_array' => is_array($this->order->data_player),
            'has_data' => $this->order->data_player ? count(array_filter($this->order->data_player)) : 0,
        ]);
    }

    public function checkStatus()
    {
        if (!$this->order || !$this->order->orderPayment || !$this->order->orderPayment->payment_reference) {
            Flux::toast(heading: 'Gagal', text: 'Informasi pembayaran tidak tersedia untuk transaksi ini.', variant: 'danger', position: 'top-right', duration: 5000);
            return;
        }

        try {
            $ipaymuService = app(IpaymuService::class);
            $result = $ipaymuService->checkTransactionStatus($this->order->orderPayment->payment_reference);

            if ($result['success']) {
                // Reload order to get updated status
                $this->loadOrder();

                Flux::toast(heading: 'Status pembayaran diperbarui', text: 'Silakan periksa status pembayaran Anda di bawah.', variant: 'success', position: 'top-right', duration: 5000);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check payment status', [
                'order_code' => $this->orderCode,
                'error' => $e->getMessage(),
            ]);

            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat memeriksa status pembayaran. Silakan coba lagi nanti.', variant: 'danger', position: 'top-right', duration: 5000);
        }
    }

    public function getPaymentMethodName()
    {
        if (!$this->order || !$this->order->orderPayment) {
            return '-';
        }

        return $this->order->orderPayment->payment_name ?? ($this->order->orderPayment->paymentGateway->name ?? ($this->order->payment_method ?? '-'));
    }

    public function getPaymentStatusText($status)
    {
        return match ($status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Sudah Dibayar',
            'failed' => 'Gagal',
            'expired' => 'Kadaluarsa',
            default => ucfirst($status),
        };
    }

    public function getPaymentStatusBadge($status)
    {
        return match ($status) {
            'pending' => 'warning',
            'paid', 'success' => 'success',
            'failed' => 'danger',
            'expired' => 'zinc',
            default => 'zinc',
        };
    }
}; ?>

<div class="min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        @if ($notFound)
            <!-- Not Found -->
            <flux:card class="shadow-xl bg-white/95 backdrop-blur">
                <div class="p-6 md:p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-20 h-20 mx-auto mb-4 text-zinc-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <h2 class="text-2xl font-bold mb-3">Transaksi Tidak Ditemukan</h2>
                    <p class="text-zinc-600 mb-6">Kode transaksi tidak valid atau tidak ditemukan di sistem kami.</p>

                    <div class="flex flex-col sm:flex-row justify-center gap-3">
                        <flux:button href="{{ route('cek-transaksi') }}" wire:navigate variant="ghost">
                            <flux:icon.magnifying-glass class="w-5 h-5" />
                            Cek Transaksi Lain
                        </flux:button>
                        <flux:button href="/" wire:navigate variant="primary">
                            <flux:icon.home class="w-5 h-5" />
                            Kembali ke Home
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @elseif($order)
            <!-- Payment Details -->
            <div class="space-y-6">
                <!-- Header -->
                <div class="text-center text-white mb-8">
                    @if ($order->orderPayment && $order->orderPayment->status === 'paid')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-16 h-16 mx-auto mb-4 text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h1 class="text-3xl md:text-4xl font-bold mb-2 text-green-400">Pembayaran Berhasil!</h1>
                        <p class="text-lg">Terima kasih, pembayaran Anda telah diterima</p>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-16 h-16 mx-auto mb-4 text-yellow-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <h1 class="text-3xl md:text-4xl font-bold mb-2 text-yellow-400">Selesaikan Pembayaran</h1>
                        <p class="text-lg">Silakan lakukan pembayaran sesuai instruksi di bawah</p>
                    @endif
                </div>

                @if ($order->orderPayment && $order->orderPayment->status === 'pending')
                    <!-- Payment Instructions -->
                    <flux:card class="shadow-xl bg-white/95 backdrop-blur">
                        <div class="p-6 md:p-8">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold flex items-center gap-2">
                                    <flux:icon.credit-card class="w-6 h-6" />
                                    Informasi Pembayaran
                                </h2>
                                @if ($order->orderPayment)
                                    <flux:badge :variant="$this->getPaymentStatusBadge($order->orderPayment->status)"
                                        size="lg">
                                        {{ $this->getPaymentStatusText($order->orderPayment->status) }}
                                    </flux:badge>
                                @endif
                            </div>

                            <flux:separator class="my-6" />

                            <!-- Payment Method -->
                            <div class="mb-6">
                                <p class="text-sm text-zinc-500 mb-2">Metode Pembayaran</p>
                                <div class="flex items-center gap-3">
                                    @if ($paymentData && isset($paymentData['logo']))
                                        <img src="{{ $paymentData['logo'] }}" alt="Payment Method" class="h-8">
                                    @endif
                                </div>
                            </div>

                            <!-- Virtual Account Number (if available) -->
                            @if ($paymentMethod !== 'alfamart' && $paymentMethod !== 'indomaret' && $paymentMethod !== 'mpm' && $paymentMethod !== 'cc' && $paymentData && isset($paymentData['payment_no']))
                                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6 mb-6">
                                    <p class="text-lg font-semibold text-zinc-800 mb-2">Nomor Virtual Account</p>
                                    <p class="text-sm text-zinc-600 mb-4">Transfer ke nomor Virtual Account berikut</p>

                                    <div class="bg-white rounded-lg p-4 mb-4">
                                        <div class="flex items-center justify-between gap-4 mb-4">
                                            <div>
                                                <p class="text-xs text-zinc-500 mb-1">Nomor Virtual Account</p>
                                                <p class="text-2xl font-bold text-zinc-900 tracking-wider">
                                                    {{ $paymentData['payment_no'] }}
                                                </p>
                                            </div>
                                            <button type="button"
                                                onclick="navigator.clipboard.writeText('{{ $paymentData['payment_no'] }}'); this.querySelector('.copy-icon').classList.add('hidden'); this.querySelector('.check-icon').classList.remove('hidden'); setTimeout(() => { this.querySelector('.copy-icon').classList.remove('hidden'); this.querySelector('.check-icon').classList.add('hidden'); }, 2000)"
                                                class="flex-shrink-0 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="2" stroke="currentColor" class="w-5 h-5 copy-icon">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="2" stroke="currentColor"
                                                    class="w-5 h-5 check-icon hidden">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                Salin
                                            </button>
                                        </div>

                                        @if ($paymentData && isset($paymentData['payment_name']))
                                            <div class="text-center pt-4 border-t border-zinc-200">
                                                <p class="text-xs text-zinc-500 mb-1">Nama Pembayaran</p>
                                                <p class="font-semibold text-zinc-800">
                                                    {{ $paymentData['payment_name'] }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="bg-yellow-100 rounded-lg p-4">
                                        <p class="text-sm font-semibold text-yellow-900 mb-2">Cara Pembayaran:</p>
                                        <ol class="text-sm text-yellow-800 space-y-1 list-decimal list-inside">
                                            <li>Salin nomor Virtual Account di atas</li>
                                            <li>Buka aplikasi mobile banking atau ATM</li>
                                            <li>Pilih menu transfer ke Virtual Account</li>
                                            <li>Masukkan nomor Virtual Account</li>
                                            <li>Masukkan jumlah pembayaran yang sesuai</li>
                                            <li>Konfirmasi dan selesaikan pembayaran</li>
                                            <li>Pembayaran akan otomatis terverifikasi</li>
                                        </ol>
                                    </div>
                                </div>
                            @endif

                            <!-- QRIS Code (if available) -->
                            @if ($paymentMethod === 'mpm' && $paymentData && isset($paymentData['qr_string']) && !empty($paymentData['qr_string']))
                                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-6">
                                    <p class="text-lg font-semibold text-zinc-800 mb-2">Scan QRIS untuk Membayar</p>
                                    <p class="text-sm text-zinc-600 mb-4">Gunakan aplikasi e-wallet atau mobile banking Anda</p>

                                    <div class="bg-white rounded-lg p-4 mb-4">
                                        <div class="text-center mb-4">
                                            {{-- Generate QR Code from qr_string (iPaymu's QrImage has CORS restrictions) --}}
                                            <div id="qrcode" class="flex justify-center"></div>
                                        </div>

                                        {{-- Link to open in new tab --}}
                                        @if (isset($paymentData['QrImage']) && !empty($paymentData['QrImage']))
                                            <div class="text-center pt-4 border-t border-zinc-200">
                                                <a href="{{ $paymentData['QrImage'] }}" target="_blank"
                                                    class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                    Buka QR Code di Tab Baru
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="bg-blue-100 rounded-lg p-4">
                                        <p class="text-sm font-semibold text-blue-900 mb-2">Cara Pembayaran:</p>
                                        <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                                            <li>Buka aplikasi mobile banking atau e-wallet</li>
                                            <li>Pilih menu scan QRIS</li>
                                            <li>Scan kode QR di atas</li>
                                            <li>Konfirmasi jumlah pembayaran</li>
                                            <li>Selesaikan pembayaran</li>
                                            <li>Pembayaran akan otomatis terverifikasi</li>
                                        </ol>
                                    </div>

                                    {{-- Load QRCode.js library --}}
                                    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const qrcodeContainer = document.getElementById('qrcode');
                                            if (qrcodeContainer && qrcodeContainer.innerHTML === '') {
                                                new QRCode(qrcodeContainer, {
                                                    text: "{{ $paymentData['qr_string'] }}",
                                                    width: 256,
                                                    height: 256,
                                                    colorDark: "#000000",
                                                    colorLight: "#ffffff",
                                                    correctLevel: QRCode.CorrectLevel.H
                                                });
                                            }
                                        });
                                    </script>
                                </div>
                            @endif

                            <!-- Payment URL (if available and not QRIS/VA) -->
                            @if (
                                $paymentMethod === 'cc' &&
                                    $paymentData &&
                                    isset($paymentData['payment_url']) &&
                                    !empty($paymentData['payment_url']))
                                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-6">
                                    <p class="text-lg font-semibold text-zinc-800 mb-2">Pembayaran Kartu Kredit</p>
                                    <p class="text-sm text-zinc-600 mb-4">Klik tombol di bawah untuk melanjutkan pembayaran</p>

                                    <div class="bg-white rounded-lg p-4 mb-4">
                                        <flux:button href="{{ $paymentData['payment_url'] }}" target="_blank"
                                            variant="primary" class="w-full">
                                            <flux:icon.arrow-top-right-on-square class="w-5 h-5" />
                                            Lanjutkan Pembayaran
                                        </flux:button>
                                    </div>

                                    <div class="bg-green-100 rounded-lg p-4">
                                        <p class="text-sm font-semibold text-green-900 mb-2">Cara Pembayaran:</p>
                                        <ol class="text-sm text-green-800 space-y-1 list-decimal list-inside">
                                            <li>Klik tombol "Lanjutkan Pembayaran" di atas</li>
                                            <li>Anda akan diarahkan ke halaman pembayaran</li>
                                            <li>Masukkan informasi kartu kredit Anda</li>
                                            <li>Konfirmasi dan selesaikan pembayaran</li>
                                            <li>Pembayaran akan otomatis terverifikasi</li>
                                        </ol>
                                    </div>
                                </div>
                            @endif

                            @if ($paymentMethod === 'alfamart' || $paymentMethod === 'indomart')
                                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-6">
                                    <p class="text-lg font-semibold text-zinc-800 mb-2">Kode Pembayaran
                                        {{ ucfirst($paymentMethod) }}</p>
                                    <p class="text-sm text-zinc-600 mb-4">Tunjukkan kode ini kepada kasir
                                        {{ ucfirst($paymentMethod) }}</p>

                                    <div class="bg-white rounded-lg p-4 mb-4">
                                        @if ($paymentData && isset($paymentData['payment_no']))
                                            <div class="flex items-center justify-between gap-4 mb-4">
                                                <div>
                                                    <p class="text-xs text-zinc-500 mb-1">Kode Pembayaran</p>
                                                    <p class="text-2xl font-bold text-zinc-900 tracking-wider">
                                                        {{ $paymentData['payment_no'] }}
                                                    </p>
                                                </div>
                                                <button type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $paymentData['payment_no'] }}'); this.querySelector('.copy-icon').classList.add('hidden'); this.querySelector('.check-icon').classList.remove('hidden'); setTimeout(() => { this.querySelector('.copy-icon').classList.remove('hidden'); this.querySelector('.check-icon').classList.add('hidden'); }, 2000)"
                                                    class="flex-shrink-0 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        class="w-5 h-5 copy-icon">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                                    </svg>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        class="w-5 h-5 check-icon hidden">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                    Salin
                                                </button>
                                            </div>
                                        @endif

                                        @if ($paymentData && isset($paymentData['payment_name']))
                                            <div class="text-center pt-4 border-t border-zinc-200">
                                                <p class="text-xs text-zinc-500 mb-1">Nama Pembayaran</p>
                                                <p class="font-semibold text-zinc-800">
                                                    {{ $paymentData['payment_name'] }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="bg-blue-100 rounded-lg p-4">
                                        <p class="text-sm font-semibold text-blue-900 mb-2">Cara Pembayaran:</p>
                                        <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                                            <li>Kunjungi gerai {{ ucfirst($paymentMethod) }} terdekat</li>
                                            <li>Informasikan kepada kasir bahwa ingin melakukan pembayaran</li>
                                            <li>Tunjukkan kode pembayaran di atas</li>
                                            <li>Lakukan pembayaran sejumlah yang tertera</li>
                                            <li>Simpan struk sebagai bukti pembayaran</li>
                                            <li>Pembayaran akan otomatis terverifikasi</li>
                                        </ol>
                                    </div>
                                </div>
                            @endif

                            <!-- Payment Amount -->
                            <div class="bg-zinc-50 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-zinc-600">Total Pembayaran</span>
                                    <span class="text-2xl font-bold text-zinc-900">Rp
                                        {{ number_format($order->orderPayment->amount ?? $order->price, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Expiry Time -->
                            @if ($paymentData && isset($paymentData['expired']))
                                <div class="flex items-center gap-2 text-amber-600 mb-6">
                                    <flux:icon.clock class="w-5 h-5" />
                                    <span class="text-sm">Berlaku hingga:
                                        <strong>{{ \Carbon\Carbon::parse($paymentData['expired'])->format('d M Y, H:i') }}
                                            WIB</strong></span>
                                </div>
                            @endif

                            <!-- Check Status Button -->
                            <div class="mt-6">
                                <flux:button wire:click="checkStatus" variant="outline" class="w-full"
                                    icon="arrow-path">
                                    Cek Status Pembayaran
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @endif

                <!-- Order Information Card -->
                <flux:card class="shadow-xl bg-white/95 backdrop-blur">
                    <div class="p-6 md:p-8">
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                            <flux:icon.document-text class="w-6 h-6" />
                            Detail Pesanan
                        </h2>

                        <flux:separator class="my-6" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Kode Transaksi</p>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold">{{ $order->order_code }}</p>
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $order->order_code }}'); this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'2\' stroke=\'currentColor\' class=\'w-4 h-4\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M4.5 12.75l6 6 9-13.5\' /></svg>'; setTimeout(() => { this.innerHTML = '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'2\' stroke=\'currentColor\' class=\'w-4 h-4\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184\' /></svg>'; }, 2000)"
                                        class="p-1 rounded hover:bg-zinc-100 text-zinc-500" title="Salin kode">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Tanggal</p>
                                <p class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }} WIB</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Game</p>
                                <p class="font-semibold">{{ $order->game->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Produk</p>
                                <p class="font-semibold">{{ $order->product->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Email</p>
                                <p class="font-semibold">{{ $order->email }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">WhatsApp</p>
                                <p class="font-semibold">{{ $order->whatsapp }}</p>
                            </div>
                        </div>

                        @if ($order->data_player && is_array($order->data_player) && count(array_filter($order->data_player)) > 0)
                            <flux:separator class="my-6" />
                            <div>
                                <p class="text-sm text-zinc-500 mb-3">Data Player</p>
                                <div class="bg-zinc-50 rounded-lg p-4 space-y-2">
                                    @foreach ($order->data_player as $key => $value)
                                        @if ($value !== null && $value !== '')
                                            <div class="flex gap-2 text-sm">
                                                <span
                                                    class="text-zinc-600 font-medium">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                <span class="font-semibold text-zinc-900">: {{ $value }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <flux:button href="{{ route('cek-transaksi') }}" wire:navigate variant="primary" class="flex-1">
                        <flux:icon.magnifying-glass class="w-5 h-5" />
                        Cek Transaksi Lain
                    </flux:button>
                    <flux:button href="/" wire:navigate variant="outline" class="flex-1">
                        <flux:icon.home class="w-5 h-5" />
                        Kembali ke Home
                    </flux:button>
                </div>

                <!-- Help Section -->
                <flux:card class="shadow-xl bg-white/95 backdrop-blur">
                    <div class="p-6 text-center">
                        <p class=" mb-3">Butuh bantuan?</p>
                        <p class="text-sm text-zinc-500 mb-4">Hubungi customer service kami jika mengalami kendala</p>
                        <div class="flex flex-col sm:flex-row justify-center gap-3">
                            <flux:button href="{{ $settings['url_whatsapp_cs'] }}" target="_blank" variant="primary"
                                class="flex-1" color="green">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                                WhatsApp CS
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</div>
