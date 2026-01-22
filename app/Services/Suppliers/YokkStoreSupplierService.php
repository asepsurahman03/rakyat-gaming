<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isString;

class YokkStoreSupplierService extends BaseSupplierService
{
    /**
     * Get supplier code identifier
     *
     * @return string
     */
    public function getSupplierCode(): string
    {
        return 'yokkstore';
    }

    /**
     * Test connection to YokkStore API
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

            // Test connection by getting balance
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/saldo', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] == true) {
                    return [
                        'success' => true,
                        'message' => 'Koneksi berhasil',
                        'data' => [
                            'saldo' => $data['saldo'] ?? 0,
                            'formatted_balance' => 'Saldo: Rp ' . number_format($data['saldo'] ?? 0, 0, ',', '.'),
                        ],
                    ];
                }
            }

            $data = $response->json();
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke YokkStore API. Status: ' . ($data['message'] ?? 'Unknown error'),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('YokkStore connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Test koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get balance from YokkStore
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
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/saldo', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] == true) {
                    return (float) ($data['saldo'] ?? 0);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('YokkStore get balance failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync products from YokkStore API
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

            Log::info('Starting YokkStore product sync');

            // Get all products from YokkStore API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/service', [
                'api_key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil produk dari YokkStore. Status: ' . $response->status(),
                    'synced_count' => 0,
                ];
            }

            $responseData = $response->json();

            if (!isset($responseData['status']) || $responseData['status'] != true) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Response tidak valid dari YokkStore',
                    'synced_count' => 0,
                ];
            }

            $products = $responseData['data'] ?? [];
            $syncedCount = 0;
            $now = now();

            foreach ($products as $productData) {
                try {
                    SupplierProduct::updateOrCreate(
                        [
                            'supplier_id' => $this->supplier->id,
                            'code' => $productData['id'],
                        ],
                        [
                            'category' => $productData['product_category'] ?? null,
                            'brand' => $productData['kategori'] ?? null,
                            'product_name' => $productData['nama_layanan'] ?? null,
                            'buyer_price' => $productData['harga'] ?? 0,
                            'status' => ($productData['status'] ?? 'aktif') === 'aktif' ? 'active' : 'inactive',
                            'raw_json' => $productData,
                            'last_sync_at' => $now,
                        ]
                    );

                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('YokkStore product sync error for product', [
                        'product_code' => $productData['product_code'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Apply auto pricing after sync
            $this->applyAutoPricing();

            Log::info('YokkStore product sync completed', [
                'synced_count' => $syncedCount,
                'total_products' => count($products),
            ]);

            return [
                'success' => true,
                'message' => "Berhasil sync {$syncedCount} produk dari YokkStore",
                'synced_count' => $syncedCount,
            ];
        } catch (\Exception $e) {
            Log::error('YokkStore product sync failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync produk gagal: ' . $e->getMessage(),
                'synced_count' => 0,
            ];
        }
    }

    /**
     * Process transaction with YokkStore
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return array
     */
    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array
    {
        try {
            if (!$this->isActive()) {
                return [
                    'success' => false,
                    'message' => 'YokkStore tidak aktif',
                ];
            }

            $dataPlayer = is_string($order->data_player)
                ? json_decode($order->data_player, true)
                : $order->data_player;

            $userId = $dataPlayer['player_id'] ?? $dataPlayer['user_id'] ?? '';
            $serverId = $dataPlayer['server_id'] ?? $dataPlayer['zone_id'] ?? $dataPlayer['server'] ?? '';

            if (!empty($userId) && !empty($serverId)) {
                $customerNo = $userId . '|' . $serverId;
            } else {
                $customerNo = $userId;
            }

            // Build callback URL
            $callbackUrl = url('/webhook/yokkstore');

            $payload = [
                'api_key' => $this->apiKey,
                'service_id' => $supplierProduct->code,
                'target' => $customerNo,
                'kontak' => $order->whatsapp ?? '',
                'partner_id' => $order->order_code,
                'callback' => $callbackUrl,
            ];

            $this->logTransaction($order, $payload, [], 'sent');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/order', $payload);

            $responseData = $response->json();

            $this->logTransaction($order, $payload, $responseData, $response->successful() ? 'success' : 'failed');

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] == true) {
                $orderData = $responseData['data'] ?? [];

                Log::info('YokkStore transaction success', [
                    'order_code' => $order->order_code,
                    'yokkstore_order_id' => $orderData['id'] ?? null,
                    'status' => $orderData['status'] ?? null,
                ]);

                $order->update([
                    'status' => $orderData['status'] ?? 'pending',
                    'note' => json_encode($responseData),
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['msg'] ?? 'Transaksi berhasil diproses',
                    'data' => [
                        'order_code' => $orderData['order_code'] ?? null,
                        'status' => $orderData['status'] ?? 'pending',
                        'sn' => $orderData['sn'] ?? null,
                    ],
                ];
            }

            Log::error('YokkStore transaction failed', [
                'order_code' => $order->order_code,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'message' => $responseData['msg'] ?? 'Transaksi gagal',
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('YokkStore transaction error', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function checkTransactionStatus($order): array
    {
        try {
            if (!$this->isActive() || empty($this->apiKey) || empty($this->baseUrl)) {
                return [
                    'success' => false,
                    'message' => 'Supplier tidak aktif atau API Key tidak ditemukan',
                ];
            }

            Log::info('Checking YokkStore transaction status', [
                'api_key' => $this->apiKey,
                'action' => 'status',
                'order_id' => $order->note['data']['id'] ?? '',
            ]);

            $payload = [
                'api_key' => $this->apiKey,
                'action' => 'status',
                'order_id' => $order->note['data']['id'] ?? '',
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/status', $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] == true) {
                $orderData = $responseData['data'] ?? [];

                return [
                    'success' => true,
                    'message' => $responseData['msg'] ?? 'Status transaksi berhasil diambil',
                    'data' => [
                        'order_code' => $orderData['order_code'] ?? null,
                        'status' => $orderData['status'] ?? 'pending',
                        'sn' => $orderData['sn'] ?? null,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['msg'] ?? 'Gagal mengambil status transaksi',
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('YokkStore check transaction status error', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }

    }

    /**
     * Handle webhook callback from YokkStore
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $signature = request()->header('X-Signature') ?? request()->header('x-signature') ?? '';
        $ipAddress = request()->ip();

        try {
            Log::info('YokkStore webhook received', ['payload' => $payload, 'signature' => $signature, 'ip' => $ipAddress]);

            $this->logWebhook($payload, $signature, $ipAddress, 'received');

            $partnerId = $payload['data']['partner_id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$partnerId) {
                Log::warning('YokkStore webhook missing partner_id', ['payload' => $payload]);
                return [
                    'success' => false,
                    'message' => 'Missing partner_id in webhook payload',
                ];
            }

            $order = Order::where('order_code', $partnerId)->first();

            if (!$order) {
                Log::warning('YokkStore webhook order not found', [
                    'partner_id' => $partnerId,
                ]);
                return [
                    'success' => false,
                    'message' => 'Order not found',
                ];
            }

            // Map YokkStore status to our status
            $orderStatus = match (strtolower($status)) {
                'success', 'sukses' => 'success',
                'failed', 'gagal', 'cancel' => 'failed',
                'pending', 'process', 'processing' => 'process',
                default => 'pending',
            };

            $order->update([
                'status' => $orderStatus,
            ]);

            Log::info('YokkStore webhook processed', [
                'order_code' => $order->order_code,
                'status' => $orderStatus,
            ]);

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('YokkStore webhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'signature' => $signature,
                'ip' => $ipAddress,
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature
     * YokkStore tidak menggunakan signature validation, hanya mengandalkan IP whitelist
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // YokkStore tidak menyediakan signature untuk webhook
        // Keamanan webhook dijaga melalui IP whitelist di middleware VerifyWebhookIP
        // Dan validasi partner_id yang harus ada di database kita

        // Pastikan partner_id ada dan valid
        if (!isset($payload['data']['partner_id']) || empty($payload['data']['partner_id'])) {
            Log::warning('YokkStore webhook: missing partner_id', ['payload' => $payload]);
            return false;
        }

        // Verifikasi bahwa partner_id adalah order_id yang valid di sistem kita
        $partnerId = $payload['data']['partner_id'];
        $orderExists = \App\Models\Order::where('order_code', $partnerId)->exists();

        if (!$orderExists) {
            Log::warning('YokkStore webhook: invalid partner_id', [
                'partner_id' => $partnerId,
            ]);
            return false;
        }

        Log::info('YokkStore webhook validation passed', [
            'partner_id' => $partnerId,
            'validation_method' => 'IP whitelist + partner_id verification',
        ]);

        return true;
    }
}
