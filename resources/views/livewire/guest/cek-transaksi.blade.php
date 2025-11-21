<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('components.layouts.guest')] class extends Component {
    public $orderCode = '';
    public $email = '';
    public $order = null;
    public $notFound = false;

    public function checkOrder()
    {
        $this->validate([
            'orderCode' => 'required|string',
            'email' => 'required|email',
        ]);

        $this->notFound = false;

        // Cari order berdasarkan order_code dan user email
        $this->order = Order::with(['user', 'game', 'product', 'orderPayment.paymentGateway'])
            ->where('order_code', $this->orderCode)
            ->whereHas('user', function($query) {
                $query->where('email', $this->email);
            })
            ->first();

        if (!$this->order) {
            $this->notFound = true;
        }
    }

    public function clearForm()
    {
        $this->orderCode = '';
        $this->email = '';
        $this->order = null;
        $this->notFound = false;
    }

    public function getStatusBadgeClass($status)
    {
        return match($status) {
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
        return match($status) {
            'pending' => 'Menunggu Pembayaran',
            'process' => 'Diproses',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'refund' => 'Refund',
            default => ucfirst($status),
        };
    }
}; ?>

<div>
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-primary to-secondary py-12">
        <div class="container mx-auto px-4 max-w-4xl text-center">
            <div class="text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mx-auto mb-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                <h1 class="text-3xl md:text-4xl font-bold mb-3">Cek Status Transaksi</h1>
                <p class="text-lg opacity-90">Lacak pesanan Anda dengan mudah menggunakan kode order dan email</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 max-w-4xl py-12">
        @if(!$order)
        <!-- Search Form -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    Masukkan Data Transaksi
                </h2>

                <form wire:submit="checkOrder" class="space-y-4">
                    <!-- Order Code -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                </svg>
                                Kode Order
                            </span>
                        </label>
                        <input
                            type="text"
                            wire:model="orderCode"
                            placeholder="Contoh: ORD-123456789"
                            class="input input-bordered w-full @error('orderCode') input-error @enderror"
                        />
                        @error('orderCode')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                                Email
                            </span>
                        </label>
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="email@example.com"
                            class="input input-bordered focus:border-primary focus:outline-primary w-full @error('email') input-error @enderror"
                        />
                        @error('email')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    @if($notFound)
                    <div class="alert alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Transaksi tidak ditemukan. Pastikan kode order dan email sudah benar.</span>
                    </div>
                    @endif

                    <div class="card-actions justify-end pt-4">
                        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkOrder" class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                Cek Transaksi
                            </span>
                            <span wire:loading style="display: none" wire:target="checkOrder" class="flex items-center gap-2">
                                <span class="loading loading-spinner loading-sm"></span>
                                Mencari...
                            </span>
                        </button>
                    </div>
                </form>

                <div class="divider">Tips</div>
                <div class="bg-base-200 p-4 rounded-lg">
                    <p class="text-sm opacity-70">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Kode order dan email yang Anda gunakan saat melakukan transaksi dapat ditemukan di email konfirmasi atau notifikasi WhatsApp.
                    </p>
                </div>
            </div>
        </div>
        @else
        <!-- Order Details -->
        <div class="space-y-6">
            <!-- Success Alert -->
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Transaksi ditemukan!</span>
            </div>

            <!-- Order Information Card -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="card-title text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Detail Transaksi
                        </h2>
                        <div class="badge {{ $this->getStatusBadgeClass($order->status) }} badge-lg">
                            {{ $this->getStatusText($order->status) }}
                        </div>
                    </div>

                    <div class="divider my-2"></div>

                    <!-- Order Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm opacity-70">Kode Order</p>
                            <p class="font-bold text-lg">{{ $order->order_code }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-70">Tanggal</p>
                            <p class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-70">Game</p>
                            <p class="font-semibold">{{ $order->game->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-70">Produk</p>
                            <p class="font-semibold">{{ $order->product->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-70">Player ID</p>
                            <p class="font-semibold">{{ $order->player_id }}</p>
                        </div>
                        @if($order->server_id)
                        <div>
                            <p class="text-sm opacity-70">Server ID</p>
                            <p class="font-semibold">{{ $order->server_id }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="divider my-2"></div>

                    <!-- Price -->
                    <div class="flex justify-between items-center bg-base-200 p-4 rounded-lg">
                        <span class="text-lg font-semibold">Total Pembayaran</span>
                        <span class="text-2xl font-bold text-primary">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                    </div>

                    @if($order->orderPayment)
                    <div class="divider my-2"></div>

                    <!-- Payment Info -->
                    <div class="bg-base-200 p-4 rounded-lg space-y-2">
                        <h3 class="font-bold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            Informasi Pembayaran
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="opacity-70">Metode</p>
                                <p class="font-semibold">{{ $order->orderPayment->paymentGateway->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="opacity-70">Status Pembayaran</p>
                                <div class="badge {{ $order->orderPayment->status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($order->orderPayment->status) }}
                                </div>
                            </div>
                            @if($order->orderPayment->payment_reference)
                            <div class="md:col-span-2">
                                <p class="opacity-70">Referensi</p>
                                <p class="font-mono text-xs">{{ $order->orderPayment->payment_reference }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($order->note)
                    <div class="divider my-2"></div>
                    <div class="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-bold">Catatan</h3>
                            <div class="text-xs">{{ $order->note }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="card-actions justify-end pt-4">
                        <button wire:click="clearForm" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            Cek Transaksi Lain
                        </button>
                        <a href="/" class="btn btn-primary" wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            Kembali ke Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
