<?php

namespace App\Console\Commands;

use App\Services\SupplierServiceFactory;
use Illuminate\Console\Command;

class SupplierSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:sync
                            {code : The supplier code (digiflazz, onoystore, byxstore)}
                            {--all : Sync products from all active suppliers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from supplier API to local database';

    /**
     * Execute the console command.
     */
    public function handle(SupplierServiceFactory $factory): int
    {
        if ($this->option('all')) {
            return $this->syncAllSuppliers($factory);
        }

        $supplierCode = $this->argument('code');

        // Validate supplier exists
        if (!$factory->has($supplierCode)) {
            $this->error("Supplier '{$supplierCode}' not found or not registered.");
            $this->info("Available suppliers: " . implode(', ', array_keys($factory->getRegistered())));
            return self::FAILURE;
        }

        return $this->syncSupplier($factory, $supplierCode);
    }

    /**
     * Sync products from a specific supplier
     */
    private function syncSupplier(SupplierServiceFactory $factory, string $supplierCode): int
    {
        try {
            $service = $factory->make($supplierCode);

            // Check if supplier is active
            if (!$service->isActive()) {
                $this->error("Supplier '{$supplierCode}' is not active or not properly configured.");
                return self::FAILURE;
            }

            $this->info("Starting product sync from {$supplierCode}...");
            $this->newLine();

            // Show progress
            $this->withProgressBar(1, function () use ($service, $supplierCode) {
                $result = $service->syncProducts();

                if ($result['success']) {
                    $this->newLine(2);
                    $this->info("✓ Successfully synced {$result['synced_count']} products from {$supplierCode}");

                    if (isset($result['new_count']) && $result['new_count'] > 0) {
                        $this->line("  • New products: {$result['new_count']}");
                    }

                    if (isset($result['updated_count']) && $result['updated_count'] > 0) {
                        $this->line("  • Updated products: {$result['updated_count']}");
                    }
                } else {
                    $this->newLine(2);
                    $this->error("✗ Failed to sync products from {$supplierCode}");
                    $this->line("  Error: " . ($result['message'] ?? 'Unknown error'));
                }
            });

            $this->newLine();
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Exception occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Sync products from all active suppliers
     */
    private function syncAllSuppliers(SupplierServiceFactory $factory): int
    {
        $this->info("Syncing products from all active suppliers...");
        $this->newLine();

        $activeServices = $factory->getActiveServices();

        if (empty($activeServices)) {
            $this->warn("No active suppliers found.");
            return self::FAILURE;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($activeServices as $code => $service) {
            $this->info("→ Syncing from {$code}...");

            try {
                $result = $service->syncProducts();

                if ($result['success']) {
                    $this->info("  ✓ Synced {$result['synced_count']} products");
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

        $this->newLine();
        $this->info("Summary:");
        $this->line("  • Successful: {$successCount}");
        $this->line("  • Failed: {$failCount}");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
