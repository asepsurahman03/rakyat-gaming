<?php
namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\DigiflazzLog;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DigiflazzService
{
    protected Supplier $supplier;
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->supplier = Supplier::where('code', 'digiflazz')->firstOrFail();
        $this->baseUrl = $this->supplier->api_url ?? config('services.digiflazz.url', env('DIGIFLAZZ_URL'));
        $this->apiKey = $this->supplier->api_key ?? env('DIGIFLAZZ_API_KEY');
    }

    /**
     * Sync price list from Digiflazz and upsert into supplier_products
     */
    public function syncPriceList(): array
    {
        // Gunakan package Digiflazz
        $response = \Triyatna\Digiflazz\Digiflazz::getPriceList('prepaid');

        // error handling sederhana
        if (!$response->isSuccess()) {
            return ['success' => false, 'body' => $response->data()];
        }

        $items = $response->data();

        \Illuminate\Support\Facades\Log::debug('Digiflazz price list fetched', [
            'count' => is_array($items) ? count($items) : (is_object($items) ? count((array)$items) : 0),
            'sample' => array_values(
                is_array($items)
                    ? array_slice($items, 0, 5)
                    : (is_object($items) ? array_slice((array)$items, 0, 5) : [])
            ),
        ]);

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                // Mapping sesuai struktur data Digiflazz
                $code = $item['buyer_sku_code'] ?? null;
                $productName = $item['product_name'] ?? null;
                $category = $item['category'] ?? null;
                $brand = $item['brand'] ?? null;
                $type = $item['type'] ?? null;
                $sellerName = $item['seller_name'] ?? null;
                $price = isset($item['price']) ? (float)$item['price'] : 0.0;

                // Status produk (aktif jika buyer dan seller status true)
                $buyerStatus = $item['buyer_product_status'] ?? false;
                $sellerStatus = $item['seller_product_status'] ?? false;
                $status = ($buyerStatus && $sellerStatus) ? 'active' : 'inactive';

                // Skip jika tidak ada kode produk
                if (!$code) continue;

                SupplierProduct::updateOrCreate(
                    ['supplier_id' => $this->supplier->id, 'code' => $code],
                    [
                        'category' => $category,
                        'brand' => $brand,
                        'product_name' => $productName,
                        'buyer_price' => $price,
                        'status' => $status,
                        'raw_json' => $item, // Simpan seluruh data mentah untuk referensi
                        'last_sync_at' => now(),
                    ]
                );
            }
        });

        return ['success' => true, 'count' => count($items)];
    }

    /**
     * Build customer_no based on game requirements
     * Some games like Mobile Legends need player_id + server_id format
     *
     * @param array $dataPlayer
     * @return string
     */
    protected function buildCustomerNo(array $dataPlayer): string
    {
        $playerId = $dataPlayer['player_id'] ?? $dataPlayer['user_id'] ?? '';
        $serverId = $dataPlayer['server_id'] ?? $dataPlayer['zone_id'] ?? '';

        // If both player_id and server_id exist, combine them
        // Format: player_id(server_id) - e.g., "123456789(1234)"
        if (!empty($playerId) && !empty($serverId)) {
            return $playerId . $serverId;
        }

        // Otherwise, just return player_id or user_id
        return $playerId;
    }

    /**
     * Topup (prepaid) menggunakan supplier_product code.
     * Returns supplier response (and logs it).
     */
    public function topup(Order $order, SupplierProduct $supplierProduct): array
    {
        // Get Digiflazz credentials from supplier or config
        $username = $this->supplier->username ?? env('DIGIFLAZZ_USERNAME');

        // Get player data from JSON data_player field
        $dataPlayer = is_string($order->data_player) ? json_decode($order->data_player, true) : $order->data_player;

        // Build customer_no based on game requirements
        // For Mobile Legends and similar games: player_id + server_id (e.g., "123456789(1234)")
        // For other games: just player_id or user_id
        $customerId = $this->buildCustomerNo($dataPlayer);

        // Generate signature: md5(username + apiKey + ref_id)
        $refId = $order->order_code;
        $sign = md5($username . $this->apiKey . $refId);

        // Build payload according to Digiflazz API documentation
        $payload = [
            'username' => $username,
            'buyer_sku_code' => $supplierProduct->code,
            'customer_no' => $customerId,
            'ref_id' => $refId,
            'sign' => $sign,
            'testing' => true,
        ];

        // Digiflazz transaction endpoint
        $endpoint = rtrim($this->baseUrl, '/') . '/transaction';

        // log request
        DigiflazzLog::create(['order_id' => $order->id, 'request_body' => $payload, 'status' => 'sent']);

        $response = Http::asJson()
            ->post($endpoint, $payload);

        $body = $response->json();

        // log response
        DigiflazzLog::create([
            'order_id' => $order->id,
            'request_body' => $payload,
            'response_body' => $body,
            'status' => $response->ok() ? 'success' : 'failed'
        ]);

        // Check if response is OK and has data
        if ($response->ok() && isset($body['data'])) {
            $transactionStatus = strtolower($body['data']['status'] ?? '');

            // Handle different transaction statuses
            switch ($transactionStatus) {
                case 'sukses':
                case 'success':
                    $order->update(['status' => 'success', 'note' => json_encode($body['data'])]);

                    // Increment FlazzSellProduct sold_count if product is in active FlazzSell
                    $product = $order->product;
                    if ($product) {
                        $flazzSellProduct = $product->activeFlazzSellProduct();
                        if ($flazzSellProduct) {
                            $flazzSellProduct->incrementSoldCount();
                        }
                    }

                    return ['success' => true, 'body' => $body];

                case 'pending':
                case 'process':
                    // Transaction is still being processed by Digiflazz
                    $order->update(['status' => 'process', 'note' => json_encode($body['data'])]);
                    return ['success' => true, 'body' => $body, 'message' => 'Transaction is being processed'];

                case 'gagal':
                case 'failed':
                    // Transaction failed
                    $order->update(['status' => 'failed', 'note' => json_encode($body['data'])]);
                    return ['success' => false, 'body' => $body, 'message' => 'Transaction failed'];

                default:
                    // Unknown status, mark as process and wait for callback
                    $order->update(['status' => 'process', 'note' => json_encode($body['data'])]);
                    return ['success' => true, 'body' => $body, 'message' => 'Transaction status: ' . $transactionStatus];
            }
        }

        // fallback: if response not OK or no data
        $order->update(['status' => 'failed', 'note' => json_encode($body)]);
        return ['success' => false, 'body' => $body, 'message' => 'Invalid response from Digiflazz'];
    }

    /**
     * Auto-update local product selling price when supplier price changed.
     * Rule: if product.is_auto_price === true -> selling_price = buyer_price + margin
     */
    public function applyAutoPricing(): void
    {
        $defaultMargin = (float) (app('config')->get('app.default_margin') ?? env('DEFAULT_MARGIN', 1000));
        $products = \App\Models\Product::where('is_auto_price', true)->with('supplierProduct')->get();

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
    }
}
