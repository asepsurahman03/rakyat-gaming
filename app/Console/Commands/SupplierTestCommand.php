<?php

namespace App\Console\Commands;

use App\Services\SupplierServiceFactory;
use Illuminate\Console\Command;

class SupplierTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:test
                            {code : The supplier code (digiflazz, onoystore, byxstore)}
                            {--all : Test connection to all active suppliers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to supplier API';

    /**
     * Execute the console command.
     */
    public function handle(SupplierServiceFactory $factory): int
    {
        if ($this->option('all')) {
            return $this->testAllSuppliers($factory);
        }

        $supplierCode = $this->argument('code');

        // Validate supplier exists
        if (!$factory->has($supplierCode)) {
            $this->error("Supplier '{$supplierCode}' not found or not registered.");
            $this->info("Available suppliers: " . implode(', ', array_keys($factory->getRegistered())));
            return self::FAILURE;
        }

        return $this->testSupplier($factory, $supplierCode);
    }

    /**
     * Test connection to a specific supplier
     */
    private function testSupplier(SupplierServiceFactory $factory, string $supplierCode): int
    {
        try {
            $service = $factory->make($supplierCode);

            $this->info("Testing connection to {$supplierCode}...");
            $this->newLine();

            // Check if supplier is active
            if (!$service->isActive()) {
                $this->error("✗ Supplier is not active or not properly configured.");
                $this->line("  Please check:");
                $this->line("  • API Key is set");
                $this->line("  • API URL is set");
                $this->line("  • Supplier is marked as active in database");
                return self::FAILURE;
            }

            // Test connection
            $result = $service->testConnection();

            if ($result['success']) {
                $this->info("✓ Connection successful!");
                $this->newLine();

                if (isset($result['message'])) {
                    $this->line("  Message: {$result['message']}");
                }

                // Show additional data if available
                if (isset($result['data'])) {
                    $this->line("  Data:");
                    foreach ($result['data'] as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $this->line("    • {$key}: {$value}");
                    }
                }

                return self::SUCCESS;
            } else {
                $this->error("✗ Connection failed!");
                $this->line("  Error: " . ($result['message'] ?? 'Unknown error'));

                if (isset($result['status_code'])) {
                    $this->line("  Status Code: {$result['status_code']}");
                }

                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("✗ Exception occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Test connection to all suppliers
     */
    private function testAllSuppliers(SupplierServiceFactory $factory): int
    {
        $this->info("Testing connection to all suppliers...");
        $this->newLine();

        $registered = $factory->getRegistered();

        if (empty($registered)) {
            $this->warn("No suppliers registered.");
            return self::FAILURE;
        }

        $successCount = 0;
        $failCount = 0;
        $inactiveCount = 0;

        foreach ($registered as $code => $serviceClass) {
            try {
                $service = $factory->make($code);

                $this->line("→ Testing {$code}...");

                if (!$service->isActive()) {
                    $this->warn("  ⊘ Inactive or not configured");
                    $inactiveCount++;
                    continue;
                }

                $result = $service->testConnection();

                if ($result['success']) {
                    $this->info("  ✓ Connected");
                    if (isset($result['data']['balance'])) {
                        $balance = $result['data']['formatted_balance'] ?? $result['data']['balance'];
                        $this->line("    Balance: {$balance}");
                    }
                    $successCount++;
                } else {
                    $this->error("  ✗ Failed: " . ($result['message'] ?? 'Unknown error'));
                    $failCount++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $failCount++;
            }

            $this->newLine();
        }

        $this->info("Summary:");
        $this->line("  • Successful: {$successCount}");
        $this->line("  • Failed: {$failCount}");
        $this->line("  • Inactive: {$inactiveCount}");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
