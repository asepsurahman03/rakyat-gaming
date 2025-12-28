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
     * Calls both /saldo and /service endpoints to get balance and product count
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->apiKey) || empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'API Key is not configured',
                ];
            }

            if (!$this->isActive()) {
                return [
                    'success' => false,
                    'message' => 'ByxStore supplier is not active or not configured',
                ];
            }

            // Get balance from /saldo endpoint
            $balance = null;
            $balanceResponse = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/saldo', [
                'api_key' => $this->apiKey,
            ]);

            if ($balanceResponse->successful()) {
                $balanceData = $balanceResponse->json();
                if (isset($balanceData['status']) && $balanceData['status'] === true) {
                    $balance = $balanceData['data']['saldo'] ?? 0;
                }
            }

            // Get products from /service endpoint
            $productCount = 0;
            $serviceResponse = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/service', [
                'api_key' => $this->apiKey,
            ]);

            if (!$serviceResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to service endpoint',
                    'status_code' => $serviceResponse->status(),
                ];
            }

            $serviceData = $serviceResponse->json();

            if (!isset($serviceData['status']) || $serviceData['status'] !== true) {
                return [
                    'success' => false,
                    'message' => $serviceData['msg'] ?? 'Connection failed',
                ];
            }

            $productCount = count($serviceData['data'] ?? []);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'username' => 'ByxStore User',
                    'balance' => $balance,
                    'product_count' => $productCount,
                    'formatted_balance' => 'Saldo: Rp ' . number_format($balance ?? 0, 0, ',', '.') . ' | ' . $productCount . ' layanan tersedia',
                ],
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
            $response = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/saldo', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === true) {
                    return (float) ($data['data']['saldo'] ?? 0);
                }
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
            Log::info('Starting ByxStore product sync');

            // Get products from /service endpoint
            $response = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/service', [
                'api_key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch products: ' . $response->body(),
                    'synced_count' => 0,
                ];
            }

            $data = $response->json();

            if ($data['status'] !== true) {
                return [
                    'success' => false,
                    'message' => $data['msg'] ?? 'Failed to fetch products',
                    'synced_count' => 0,
                ];
            }

            $products = $data['data'] ?? [];
            $syncedCount = 0;
            $newCount = 0;
            $updatedCount = 0;

            foreach ($products as $productData) {
                // Determine which price to use based on user level (default to regular price)
                $price = $productData['harga'] ?? 0;

                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'code' => $productData['id'],
                    ],
                    [
                        'product_name' => $productData['nama_layanan'],
                        'category' => $productData['kategori'] ?? 'General',
                        'brand' => $productData['kategori'] ?? 'ByxStore',
                        'type' => 'digital',
                        'buyer_price' => $price,
                        'seller_price' => $price,
                        'status' => $productData['status'] === 'aktif' ? 'active' : 'inactive',
                        'description' => null,
                        'meta_data' => json_encode([
                            'harga' => $productData['harga'],
                            'harga_gold' => $productData['harga_gold'] ?? null,
                            'harga_silver' => $productData['harga_silver'] ?? null,
                            'harga_pro' => $productData['harga_pro'] ?? null,
                        ]),
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
            Log::info('Processing ByxStore transaction', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'product_code' => $supplierProduct->code,
                'customer_no' => $order->customer_no,
            ]);

            // Prepare payload according to ByxStore API documentation
            $payload = [
                'api_key' => $this->apiKey,
                'service_id' => $supplierProduct->code,
                'target' => $order->customer_no, // Data ID|Zone ID format
                'kontak' => $order->customer_phone ?? '0', // Phone number
                'idtrx' => $order->order_code, // Unique ID from our site
            ];

            // Add callback URL if configured
            if (!empty(config('services.byxstore.callback_url'))) {
                $payload['callback'] = config('services.byxstore.callback_url');
            }

            // Make request to /order endpoint
            $response = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/order', $payload);

            // Parse response
            $responseData = $response->json();

            // Log transaction
            $this->logTransaction($order, $payload, $responseData, 'sent');

            if (!$response->successful()) {
                Log::error('ByxStore transaction HTTP error', [
                    'order_id' => $order->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'HTTP Error: ' . $response->status(),
                    'data' => $responseData ?? [],
                ];
            }

            // Check response status
            if (isset($responseData['status']) && $responseData['status'] === true) {
                Log::info('ByxStore transaction success', [
                    'order_id' => $order->id,
                    'invoice' => $responseData['data']['id'] ?? null,
                    'status' => $responseData['data']['status'] ?? 'pending',
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['msg'] ?? 'Transaction processed',
                    'data' => [
                        'invoice' => $responseData['data']['id'] ?? null,
                        'service_name' => $responseData['data']['service_name'] ?? null,
                        'service_id' => $responseData['data']['service_id'] ?? null,
                        'target' => $responseData['data']['target'] ?? null,
                        'status' => $responseData['data']['status'] ?? 'pending',
                        'keterangan' => $responseData['data']['keterangan'] ?? null,
                    ],
                ];
            }

            // Transaction failed
            Log::warning('ByxStore transaction failed', [
                'order_id' => $order->id,
                'message' => $responseData['msg'] ?? 'Unknown error',
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'message' => $responseData['msg'] ?? 'Transaction failed',
                'data' => $responseData['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('ByxStore transaction exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check order status from ByxStore
     *
     * @param string $byxstoreInvoice ByxStore invoice/order_id
     * @return array
     */
    /**
     * Check transaction status by Order object
     * This method is called by the order detail page
     *
     * @param Order $order
     * @return array
     */
    public function checkTransactionStatus(Order $order): array
    {
        try {
            // Get ByxStore invoice from order note
            $note = is_string($order->note) ? json_decode($order->note, true) : $order->note;
            $byxstoreInvoice = null;

            if (is_array($note) && isset($note['invoice'])) {
                $byxstoreInvoice = $note['invoice'];
            } elseif (is_array($note) && isset($note['data']['id'])) {
                $byxstoreInvoice = $note['data']['id'];
            }

            if (!$byxstoreInvoice) {
                return [
                    'success' => false,
                    'message' => 'ByxStore invoice tidak ditemukan pada order',
                ];
            }

            // Check status at ByxStore
            $result = $this->checkOrderStatus($byxstoreInvoice);

            if (!$result['success']) {
                return $result;
            }

            // Update order based on status
            $statusData = $result['data'] ?? [];
            $transactionStatus = strtolower($statusData['status'] ?? '');

            return $this->handleTransactionStatus($order, $transactionStatus, $statusData);

        } catch (\Exception $e) {
            Log::error('ByxStore check transaction status failed', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check transaction status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check order status by ByxStore invoice ID
     *
     * @param string $byxstoreInvoice
     * @return array
     */
    public function checkOrderStatus(string $byxstoreInvoice): array
    {
        try {
            Log::info('Checking ByxStore order status', [
                'invoice' => $byxstoreInvoice,
            ]);

            // Prepare payload according to ByxStore API documentation
            $payload = [
                'api_key' => $this->apiKey,
                'order_id' => $byxstoreInvoice,
            ];

            // Make request to /status endpoint
            $response = \Illuminate\Support\Facades\Http::post($this->baseUrl . '/status', $payload);

            // Parse response
            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error('ByxStore status check HTTP error', [
                    'invoice' => $byxstoreInvoice,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'HTTP Error: ' . $response->status(),
                ];
            }

            // Check response status
            if (isset($responseData['status']) && $responseData['status'] === true) {
                Log::info('ByxStore status check success', [
                    'invoice' => $byxstoreInvoice,
                    'status' => $responseData['data']['status'] ?? 'unknown',
                    'keterangan' => $responseData['data']['keterangan'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['msg'] ?? 'Status check successful',
                    'data' => [
                        'invoice' => $responseData['data']['id'] ?? $byxstoreInvoice,
                        'status' => $responseData['data']['status'] ?? 'unknown',
                        'sn' => $responseData['data']['keterangan'] ?? null, // Serial Number
                        'keterangan' => $responseData['data']['keterangan'] ?? null,
                    ],
                ];
            }

            // Status check failed
            Log::warning('ByxStore status check failed', [
                'invoice' => $byxstoreInvoice,
                'message' => $responseData['msg'] ?? 'Unknown error',
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'message' => $responseData['msg'] ?? 'Status check failed',
                'data' => $responseData['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('ByxStore status check exception', [
                'invoice' => $byxstoreInvoice,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle different transaction statuses from ByxStore
     *
     * @param Order $order
     * @param string $status
     * @param array $transactionData
     * @return array
     */
    protected function handleTransactionStatus(Order $order, string $status, array $transactionData): array
    {
        $invoice = $transactionData['invoice'] ?? $transactionData['id'] ?? null;
        $keterangan = $transactionData['keterangan'] ?? '';

        switch ($status) {
            case 'sukses':
            case 'success':
                $order->update([
                    'status' => 'success',
                    'note' => json_encode($transactionData)
                ]);

                // Increment sold_count if applicable
                $product = $order->product;
                if ($product) {
                    $flazzSellProduct = $product->activeFlazzSellProduct();
                    if ($flazzSellProduct) {
                        $flazzSellProduct->incrementSoldCount();
                    }
                }

                Log::info('ByxStore transaction successful', [
                    'order_id' => $order->id,
                    'invoice' => $invoice,
                    'status' => $status,
                ]);

                return [
                    'success' => true,
                    'message' => $keterangan ?: 'Transaction successful',
                    'invoice' => $invoice,
                    'data' => $transactionData,
                ];

            case 'pending':
            case 'process':
                $order->update([
                    'status' => 'process',
                    'note' => json_encode($transactionData)
                ]);

                Log::info('ByxStore transaction pending', [
                    'order_id' => $order->id,
                    'invoice' => $invoice,
                    'status' => $status,
                ]);

                return [
                    'success' => true,
                    'message' => 'Transaction is being processed',
                    'invoice' => $invoice,
                    'data' => $transactionData,
                ];

            case 'gagal':
            case 'failed':
            default:
                $order->update([
                    'status' => 'failed',
                    'note' => json_encode($transactionData)
                ]);

                Log::warning('ByxStore transaction failed', [
                    'order_id' => $order->id,
                    'invoice' => $invoice,
                    'status' => $status,
                    'keterangan' => $keterangan,
                ]);

                return [
                    'success' => false,
                    'message' => $keterangan ?: 'Transaction failed',
                    'invoice' => $invoice,
                    'data' => $transactionData,
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
}
