<?php

// Test script untuk memverifikasi TAHAP 1 implementation

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TAHAP 1: Service Layer Architecture Test ===\n\n";

try {
    // Test 1: Load Factory
    echo "1. Testing SupplierServiceFactory...\n";
    $factory = app(\App\Services\SupplierServiceFactory::class);
    echo "   ✓ Factory loaded: " . get_class($factory) . "\n";

    // Test 2: Check registered suppliers
    echo "\n2. Checking registered suppliers...\n";
    $registered = $factory->getRegistered();
    echo "   ✓ Registered suppliers: " . implode(', ', $registered) . "\n";

    // Test 3: Make Digiflazz service
    echo "\n3. Instantiating DigiflazzSupplierService...\n";
    $service = $factory->make('digiflazz');
    echo "   ✓ Service loaded: " . get_class($service) . "\n";
    echo "   ✓ Supplier code: " . $service->getSupplierCode() . "\n";
    echo "   ✓ Is active: " . ($service->isActive() ? 'Yes' : 'No') . "\n";

    // Test 4: Check if implements interface
    echo "\n4. Checking interface implementation...\n";
    if ($service instanceof \App\Services\Contracts\SupplierServiceInterface) {
        echo "   ✓ Service implements SupplierServiceInterface\n";
    } else {
        echo "   ✗ Service does NOT implement SupplierServiceInterface\n";
    }

    // Test 5: Test connection (if configured)
    echo "\n5. Testing connection to supplier...\n";
    try {
        $result = $service->testConnection();
        if ($result['success']) {
            echo "   ✓ Connection successful\n";
            if (isset($result['data']['formatted_balance'])) {
                echo "   ✓ Balance: " . $result['data']['formatted_balance'] . "\n";
            }
        } else {
            echo "   ⚠ Connection failed: " . $result['message'] . "\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠ Connection test error: " . $e->getMessage() . "\n";
    }

    // Test 6: Test active services
    echo "\n6. Getting all active services...\n";
    $activeServices = $factory->getActiveServices();
    echo "   ✓ Active services count: " . count($activeServices) . "\n";
    foreach ($activeServices as $code => $svc) {
        echo "     - {$code}: " . get_class($svc) . "\n";
    }

    echo "\n=== All Tests Completed Successfully! ===\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
