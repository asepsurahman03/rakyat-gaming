<?php

namespace App\Http\Controllers;

use App\Services\SupplierServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private SupplierServiceFactory $factory
    ) {}

    /**
     * Handle incoming webhook from supplier
     *
     * @param Request $request
     * @param string $supplierCode
     * @return JsonResponse
     */
    public function handle(Request $request, string $supplierCode): JsonResponse
    {
        try {
            // Check if supplier service exists
            if (!$this->factory->has($supplierCode)) {
                Log::warning("Webhook received for unknown supplier: {$supplierCode}");

                return response()->json([
                    'success' => false,
                    'message' => 'Unknown supplier'
                ], 404);
            }

            // Get supplier service
            $service = $this->factory->make($supplierCode);

            // Check if supplier is active
            if (!$service->isActive()) {
                Log::warning("Webhook received for inactive supplier: {$supplierCode}");

                return response()->json([
                    'success' => false,
                    'message' => 'Supplier is not active'
                ], 403);
            }

            // Get raw payload content
            $rawPayload = $request->getContent();
            $signature = $request->header('X-Signature') ?? $request->header('x-signature') ?? $request->header('Authorization') ?? '';

            // Parse JSON payload first
            $data = json_decode($rawPayload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Invalid JSON webhook from {$supplierCode}: " . json_last_error_msg());

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ], 400);
            }

            // Validate webhook signature (now passes array)
            if (!$service->validateWebhookSignature($data, $signature)) {
                Log::warning("Invalid webhook signature from {$supplierCode}", [
                    'signature' => $signature,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            // Handle webhook through supplier service
            $result = $service->handleWebhook($data);

            // Return response
            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'Webhook processed'
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("Webhook error for {$supplierCode}: " . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->getContent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Test webhook endpoint (for development)
     *
     * @param Request $request
     * @param string $supplierCode
     * @return JsonResponse
     */
    public function test(Request $request, string $supplierCode): JsonResponse
    {
        try {
            if (!$this->factory->has($supplierCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown supplier'
                ], 404);
            }

            $service = $this->factory->make($supplierCode);

            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint is ready',
                'supplier' => [
                    'code' => $service->getSupplierCode(),
                    'active' => $service->isActive(),
                ],
                'url' => route('webhook.handle', ['supplierCode' => $supplierCode]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
