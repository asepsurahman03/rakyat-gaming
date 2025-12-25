<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\IpaymuService;
use App\Services\SupplierServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $ipaymuService;
    protected $supplierServiceFactory;

    public function __construct(IpaymuService $ipaymuService, SupplierServiceFactory $supplierServiceFactory)
    {
        $this->ipaymuService = $ipaymuService;
        $this->supplierServiceFactory = $supplierServiceFactory;
    }

    /**
     * Handle payment callback from Ipaymu
     */
    public function callback(Request $request)
    {
        $data = $request->all();

        Log::info('Ipaymu callback received', $data);

        // Verify signature
        // if (!$this->ipaymuService->verifyCallback($request)) {
        //     Log::warning('Invalid Ipaymu callback signature', [
        //         'ip' => $request->ip(),
        //         'data' => $data,
        //         'headers' => $request->headers->all(),
        //     ]);
        //     return response()->json(['message' => 'Invalid signature'], 403);
        // }

        // Ipaymu uses 'reference_id' or 'trx_id' instead of 'merchantOrderId'
        $merchantOrderId = $data['reference_id'] ?? $data['trx_id'] ?? null;
        $status = $data['status'] ?? '';
        $statusCode = $data['status_code'] ?? '';

        if (!$merchantOrderId) {
            return response()->json(['message' => 'reference_id is required'], 400);
        }

        // Find order
        $order = Order::where('order_code', $merchantOrderId)->first();

        if (!$order) {
            Log::warning('Order not found for Ipaymu callback', ['merchantOrderId' => $merchantOrderId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update order payment status
        $orderPayment = $order->orderPayment;

        if (!$orderPayment) {
            Log::error('OrderPayment not found', ['order_id' => $order->id]);
            return response()->json(['message' => 'OrderPayment not found'], 404);
        }

        // Update payment status based on Ipaymu status
        // Ipaymu status: 'berhasil' = success, 'pending' = pending, 'gagal' = failed, 'expired' = expired
        // Status code: 1 = pending, 2 = expired, 5 = success, 6 = failed/cancelled
        if ($statusCode == '5' || strtolower($status) === 'berhasil') {
            // Payment success
            $orderPayment->update([
                'status' => 'paid',
                'payment_reference' => $data['trx_id'] ?? $data['sid'] ?? null,
            ]);

            $order->update(['status' => 'process']);

            // Trigger topup to Digiflazz or handle manual product stock
            if ($order->product) {
                // Handle manual products - decrement stock
                if ($order->product->product_type === 'manual') {
                    try {
                        $product = $order->product;

                        if ($product->stock !== null && $product->stock > 0) {
                            $product->decrement('stock', 1);

                            // Update order status to success for manual products
                            $order->update([
                                'status' => 'process',
                                'note' => 'Manual product - stock decremented',
                            ]);

                            Log::info('Manual product stock decremented', [
                                'order_id' => $order->id,
                                'product_id' => $product->id,
                                'remaining_stock' => $product->fresh()->stock,
                            ]);
                        } else {
                            throw new \Exception('Stock not available');
                        }
                    } catch (\Exception $e) {
                        Log::error('Manual product stock decrement failed', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);

                        $order->update([
                            'status' => 'failed',
                            'note' => 'Stock decrement failed: ' . $e->getMessage(),
                        ]);
                    }
                }
                // Handle supplier products - trigger topup dynamically based on product_type
                elseif ($order->product->isSupplierProduct() && $order->product->supplierProduct) {
                    try {
                        $supplierCode = $order->product->getSupplierCode();

                        // Resolve supplier service dynamically using factory
                        $supplierService = $this->supplierServiceFactory->make($supplierCode);
                        $result = $supplierService->processTransaction($order, $order->product->supplierProduct);

                        Log::info('Supplier topup triggered from payment callback', [
                            'order_id' => $order->id,
                            'supplier' => $supplierCode,
                            'result' => $result,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Supplier topup failed', [
                            'order_id' => $order->id,
                            'supplier' => $order->product->product_type,
                            'error' => $e->getMessage(),
                        ]);

                        $order->update([
                            'status' => 'failed',
                            'note' => 'Topup failed: ' . $e->getMessage(),
                        ]);
                    }
                }
            }
        } elseif ($statusCode == '2' || strtolower($status) === 'expired') {
            // Payment expired
            $orderPayment->update([
                'status' => 'expired',
                'payment_reference' => $data['trx_id'] ?? $data['sid'] ?? null,
            ]);

            $order->update(['status' => 'failed']);
        } else {
            // Payment failed or pending
            $orderPayment->update([
                'status' => ($statusCode == '1' || strtolower($status) === 'pending') ? 'pending' : 'failed',
                'payment_reference' => $data['trx_id'] ?? $data['sid'] ?? null,
            ]);

            if ($statusCode != '1' && strtolower($status) !== 'pending') {
                $order->update(['status' => 'failed']);
            }
        }

        return response()->json(['message' => 'Callback processed'], 200);
    }

    /**
     * Handle return from payment page
     */
    public function return(Request $request)
    {
        $merchantOrderId = $request->query('merchantOrderId');
        $resultCode = $request->query('resultCode');

        if (!$merchantOrderId) {
            Log::warning('merchantOrderId missing in payment return', [
                'ip' => $request->ip(),
                'query' => $request->query(),
            ]);
            return redirect()->route('home')->with('error', 'Invalid payment response');
        }

        // Redirect to payment detail page with order code parameter
        return redirect()->route('payment.detail', ['order_code' => $merchantOrderId]);
    }
}
