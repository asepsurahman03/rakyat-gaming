<?php

/**
 * Script untuk test koneksi Ipaymu secara langsung
 * Jalankan dengan: php test-ipaymu.php
 */

require __DIR__ . '/vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Ipaymu Connection Test ===\n\n";

// Check configuration
echo "1. Checking Configuration...\n";
$va = config('services.ipaymu.va');
$apiKey = config('services.ipaymu.api_key');
$sandbox = config('services.ipaymu.sandbox', false);

echo "   VA: " . ($va ? substr($va, 0, 5) . '***' : 'NOT SET') . "\n";
echo "   API Key: " . ($apiKey ? substr($apiKey, 0, 8) . '***' : 'NOT SET') . "\n";
echo "   Sandbox: " . ($sandbox ? 'YES' : 'NO') . "\n";
echo "   Base URL: " . ($sandbox ? 'https://sandbox.ipaymu.com/api/v2' : 'https://my.ipaymu.com/api/v2') . "\n\n";

if (!$va || !$apiKey) {
    echo "ERROR: VA atau API Key belum diset di .env file!\n";
    echo "Tambahkan:\n";
    echo "IPAYMU_VA=your_va_number\n";
    echo "IPAYMU_API_KEY=your_api_key\n";
    exit(1);
}

// Check database configuration
echo "2. Checking Database Configuration...\n";
$gateway = \App\Models\PaymentGateway::where('code', 'ipaymu')->first();
if ($gateway) {
    echo "   ✓ Payment Gateway found in database\n";
    echo "   Name: {$gateway->name}\n";
    echo "   Active: " . ($gateway->is_active ? 'YES' : 'NO') . "\n";
    echo "   Sandbox: " . ($gateway->is_sandbox ? 'YES' : 'NO') . "\n";
} else {
    echo "   ✗ Payment Gateway NOT found in database\n";
    echo "   Run: php artisan db:seed --class=PaymentGatewaySeeder\n";
}
echo "\n";

// Test API Connection
echo "3. Testing API Connection (First Call - No Cache)...\n";
try {
    $service = new \App\Services\IpaymuService();
    $startTime = microtime(true);
    $result = $service->getPaymentMethods(10000, [], false); // Force fresh call without cache
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    if ($result['success']) {
        echo "   ✓ Connection SUCCESSFUL!\n";
        echo "   Found " . count($result['methods']) . " payment methods\n";
        echo "   Response Time: {$duration}ms\n";
        echo "   Cached: " . ($result['cached'] ?? false ? 'YES' : 'NO') . "\n";

        if (count($result['methods']) > 0) {
            echo "\n   Payment Methods:\n";
            foreach (array_slice($result['methods'], 0, 5) as $method) {
                $min = $method['minimum'] > 0 ? 'Min: Rp ' . number_format($method['minimum']) : '';
                $max = $method['maximum'] > 0 ? 'Max: Rp ' . number_format($method['maximum']) : '';
                $range = trim($min . ' ' . $max);
                echo "   - {$method['name']} ({$method['code']}) " . ($range ? "[$range]" : '') . "\n";
            }
            if (count($result['methods']) > 5) {
                echo "   ... and " . (count($result['methods']) - 5) . " more\n";
            }
        }
    } else {
        echo "   ✗ Connection FAILED!\n";
        echo "   Message: {$result['message']}\n";
        echo "\n   Kemungkinan masalah:\n";
        echo "   - VA atau API Key salah\n";
        echo "   - Mode sandbox/production tidak sesuai dengan credentials\n";
        echo "   - IP server belum di-whitelist di dashboard Ipaymu\n";
        echo "   - API endpoint berubah atau tidak tersedia\n";
    }
} catch (\Exception $e) {
    echo "   ✗ EXCEPTION: {$e->getMessage()}\n";
}
echo "\n";

// Test Cache
echo "4. Testing Cache (Second Call - Should Use Cache)...\n";
try {
    $startTime = microtime(true);
    $result = $service->getPaymentMethods(10000); // Use cache if available
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    if ($result['success']) {
        echo "   ✓ Cache Test SUCCESSFUL!\n";
        echo "   Found " . count($result['methods']) . " payment methods\n";
        echo "   Response Time: {$duration}ms\n";
        echo "   Cached: " . ($result['cached'] ?? false ? 'YES (Much Faster!)' : 'NO') . "\n";
    }
} catch (\Exception $e) {
    echo "   ✗ EXCEPTION: {$e->getMessage()}\n";
}
echo "\n";

// Test Filters
echo "5. Testing Payment Method Filters...\n";
try {
    // Test with filter: exclude specific channels
    $filters = [
        'exclude' => ['cod'], // Example: exclude COD
        'min_amount' => 5000,
        'max_amount' => 1000000,
    ];

    $result = $service->getPaymentMethods(50000, $filters);

    if ($result['success']) {
        echo "   ✓ Filter Test SUCCESSFUL!\n";
        echo "   Original methods: (see previous test)\n";
        echo "   Filtered methods: " . count($result['methods']) . " (after applying filters)\n";
        echo "   Filters applied:\n";
        echo "     - Excluded channels: " . implode(', ', $filters['exclude']) . "\n";
        echo "     - Min amount: Rp " . number_format($filters['min_amount']) . "\n";
        echo "     - Max amount: Rp " . number_format($filters['max_amount']) . "\n";

        if (count($result['methods']) > 0) {
            echo "\n   Filtered Payment Methods:\n";
            foreach (array_slice($result['methods'], 0, 3) as $method) {
                echo "   - {$method['name']} ({$method['code']})\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ✗ EXCEPTION: {$e->getMessage()}\n";
}
echo "\n";

// Test Clear Cache
echo "6. Testing Clear Cache...\n";
try {
    $cleared = $service->clearPaymentMethodsCache(10000);
    echo "   " . ($cleared ? '✓' : '✗') . " Cache cleared for amount: Rp 10,000\n";

    // Get again to verify cache was cleared
    $startTime = microtime(true);
    $result = $service->getPaymentMethods(10000);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    if ($result['success']) {
        echo "   Response after clear cache: {$duration}ms\n";
        echo "   Cached: " . ($result['cached'] ?? false ? 'YES' : 'NO (Fresh API Call)') . "\n";
    }
} catch (\Exception $e) {
    echo "   ✗ EXCEPTION: {$e->getMessage()}\n";
}

echo "\n=== Test Completed ===\n";
echo "Check storage/logs/laravel.log for detailed logs\n";
