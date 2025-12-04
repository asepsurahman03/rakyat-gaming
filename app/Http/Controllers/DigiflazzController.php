<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\DigiflazzLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DigiflazzController extends Controller
{
    /**
     * Handle webhook callback from Digiflazz
     */
    public function handle(Request $request)
    {
        // Get webhook secret from Supplier configuration
        $supplier = Supplier::where('code', 'digiflazz')->first();
        $secret = $supplier->secret ?? null;

        // Validate signature if secret is configured
        if ($secret) {
            $signature = $request->header('X-Hub-Signature');
            $payload = $request->getContent();

            // Calculate expected signature: sha1=HMAC
            $expectedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

            if (!hash_equals($expectedSignature, $signature ?? '')) {
                Log::warning('Invalid Digiflazz webhook signature', [
                    'ip' => $request->ip(),
                    'expected' => $expectedSignature,
                    'received' => $signature,
                ]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        // Get event type and user agent for logging
        $event = $request->header('X-Digiflazz-Event');
        $userAgent = $request->header('User-Agent');

        $data = $request->all();

        // Log the webhook with headers
        Log::info('Digiflazz webhook received', [
            'event' => $event,
            'user_agent' => $userAgent,
            'data' => $data,
        ]);

        // Extract data from nested structure
        $webhookData = $data['data'] ?? $data;

        // Find order by ref_id
        $refId = $webhookData['ref_id'] ?? null;

        if (!$refId) {
            Log::warning('Digiflazz webhook: ref_id is missing', $data);
            return response()->json(['message' => 'ref_id is required'], 400);
        }

        $order = Order::where('order_code', $refId)->first();

        if (!$order) {
            Log::warning('Order not found for Digiflazz webhook', ['ref_id' => $refId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Log the callback
        DigiflazzLog::create([
            'order_id' => $order->id,
            'request_body' => null,
            'response_body' => $webhookData,
            'status' => 'callback',
        ]);

        // Update order status based on webhook data
        $status = strtolower($webhookData['status'] ?? 'pending');

        switch ($status) {
            case 'success':
            case 'sukses':
                $order->update([
                    'status' => 'success',
                    'note' => json_encode($webhookData),
                ]);

                // Increment sold count for successful transactions
                $product = $order->product;
                if ($product) {
                    $flazzSellProduct = $product->activeFlazzSellProduct();
                    if ($flazzSellProduct) {
                        $flazzSellProduct->incrementSoldCount();
                    }
                }

                Log::info('Digiflazz webhook: Order marked as success', [
                    'order_id' => $order->id,
                    'ref_id' => $refId,
                    'sn' => $webhookData['sn'] ?? null,
                ]);
                break;

            case 'failed':
            case 'gagal':
                $order->update([
                    'status' => 'failed',
                    'note' => json_encode($webhookData),
                ]);
                Log::info('Digiflazz webhook: Order marked as failed', [
                    'order_id' => $order->id,
                    'ref_id' => $refId,
                    'message' => $webhookData['message'] ?? 'Unknown error',
                    'rc' => $webhookData['rc'] ?? null,
                ]);
                break;

            case 'pending':
            case 'process':
                $order->update([
                    'status' => 'process',
                    'note' => json_encode($webhookData),
                ]);
                Log::info('Digiflazz webhook: Order still in process', [
                    'order_id' => $order->id,
                    'ref_id' => $refId,
                ]);
                break;
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
