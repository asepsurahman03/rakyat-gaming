<?php

use App\Models\Order;
use App\Services\SupplierServiceFactory;
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

        // Get supplier code from product type
        $supplierCode = $this->order->product->product_type;

        if (!$supplierCode || $supplierCode === 'manual') {
            throw new \Exception('Produk ini adalah produk manual, tidak dapat di-retry');
        }

        // Get supplier service dynamically based on product type
        $factory = app(SupplierServiceFactory::class);

        if (!$factory->has($supplierCode)) {
            throw new \Exception("Supplier service tidak ditemukan untuk: {$supplierCode}");
        }

        $supplierService = $factory->make($supplierCode);
        $result = $supplierService->processTransaction($this->order, $this->order->product->supplierProduct);

        if ($result['success']) {
            Flux::toast(heading: 'Topup berhasil di-retry!', text: "Permintaan topup telah dikirim ulang ke " . ucfirst($supplierCode) . ".", variant: 'success', duration: 3000, position: 'top-right');
        } else {
            Flux::toast(heading: 'Gagal melakukan retry topup', text: 'Error: ' . ($result['message'] ?? 'Unknown error'), variant: 'danger', position: 'top-right');
        }

        $this->order->refresh();
    } catch (\Exception $e) {
        Flux::toast(heading: 'Gagal melakukan retry topup', text: 'Error: ' . $e->getMessage(), variant: 'danger', position: 'top-right');
    }
};

$updateStatus = function ($newStatus) {
    $this->order->update(['status' => $newStatus]);
    Flux::toast(heading: 'Status order diperbarui!', text: 'Status order telah diubah menjadi ' . ucfirst($newStatus) . '.', variant: 'success', duration: 3000, position: 'top-right');
    $this->order->refresh();
};

$refreshStatus = function () {
    try {
        if (!$this->order->product || !$this->order->product->supplierProduct) {
            throw new \Exception('Produk atau supplier product tidak ditemukan');
        }

        // Get supplier code from product type
        $supplierCode = $this->order->product->product_type;

        if (!$supplierCode || $supplierCode === 'manual') {
            throw new \Exception('Produk ini adalah produk manual, tidak dapat melakukan check status');
        }

        // Get supplier service dynamically based on product type
        $factory = app(SupplierServiceFactory::class);

        if (!$factory->has($supplierCode)) {
            throw new \Exception("Supplier service tidak ditemukan untuk: {$supplierCode}");
        }

        $supplierService = $factory->make($supplierCode);

        // Check if supplier service has checkTransactionStatus method
        if (!method_exists($supplierService, 'checkTransactionStatus')) {
            throw new \Exception("Supplier {$supplierCode} tidak mendukung pengecekan status");
        }

        $result = $supplierService->checkTransactionStatus($this->order);

        if ($result['success']) {
            Flux::toast(heading: 'Status berhasil diperbarui!', text: "Status telah di-refresh dari " . ucfirst($supplierCode) . ".", variant: 'success', duration: 3000, position: 'top-right');
        } else {
            Flux::toast(heading: 'Gagal memeriksa status', text: 'Error: ' . ($result['message'] ?? 'Unknown error'), variant: 'warning', position: 'top-right');
        }

        $this->order->refresh();
        $this->order->load(['user', 'game', 'product.supplierProduct', 'orderPayment.paymentGateway', 'digiflazzLogs']);
    } catch (\Exception $e) {
        Flux::toast(heading: 'Gagal memeriksa status', text: 'Error: ' . $e->getMessage(), variant: 'danger', position: 'top-right');
    }
};

