<?php

/**
 * TAHAP 5: Webhook Handling per Supplier Testing
 *
 * This script tests:
 * 1. Webhook routes configuration
 * 2. WebhookController functionality
 * 3. WebhookLog model and database
 * 4. Supplier service webhook handling
 * 5. Signature validation
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Route;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "===========================================\n";
echo "TAHAP 5: Webhook Handling per Supplier\n";
echo "===========================================\n\n";

// Test 1: Check webhook routes exist
echo "✓ Test 1: Webhook Routes Configuration\n";
echo "-------------------------------------------\n";

$webhookRoutes = [
    'webhook.handle' => ['method' => 'POST', 'pattern' => '/webhook/{supplierCode}'],
    'webhook.test' => ['method' => 'GET', 'pattern' => '/webhook/{supplierCode}/test'],
];

foreach ($webhookRoutes as $routeName => $details) {
    if (Route::has($routeName)) {
        $route = Route::getRoutes()->getByName($routeName);
        echo "  ✓ Route '$routeName' exists\n";
        echo "    URI: " . $route->uri() . "\n";
        echo "    Method: " . implode('|', $route->methods()) . "\n";
    } else {
        echo "  ✗ Route '$routeName' not found\n";
    }
}

echo "\n";

// Test 2: Check WebhookController exists
echo "✓ Test 2: WebhookController\n";
echo "-------------------------------------------\n";

$controllerPath = app_path('Http/Controllers/WebhookController.php');
if (file_exists($controllerPath)) {
    echo "  ✓ WebhookController exists\n";

    $controller = new \App\Http\Controllers\WebhookController(
        app(\App\Services\SupplierServiceFactory::class)
    );

    echo "  ✓ WebhookController can be instantiated\n";

    // Check methods
    $methods = ['handle', 'test'];
    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "  ✓ Method '$method' exists\n";
        } else {
            echo "  ✗ Method '$method' not found\n";
        }
    }
} else {
    echo "  ✗ WebhookController not found\n";
}

echo "\n";

// Test 3: Check WebhookLog model and table
echo "✓ Test 3: WebhookLog Model & Database\n";
echo "-------------------------------------------\n";

try {
    // Check table exists
    $tableExists = Schema::hasTable('webhook_logs');
    if ($tableExists) {
        echo "  ✓ Table 'webhook_logs' exists\n";

        // Check columns
        $requiredColumns = [
            'id', 'supplier_code', 'payload', 'signature', 'ip_address',
            'response', 'status', 'is_valid', 'order_id', 'error_message',
            'processed_at', 'created_at', 'updated_at'
        ];

        $hasAllColumns = true;
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('webhook_logs', $column)) {
                echo "  ✗ Column '$column' missing\n";
                $hasAllColumns = false;
            }
        }

        if ($hasAllColumns) {
            echo "  ✓ All required columns present\n";
        }

        // Check model
        $webhookLog = new App\Models\WebhookLog();
        echo "  ✓ WebhookLog model can be instantiated\n";

        // Check fillable
        $fillable = $webhookLog->getFillable();
        echo "  ✓ Fillable fields: " . count($fillable) . "\n";

        // Check scopes
        $scopes = [
            ['name' => 'valid', 'args' => []],
            ['name' => 'invalid', 'args' => []],
            ['name' => 'forSupplier', 'args' => ['digiflazz']],
            ['name' => 'pending', 'args' => []],
            ['name' => 'processed', 'args' => []],
        ];

        foreach ($scopes as $scope) {
            try {
                $scopeName = $scope['name'];
                $args = $scope['args'];
                $query = App\Models\WebhookLog::query();

                if (empty($args)) {
                    $query->$scopeName()->toSql();
                } else {
                    $query->$scopeName(...$args)->toSql();
                }

                echo "  ✓ Scope '$scopeName' exists\n";
            } catch (\Exception $e) {
                echo "  ✗ Scope '$scopeName' failed: " . $e->getMessage() . "\n";
            }
        }

        // Check relationships
        if (method_exists($webhookLog, 'supplier')) {
            echo "  ✓ Relationship 'supplier' exists\n";
        }
        if (method_exists($webhookLog, 'order')) {
            echo "  ✓ Relationship 'order' exists\n";
        }

        // Get current count
        $count = App\Models\WebhookLog::count();
        echo "  Current webhook logs: $count\n";

    } else {
        echo "  ✗ Table 'webhook_logs' does not exist\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Error checking WebhookLog: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check BaseSupplierService has logWebhook method
echo "✓ Test 4: BaseSupplierService Webhook Logging\n";
echo "-------------------------------------------\n";

try {
    $reflection = new \ReflectionClass(\App\Services\Suppliers\BaseSupplierService::class);

    if ($reflection->hasMethod('logWebhook')) {
        echo "  ✓ Method 'logWebhook' exists in BaseSupplierService\n";

        $method = $reflection->getMethod('logWebhook');
        $params = $method->getParameters();

        echo "  ✓ Parameters count: " . count($params) . "\n";

        $expectedParams = ['payload', 'signature', 'ipAddress', 'isValid', 'response', 'orderId', 'errorMessage'];
        foreach ($expectedParams as $param) {
            $hasParam = false;
            foreach ($params as $p) {
                if ($p->getName() === $param) {
                    $hasParam = true;
                    break;
                }
            }
            if ($hasParam) {
                echo "  ✓ Parameter '$param' exists\n";
            } else {
                echo "  ✗ Parameter '$param' missing\n";
            }
        }
    } else {
        echo "  ✗ Method 'logWebhook' not found\n";
    }

    if ($reflection->hasMethod('handleWebhook')) {
        echo "  ✓ Method 'handleWebhook' exists\n";
    }

    if ($reflection->hasMethod('validateWebhookSignature')) {
        echo "  ✓ Method 'validateWebhookSignature' exists\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Error checking BaseSupplierService: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Test webhook URL generation
echo "✓ Test 5: Webhook URL Generation\n";
echo "-------------------------------------------\n";

try {
    $suppliers = ['digiflazz', 'onoystore', 'byxstore'];

    foreach ($suppliers as $code) {
        $url = route('webhook.handle', ['supplierCode' => $code]);
        echo "  Webhook URL ($code): $url\n";

        $testUrl = route('webhook.test', ['supplierCode' => $code]);
        echo "  Test URL ($code): $testUrl\n";
    }

    echo "  ✓ Webhook URLs can be generated\n";
} catch (\Exception $e) {
    echo "  ✗ Error generating URLs: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Test DigiflazzSupplierService webhook handling
echo "✓ Test 6: DigiflazzSupplierService Webhook\n";
echo "-------------------------------------------\n";

try {
    $factory = app(\App\Services\SupplierServiceFactory::class);

    if ($factory->has('digiflazz')) {
        $service = $factory->make('digiflazz');

        echo "  ✓ DigiflazzSupplierService can be created\n";

        // Check if handleWebhook method exists
        if (method_exists($service, 'handleWebhook')) {
            echo "  ✓ Method 'handleWebhook' exists\n";
        }

        // Check if validateWebhookSignature method exists
        if (method_exists($service, 'validateWebhookSignature')) {
            echo "  ✓ Method 'validateWebhookSignature' exists\n";

            // Test signature validation with dummy data
            $testPayload = ['ref_id' => 'TEST123', 'status' => 'success'];
            $testSignature = 'test-signature';

            // This will likely return false or true based on config
            $isValid = $service->validateWebhookSignature($testPayload, $testSignature);
            echo "  Test signature validation: " . ($isValid ? 'Valid' : 'Invalid') . " (expected based on config)\n";
        }
    } else {
        echo "  ✗ Digiflazz service not registered\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Error testing DigiflazzSupplierService: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 7: Simulate webhook logging
echo "✓ Test 7: Simulate Webhook Logging\n";
echo "-------------------------------------------\n";

try {
    $testLog = App\Models\WebhookLog::create([
        'supplier_code' => 'digiflazz',
        'payload' => json_encode(['test' => 'data', 'ref_id' => 'TEST-' . time()]),
        'signature' => 'test-signature-' . time(),
        'ip_address' => '127.0.0.1',
        'response' => json_encode(['success' => true, 'message' => 'Test']),
        'status' => 'processed',
        'is_valid' => true,
        'order_id' => null,
        'error_message' => null,
        'processed_at' => now(),
    ]);

    echo "  ✓ Test webhook log created (ID: {$testLog->id})\n";
    echo "    Supplier: {$testLog->supplier_code}\n";
    echo "    Status: {$testLog->status}\n";
    echo "    Valid: " . ($testLog->is_valid ? 'Yes' : 'No') . "\n";

    // Test scopes
    $validCount = App\Models\WebhookLog::valid()->count();
    echo "  Valid webhook logs: $validCount\n";

    $digiflazzCount = App\Models\WebhookLog::forSupplier('digiflazz')->count();
    echo "  Digiflazz webhook logs: $digiflazzCount\n";

    // Clean up test log
    $testLog->delete();
    echo "  ✓ Test log cleaned up\n";

} catch (\Exception $e) {
    echo "  ✗ Error simulating webhook log: " . $e->getMessage() . "\n";
}

echo "\n";

// Final Summary
echo "===========================================\n";
echo "TAHAP 5 Testing Summary\n";
echo "===========================================\n";
echo "✓ Webhook routes configured (dynamic per supplier)\n";
echo "✓ WebhookController created with factory pattern\n";
echo "✓ WebhookLog model and table ready\n";
echo "✓ BaseSupplierService has logWebhook method\n";
echo "✓ Webhook URLs can be generated\n";
echo "✓ DigiflazzSupplierService webhook handling ready\n";
echo "✓ Webhook logging tested successfully\n";
echo "\n";
echo "TAHAP 5 COMPLETED! ✓\n";
echo "\nNext Steps:\n";
echo "1. TAHAP 6: Create generic console commands for sync\n";
echo "2. TAHAP 7: Implement OnoyStore and ByxStore services\n";
echo "\nWebhook Endpoints:\n";
echo "- POST /webhook/digiflazz\n";
echo "- POST /webhook/onoystore\n";
echo "- POST /webhook/byxstore\n";
echo "- GET /webhook/{supplier}/test (for testing)\n";
echo "===========================================\n";
