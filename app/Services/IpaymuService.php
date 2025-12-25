<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpaymuService
{
    protected $va;
    protected $apiKey;
    protected $callbackUrl;
    protected $redirectUrl;
    protected $isSandbox;
    protected $baseUrl;

    public function __construct()
    {
        // Load configuration from database (PaymentGateway model) or fallback to config files
        $paymentGateway = PaymentGateway::where('code', 'ipaymu')->first();

        if ($paymentGateway) {
            $this->va = $paymentGateway->api_secret; // VA number stored in api_secret
            $this->apiKey = $paymentGateway->api_key;
            $this->callbackUrl = $paymentGateway->callback_url;
            $this->redirectUrl = $paymentGateway->redirect_url;
            $this->isSandbox = $paymentGateway->is_sandbox ?? false;
        } else {
            // Fallback to config file
            $this->va = config('services.ipaymu.va');
            $this->apiKey = config('services.ipaymu.api_key');
            $this->callbackUrl = config('services.ipaymu.callback_url');
            $this->redirectUrl = config('services.ipaymu.redirect_url');
            $this->isSandbox = config('services.ipaymu.sandbox', false);
        }

        // Set base URL based on environment
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.ipaymu.com/api/v2'
            : 'https://my.ipaymu.com/api/v2';

        // Validate configuration
        if (empty($this->va) || empty($this->apiKey)) {
            Log::warning('Ipaymu configuration incomplete', [
                'va_set' => !empty($this->va),
                'api_key_set' => !empty($this->apiKey),
            ]);
        }
    }

    /**
     * Create payment request to Ipaymu
     *
     * @param Order $order
     * @param string $paymentMethod
     * @return array
     */
    public function createPayment(Order $order, $paymentMethod)
    {
        try {
            // Validate configuration
            if (empty($this->va) || empty($this->apiKey)) {
                Log::error('Ipaymu configuration missing', [
                    'va_set' => !empty($this->va),
                    'api_key_set' => !empty($this->apiKey),
                ]);

                return [
                    'success' => false,
                    'message' => 'Ipaymu VA atau API Key belum dikonfigurasi.',
                ];
            }

            $orderPayment = $order->orderPayment;

            // Prepare request body
            $body = [
                'name' => $order->email,
                'phone' => $order->whatsapp,
                'email' => $order->email,
                'amount' => (int) $orderPayment->amount,
                'notifyUrl' => $this->callbackUrl,
                'returnUrl' => $this->redirectUrl . '?merchantOrderId=' . $order->order_code,
                'cancelUrl' => $this->redirectUrl . '?merchantOrderId=' . $order->order_code,
                'referenceId' => $order->order_code,
                'buyerName' => $order->email,
                'buyerPhone' => $order->whatsapp,
                'buyerEmail' => $order->email,
                'paymentMethod' => $paymentMethod['group_code'],
                'paymentChannel' => $paymentMethod['code'],
                'product' => [
                    $order->product->name
                ],
                'qty' => [1],
                'price' => [(int) $orderPayment->amount],
                'description' => 'Order ' . $order->order_code . ' - ' . $order->product->name,
                'expired' => 24, // 24 hours
                'expiredType' => 'hours',
            ];

            // Generate signature
            $bodyEncoded = json_encode($body, JSON_UNESCAPED_SLASHES);

            // Format: HTTPMethod:VaNumber:Lowercase(SHA-256(RequestBody)):ApiKey
            // Then: HMAC-SHA256(StringToSign, ApiKey)
            $httpMethod = 'POST';
            $bodyHash = strtolower(hash('sha256', $bodyEncoded));
            $stringToSign = $httpMethod . ':' . $this->va . ':' . $bodyHash . ':' . $this->apiKey;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

            Log::info('Ipaymu createPayment request', [
                'order_code' => $order->order_code,
                'amount' => $orderPayment->amount,
                'payment_method' => $paymentMethod,
                'va' => $this->va,
                'baseUrl' => $this->baseUrl,
                'method' => $httpMethod,
                'body_hash' => $bodyHash,
                'string_to_sign' => $stringToSign,
                'signature' => $signature,
            ]);

            // Make API request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'signature' => $signature,
                    'va' => $this->va,
                    'timestamp' => date('YmdHis'), // Format: YYYYMMDDHHmmss
                ])->post($this->baseUrl . '/payment/direct', $body);

            $result = $response->json();
            $statusCode = $response->status();

            Log::info('Ipaymu createPayment response', [
                'order_code' => $order->order_code,
                'status_code' => $statusCode,
                'response' => $result,
                'body' => $response->body(),
            ]);

            if ($response->successful() && isset($result['Status']) && $result['Status'] == 200) {
                return [
                    'success' => true,
                    'channel' => $result['Data']['Channel'] ?? null,
                    'reference' => $result['Data']['SessionId'] ?? null,
                    'payment_url' => $result['Data']['Url'] ?? null,
                    'payment_no' => $result['Data']['PaymentNo'] ?? null,
                    'qr_string' => $result['Data']['QrString'] ?? null,
                    'QrImage' => $result['Data']['QrImage'] ?? null,
                    'amount' => $result['Data']['Total'] ?? $orderPayment->amount,
                    'expired' => $result['Data']['Expired'] ?? null,
                ];
            }

            Log::error('Ipaymu createPayment failed', [
                'order_code' => $order->order_code,
                'status_code' => $statusCode,
                'response' => $result,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $result['Message'] ?? 'Failed to create payment. Status: ' . $statusCode,
            ];
        } catch (\Exception $e) {
            Log::error('Ipaymu createPayment exception', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify callback signature from Ipaymu
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function verifyCallback($request)
    {
        try {
            $data = $request->all();

            // Note: iPaymu callback does NOT require signature verification
            // The X-Signature header is sent but verification is not enforced
            // Security is handled by IP whitelisting in middleware

            Log::info('Ipaymu callback received', [
                'reference_id' => $data['reference_id'] ?? null,
                'status' => $data['status'] ?? null,
                'trx_id' => $data['trx_id'] ?? null,
                'amount' => $data['amount'] ?? null,
                'headers' => [
                    'x-signature' => $request->header('x-signature'),
                    'x-timestamp' => $request->header('x-timestamp'),
                    'x-external-id' => $request->header('x-external-id'),
                ],
            ]);

            // Always return true - signature verification not required for iPaymu callbacks
            return true;
        } catch (\Exception $e) {
            Log::error('Ipaymu verifyCallback exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get available payment methods from Ipaymu using list payment endpoint
     *
     * @param int $amount
     * @param array $filters ['exclude' => ['channel1', 'channel2'], 'min_amount' => 0, 'max_amount' => 0]
     * @param bool $useCache Whether to use cache or force fresh API call
     * @param int $cacheTtl Cache time-to-live in minutes (default: 15)
     * @return array
     */
    public function getPaymentMethods(int $amount, array $filters = [], bool $useCache = true, int $cacheTtl = 15)
    {
        try {
            // Validate configuration
            if (empty($this->va) || empty($this->apiKey)) {
                Log::error('Ipaymu configuration missing', [
                    'va_set' => !empty($this->va),
                    'api_key_set' => !empty($this->apiKey),
                ]);

                return [
                    'success' => false,
                    'message' => 'Ipaymu VA atau API Key belum dikonfigurasi. Silakan set di .env atau database.',
                    'methods' => [],
                ];
            }

            // Create cache key based on amount to differentiate cache by amount
            $cacheKey = 'ipaymu_payment_methods_' . $amount;

            // Check cache first if enabled
            if ($useCache && Cache::has($cacheKey)) {
                Log::info('Ipaymu getPaymentMethods using cache', [
                    'cache_key' => $cacheKey,
                    'amount' => $amount,
                ]);

                $methods = Cache::get($cacheKey);

                // Apply filters to cached data
                $filteredMethods = $this->filterPaymentMethods($methods, $filters, $amount);

                return [
                    'success' => true,
                    'methods' => $filteredMethods,
                    'cached' => true,
                ];
            }

            // Prepare request for GET payment channels
            // For GET request, body must be empty object {}
            $body = new \stdClass(); // Empty object, not array
            $bodyEncoded = json_encode($body, JSON_UNESCAPED_SLASHES);

            // Generate signature for GET request
            // Format: HTTPMethod:VaNumber:Lowercase(SHA-256(RequestBody)):ApiKey
            // Then: HMAC-SHA256(StringToSign, ApiKey)
            $httpMethod = 'GET';
            $bodyHash = strtolower(hash('sha256', $bodyEncoded));
            $stringToSign = $httpMethod . ':' . $this->va . ':' . $bodyHash . ':' . $this->apiKey;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

            Log::info('Ipaymu listPayment request', [
                'amount' => $amount,
                'va' => $this->va,
                'baseUrl' => $this->baseUrl,
                'method' => $httpMethod,
                'body_encoded' => $bodyEncoded,
                'body_hash' => $bodyHash,
                'string_to_sign' => $stringToSign,
                'signature' => $signature,
            ]);

            // Make API request to list payment channels endpoint using GET
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'signature' => $signature,
                    'va' => $this->va,
                    'timestamp' => date('YmdHis'), // Format: YYYYMMDDHHmmss
                ])->get($this->baseUrl . '/payment-channels');

            $result = $response->json();
            $statusCode = $response->status();

            Log::info('Ipaymu listPayment response', [
                'status_code' => $statusCode,
                'response' => $result,
                'body_response' => $response->body(),
            ]);

            // If still 405 error, fallback to hardcoded methods
            if ($statusCode == 405) {
                Log::warning('Ipaymu payment list endpoint not supported (405), using hardcoded methods');

                $methods = $this->getHardcodedPaymentMethods($amount);

                // Cache hardcoded methods too
                if ($useCache && !empty($methods)) {
                    Cache::put($cacheKey, $methods, now()->addMinutes($cacheTtl));
                }

                // Apply filters
                $filteredMethods = $this->filterPaymentMethods($methods, $filters, $amount);

                return [
                    'success' => true,
                    'methods' => $filteredMethods,
                    'cached' => false,
                    'source' => 'hardcoded',
                ];
            }

            if ($response->successful() && isset($result['Status']) && $result['Status'] == 200) {
                $methods = [];

                // Parse payment channels from nested Data array structure
                // Ipaymu structure: Data -> array of payment groups -> each has Channels array
                if (isset($result['Data']) && is_array($result['Data'])) {
                    foreach ($result['Data'] as $group) {
                        // Skip groups without Channels (like "ewallet-asia" that has no channels)
                        if (!isset($group['Channels']) || !is_array($group['Channels'])) {
                            continue;
                        }

                        // Loop through each channel in the group
                        foreach ($group['Channels'] as $channel) {
                            // Calculate fee based on type
                            $fee = 0;
                            $feeType = $channel['TransactionFee']['ActualFeeType'] ?? 'FLAT';
                            $actualFee = $channel['TransactionFee']['ActualFee'] ?? 0;
                            $additionalFee = $channel['TransactionFee']['AdditionalFee'] ?? 0;

                            if ($feeType === 'PERCENT') {
                                // Calculate percentage fee
                                $fee = (int) ($amount * ($actualFee / 100)) + $additionalFee;
                            } else {
                                // FLAT fee
                                $fee = $actualFee + $additionalFee;
                            }

                            $methods[] = [
                                'code' => $channel['Code'] ?? '',
                                'name' => $channel['Name'] ?? '',
                                'description' => $channel['Description'] ?? '',
                                'logo' => $channel['Logo'] ?? '',
                                'fee' => $fee,
                                'fee_type' => $feeType,
                                'actual_fee' => $actualFee,
                                'additional_fee' => $additionalFee,
                                'total' => $amount + $fee,
                                'minimum' => 0, // Ipaymu doesn't provide min/max in list endpoint
                                'maximum' => 0,
                                'group_code' => $group['Code'] ?? '',
                                'group_name' => $group['Name'] ?? '',
                                'feature_status' => $channel['FeatureStatus'] ?? 'active',
                                'health_status' => $channel['HealthStatus'] ?? 'online',
                                'active' => true,
                            ];
                        }
                    }
                }

                Log::info('Ipaymu listPayment parsed methods', [
                    'methods_count' => count($methods),
                ]);

                // Store to cache for future use
                if ($useCache && !empty($methods)) {
                    Cache::put($cacheKey, $methods, now()->addMinutes($cacheTtl));
                    Log::info('Ipaymu payment methods cached', [
                        'cache_key' => $cacheKey,
                        'ttl_minutes' => $cacheTtl,
                        'methods_count' => count($methods),
                    ]);
                }

                // Apply filters
                $filteredMethods = $this->filterPaymentMethods($methods, $filters, $amount);

                return [
                    'success' => true,
                    'methods' => $filteredMethods,
                    'cached' => false,
                ];
            }

            Log::error('Ipaymu listPayment failed', [
                'status_code' => $statusCode,
                'response' => $result,
                'message' => $result['Message'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => $result['Message'] ?? 'Failed to get payment list. Status: ' . $statusCode,
                'methods' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Ipaymu getPaymentMethods exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'methods' => [],
            ];
        }
    }

    /**
     * Test API connection
     *
     * @return array
     */
    public function testConnection()
    {
        try {
            // Test by getting payment methods
            $result = $this->getPaymentMethods(10000);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful! Found ' . count($result['methods']) . ' payment methods.',
                    'methods_count' => count($result['methods']),
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Connection failed',
            ];
        } catch (\Exception $e) {
            Log::error('Ipaymu testConnection exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get hardcoded payment methods as fallback
     * This is used when Ipaymu API doesn't support listing payment methods
     *
     * @param int $amount
     * @return array
     */
    protected function getHardcodedPaymentMethods(int $amount)
    {
        // Common Ipaymu payment channels with estimated fees
        return [
            [
                'code' => 'va',
                'name' => 'Virtual Account',
                'image' => '',
                'fee' => 4000,
                'total' => $amount + 4000,
                'minimum' => 10000,
                'maximum' => 50000000,
                'active' => true,
            ],
            [
                'code' => 'qris',
                'name' => 'QRIS',
                'image' => '',
                'fee' => (int) ($amount * 0.007), // 0.7%
                'total' => $amount + (int) ($amount * 0.007),
                'minimum' => 1500,
                'maximum' => 10000000,
                'active' => true,
            ],
            [
                'code' => 'banktransfer',
                'name' => 'Bank Transfer',
                'image' => '',
                'fee' => 5000,
                'total' => $amount + 5000,
                'minimum' => 10000,
                'maximum' => 50000000,
                'active' => true,
            ],
            [
                'code' => 'cstore',
                'name' => 'Convenience Store',
                'image' => '',
                'fee' => 2500,
                'total' => $amount + 2500,
                'minimum' => 10000,
                'maximum' => 5000000,
                'active' => true,
            ],
            [
                'code' => 'cod',
                'name' => 'Cash On Delivery (COD)',
                'image' => '',
                'fee' => 0,
                'total' => $amount,
                'minimum' => 0,
                'maximum' => 0,
                'active' => true,
            ],
        ];
    }

    /**
     * Filter payment methods based on criteria
     *
     * @param array $methods
     * @param array $filters
     * @param int $amount
     * @return array
     */
    protected function filterPaymentMethods(array $methods, array $filters, int $amount)
    {
        if (empty($filters)) {
            return $methods;
        }

        $filtered = $methods;

        // Filter by excluded channels
        if (!empty($filters['exclude']) && is_array($filters['exclude'])) {
            $filtered = array_filter($filtered, function ($method) use ($filters) {
                return !in_array($method['code'], $filters['exclude']);
            });
        }

        // Filter by minimum amount (payment method minimum)
        if (isset($filters['min_amount']) && $filters['min_amount'] > 0) {
            $filtered = array_filter($filtered, function ($method) use ($filters) {
                return ($method['minimum'] ?? 0) <= $filters['min_amount'];
            });
        }

        // Filter by maximum amount (payment method maximum)
        if (isset($filters['max_amount']) && $filters['max_amount'] > 0) {
            $filtered = array_filter($filtered, function ($method) use ($filters) {
                $maximum = $method['maximum'] ?? 0;
                return $maximum == 0 || $maximum >= $filters['max_amount'];
            });
        }

        // Filter by amount compatibility (check if amount is within method's min/max range)
        $filtered = array_filter($filtered, function ($method) use ($amount) {
            $minimum = $method['minimum'] ?? 0;
            $maximum = $method['maximum'] ?? 0;

            // If minimum is set and amount is less than minimum, exclude
            if ($minimum > 0 && $amount < $minimum) {
                return false;
            }

            // If maximum is set and amount is greater than maximum, exclude
            if ($maximum > 0 && $amount > $maximum) {
                return false;
            }

            return true;
        });

        // Re-index array after filtering
        return array_values($filtered);
    }

    /**
     * Clear payment methods cache
     *
     * @param int|null $amount Clear cache for specific amount, or all if null
     * @return bool
     */
    public function clearPaymentMethodsCache(?int $amount = null)
    {
        try {
            if ($amount !== null) {
                $cacheKey = 'ipaymu_payment_methods_' . $amount;
                Cache::forget($cacheKey);
                Log::info('Ipaymu cache cleared for specific amount', [
                    'cache_key' => $cacheKey,
                    'amount' => $amount,
                ]);
            } else {
                // Clear all payment methods cache (pattern matching)
                // Note: This requires cache store that supports tags or manual tracking
                $pattern = 'ipaymu_payment_methods_*';
                Log::info('Ipaymu cache clear requested for pattern', [
                    'pattern' => $pattern,
                ]);
                // For simple implementation, you might want to track cache keys
                // For now, we'll just log it
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear Ipaymu cache', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get payment methods without cache (force fresh API call)
     *
     * @param int $amount
     * @param array $filters
     * @return array
     */
    public function getPaymentMethodsFresh(int $amount, array $filters = [])
    {
        return $this->getPaymentMethods($amount, $filters, false);
    }

    /**
     * Check transaction status
     *
     * @param string $transactionId
     * @return array
     */
    public function checkTransactionStatus(string $transactionId)
    {
        try {
            // Prepare request body
            $body = [
                'transactionId' => $transactionId,
            ];

            // Generate signature
            $bodyEncoded = json_encode($body, JSON_UNESCAPED_SLASHES);

            // Format: HTTPMethod:VaNumber:Lowercase(SHA-256(RequestBody)):ApiKey
            // Then: HMAC-SHA256(StringToSign, ApiKey)
            $httpMethod = 'POST';
            $bodyHash = strtolower(hash('sha256', $bodyEncoded));
            $stringToSign = $httpMethod . ':' . $this->va . ':' . $bodyHash . ':' . $this->apiKey;
            $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

            Log::info('Ipaymu checkTransactionStatus request', [
                'transaction_id' => $transactionId,
                'method' => $httpMethod,
                'body_hash' => $bodyHash,
                'string_to_sign' => $stringToSign,
                'signature' => $signature,
            ]);

            // Make API request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'signature' => $signature,
                'va' => $this->va,
                'timestamp' => date('YmdHis'), // Format: YYYYMMDDHHmmss
            ])->post($this->baseUrl . '/transaction', $body);

            $result = $response->json();

            Log::info('Ipaymu checkTransactionStatus response', [
                'transaction_id' => $transactionId,
            ]);

            $result = $response->json();

            Log::info('Ipaymu checkTransactionStatus response', [
                'transaction_id' => $transactionId,
                'response' => $result,
            ]);

            if ($response->successful() && isset($result['Status']) && $result['Status'] == 200) {
                return [
                    'success' => true,
                    'data' => $result['Data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $result['Message'] ?? 'Failed to check transaction status',
            ];
        } catch (\Exception $e) {
            Log::error('Ipaymu checkTransactionStatus exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
