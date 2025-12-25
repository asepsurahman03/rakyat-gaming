<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\WebhookLog;
use App\Services\Contracts\SupplierServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseSupplierService implements SupplierServiceInterface
{
    protected Supplier $supplier;
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $username = null;
    protected ?string $secret = null;
    protected string $supplierCode;

    /**
     * Constructor - Load supplier configuration
     */
    public function __construct()
    {
        $this->loadSupplier();
        $this->initializeConfig();
    }

    /**
     * Load supplier from database
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function loadSupplier(): void
    {
        $this->supplier = Supplier::where('code', $this->getSupplierCode())->firstOrFail();
    }

    /**
     * Initialize configuration from supplier model
     */
    protected function initializeConfig(): void
    {
        $this->baseUrl = $this->supplier->api_url ?? '';
        $this->apiKey = $this->supplier->api_key ?? '';
        $this->username = $this->supplier->username;
        $this->secret = $this->supplier->secret;
    }

    /**
     * Get supplier code - must be implemented by child classes
     *
     * @return string
     */
    abstract public function getSupplierCode(): string;

    /**
     * Check if supplier is active and properly configured
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->supplier->is_active && !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Make HTTP request to supplier API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $payload
     * @param array $headers
     * @return array
     */
    protected function makeRequest(string $method, string $endpoint, array $payload = [], array $headers = []): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

            $request = Http::asJson();

            // Add custom headers if provided
            if (!empty($headers)) {
                $request->withHeaders($headers);
            }

            $response = match(strtoupper($method)) {
                'GET' => $request->get($url, $payload),
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'DELETE' => $request->delete($url, $payload),
                default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
            };

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error("Supplier API request failed [{$this->getSupplierCode()}]", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 0,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log transaction for debugging and audit trail
     *
     * @param Order|null $order
     * @param array $request
     * @param array $response
     * @param string $status
     * @return void
     */
    protected function logTransaction(?Order $order, array $request, array $response, string $status = 'sent'): void
    {
        // This can be customized per supplier if needed
        // For now, using DigiflazzLog as generic log table
        \App\Models\DigiflazzLog::create([
            'order_id' => $order?->id,
            'request_body' => $request,
            'response_body' => $response,
            'status' => $status,
        ]);
    }

    /**
     * Generate signature for API authentication - override if needed
     *
     * @param array $data
     * @return string
     */
    protected function generateSignature(array $data): string
    {
        // Default implementation - override in child classes if needed
        return md5(implode('', $data));
    }

    /**
     * Build customer number/player ID based on game requirements
     * Default implementation: combine player_id and server_id
     * Override in child classes if supplier has different format
     *
     * @param Order $order
     * @return string
     */
    public function buildCustomerNo(Order $order): string
    {
        $dataPlayer = is_string($order->data_player)
            ? json_decode($order->data_player, true)
            : $order->data_player;

        $playerId = $dataPlayer['player_id'] ?? $dataPlayer['user_id'] ?? '';
        $serverId = $dataPlayer['server_id'] ?? $dataPlayer['zone_id'] ?? '';

        // If both exist, combine them (e.g., Mobile Legends format)
        if (!empty($playerId) && !empty($serverId)) {
            return $playerId . $serverId;
        }

        return $playerId;
    }

    /**
     * Apply auto pricing to products using this supplier
     * Updates selling_price = buyer_price + margin for products with is_auto_price = true
     *
     * @return void
     */
    public function applyAutoPricing(): void
    {
        $defaultMargin = (float) (config('app.default_margin') ?? env('DEFAULT_MARGIN', 1000));

        // Filter products by product_type = supplier code (e.g., 'digiflazz', 'onoystore', 'byxstore')
        $products = Product::where('is_auto_price', true)
            ->where('product_type', $this->getSupplierCode())
            ->whereHas('supplierProduct', function ($query) {
                $query->where('supplier_id', $this->supplier->id);
            })
            ->with('supplierProduct')
            ->get();

        foreach ($products as $product) {
            $supplierPrice = $product->supplierProduct->buyer_price ?? null;
            $margin = $product->margin ?? $defaultMargin;

            if ($supplierPrice !== null) {
                $newPrice = round($supplierPrice + $margin, 2);
                if ((float)$product->selling_price !== (float)$newPrice) {
                    $product->update(['selling_price' => $newPrice]);
                }
            }
        }

        Log::info("Auto pricing applied for {$this->getSupplierCode()}", [
            'products_updated' => $products->count(),
        ]);
    }

    /**
     * Default webhook validation - override in child classes
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool
    {
        // Default: no validation (allow all)
        // Child classes should override this for security
        Log::warning("Webhook signature validation not implemented for {$this->getSupplierCode()}");
        return true;
    }

    /**
     * Default webhook handler - override in child classes
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        Log::warning("Webhook handler not implemented for {$this->getSupplierCode()}", [
            'payload' => $payload,
        ]);

        return [
            'success' => false,
            'message' => 'Webhook handler not implemented',
            'order_id' => null,
            'status' => null,
        ];
    }

    /**
     * Log webhook activity
     *
     * @param array $payload
     * @param string $signature
     * @param string|null $ipAddress
     * @param bool $isValid
     * @param array|null $response
     * @param string|null $orderId
     * @param string|null $errorMessage
     * @return WebhookLog
     */
    protected function logWebhook(
        array $payload,
        string $signature,
        ?string $ipAddress = null,
        bool $isValid = false,
        ?array $response = null,
        ?string $orderId = null,
        ?string $errorMessage = null
    ): WebhookLog {
        return WebhookLog::create([
            'supplier_code' => $this->getSupplierCode(),
            'payload' => json_encode($payload),
            'signature' => $signature,
            'ip_address' => $ipAddress,
            'response' => $response ? json_encode($response) : null,
            'status' => $isValid ? 'processed' : 'failed',
            'is_valid' => $isValid,
            'order_id' => $orderId,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Abstract methods that must be implemented by child classes
     */
    abstract public function testConnection(): array;
    abstract public function getBalance(): ?float;
    abstract public function syncProducts(): array;
    abstract public function processTransaction(Order $order, SupplierProduct $supplierProduct): array;
}
