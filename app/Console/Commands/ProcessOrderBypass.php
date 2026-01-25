<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SupplierProduct;
use App\Services\Suppliers\OnoyStoreSupplierService;
use Illuminate\Console\Command;

class ProcessOrderBypass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:process-bypass {order_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process order transaction bypass without payment (for testing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderCode = $this->argument('order_code');

        // Find order with relations
        $order = Order::with(['product.supplierProduct.supplier'])->where('order_code', $orderCode)->first();

        if (!$order) {
            $this->error("Order dengan code {$orderCode} tidak ditemukan!");
            return 1;
        }

        $this->info("Order ditemukan: {$order->order_code}");
        $this->info("Status saat ini: {$order->status}");
        $this->info("Total: Rp " . number_format($order->total_amount, 0, ',', '.'));

        // Konfirmasi
        if (!$this->confirm('Apakah Anda yakin ingin memproses transaksi ini tanpa pembayaran?')) {
            $this->info('Dibatalkan.');
            return 0;
        }

        // Get supplier product through product relation
        if (!$order->product) {
            $this->error('Product tidak ditemukan pada order ini!');
            return 1;
        }

        $supplierProduct = $order->product->supplierProduct;

        if (!$supplierProduct) {
            $this->error('Supplier product tidak ditemukan!');
            return 1;
        }

        $this->info("Product: {$order->product->name}");
        $this->info("Supplier Product: {$supplierProduct->product_name}");
        $this->info("Supplier: {$supplierProduct->supplier->name}");

        // Update order status to paid (bypass payment)
        $orderPayment = $order->orderPayment;
        if ($orderPayment) {
            $orderPayment->update([
                'status' => 'paid',
                'payment_reference' => 'BYPASS-' . strtoupper(uniqid()),
            ]);
            $this->info('Status pembayaran diupdate ke PAID (bypass payment)');
        } else {
            $this->warn('OrderPayment tidak ditemukan, melewati update status pembayaran.');
        }

        $this->info('Status order Payment diupdate ke PAID (bypass payment)');

        // Process transaction
        $this->info('Memproses transaksi ke supplier...');

        try {
            $supplierService = new OnoyStoreSupplierService($supplierProduct->supplier);
            $result = $supplierService->processTransaction($order, $supplierProduct);

            if ($result['success']) {
                $this->info('✓ Transaksi berhasil diproses!');
                $this->info("Message: {$result['message']}");

                if (isset($result['invoice_number'])) {
                    $this->info("Invoice Number: {$result['invoice_number']}");
                }

                // Refresh order
                $order->refresh();
                $this->info("Status order sekarang: {$order->status}");
            } else {
                $this->error('✗ Transaksi gagal!');
                $this->error("Message: {$result['message']}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Error saat memproses transaksi:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}
