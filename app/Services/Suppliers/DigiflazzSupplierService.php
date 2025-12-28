<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\SupplierProduct;
use App\Models\DigiflazzLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzSupplierService extends BaseSupplierService
{
    /**
     * Get supplier code identifier
     *
     * @return string
     */
    public function getSupplierCode(): string
    {
        return 'digiflazz';
    }

    /**
     * Test connection to Digiflazz API by checking balance
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $sign = md5($this->username . $this->apiKey . 'depo');

            $payload = [
                'username' => $this->username,
                'cmd' => 'deposit',
                'sign' => $sign,
            ];

            $endpoint = '/cek-saldo';
            $response = $this->makeRequest('POST', $endpoint, $payload);

            if ($response['success'] && isset($response['data']['data'])) {
                $balance = $response['data']['data']['deposit'] ?? 0;
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => [
                        'balance' => $balance,
                        'formatted_balance' => 'Rp ' . number_format($balance, 0, ',', '.'),
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $response['data']['data']['message'] ?? 'Failed to connect to Digiflazz',
                'data' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get current balance from Digiflazz
     *
     * @return float|null
     */
    public function getBalance(): ?float
    {
        $result = $this->testConnection();
        return $result['success'] ? ($result['data']['balance'] ?? null) : null;
    }

    /**
     * Sync product price list from Digiflazz API
     *
     * @return array
     */
    public function syncProducts(): array
    {
        try {
            // Use Digiflazz package to get price list
            $response = \Triyatna\Digiflazz\Digiflazz::getPriceList('prepaid');

            if (!$response->isSuccess()) {
                return [
                    'success' => false,
                    'synced_count' => 0,
                    'message' => 'Failed to fetch price list from Digiflazz',
                ];
            }

            $items = $response->data();
            $syncedCount = 0;

            Log::debug('Digiflazz price list fetched', [
                'count' => is_array($items) ? count($items) : (is_object($items) ? count((array)$items) : 0),
            ]);

            DB::transaction(function () use ($items, &$syncedCount) {
                foreach ($items as $item) {
                    // Map Digiflazz response structure
                    $code = $item['buyer_sku_code'] ?? null;
                    $productName = $item['product_name'] ?? null;
                    $category = $item['category'] ?? null;
                    $brand = $item['brand'] ?? null;
                    $price = isset($item['price']) ? (float)$item['price'] : 0.0;

                    // Product status (active if both buyer and seller status are true)
                    $buyerStatus = $item['buyer_product_status'] ?? false;
                    $sellerStatus = $item['seller_product_status'] ?? false;
                    $status = ($buyerStatus && $sellerStatus) ? 'active' : 'inactive';

                    // Skip if no product code
                    if (!$code) continue;

                    SupplierProduct::updateOrCreate(
                        ['supplier_id' => $this->supplier->id, 'code' => $code],
                        [
                            'category' => $category,
                            'brand' => $brand,
                            'product_name' => $productName,
                            'buyer_price' => $price,
                            'status' => $status,
                            'raw_json' => $item,
                            'last_sync_at' => now(),
                        ]
                    );

                    $syncedCount++;
                }
            });

            // Apply auto pricing after sync
            $this->applyAutoPricing();

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'message' => "Successfully synced {$syncedCount} products from Digiflazz",
            ];
        } catch (\Exception $e) {
            Log::error('Digiflazz sync failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'synced_count' => 0,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process transaction/topup for an order
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return array
     */
    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array
    {
        try {
            // Build customer number based on game requirements
            $customerId = $this->buildCustomerNo($order);

            // Generate signature: md5(username + apiKey + ref_id)
            $refId = $order->order_code;
            $sign = md5($this->username . $this->apiKey . $refId);

            // Build payload according to Digiflazz API
            $payload = [
                'username' => $this->username,
                'buyer_sku_code' => $supplierProduct->code,
                'customer_no' => $customerId,
                'ref_id' => $refId,
                'sign' => $sign,
            ];

            // Log request
            DigiflazzLog::create([
                'order_id' => $order->id,
                'request_body' => $payload,
                'status' => 'sent',
            ]);

            // Send transaction request
            $endpoint = '/transaction';
            $response = Http::asJson()->post(rtrim($this->baseUrl, '/') . $endpoint, $payload);
            $body = $response->json();

            // Log response
            DigiflazzLog::create([
                'order_id' => $order->id,
                'request_body' => $payload,
                'response_body' => $body,
                'status' => $response->ok() ? 'success' : 'failed',
            ]);

            // Process response
            if ($response->ok() && isset($body['data'])) {
                $transactionStatus = strtolower($body['data']['status'] ?? '');

                return $this->handleTransactionStatus($order, $transactionStatus, $body);
            }

            // Invalid response
            $order->update(['status' => 'failed', 'note' => json_encode($body)]);
            return [
                'success' => false,
                'ref_id' => null,
                'message' => 'Invalid response from Digiflazz',
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('Digiflazz transaction failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $order->update(['status' => 'failed', 'note' => $e->getMessage()]);
            return [
                'success' => false,
                'ref_id' => null,
                'message' => 'Transaction failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Check transaction status from Digiflazz
     *
     * @param Order $order
     * @return array
     */
    public function checkTransactionStatus(Order $order): array
    {
        try {
            // Validate order has product and supplier product
            if (!$order->product || !$order->product->supplierProduct) {
                return [
                    'success' => false,
                    'message' => 'Produk atau supplier product tidak ditemukan',
                ];
            }

            // Validate credentials
            if (empty($this->username) || empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'Username atau API Key tidak terkonfigurasi',
                ];
            }

            $refId = $order->order_code;
            $sign = md5($this->username . $this->apiKey . $refId);

            $payload = [
                'username' => $this->username,
                'buyer_sku_code' => $order->product->supplierProduct->code,
                'ref_id' => $refId,
                'sign' => $sign,
            ];

            $endpoint = '/cek-status';
            $response = $this->makeRequest('POST', $endpoint, $payload);

            // Log request and response
            DigiflazzLog::create([
                'order_id' => $order->id,
                'request_body' => $payload,
                'response_body' => $response['data'] ?? null,
                'status' => $response['success'] ? 'success' : 'failed',
            ]);

            if ($response['success'] && isset($response['data']['data'])) {
                $body = $response['data'];
                $transactionStatus = strtolower($body['data']['status'] ?? '');

                // Update order status based on supplier response
                return $this->handleTransactionStatus($order, $transactionStatus, $body);
            }

            // Return more detailed error message
            $errorMessage = 'Failed to check transaction status';
            if (isset($response['data']['message'])) {
                $errorMessage .= ': ' . $response['data']['message'];
            } elseif (isset($response['error'])) {
                $errorMessage .= ': ' . $response['error'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $response['data'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Digiflazz check status failed', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check transaction status: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Handle different transaction statuses from Digiflazz
     *
     * @param Order $order
     * @param string $status
     * @param array $body
     * @return array
     */
    protected function handleTransactionStatus(Order $order, string $status, array $body): array
    {
        switch ($status) {
            case 'sukses':
            case 'success':
                $order->update(['status' => 'success', 'note' => json_encode($body['data'])]);

                // Increment FlazzSellProduct sold_count if applicable
                $product = $order->product;
                if ($product) {
                    $flazzSellProduct = $product->activeFlazzSellProduct();
                    if ($flazzSellProduct) {
                        $flazzSellProduct->incrementSoldCount();
                    }
                }

                return [
                    'success' => true,
                    'ref_id' => $body['data']['ref_id'] ?? null,
                    'message' => 'Transaction successful',
                    'data' => $body,
                ];

            case 'pending':
            case 'process':
                $order->update(['status' => 'process', 'note' => json_encode($body['data'])]);
                return [
                    'success' => true,
                    'ref_id' => $body['data']['ref_id'] ?? null,
                    'message' => 'Transaction is being processed',
                    'data' => $body,
                ];

            case 'gagal':
            case 'failed':
                $order->update(['status' => 'failed', 'note' => json_encode($body['data'])]);
                return [
                    'success' => false,
                    'ref_id' => $body['data']['ref_id'] ?? null,
                    'message' => 'Transaction failed',
                    'data' => $body,
                ];

            default:
                // Unknown status - mark as process and wait for callback
                $order->update(['status' => 'process', 'note' => json_encode($body['data'])]);
                return [
                    'success' => true,
                    'ref_id' => $body['data']['ref_id'] ?? null,
                    'message' => 'Transaction status: ' . $status,
                    'data' => $body,
                ];
        }
    }

    /**
     * Handle webhook callback from Digiflazz
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
            $refId = $payload['ref_id'] ?? $payload['data']['ref_id'] ?? null;

            if (!$refId) {
                Log::warning('Digiflazz webhook: missing ref_id', ['payload' => $payload]);

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
                    'order_id' => null,
                    'status' => null,
                ];
            }

            // Find order by order_code (ref_id)
            $order = Order::where('order_code', $refId)->first();

            if (!$order) {
                Log::warning('Digiflazz webhook: order not found', ['ref_id' => $refId]);

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
                    'order_id' => null,
                    'status' => null,
                ];
            }

            $orderId = $order->order_id;

            // Log webhook callback (legacy DigiflazzLog for backward compatibility)
            DigiflazzLog::create([
                'order_id' => $order->id,
                'request_body' => null,
                'response_body' => $payload,
                'status' => 'callback',
            ]);

            // Get status from payload
            $status = strtolower($payload['status'] ?? $payload['data']['status'] ?? '');

            // Update order based on status
            switch ($status) {
                case 'sukses':
                case 'success':
                    $order->update(['status' => 'success', 'note' => json_encode($payload)]);

                    // Increment sold count
                    $product = $order->product;
                    if ($product) {
                        $flazzSellProduct = $product->activeFlazzSellProduct();
                        if ($flazzSellProduct) {
                            $flazzSellProduct->incrementSoldCount();
                        }
                    }
                    break;

                case 'gagal':
                case 'failed':
                    $order->update(['status' => 'failed', 'note' => json_encode($payload)]);
                    break;

                case 'pending':
                case 'process':
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
            Log::error('Digiflazz webhook processing failed', [
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
                'order_id' => null,
                'status' => null,
            ];
        }
    }

    /**
     * Validate webhook signature from Digiflazz
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // Digiflazz webhook validation
        // If secret is not configured, skip validation
        if (empty($this->secret)) {
            Log::warning('Digiflazz webhook: secret not configured, skipping signature validation');
            return true;
        }

        // Get signature from header or payload
        $receivedSignature = $signature ?? $payload['sign'] ?? null;

        if (!$receivedSignature) {
            Log::warning('Digiflazz webhook: no signature provided');
            return false;
        }

        // Generate expected signature
        // Format varies by supplier - this is an example
        $expectedSignature = md5($this->username . $this->secret . ($payload['ref_id'] ?? ''));

        $isValid = hash_equals($expectedSignature, $receivedSignature);

        if (!$isValid) {
            Log::warning('Digiflazz webhook: invalid signature', [
                'expected' => $expectedSignature,
                'received' => $receivedSignature,
            ]);
        }

        return $isValid;
    }
}
