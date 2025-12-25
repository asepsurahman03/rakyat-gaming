<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\Log;

class OnoyStoreSupplierService extends BaseSupplierService
{
    /**
     * Get supplier code identifier
     *
     * @return string
     */
    public function getSupplierCode(): string
    {
        return 'onoystore';
    }

    /**
     * Test connection to OnoyStore API
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            // TODO: Implement actual OnoyStore API connection test
            // This is a placeholder implementation

            if (!$this->isActive()) {
                return [
                    'success' => false,
                    'message' => 'OnoyStore supplier is not active or not configured',
                ];
            }

            // Placeholder: simulate API call
            $response = $this->makeRequest('POST', '/balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => [
                        'balance' => $response['data']['balance'] ?? 0,
                        'formatted_balance' => 'Rp ' . number_format($response['data']['balance'] ?? 0, 0, ',', '.'),
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Connection failed',
                'status_code' => $response['status_code'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('OnoyStore connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get balance from OnoyStore
     *
     * @return float|null
     */
    public function getBalance(): ?float
    {
        try {
            // TODO: Implement actual OnoyStore balance API
            $response = $this->makeRequest('POST', '/balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response['success'] && isset($response['data']['balance'])) {
                return (float) $response['data']['balance'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('OnoyStore get balance failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync products from OnoyStore API
     *
     * @return array
     */
    public function syncProducts(): array
    {
        try {
            // TODO: Implement actual OnoyStore product sync API
            // This is a placeholder implementation

            Log::info('Starting OnoyStore product sync');

            $response = $this->makeRequest('POST', '/products', [
                'api_key' => $this->apiKey,
            ]);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Failed to fetch products',
                    'synced_count' => 0,
                ];
            }

            $products = $response['data']['products'] ?? [];
            $syncedCount = 0;
            $newCount = 0;
            $updatedCount = 0;

            foreach ($products as $productData) {
                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'code' => $productData['code'] ?? $productData['product_code'],
                    ],
                    [
                        'product_name' => $productData['name'] ?? $productData['product_name'],
                        'category' => $productData['category'] ?? 'General',
                        'brand' => $productData['brand'] ?? 'OnoyStore',
                        'type' => $productData['type'] ?? 'digital',
                        'buyer_price' => $productData['price'] ?? 0,
                        'seller_price' => $productData['price'] ?? 0,
                        'status' => $productData['status'] ?? 'active',
                        'description' => $productData['description'] ?? null,
                        'last_sync_at' => now(),
                    ]
                );

                if ($supplierProduct->wasRecentlyCreated) {
                    $newCount++;
                } else {
                    $updatedCount++;
                }

                $syncedCount++;
            }

            Log::info('OnoyStore product sync completed', [
                'synced' => $syncedCount,
                'new' => $newCount,
                'updated' => $updatedCount,
            ]);

            return [
                'success' => true,
                'message' => 'Products synced successfully',
                'synced_count' => $syncedCount,
                'new_count' => $newCount,
                'updated_count' => $updatedCount,
            ];

        } catch (\Exception $e) {
            Log::error('OnoyStore product sync failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'synced_count' => 0,
            ];
        }
    }

    /**
     * Process transaction through OnoyStore
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return array
     */
    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array
    {
        try {
            // TODO: Implement actual OnoyStore transaction API

            Log::info('Processing OnoyStore transaction', [
                'order_id' => $order->id,
                'product_code' => $supplierProduct->code,
            ]);

            $payload = [
                'api_key' => $this->apiKey,
                'ref_id' => $order->order_code,
                'product_code' => $supplierProduct->code,
                'customer_no' => $order->customer_no,
            ];

            $response = $this->makeRequest('POST', '/order', $payload);

            // Log transaction
            $this->logTransaction($order, $payload, $response['data'] ?? [], 'sent');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Transaction processed',
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Transaction failed',
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('OnoyStore transaction failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook from OnoyStore
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $refId = null;
        $orderId = null;
        $signature = $payload['signature'] ?? '';
        $ipAddress = request()->ip();

        try {
            // Get ref_id from payload
            $refId = $payload['ref_id'] ?? $payload['reference_id'] ?? null;

            if (!$refId) {
                Log::warning('OnoyStore webhook: missing ref_id', ['payload' => $payload]);

                $this->logWebhook(
                    $payload,
                    $signature,
                    $ipAddress,
                    false,
                    ['success' => false, 'message' => 'Missing ref_id'],
                    null,
                    'Missing ref_id in webhook payload'
                );

                return [
                    'success' => false,
                    'message' => 'Missing ref_id in webhook payload',
                ];
            }

            // Find order
            $order = Order::where('order_code', $refId)->first();

            if (!$order) {
                Log::warning('OnoyStore webhook: order not found', ['ref_id' => $refId]);

                $this->logWebhook(
                    $payload,
                    $signature,
                    $ipAddress,
                    false,
                    ['success' => false, 'message' => 'Order not found'],
                    null,
                    'Order not found for ref_id: ' . $refId
                );

                return [
                    'success' => false,
                    'message' => 'Order not found',
                ];
            }

            $orderId = $order->order_id;

            // Get status from payload
            $status = strtolower($payload['status'] ?? '');

            // Update order based on status
            switch ($status) {
                case 'success':
                case 'completed':
                    $order->update(['status' => 'success', 'note' => json_encode($payload)]);
                    break;

                case 'failed':
                case 'cancelled':
                    $order->update(['status' => 'failed', 'note' => json_encode($payload)]);
                    break;

                case 'pending':
                case 'processing':
                    $order->update(['status' => 'process', 'note' => json_encode($payload)]);
                    break;
            }

            // Log successful webhook
            $this->logWebhook(
                $payload,
                $signature,
                $ipAddress,
                true,
                ['success' => true, 'message' => 'Webhook processed', 'status' => $status],
                $orderId,
                null
            );

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
                'order_id' => $order->id,
                'status' => $status,
            ];

        } catch (\Exception $e) {
            Log::error('OnoyStore webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Log failed webhook
            $this->logWebhook(
                $payload,
                $signature,
                $ipAddress,
                false,
                ['success' => false, 'message' => 'Exception occurred'],
                $orderId,
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature from OnoyStore
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // TODO: Implement actual OnoyStore signature validation

        if (empty($this->secret)) {
            Log::warning('OnoyStore webhook: secret not configured, skipping validation');
            return true;
        }

        if (!$signature) {
            Log::warning('OnoyStore webhook: no signature provided');
            return false;
        }

        // Convert payload to JSON string for signature calculation
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);

        // Example signature validation (adjust based on OnoyStore documentation)
        $expectedSignature = hash_hmac('sha256', $payloadString, $this->secret);

        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('OnoyStore webhook: invalid signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }
}
