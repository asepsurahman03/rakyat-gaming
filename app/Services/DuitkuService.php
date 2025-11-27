<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuitkuService
{
    protected string $merchantCode;
    protected string $apiKey;
    protected string $callbackUrl;
    protected string $returnUrl;
    protected bool $sandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = config('services.duitku.merchant_code');
        $this->apiKey = config('services.duitku.api_key');
        $this->callbackUrl = config('services.duitku.callback_url');
        $this->returnUrl = config('services.duitku.return_url');
        $this->sandbox = config('services.duitku.sandbox', true);

        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.duitku.com/webapi/api/merchant'
            : 'https://passport.duitku.com/webapi/api/merchant';
    }

    /**
     * Create payment request to Duitku
     *
     * @param Order $order
     * @param string $paymentMethod Payment method code (e.g., 'BC' for BCA)
     * @return array
     */
    public function createPayment(Order $order, string $paymentMethod): array
    {
        $amount = (int) $order->price;
        $merchantOrderId = $order->order_code;

        // Generate signature
        $signature = md5($this->merchantCode . $merchantOrderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $order->product->name ?? 'Game Top Up',
            'additionalParam' => json_encode([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]),
            'merchantUserInfo' => $order->user->name ?? 'Guest',
            'customerVaName' => $order->user->name ?? 'Guest',
            'email' => $order->user->email ?? '',
            'phoneNumber' => $order->user->profile->whatsapp ?? '',
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'signature' => $signature,
            'expiryPeriod' => 60, // 60 minutes
        ];

        try {
            $response = Http::post($this->baseUrl . '/v2/inquiry', $payload);

            $body = $response->json();

            Log::info('Duitku payment request', [
                'order_id' => $order->id,
                'payload' => $payload,
                'response' => $body,
            ]);

            if ($response->successful() && isset($body['reference'])) {
                return [
                    'success' => true,
                    'reference' => $body['reference'],
                    'pay_url' => $body['paymentUrl'] ?? null,
                    'va_number' => $body['vaNumber'] ?? null,
                    'amount' => $body['amount'] ?? $amount,
                ];
            }

            return [
                'success' => false,
                'message' => $body['Message'] ?? 'Payment request failed',
                'body' => $body,
            ];

        } catch (\Exception $e) {
            Log::error('Duitku payment request failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment gateway error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify callback signature from Duitku
     *
     * @param array $data
     * @return bool
     */
    public function verifyCallback(array $data): bool
    {
        $merchantOrderId = $data['merchantOrderId'] ?? '';
        $amount = $data['amount'] ?? 0;
        $receivedSignature = $data['signature'] ?? '';

        $signature = md5($this->merchantCode . $amount . $merchantOrderId . $this->apiKey);

        return hash_equals($signature, $receivedSignature);
    }

    /**
     * Get available payment methods from Duitku
     *
     * @param int $amount
     * @return array
     */
    public function getPaymentMethods(int $amount = 10000): array
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = md5($this->merchantCode . $amount . $datetime . $this->apiKey);

        $payload = [
            'merchantcode' => $this->merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ];

        try {
            $response = Http::post($this->baseUrl . '/paymentmethod/getpaymentmethod', $payload);

            $body = $response->json();

            Log::info('Duitku get payment methods', [
                'payload' => $payload,
                'response' => $body,
            ]);

            if ($response->successful() && isset($body['paymentFee'])) {
                return [
                    'success' => true,
                    'methods' => $body['paymentFee'],
                ];
            }

            return [
                'success' => false,
                'methods' => [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get payment methods', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'methods' => [],
            ];
        }
    }
}
