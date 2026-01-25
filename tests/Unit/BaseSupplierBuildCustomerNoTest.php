<?php

use App\Services\Suppliers\BaseSupplierService;
use App\Models\Order;
use App\Models\SupplierProduct;

// Create a test stub for BaseSupplierService since it's abstract
class TestSupplierService extends BaseSupplierService
{
    public function __construct()
    {
        // Override constructor to avoid database calls
    }

    public function getSupplierCode(): string
    {
        return 'test';
    }

    public function testConnection(): array
    {
        return ['success' => true];
    }

    public function getBalance(): ?float
    {
        return 0.0;
    }

    public function syncProducts(): array
    {
        return ['success' => true];
    }

    public function processTransaction(Order $order, SupplierProduct $supplierProduct): array
    {
        return ['success' => true];
    }
}



test('builds customer number from data_player array', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '123456',
        'zone_id' => '7890'
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456.7890');
});

test('builds customer number from data_player JSON string', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = json_encode([
        'player_id' => '123456',
        'zone_id' => '7890'
    ]);

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456.7890');
});

test('builds customer number ignoring empty values', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '123456',
        'zone_id' => '',
        'server' => 'asia'
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456.asia');
});

test('builds customer number with single field', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = ['player_id' => '123456'];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456');
});

test('returns empty string when all values are empty', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '',
        'zone_id' => ''
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('');
});

test('ignores non-scalar values in data_player', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '123456',
        'metadata' => ['extra' => 'data'],
        'zone_id' => '7890'
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456.7890');
});

test('handles null values in data_player', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '123456',
        'zone_id' => null,
        'server' => 'asia'
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('123456.asia');
});

test('handles zero values in data_player', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'player_id' => '123456',
        'zone_id' => 0,
        'server' => 'asia'
    ];

    $result = $service->buildCustomerNo($order);

    // Note: 0 is considered empty by PHP's empty() function
    expect($result)->toBe('123456.asia');
});

test('preserves order of fields in data_player', function () {
    $service = new TestSupplierService();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->data_player = [
        'zone_id' => '7890',
        'player_id' => '123456',
    ];

    $result = $service->buildCustomerNo($order);

    expect($result)->toBe('7890.123456');
});




test('display build customer number results - demo', function () {
    $service = new TestSupplierService();

    echo "\n\n=== DEMO HASIL BUILD CUSTOMER NUMBER ===\n";

    // Test case 1: Mobile Legends
    $order1 = Mockery::mock(Order::class)->makePartial();
    $data1 = ['user_id' => '123456789', 'zone_id' => '12345'];
    $order1->data_player = $data1;
    $result1 = $service->buildCustomerNo($order1);
    echo "\nMobile Legends:";
    echo "\nInput: " . json_encode($data1);
    echo "\nOutput: " . $result1 . "\n";

    // Test case 2: Free Fire
    $order2 = Mockery::mock(Order::class)->makePartial();
    $data2 = ['player_id' => '1234567890'];
    $order2->data_player = $data2;
    $result2 = $service->buildCustomerNo($order2);
    echo "\nFree Fire:";
    echo "\nInput: " . json_encode($data2);
    echo "\nOutput: " . $result2 . "\n";

    // Test case 3: From JSON string
    $order3 = Mockery::mock(Order::class)->makePartial();
    $jsonData = json_encode(['player_id' => '999888', 'server' => 'SEA']);
    $order3->data_player = $jsonData;
    $result3 = $service->buildCustomerNo($order3);
    echo "\nFrom JSON String:";
    echo "\nInput (JSON): " . $jsonData;
    echo "\nOutput: " . $result3 . "\n";

    // Test case 4: PUBG Mobile
    $order4 = Mockery::mock(Order::class)->makePartial();
    $data4 = ['player_id' => '5123456789'];
    $order4->data_player = $data4;
    $result4 = $service->buildCustomerNo($order4);
    echo "\nPUBG Mobile:";
    echo "\nInput: " . json_encode($data4);
    echo "\nOutput: " . $result4 . "\n";

    // Test case 5: With empty values (cleaned)
    $order5 = Mockery::mock(Order::class)->makePartial();
    $data5 = ['player_id' => '111', 'zone_id' => '', 'server' => 'asia', 'nickname' => ''];
    $order5->data_player = $data5;
    $result5 = $service->buildCustomerNo($order5);
    echo "\nWith Empty Values (cleaned):";
    echo "\nInput: " . json_encode($data5);
    echo "\nOutput: " . $result5 . "\n";

    echo "\n========================================\n\n";

    expect(true)->toBeTrue();
});
