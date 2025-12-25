<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\SupplierProduct;

interface SupplierServiceInterface
{
    /**
     * Get the supplier code identifier
     *
     * @return string
     */
    public function getSupplierCode(): string;

    /**
     * Check if supplier is active and configured
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Test connection to supplier API
     *
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function testConnection(): array;

    /**
     * Get current balance/deposit from supplier
     *
     * @return float|null
     */
    public function getBalance(): ?float;

    /**
     * Sync product price list from supplier API
     *
     * @return array ['success' => bool, 'synced_count' => int, 'message' => string]
     */
    public function syncProducts(): array;

    /**
     * Process transaction/topup for an order
     *
     * @param Order $order
     * @param SupplierProduct $supplierProduct
     * @return array ['success' => bool, 'ref_id' => string|null, 'message' => string, 'data' => array|null]
     */
    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array;

    /**
     * Handle webhook callback from supplier
     *
     * @param array $payload
     * @return array ['success' => bool, 'order_id' => int|null, 'status' => string|null]
     */
    public function handleWebhook(array $payload): array;

    /**
     * Validate webhook signature/authenticity
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhookSignature(array $payload, ?string $signature = null): bool;

    /**
     * Apply auto pricing to products using this supplier
     *
     * @return void
     */
    public function applyAutoPricing(): void;

    /**
     * Build customer number/player ID format based on game type
     * e.g., Mobile Legends: zone_id + user_id
     *
     * @param Order $order
     * @return string
     */
    public function buildCustomerNo(Order $order): string;
}
