<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\DuitkuService;
use App\Services\DigiflazzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $duitkuService;
    protected $digiflazzService;

    public function __construct(DuitkuService $duitkuService, DigiflazzService $digiflazzService)
    {
        $this->duitkuService = $duitkuService;
        $this->digiflazzService = $digiflazzService;
    }

    /**
     * Handle payment callback from Duitku
     */
    public function callback(Request $request)
    {
        $data = $request->all();

        Log::info('Duitku callback received', $data);

        // Verify signature
        if (!$this->duitkuService->verifyCallback($data)) {
            Log::warning('Invalid Duitku callback signature', [
                'ip' => $request->ip(),
                'data' => $data,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $merchantOrderId = $data['merchantOrderId'] ?? null;
        $resultCode = $data['resultCode'] ?? '';

        if (!$merchantOrderId) {
            return response()->json(['message' => 'merchantOrderId is required'], 400);
        }

        // Find order
        $order = Order::where('order_code', $merchantOrderId)->first();

        if (!$order) {
            Log::warning('Order not found for Duitku callback', ['merchantOrderId' => $merchantOrderId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update order payment status
        $orderPayment = $order->orderPayment;

        if (!$orderPayment) {
            Log::error('OrderPayment not found', ['order_id' => $order->id]);
            return response()->json(['message' => 'OrderPayment not found'], 404);
        }

        // Update payment status based on result code
        if ($resultCode === '00') {
            // Payment success
            $orderPayment->update([
                'status' => 'paid',
                'payment_reference' => $data['reference'] ?? null,
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
                // Handle Digiflazz products - trigger topup
                elseif ($order->product->product_type === 'digiflazz' && $order->product->supplierProduct) {
                    try {
                        $result = $this->digiflazzService->topup($order, $order->product->supplierProduct);

                        Log::info('Digiflazz topup triggered from payment callback', [
                            'order_id' => $order->id,
                            'result' => $result,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Digiflazz topup failed', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);

                        $order->update([
                            'status' => 'failed',
                            'note' => 'Topup failed: ' . $e->getMessage(),
                        ]);
                    }
                }
            }
        } else {
            // Payment failed or expired
            $orderPayment->update([
                'status' => $resultCode === '01' ? 'expired' : 'failed',
                'payment_reference' => $data['reference'] ?? null,
            ]);

            $order->update(['status' => 'failed']);
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
