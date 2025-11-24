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

<div class="bg-gradient-to-br from-black to-red-500 ">
    <!-- Hero Section -->
    <div class="py-12">
        <div class="container mx-auto px-4 max-w-4xl text-center">
            <div class="text-gray-800 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mx-auto mb-4 text-yellow-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                <h1 class="text-3xl md:text-4xl font-bold mb-3 text-yellow-400 text-shadow">Cek Status Transaksi</h1>
                <p class="text-lg opacity-90 text-gray-300 text-shadow">Lacak pesanan Anda dengan mudah menggunakan kode transaksi</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 max-w-4xl py-12">
        @if(!$order)
        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-6 flex items-center gap-3">
                    Masukkan Data Transaksi
                </h2>

                <form wire:submit="checkOrder" class="space-y-4">
                    <!-- Order Code -->
                    <div>
                        <label class="block mb-2 font-semibold text-sm">
                            <span class="inline-flex items-center gap-2">
                                Kode Transaksi
                            </span>
                        </label>
                        <flux:input
                            type="text"
                            wire:model="orderCode"
                            placeholder="Contoh: ORD-123456789"
                            class="w-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        />
                        <flux:error>
                            @error('orderCode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                {{ $message }}
                            @enderror
                        </flux:error>

                    </div>

                    @if($notFound)
                    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-md flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>Transaksi tidak ditemukan. Pastikan kode order transaksi benar.</div>
                    </div>
                    @endif

                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" class="w-full md:w-auto bg-primary text-white px-5 py-2 rounded-md flex items-center justify-center gap-3 disabled:opacity-60" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkOrder" class="flex items-center gap-2">
                                Cek Transaksi
                            </span>
                            <span wire:loading wire:target="checkOrder" class="flex items-center gap-2">
                                <span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Mencari...
                            </span>
                        </flux:button>
                    </div>
                </form>

                <div class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-medium mb-2">Tips</h3>
                    <div class="bg-gray-50 p-4 rounded-md text-sm text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Kode order dan email yang Anda gunakan saat melakukan transaksi dapat ditemukan di email konfirmasi atau notifikasi WhatsApp.
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Order Details -->
        <div class="space-y-6">
            <!-- Success Alert -->
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-md flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>Transaksi ditemukan!</div>
            </div>

            <!-- Order Information Card -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-2xl font-semibold flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Detail Transaksi
                        </h2>

                        @php
                            $statusBadge = match($order->status) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'process' => 'bg-blue-100 text-blue-800',
                                'success' => 'bg-green-100 text-green-800',
                                'failed'  => 'bg-red-100 text-red-800',
                                'refund'  => 'bg-gray-100 text-gray-800',
                                default   => 'bg-gray-100 text-gray-800',
                            };
                        @endphp

                        <span class="px-3 py-1 rounded-full font-semibold {{ $statusBadge }}">
                            {{ $this->getStatusText($order->status) }}
                        </span>
                    </div>

                    <hr class="border-t border-gray-200 my-2" />

                    <!-- Order Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Kode Order</p>
                            <p class="font-bold text-lg">{{ $order->order_code }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal</p>
                            <p class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Game</p>
                            <p class="font-semibold">{{ $order->game->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Produk</p>
                            <p class="font-semibold">{{ $order->product->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Player ID</p>
                            <p class="font-semibold">{{ $order->player_id }}</p>
                        </div>
                        @if($order->server_id)
                        <div>
                            <p class="text-sm text-gray-500">Server ID</p>
                            <p class="font-semibold">{{ $order->server_id }}</p>
                        </div>
                        @endif
                    </div>

                    <hr class="border-t border-gray-200 my-2" />

                    <!-- Price -->
                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md">
                        <span class="text-lg font-semibold">Total Pembayaran</span>
                        <span class="text-2xl font-bold text-primary">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                    </div>

                    @if($order->orderPayment)
                    <hr class="border-t border-gray-200 my-2" />

                    <!-- Payment Info -->
                    <div class="bg-gray-50 p-4 rounded-md space-y-3">
                        <h3 class="font-bold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            Informasi Pembayaran
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="text-gray-500">Metode</p>
                                <p class="font-semibold">{{ $order->orderPayment->paymentGateway->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Status Pembayaran</p>
                                @php $payClass = $order->orderPayment->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; @endphp
                                <span class="px-2 py-1 rounded-full text-sm font-semibold {{ $payClass }}">
                                    {{ ucfirst($order->orderPayment->status) }}
                                </span>
                            </div>
                            @if($order->orderPayment->payment_reference)
                            <div class="md:col-span-2">
                                <p class="text-gray-500">Referensi</p>
                                <p class="font-mono text-xs break-all">{{ $order->orderPayment->payment_reference }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($order->note)
                    <hr class="border-t border-gray-200 my-2" />
                    <div class="bg-gray-50 p-4 rounded-md flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-bold">Catatan</h3>
                            <div class="text-xs text-gray-700">{{ $order->note }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="flex justify-end items-center gap-3 pt-4">
                        <button wire:click="clearForm" class="border border-gray-300 px-4 py-2 rounded-md bg-white hover:bg-gray-50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            Cek Transaksi Lain
                        </button>
                        <a href="/" class="bg-primary text-white px-4 py-2 rounded-md flex items-center gap-2" wire:navigate>
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
