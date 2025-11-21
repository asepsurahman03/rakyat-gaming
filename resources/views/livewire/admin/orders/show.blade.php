<?php

use App\Models\Order;
use App\Services\DigiflazzService;
use function Livewire\Volt\{state, mount};

state(['order']);

mount(function (Order $order) {
    $this->order = $order->load(['user', 'game', 'product.supplierProduct', 'orderPayment.paymentGateway', 'digiflazzLogs']);
});

$retryTopup = function () {
    try {
        if (!$this->order->product || !$this->order->product->supplierProduct) {
            throw new \Exception('Produk atau supplier product tidak ditemukan');
        }

        $digiflazzService = app(DigiflazzService::class);
        $result = $digiflazzService->topup($this->order, $this->order->product->supplierProduct);

        if ($result['success']) {
            $this->dispatch('success', message: 'Topup berhasil diulang');
        } else {
            $this->dispatch('error', message: 'Topup gagal: ' . ($result['body']['message'] ?? 'Unknown error'));
        }

        $this->order->refresh();
    } catch (\Exception $e) {
        $this->dispatch('error', message: 'Error: ' . $e->getMessage());
    }
};

$updateStatus = function ($newStatus) {
    $this->order->update(['status' => $newStatus]);
    $this->dispatch('success', message: 'Status berhasil diupdate');
    $this->order->refresh();
};

$getStatusBadge = function ($status) {
    return match($status) {
        'pending' => 'badge-warning',
        'process' => 'badge-info',
        'success' => 'badge-success',
        'failed' => 'badge-error',
        'refund' => 'badge-secondary',
        'paid' => 'badge-success',
        'expired' => 'badge-error',
        default => 'badge-ghost',
    };
};

?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.orders.index') }}" wire:navigate class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>
        <div>
            <h1 class="text-2xl font-bold">Detail Pesanan</h1>
            <p class="text-base-content/60">{{ $order->order_code }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Info -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Informasi Pesanan</h2>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <p class="text-sm text-base-content/60">Order Code</p>
                            <p class="font-mono font-semibold">{{ $order->order_code }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Tanggal</p>
                            <p class="font-semibold">{{ $order->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Status Order</p>
                            <div class="badge {{ $this->getStatusBadge($order->status) }} mt-1">{{ ucfirst($order->status) }}</div>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Harga</p>
                            <p class="font-semibold text-lg">Rp {{ number_format($order->price, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if($order->note)
                        <div class="divider"></div>
                        <div>
                            <p class="text-sm text-base-content/60 mb-1">Catatan</p>
                            <div class="bg-base-200 p-3 rounded-lg text-sm">
                                {{ is_array(json_decode($order->note, true)) ? json_encode(json_decode($order->note), JSON_PRETTY_PRINT) : $order->note }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Info -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Informasi Customer</h2>

                    @if($order->user)
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <p class="text-sm text-base-content/60">Nama</p>
                                <p class="font-semibold">{{ $order->user->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-base-content/60">Email</p>
                                <p class="font-semibold">{{ $order->user->email }}</p>
                            </div>
                            @if($order->user->profile && $order->user->profile->whatsapp)
                                <div>
                                    <p class="text-sm text-base-content/60">WhatsApp</p>
                                    <p class="font-semibold">{{ $order->user->profile->whatsapp }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-base-content/60">Guest Order</p>
                    @endif
                </div>
            </div>

            <!-- Product Info -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Detail Produk</h2>

                    <div class="flex items-center gap-4 mt-4">
                        @if($order->game && $order->game->image)
                            <img src="{{ Storage::url($order->game->image) }}" alt="{{ $order->game->name }}" class="w-20 h-20 rounded-lg object-cover">
                        @endif
                        <div class="flex-1">
                            <h3 class="font-bold text-lg">{{ $order->game->name ?? '-' }}</h3>
                            <p class="text-sm text-base-content/60">{{ $order->product->name ?? '-' }}</p>
                            @if($order->product && $order->product->supplierProduct)
                                <p class="text-xs text-base-content/40 mt-1">SKU: {{ $order->product->supplierProduct->code }}</p>
                            @endif
                        </div>
                    </div>

                    @if($order->data_player && is_array($order->data_player))
                        <div class="divider"></div>
                        <div>
                            <p class="text-sm text-base-content/60 mb-2">Data Player</p>
                            <div class="bg-base-200 p-3 rounded-lg space-y-2">
                                @foreach($order->data_player as $key => $value)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-base-content/60">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="font-medium">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Info -->
            @if($order->orderPayment)
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Informasi Pembayaran</h2>

                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <p class="text-sm text-base-content/60">Gateway</p>
                                <p class="font-semibold">{{ $order->orderPayment->paymentGateway->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-base-content/60">Metode</p>
                                <p class="font-semibold">{{ strtoupper($order->orderPayment->method) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-base-content/60">Status Pembayaran</p>
                                <div class="badge {{ $this->getStatusBadge($order->orderPayment->status) }} mt-1">
                                    {{ ucfirst($order->orderPayment->status) }}
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-base-content/60">Jumlah</p>
                                <p class="font-semibold text-lg">Rp {{ number_format($order->orderPayment->amount, 0, ',', '.') }}</p>
                            </div>
                            @if($order->orderPayment->payment_reference)
                                <div class="col-span-2">
                                    <p class="text-sm text-base-content/60">Reference</p>
                                    <p class="font-mono text-sm">{{ $order->orderPayment->payment_reference }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Digiflazz Logs -->
            @if($order->digiflazzLogs->isNotEmpty())
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Log Digiflazz</h2>

                        <div class="space-y-4 mt-4">
                            @foreach($order->digiflazzLogs as $log)
                                <div class="bg-base-200 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="badge {{ $this->getStatusBadge($log->status) }} badge-sm">{{ $log->status }}</div>
                                        <span class="text-xs text-base-content/60">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                                    </div>
                                    @if($log->request_body)
                                        <details class="collapse collapse-arrow bg-base-300 mt-2">
                                            <summary class="collapse-title text-sm font-medium">Request</summary>
                                            <div class="collapse-content">
                                                <pre class="text-xs overflow-x-auto">{{ json_encode($log->request_body, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </details>
                                    @endif
                                    @if($log->response_body)
                                        <details class="collapse collapse-arrow bg-base-300 mt-2">
                                            <summary class="collapse-title text-sm font-medium">Response</summary>
                                            <div class="collapse-content">
                                                <pre class="text-xs overflow-x-auto">{{ json_encode($log->response_body, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column - Actions -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl sticky top-4">
                <div class="card-body">
                    <h2 class="card-title text-lg">Aksi</h2>

                    <div class="space-y-2 mt-4">
                        <!-- Retry Topup -->
                        @if(in_array($order->status, ['failed', 'pending']) && $order->orderPayment && $order->orderPayment->status === 'paid')
                            <button wire:click="retryTopup" wire:confirm="Yakin ingin retry topup?" class="btn btn-primary btn-block btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Retry Topup
                            </button>
                        @endif

                        <!-- Update Status -->
                        <div class="divider text-xs">Update Status</div>
                        <button wire:click="updateStatus('success')" wire:confirm="Set status menjadi Success?" class="btn btn-success btn-block btn-sm">
                            Set Success
                        </button>
                        <button wire:click="updateStatus('failed')" wire:confirm="Set status menjadi Failed?" class="btn btn-error btn-block btn-sm">
                            Set Failed
                        </button>
                        <button wire:click="updateStatus('refund')" wire:confirm="Set status menjadi Refund?" class="btn btn-secondary btn-block btn-sm">
                            Set Refund
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
