<?php

use App\Services\Suppliers\ByxStoreSupplierService;
use App\Models\Supplier;

test('formats data player with multiple fields', function () {
    // Create a partial mock that only mocks the constructor dependencies
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    // Bypass constructor initialization
    $service->shouldAllowMockingProtectedMethods();

    $result = $service->formatDataPlayer([
        'player_id' => '123456',
        'zone_id' => '7890'
    ]);

    expect($result)->toBe('123456|7890');
});

test('formats data player ignoring empty values', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '123456',
        'zone_id' => '',
        'server' => 'asia'
    ]);

    expect($result)->toBe('123456|asia');
});

test('formats data player with single field', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '123456'
    ]);

    expect($result)->toBe('123456');
});

test('returns empty string when all values are empty', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '',
        'zone_id' => ''
    ]);

    expect($result)->toBe('');
});

test('ignores non-scalar values', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '123456',
        'metadata' => ['key' => 'value'], // array, should be ignored
        'zone_id' => '7890'
    ]);

    expect($result)->toBe('123456|7890');
});

test('formats data player with null values', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '123456',
        'zone_id' => null,
        'server' => 'asia'
    ]);

    expect($result)->toBe('123456|asia');
});

test('formats data player with zero values', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'player_id' => '123456',
        'zone_id' => 0,
        'server' => 'asia'
    ]);

    // Note: 0 is considered empty by PHP's empty() function
    expect($result)->toBe('123456|asia');
});

test('formats data player preserves order', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    $result = $service->formatDataPlayer([
        'zone_id' => '7890',
        'player_id' => '123456',
    ]);

    // Should maintain the order of the array
    expect($result)->toBe('7890|123456');
});

test('display format data player results - demo', function () {
    $service = Mockery::mock(ByxStoreSupplierService::class)->makePartial();

    echo "\n\n=== DEMO HASIL FORMAT DATA PLAYER ===\n";

    // Test case 1: Multiple fields
    $data1 = ['player_id' => '123456', 'zone_id' => '7890'];
    $result1 = $service->formatDataPlayer($data1);
    echo "\nInput: " . json_encode($data1);
    echo "\nOutput: " . $result1 . "\n";

    // Test case 2: With empty values
    $data2 = ['player_id' => '123456', 'zone_id' => '', 'server' => 'asia'];
    $result2 = $service->formatDataPlayer($data2);
    echo "\nInput: " . json_encode($data2);
    echo "\nOutput: " . $result2 . "\n";

    // Test case 3: Mobile Legends example
    $data3 = ['user_id' => '987654321', 'zone_id' => '12345'];
    $result3 = $service->formatDataPlayer($data3);
    echo "\nInput: " . json_encode($data3);
    echo "\nOutput: " . $result3 . "\n";

    // Test case 4: Free Fire example
    $data4 = ['player_id' => '1234567890'];
    $result4 = $service->formatDataPlayer($data4);
    echo "\nInput: " . json_encode($data4);
    echo "\nOutput: " . $result4 . "\n";

    // Test case 5: With extra fields
    $data5 = ['player_id' => '111', 'server' => 'SEA', 'role' => 'tank'];
    $result5 = $service->formatDataPlayer($data5);
    echo "\nInput: " . json_encode($data5);
    echo "\nOutput: " . $result5 . "\n";

    echo "\n======================================\n\n";

    expect(true)->toBeTrue();
});
