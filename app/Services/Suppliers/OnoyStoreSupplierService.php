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
            if (empty($this->apiKey) || empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'API Key atau API URL tidak ditemukan',
                ];
            }

            // Test connection by getting user info
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/v1/public/user');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['statusCode']) && $data['statusCode'] == 200) {
                    $userData = $data['data'];
                    return [
                        'success' => true,
                        'message' => 'Koneksi berhasil',
                        'data' => [
                            'balance' => $userData['user_balance'] ?? 0,
                            'formatted_balance' => 'Saldo: Rp ' . number_format($userData['user_balance'] ?? 0, 0, ',', '.') . ' | User: ' . ($userData['user_name'] ?? 'Unknown'),
                            'user_name' => $userData['user_name'] ?? null,
                            'user_email' => $userData['user_email'] ?? null,
                            'role_name' => $userData['role_name'] ?? null,
                        ],
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke OnoyStore API. Status: ' . $response->status(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('OnoyStore connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Test koneksi gagal: ' . $e->getMessage(),
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
            if (!$this->isActive() || empty($this->apiKey) || empty($this->baseUrl)) {
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/v1/public/user');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['statusCode']) && $data['statusCode'] == 200) {
                    return (float) ($data['data']['user_balance'] ?? 0);
                }
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
            if (!$this->isActive() || empty($this->apiKey) || empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Supplier tidak aktif atau API Key tidak ditemukan',
                    'synced_count' => 0,
                ];
            }

            Log::info('Starting OnoyStore product sync');

            // Get all products from OnoyStore API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/v1/public/products');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil produk dari OnoyStore. Status: ' . $response->status(),
                    'synced_count' => 0,
                ];
            }

            $responseData = $response->json();

            if (!isset($responseData['statusCode']) || $responseData['statusCode'] != 200) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Response tidak valid dari OnoyStore',
                    'synced_count' => 0,
                ];
            }

            $products = $responseData['data'] ?? [];
            $syncedCount = 0;
            $newCount = 0;
            $updatedCount = 0;

            foreach ($products as $productData) {
                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'code' => $productData['product_code'],
                    ],
                    [
                        'product_name' => $productData['product_name'],
                        'category' => $productData['category_title'] ?? 'General',
                        'brand' => $productData['category_title'] ?? 'OnoyStore',
                        'buyer_price' => $productData['product_price'],
                        'status' => ($productData['is_active'] ?? false) ? 'active' : 'inactive',
                        'raw_json' => $productData,
                        'last_sync_at' => now(),
                    ],
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
                'message' => "Berhasil sync {$syncedCount} produk ({$newCount} baru, {$updatedCount} diupdate)",
                'synced_count' => $syncedCount,
                'new_count' => $newCount,
                'updated_count' => $updatedCount,
            ];
        } catch (\Exception $e) {
            Log::error('OnoyStore product sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync gagal: ' . $e->getMessage(),
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
            if (!$this->isActive() || empty($this->apiKey) || empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Supplier tidak aktif atau API Key tidak ditemukan',
                ];
            }

            Log::info('Processing OnoyStore transaction', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'product_code' => $supplierProduct->code,
            ]);

            // Parse data player
            $dataPlayer = is_string($order->data_player)
                ? json_decode($order->data_player, true)
                : $order->data_player;

            $userId = $dataPlayer['player_id'] ?? $dataPlayer['user_id'] ?? '';
            $serverId = $dataPlayer['server_id'] ?? $dataPlayer['zone_id'] ?? '';

            // Build payload according to OnoyStore API
            $payload = [
                'product_code' => $supplierProduct->code,
                'user_id' => $userId,
                'server_id' => $serverId,
                'trx_id' => $order->order_code,
            ];

            // Add callback URL if configured
            // $callbackUrl = config('app.url') . '/api/webhooks/onoystore';
            $callbackUrl = "https://rakyatgaming.id/webhook/onoystore";
            if (!empty($callbackUrl)) {
                $payload['callback_url'] = $callbackUrl;
            }

            // Log request
            $this->logTransaction($order, $payload, [], 'sent');

            // Send transaction request
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/public/order', $payload);

            $body = $response->json();

            // Log response
            $this->logTransaction($order, $payload, $body, $response->successful() ? 'success' : 'failed');

            // Process response
            if ($response->successful() && isset($body['statusCode']) && $body['statusCode'] == 200) {
                $transactionData = $body['data'];
                $status = strtoupper($transactionData['status'] ?? '');

                return $this->handleTransactionStatus($order, $status, $transactionData);
            }

            // Invalid response
            $errorMessage = $body['message'] ?? 'Invalid response from OnoyStore';
            $order->update([
                'status' => 'failed',
                'note' => json_encode($body)
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('OnoyStore transaction failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $order->update([
                'status' => 'failed',
                'note' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle different transaction statuses from OnoyStore
     *
     * @param Order $order
     * @param string $status
     * @param array $transactionData
     * @return array
     */
    protected function handleTransactionStatus(Order $order, string $status, array $transactionData): array
    {
        $invoiceNumber = $transactionData['invoice_number'] ?? null;
        $responseNote = $transactionData['response_note'] ?? '';

        switch ($status) {
            case 'SUCCESS':
            case 'PARTIAL_SUCCESS':
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

                Log::info('OnoyStore transaction successful', [
                    'order_id' => $order->id,
                    'invoice_number' => $invoiceNumber,
                    'status' => $status,
                ]);

                return [
                    'success' => true,
                    'message' => $responseNote ?: 'Transaction successful',
                    'invoice_number' => $invoiceNumber,
                    'data' => $transactionData,
                ];

            case 'PENDING':
            case 'PAID':
                $order->update([
                    'status' => 'process',
                    'note' => json_encode($transactionData)
                ]);

                Log::info('OnoyStore transaction pending', [
                    'order_id' => $order->id,
                    'invoice_number' => $invoiceNumber,
                    'status' => $status,
                ]);

                return [
                    'success' => true,
                    'message' => 'Transaction is being processed',
                    'invoice_number' => $invoiceNumber,
                    'data' => $transactionData,
                ];

            case 'FAILED':
            case 'REFUNDED':
            default:
                $order->update([
                    'status' => 'failed',
                    'note' => json_encode($transactionData)
                ]);

                Log::warning('OnoyStore transaction failed', [
                    'order_id' => $order->id,
                    'invoice_number' => $invoiceNumber,
                    'status' => $status,
                    'note' => $responseNote,
                ]);

                return [
                    'success' => false,
                    'message' => $responseNote ?: 'Transaction failed',
                    'invoice_number' => $invoiceNumber,
                    'data' => $transactionData,
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
        $trxId = null;
        $orderId = null;
        $signature = request()->header('X-Signature') ?? request()->header('x-signature') ?? '';
        $ipAddress = request()->ip();

        try {
            // Validate signature first
            if (!$this->validateWebhookSignature($payload, $signature)) {
                Log::warning('OnoyStore webhook: invalid signature', [
                    'payload' => $payload,
                    'signature' => $signature,
                    'ip' => $ipAddress,
                ]);

                $this->logWebhook($payload, $signature, $ipAddress, false, ['success' => false, 'message' => 'Invalid signature'], null, 'Invalid webhook signature');

                return [
                    'success' => false,
                    'message' => 'Invalid signature',
                ];
            }

            // Get trx_id from payload (this is our order_code)
            $trxId = $payload['trx_id'] ?? null;

            if (!$trxId) {
                Log::warning('OnoyStore webhook: missing trx_id', ['payload' => $payload]);

                $this->logWebhook($payload, $signature, $ipAddress, false, ['success' => false, 'message' => 'Missing trx_id'], null, 'Missing trx_id in webhook payload');

                return [
                    'success' => false,
                    'message' => 'Missing trx_id in webhook payload',
                ];
            }

            // Find order by trx_id (which is our order_code)
            $order = Order::where('order_code', $trxId)->first();

            if (!$order) {
                Log::warning('OnoyStore webhook: order not found', [
                    'trx_id' => $trxId,
                    'invoice_number' => $payload['invoice_number'] ?? null,
                ]);

                $this->logWebhook($payload, $signature, $ipAddress, false, ['success' => false, 'message' => 'Order not found'], null, 'Order not found for trx_id: ' . $trxId);

                return [
                    'success' => false,
                    'message' => 'Order not found',
                ];
            }

            $orderId = $order->id;

            // Get status from payload (SUCCESS | PARTIAL_SUCCESS | REFUNDED | FAILED)
            $status = strtoupper($payload['status'] ?? '');
            $invoiceNumber = $payload['invoice_number'] ?? null;
            $responseNote = $payload['response_note'] ?? '';

            Log::info('OnoyStore webhook received', [
                'order_id' => $orderId,
                'trx_id' => $trxId,
                'invoice_number' => $invoiceNumber,
                'status' => $status,
            ]);

            // Update order based on status using the same logic as processTransaction
            $result = $this->handleTransactionStatus($order, $status, $payload);

            // Log successful webhook
            $this->logWebhook($payload, $signature, $ipAddress, true, $result, $orderId, null);

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
                'order_id' => $order->id,
                'status' => $status,
                'invoice_number' => $invoiceNumber,
            ];
        } catch (\Exception $e) {
            Log::error('OnoyStore webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            // Log failed webhook
            $this->logWebhook($payload, $signature, $ipAddress, false, ['success' => false, 'message' => 'Exception occurred'], $orderId, $e->getMessage());

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature from OnoyStore
     * Uses HMAC-SHA256 with API key as secret
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // If no API key configured, skip validation (not recommended for production)
        if (empty($this->apiKey)) {
            Log::warning('OnoyStore webhook: API key not configured, skipping signature validation');
            return true;
        }

        if (!$signature) {
            Log::warning('OnoyStore webhook: no signature provided in x-signature header');
            return false;
        }

        // Convert payload to JSON string (without spaces, no escaped slashes)
        // This must match exactly how OnoyStore generates the signature
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Calculate HMAC-SHA256 signature using API key as secret
        $expectedSignature = hash_hmac('sha256', $payloadString, $this->apiKey);

        // Compare signatures
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('OnoyStore webhook: signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $signature,
                'payload_string' => $payloadString,
            ]);
        }

        return $isValid;
    }
}