$getStatusBadge = function ($status) {
    return match ($status) {
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
            </flux:card>

            <!-- Customer Info -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Informasi Customer</flux:heading>

                @if ($order->user)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:subheading class="mb-1">Nama</flux:subheading>
                            <p class="font-semibold">{{ $order->user->name }}</p>
                        </div>
                        <div>
                            <flux:subheading class="mb-1">Email</flux:subheading>
                            <p class="font-semibold">{{ $order->user->email }}</p>
                        </div>
                        @if ($order->user->profile && $order->user->profile->whatsapp)
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
                    @if ($order->game && $order->game->image)
                        <img src="{{ Storage::url($order->game->image) }}" alt="{{ $order->game->name }}"
                            class="w-20 h-20 rounded-lg object-cover">
                    @endif
                    <div class="flex-1">
                        <h3 class="font-bold text-lg">{{ $order->game->name ?? '-' }}</h3>
                        <p class="text-sm text-zinc-500">{{ $order->product->name ?? '-' }}</p>
                        @if ($order->product && $order->product->supplierProduct)
                            <p class="text-xs text-zinc-400 mt-1">SKU: {{ $order->product->supplierProduct->code }}</p>
                        @endif
                    </div>
                </div>

                @if ($order->data_player && is_array($order->data_player))
                    <flux:separator class="my-4" />
                    <div>
                        <flux:subheading class="mb-2">Data Player</flux:subheading>
                        <div class="bg-zinc-100 dark:bg-zinc-800 p-3 rounded-lg space-y-2">
                            @foreach ($order->data_player as $key => $value)
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
            @if ($order->orderPayment)
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
                            <p class="font-semibold text-lg">Rp
                                {{ number_format($order->orderPayment->amount, 0, ',', '.') }}</p>
                        </div>
                        @if ($order->orderPayment->payment_reference)
                            <div class="col-span-2">
                                <flux:subheading class="mb-1">Reference</flux:subheading>
                                <p class="font-mono text-sm">{{ $order->orderPayment->payment_reference }}</p>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            <!-- Digiflazz Logs -->
            @if ($order->digiflazzLogs->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Log </flux:heading>

                    <div class="space-y-4">
                        @foreach ($order->digiflazzLogs as $log)
                            <div class="bg-zinc-100 dark:bg-zinc-800 p-4 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <flux:badge :color="$this->getStatusBadge($log->status)" size="sm">
                                        {{ $log->status }}</flux:badge>
                                    <span
                                        class="text-xs text-zinc-500">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                                </div>
                                @if ($log->request_body)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-sm font-medium py-2">Request</summary>
                                        <div class="mt-2 bg-white dark:bg-zinc-900 p-3 rounded">
                                            <pre class="text-xs overflow-x-auto">{{ json_encode($log->request_body, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </details>
                                @endif
                                @if ($log->response_body)
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
                    <!-- Refresh Status from Supplier -->
                    @if ($order->product && $order->product->product_type && $order->product->product_type !== 'manual')
                        <flux:button wire:click="refreshStatus" icon="arrow-path" variant="primary" class="w-full">
                            Cek Status di Supplier
                        </flux:button>
                    @endif

                    <!-- Retry Topup -->
                    @if (in_array($order->status, ['failed', 'pending']) && $order->orderPayment && $order->orderPayment->status === 'paid')
                        <flux:button wire:click="retryTopup" icon="arrow-path" wire:confirm="Yakin ingin retry topup?"
                            variant="primary" class="w-full">
                            Retry Topup
                        </flux:button>
                    @endif

                    <!-- Update Status -->
                    <flux:separator />
                    <flux:subheading class="text-xs">Update Status</flux:subheading>

                    <flux:modal.trigger name="update-status-success">
                        <flux:button variant="ghost" class="w-full">
                            Set Success
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:modal name="update-status-success">
                    <flux:heading size="lg" class="mb-4">Konfirmasi Set Success</flux:heading>
                    <flux:text>Apakah Anda yakin ingin mengubah status order menjadi Success?
                    </flux:text>
                    <div class="mt-6 flex justify-end gap-3">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Batal</flux:button>
                        </flux:modal.close>
                        <flux:modal.close>
                            <flux:button wire:click="updateStatus('success')" variant="primary">
                                Ya, Set Success
                            </flux:button>
                        </flux:modal.close>

                    </div>
                    </flux:modal>
                    <flux:modal.trigger name="update-status-failed">
                        <flux:button variant="ghost" class="w-full">
                            Set Failed
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:modal name="update-status-failed">
                    <flux:heading size="lg" class="mb-4">Konfirmasi Set Failed</flux:heading>
                    <flux:text>Apakah Anda yakin ingin mengubah status order menjadi Failed?</flux:text>
                    <div class="mt-6 flex justify-end gap-3">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Batal</flux:button>
                        </flux:modal.close>
                        <flux:modal.close>
                            <flux:button wire:click="updateStatus('failed')" variant="primary">
                                Ya, Set failed
                            </flux:button>
                        </flux:modal.close>

                    </div>
                    </flux:modal>
                    <flux:modal.trigger name="update-status-refund">
                        <flux:button variant="ghost" class="w-full">
                            Set Refund
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:modal name="update-status-refund">
                    <flux:heading size="lg" class="mb-4">Konfirmasi Set Refund</flux:heading>
                    <flux:text>Apakah Anda yakin ingin mengubah status order menjadi Refund?
                    </flux:text>
                    <div class="mt-6 flex justify-end gap-3">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Batal</flux:button>
                        </flux:modal.close>
                        <flux:modal.close>
                            <flux:button wire:click="updateStatus('refund')" variant="primary">
                                Ya, Set refund
                            </flux:button>
                        </flux:modal.close>
                    </div>
                    </flux:modal>
                </div>
            </flux:card>
        </div>
    </div>
</div>
