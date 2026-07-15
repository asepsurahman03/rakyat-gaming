<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentGateway;
use App\Models\Product;

class TripayService
{
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $baseUrl;
    private $callbackUrl;
    private $returnUrl;

    public function __construct()
    {
        // Load configuration from database (PaymentGateway model) or fallback to config files
        $paymentGateway = PaymentGateway::where('code', 'tripay')->first();

        if ($paymentGateway) {
            $this->apiKey = $paymentGateway->api_key;
            $this->privateKey = $paymentGateway->api_secret;
            $this->merchantCode = $paymentGateway->merchant_code;
            $this->baseUrl = $paymentGateway->is_sandbox ? 'https://tripay.co.id/api-sandbox' : 'https://tripay.co.id/api';
            $this->callbackUrl = $paymentGateway->callback_url;
            $this->returnUrl = $paymentGateway->redirect_url;
        } else {
            $this->apiKey = config('services.tripay.api_key');
            $this->privateKey = config('services.tripay.private_key');
            $this->merchantCode = config('services.tripay.merchant_code');
            // Set base URL based on mode
            $mode = config('services.tripay.mode', 'production');
            $this->baseUrl = $mode === 'sandbox' ? 'https://tripay.co.id/api-sandbox' : 'https://tripay.co.id/api';
            $this->callbackUrl = config('services.tripay.callback_url');
            $this->returnUrl = config('services.tripay.redirect_url');
        }

    }

    public function testConection($apiKey, $privateKey, $merchantCode, $isSandbox)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get(($isSandbox ? 'https://tripay.co.id/api-sandbox' : 'https://tripay.co.id/api') . '/merchant/payment-channel');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Channels Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available payment channels
     */
    public function getPaymentChannels()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/merchant/payment-channel');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Channels Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment instructions for specific channel
     */
    public function getPaymentInstructions($code, $amount = null, $allowHtml = false)
    {
        try {
            $params = [
                'code' => $code,
                'amount' => $amount,
                'allow_html' => $allowHtml ? 1 : 0,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/payment/instruction', array_filter($params));

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Instructions Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate fee for specific amount and payment method
     */
    public function calculateFee($code, $amount)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/merchant/fee-calculator', [
                'code' => $code,
                'amount' => $amount,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Calculate Fee Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create closed payment transaction
     */
    public function createPayment(Order $order, OrderPayment $orderPayment)
    {
        try {
            $signature = $this->generateSignature($order->order_code, $orderPayment->amount);

            $name = null;

            if ($order->user) {
                $name = $order->user->name;
            } else {
                $name = 'Guest' . rand(1000, 9999);
            }

            $data = [
                'customer_name' => $name,
                'customer_email' => $order->email,
                'customer_phone' => $order->whatsapp,
                'order_items' => [
                    [
                        'sku' => "product_" . $order->product_id,
                        'name' => $order->product->name,
                        'price' => $orderPayment->amount,
                        'quantity' => 1,
                    ],
                ],
                'return_url' => $this->returnUrl,
                'callback_url' => $this->callbackUrl,
                'expired_time' => time() + 24 * 60 * 60,
            ];

            $payload = [
                'method' => $orderPayment->method,
                'merchant_ref' => $order->order_code,
                'amount' => $orderPayment->amount,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'order_items' => $data['order_items'],
                'return_url' => $data['return_url'] ?? url('/payment/return'),
                'expired_time' => $data['expired_time'] ?? time() + 24 * 60 * 60,
                'signature' => $signature,
            ];

            // Add optional callback_url if provided
            if (isset($data['callback_url'])) {
                $payload['callback_url'] = $data['callback_url'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . '/transaction/create', $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Create Transaction Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction detail
     */
    public function getTransactionDetail($reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/transaction/detail', [
                'reference' => $reference,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Transaction Detail Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of transactions
     */
    public function getTransactionList($page = 1, $perPage = 10)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/merchant/transactions', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Transaction List Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create open payment (static payment)
     */
    public function createOpenPayment($method, $merchantRef, $customerName)
    {
        try {
            $signature = hash_hmac('sha256', $this->merchantCode . $method . $merchantRef, $this->privateKey);

            $payload = [
                'method' => $method,
                'merchant_ref' => $merchantRef,
                'customer_name' => $customerName,
                'signature' => $signature,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . '/open-payment/create', $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Create Open Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get open payment detail
     */
    public function getOpenPaymentDetail($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/open-payment/' . $uuid . '/detail');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tripay Get Open Payment Detail Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate signature for closed transaction
     */
    private function generateSignature($merchantRef, $amount)
    {
        $amount = intval($amount);
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }

    /**
     * Validate callback signature
     */
    public function validateCallbackSignature($callbackSignature, $jsonBody)
    {
        $signature = hash_hmac('sha256', $jsonBody, $this->privateKey);
        return $signature === $callbackSignature;
    }
}
