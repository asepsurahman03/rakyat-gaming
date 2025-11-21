<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
        // Verify webhook secret
        $secret = config('services.digiflazz.webhook_secret');
        $signature = $request->header('X-Digiflazz-Signature');

        if ($signature !== hash_hmac('sha256', $request->getContent(), $secret)) {
            Log::warning('Invalid Digiflazz webhook signature', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        // Log the webhook
        Log::info('Digiflazz webhook received', $data);

        // Find order by ref_id
        $refId = $data['ref_id'] ?? null;

        if (!$refId) {
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
            'response_body' => $data,
            'status' => 'callback',
        ]);

        // Update order status based on webhook data
        $status = strtolower($data['status'] ?? 'pending');

        switch ($status) {
            case 'success':
            case 'sukses':
                $order->update([
                    'status' => 'success',
                    'note' => json_encode($data),
                ]);
                break;
            case 'failed':
            case 'gagal':
                $order->update([
                    'status' => 'failed',
                    'note' => json_encode($data),
                ]);
                break;
            case 'pending':
            case 'process':
                $order->update([
                    'status' => 'process',
                    'note' => json_encode($data),
                ]);
                break;
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
