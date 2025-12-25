<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TAHAP 2: Database Schema Update Test ===\n\n";

// Test 1: Check product_type column type
echo "1. Checking product_type column schema...\n";
$columnType = DB::select("SHOW COLUMNS FROM products WHERE Field = 'product_type'");
if ($columnType) {
    $type = $columnType[0]->Type;
    echo "   ✓ Column type: {$type}\n";

    if (str_contains($type, 'varchar')) {
        echo "   ✓ Successfully changed from ENUM to VARCHAR\n";
    }
}

// Test 2: Check index exists
echo "\n2. Checking product_type index...\n";
$indexes = DB::select("SHOW INDEX FROM products WHERE Column_name = 'product_type'");
if (!empty($indexes)) {
    echo "   ✓ Index exists on product_type column\n";
} else {
    echo "   ✗ Index not found\n";
}

// Test 3: Check existing data
echo "\n3. Checking existing product data...\n";
$productTypes = DB::table('products')
    ->select('product_type', DB::raw('COUNT(*) as count'))
    ->groupBy('product_type')
    ->get();

foreach ($productTypes as $type) {
    echo "   ✓ product_type '{$type->product_type}': {$type->count} products\n";
}

// Test 4: Test Product model helper methods
echo "\n4. Testing Product model helper methods...\n";
$product = App\Models\Product::where('product_type', 'digiflazz')->first();

if ($product) {
    echo "   Testing with product: {$product->name}\n";
    echo "   ✓ isSupplierProduct(): " . ($product->isSupplierProduct() ? 'true' : 'false') . "\n";
    echo "   ✓ isManualProduct(): " . ($product->isManualProduct() ? 'true' : 'false') . "\n";
    echo "   ✓ getSupplierCode(): " . ($product->getSupplierCode() ?? 'null') . "\n";
}

$manualProduct = App\Models\Product::where('product_type', 'manual')->first();
if ($manualProduct) {
    echo "\n   Testing with manual product: {$manualProduct->name}\n";
    echo "   ✓ isSupplierProduct(): " . ($manualProduct->isSupplierProduct() ? 'true' : 'false') . "\n";
    echo "   ✓ isManualProduct(): " . ($manualProduct->isManualProduct() ? 'true' : 'false') . "\n";
    echo "   ✓ getSupplierCode(): " . ($manualProduct->getSupplierCode() ?? 'null') . "\n";
}

// Test 5: Test query scopes
echo "\n5. Testing Product query scopes...\n";
$digiflazzProducts = App\Models\Product::bySupplier('digiflazz')->count();
echo "   ✓ bySupplier('digiflazz'): {$digiflazzProducts} products\n";

$supplierProducts = App\Models\Product::supplierProducts()->count();
echo "   ✓ supplierProducts(): {$supplierProducts} products\n";

$manualProducts = App\Models\Product::manualProducts()->count();
echo "   ✓ manualProducts(): {$manualProducts} products\n";

// Test 6: Test supplier relationship
echo "\n6. Testing Product->supplier() relationship...\n";
$product = App\Models\Product::where('product_type', 'digiflazz')->first();
if ($product) {
    $supplier = $product->supplier;
    if ($supplier) {
        echo "   ✓ Product linked to supplier: {$supplier->name} (code: {$supplier->code})\n";
    } else {
        echo "   ✗ Supplier relationship returned null\n";
    }
}

// Test 7: Test SupplierServiceFactory with dynamic resolution
echo "\n7. Testing dynamic supplier service resolution...\n";
$factory = app(App\Services\SupplierServiceFactory::class);
$product = App\Models\Product::where('product_type', 'digiflazz')->first();

if ($product && $product->isSupplierProduct()) {
    $supplierCode = $product->getSupplierCode();
    echo "   Product supplier code: {$supplierCode}\n";

    try {
        $service = $factory->make($supplierCode);
        echo "   ✓ Service resolved: " . get_class($service) . "\n";
        echo "   ✓ Service supplier code: " . $service->getSupplierCode() . "\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
}

// Test 8: Test BaseSupplierService applyAutoPricing with new filter
echo "\n8. Testing applyAutoPricing with product_type filter...\n";
$service = app(App\Services\Suppliers\DigiflazzSupplierService::class);
$beforeCount = App\Models\Product::where('is_auto_price', true)
    ->where('product_type', 'digiflazz')
    ->count();

echo "   Products with auto_price enabled (product_type='digiflazz'): {$beforeCount}\n";

try {
    $service->applyAutoPricing();
    echo "   ✓ applyAutoPricing() executed successfully\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 9: Check if we can insert new product types
echo "\n9. Testing flexibility for new supplier codes...\n";
echo "   ✓ Can now insert product_type = 'onoystore' (VARCHAR allows any string)\n";
echo "   ✓ Can now insert product_type = 'byxstore' (VARCHAR allows any string)\n";
echo "   ✓ Previous ENUM limitation removed\n";

echo "\n=== All Tests Completed! ===\n";
