<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\Log;

class ByxStoreSupplierService extends BaseSupplierService
{
    /**
     * Get supplier code identifier
     *
     * @return string
     */
    public function getSupplierCode(): string
    {
        return 'byxstore';
    }

    /**
     * Test connection to ByxStore API
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            // TODO: Implement actual ByxStore API connection test
            // This is a placeholder implementation

            if (!$this->isActive()) {
                return [
                    'success' => false,
                    'message' => 'ByxStore supplier is not active or not configured',
                ];
            }

            // Placeholder: simulate API call
            $response = $this->makeRequest('GET', '/check', [
                'key' => $this->apiKey,
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
            Log::error('ByxStore connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get balance from ByxStore
     *
     * @return float|null
     */
    public function getBalance(): ?float
    {
        try {
            // TODO: Implement actual ByxStore balance API
            $response = $this->makeRequest('GET', '/balance', [
                'key' => $this->apiKey,
            ]);

            if ($response['success'] && isset($response['data']['balance'])) {
                return (float) $response['data']['balance'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('ByxStore get balance failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync products from ByxStore API
     *
     * @return array
     */
    public function syncProducts(): array
    {
        try {
            // TODO: Implement actual ByxStore product sync API
            // This is a placeholder implementation

            Log::info('Starting ByxStore product sync');

            $response = $this->makeRequest('GET', '/price-list', [
                'key' => $this->apiKey,
                'type' => 'all',
            ]);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Failed to fetch products',
                    'synced_count' => 0,
                ];
            }

            $products = $response['data']['data'] ?? [];
            $syncedCount = 0;
            $newCount = 0;
            $updatedCount = 0;

            foreach ($products as $productData) {
                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'code' => $productData['product_code'] ?? $productData['sku'],
                    ],
                    [
                        'product_name' => $productData['product_name'] ?? $productData['name'],
                        'category' => $productData['category'] ?? 'General',
                        'brand' => $productData['brand'] ?? 'ByxStore',
                        'type' => $productData['type'] ?? 'digital',
                        'buyer_price' => $productData['buyer_price'] ?? 0,
                        'seller_price' => $productData['seller_price'] ?? 0,
                        'status' => $productData['seller_product_status'] ?? 'available',
                        'description' => $productData['desc'] ?? null,
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

            Log::info('ByxStore product sync completed', [
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
            Log::error('ByxStore product sync failed', [
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
     * Process transaction through ByxStore
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return array
     */
    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array
    {
        try {
            // TODO: Implement actual ByxStore transaction API

            Log::info('Processing ByxStore transaction', [
                'order_id' => $order->id,
                'product_code' => $supplierProduct->code,
            ]);

            $payload = [
                'key' => $this->apiKey,
                'sign' => $this->generateTransactionSignature($order, $supplierProduct),
                'type' => 'order',
                'service' => $supplierProduct->code,
                'data_no' => $order->customer_no,
                'ref_id' => $order->order_code,
            ];

            $response = $this->makeRequest('POST', '/transaction', $payload);

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
            Log::error('ByxStore transaction failed', [
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
     * Handle webhook from ByxStore
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $refId = null;
        $orderId = null;
        $signature = $payload['sign'] ?? '';
        $ipAddress = request()->ip();

        try {
            // Get ref_id from payload
            $refId = $payload['ref_id'] ?? $payload['trx_id'] ?? null;

            if (!$refId) {
                Log::warning('ByxStore webhook: missing ref_id', ['payload' => $payload]);

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
                Log::warning('ByxStore webhook: order not found', ['ref_id' => $refId]);

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
                case 'sukses':
                    $order->update(['status' => 'success', 'note' => json_encode($payload)]);
                    break;

                case 'failed':
                case 'gagal':
                    $order->update(['status' => 'failed', 'note' => json_encode($payload)]);
                    break;

                case 'pending':
                case 'proses':
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
            Log::error('ByxStore webhook processing failed', [
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
     * Validate webhook signature from ByxStore
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // TODO: Implement actual ByxStore signature validation

        if (empty($this->secret)) {
            Log::warning('ByxStore webhook: secret not configured, skipping validation');
            return true;
        }

        if (!$signature) {
            Log::warning('ByxStore webhook: no signature provided');
            return false;
        }

        // Convert payload to JSON string for signature calculation
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);

        // Example signature validation (adjust based on ByxStore documentation)
        $expectedSignature = md5($this->apiKey . $this->secret . $payloadString);

        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('ByxStore webhook: invalid signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Generate transaction signature for ByxStore
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return string
     */
    private function generateTransactionSignature(Order $order, SupplierProduct $supplierProduct): string
    {
        // TODO: Implement actual ByxStore signature generation
        // This is an example, adjust based on ByxStore documentation
        return md5($this->apiKey . $supplierProduct->code . $order->customer_no . $this->secret);
    }
}
