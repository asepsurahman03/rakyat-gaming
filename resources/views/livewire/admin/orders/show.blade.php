<?php

use App\Models\Order;
use App\Services\DigiflazzService;
use Livewire\Attributes\{Layout, Title};
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
        'pending' => 'warning',
        'process' => 'info',
        'success' => 'success',
        'failed' => 'danger',
        'refund' => 'zinc',
        'paid' => 'success',
        'expired' => 'danger',
        default => 'zinc',
    };
};

?>

<div>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="/admin/dashboard" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.orders.index') }}" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $order->order_code }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('admin.orders.index') }}" wire:navigate variant="ghost" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Detail Pesanan</flux:heading>
            <flux:subheading>{{ $order->order_code }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Info -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Informasi Pesanan</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:subheading class="mb-1">Order Code</flux:subheading>
                        <p class="font-mono font-semibold">{{ $order->order_code }}</p>
                    </div>
                    <div>
                        <flux:subheading class="mb-1">Tanggal</flux:subheading>
                        <p class="font-semibold">{{ $order->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <flux:subheading class="mb-1">Status Order</flux:subheading>
                        <flux:badge :color="$this->getStatusBadge($order->status)" class="mt-1">
                            {{ ucfirst($order->status) }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:subheading class="mb-1">Harga</flux:subheading>
                        <p class="font-semibold text-lg">Rp {{ number_format($order->price, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($order->note)
                    <flux:separator class="my-4" />
                    <div>
                        <flux:subheading class="mb-2">Catatan</flux:subheading>
                        <div class="bg-zinc-100 dark:bg-zinc-800 p-3 rounded-lg text-sm">
                            {{ is_array(json_decode($order->note, true)) ? json_encode(json_decode($order->note), JSON_PRETTY_PRINT) : $order->note }}
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Customer Info -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Informasi Customer</flux:heading>

                @if($order->user)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:subheading class="mb-1">Nama</flux:subheading>
                            <p class="font-semibold">{{ $order->user->name }}</p>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Email</flux:subheading>
                            <p class="font-semibold">{{ $order->user->email }}</p>
                        </div>
                        @if($order->user->profile && $order->user->profile->whatsapp)
                            <div>
                                <flux:subheading class="mb-1">WhatsApp</flux:subheading>
                                <p class="font-semibold">{{ $order->user->profile->whatsapp }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-zinc-500">Guest Order</p>
                @endif
            </flux:card>

            <!-- Product Info -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Detail Produk</flux:heading>

                <div class="flex items-center gap-4">
                    @if($order->game && $order->game->image)
                        <img src="{{ Storage::url($order->game->image) }}" alt="{{ $order->game->name }}" class="w-20 h-20 rounded-lg object-cover">
                    @endif
                    <div class="flex-1">
                        <h3 class="font-bold text-lg">{{ $order->game->name ?? '-' }}</h3>
                        <p class="text-sm text-zinc-500">{{ $order->product->name ?? '-' }}</p>
                        @if($order->product && $order->product->supplierProduct)
                            <p class="text-xs text-zinc-400 mt-1">SKU: {{ $order->product->supplierProduct->code }}</p>
                        @endif
                    </div>
                </div>

                @if($order->data_player && is_array($order->data_player))
                    <flux:separator class="my-4" />
                    <div>
                        <flux:subheading class="mb-2">Data Player</flux:subheading>
                        <div class="bg-zinc-100 dark:bg-zinc-800 p-3 rounded-lg space-y-2">
                            @foreach($order->data_player as $key => $value)
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-500">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                    <span class="font-medium">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Payment Info -->
            @if($order->orderPayment)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Informasi Pembayaran</flux:heading>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:subheading class="mb-1">Gateway</flux:subheading>
                            <p class="font-semibold">{{ $order->orderPayment->paymentGateway->name ?? '-' }}</p>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Metode</flux:subheading>
                            <p class="font-semibold">{{ strtoupper($order->orderPayment->method) }}</p>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Status Pembayaran</flux:subheading>
                            <flux:badge :color="$this->getStatusBadge($order->orderPayment->status)" class="mt-1">
                                {{ ucfirst($order->orderPayment->status) }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Jumlah</flux:subheading>
                            <p class="font-semibold text-lg">Rp {{ number_format($order->orderPayment->amount, 0, ',', '.') }}</p>
                        </div>
                        @if($order->orderPayment->payment_reference)
                            <div class="col-span-2">
                                <flux:subheading class="mb-1">Reference</flux:subheading>
                                <p class="font-mono text-sm">{{ $order->orderPayment->payment_reference }}</p>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            <!-- Digiflazz Logs -->
            @if($order->digiflazzLogs->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Log Digiflazz</flux:heading>

                    <div class="space-y-4">
                        @foreach($order->digiflazzLogs as $log)
                            <div class="bg-zinc-100 dark:bg-zinc-800 p-4 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <flux:badge :color="$this->getStatusBadge($log->status)" size="sm">{{ $log->status }}</flux:badge>
                                    <span class="text-xs text-zinc-500">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                                </div>
                                @if($log->request_body)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-sm font-medium py-2">Request</summary>
                                        <div class="mt-2 bg-white dark:bg-zinc-900 p-3 rounded">
                                            <pre class="text-xs overflow-x-auto">{{ json_encode($log->request_body, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </details>
                                @endif
                                @if($log->response_body)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-sm font-medium py-2">Response</summary>
                                        <div class="mt-2 bg-white dark:bg-zinc-900 p-3 rounded">
                                            <pre class="text-xs overflow-x-auto">{{ json_encode($log->response_body, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Right Column - Actions -->
        <div class="lg:col-span-1">
            <flux:card class="sticky top-4">
                <flux:heading size="lg" class="mb-4">Aksi</flux:heading>

                <div class="space-y-3">
                    <!-- Retry Topup -->
                    @if(in_array($order->status, ['failed', 'pending']) && $order->orderPayment && $order->orderPayment->status === 'paid')
                        <flux:button wire:click="retryTopup" wire:confirm="Yakin ingin retry topup?" variant="primary" class="w-full">
                            <flux:icon.arrow-path class="w-4 h-4" />
                            Retry Topup
                        </flux:button>
                    @endif

                    <!-- Update Status -->
                    <flux:separator />
                    <flux:subheading class="text-xs">Update Status</flux:subheading>

                    <flux:button wire:click="updateStatus('success')" wire:confirm="Set status menjadi Success?" variant="ghost" class="w-full text-green-600">
                        Set Success
                    </flux:button>
                    <flux:button wire:click="updateStatus('failed')" wire:confirm="Set status menjadi Failed?" variant="ghost" class="w-full text-red-600">
                        Set Failed
                    </flux:button>
                    <flux:button wire:click="updateStatus('refund')" wire:confirm="Set status menjadi Refund?" variant="ghost" class="w-full">
                        Set Refund
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>
