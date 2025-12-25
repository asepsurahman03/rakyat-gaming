<?php

namespace App\Console\Commands;

use App\Services\SupplierServiceFactory;
use Illuminate\Console\Command;

class SupplierBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:balance
                            {code : The supplier code (digiflazz, onoystore, byxstore)}
                            {--all : Check balance for all active suppliers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check balance/deposit from supplier API';

    /**
     * Execute the console command.
     */
    public function handle(SupplierServiceFactory $factory): int
    {
        if ($this->option('all')) {
            return $this->checkAllSuppliers($factory);
        }

        $supplierCode = $this->argument('code');

        // Validate supplier exists
        if (!$factory->has($supplierCode)) {
            $this->error("Supplier '{$supplierCode}' not found or not registered.");
            $this->info("Available suppliers: " . implode(', ', array_keys($factory->getRegistered())));
            return self::FAILURE;
        }

        return $this->checkSupplierBalance($factory, $supplierCode);
    }

    /**
     * Check balance for a specific supplier
     */
    private function checkSupplierBalance(SupplierServiceFactory $factory, string $supplierCode): int
    {
        try {
            $service = $factory->make($supplierCode);

            $this->info("Checking balance for {$supplierCode}...");
            $this->newLine();

            // Check if supplier is active
            if (!$service->isActive()) {
                $this->error("✗ Supplier is not active or not properly configured.");
                return self::FAILURE;
            }

            // Get balance
            $balance = $service->getBalance();

            if ($balance !== null) {
                $this->info("✓ Balance retrieved successfully!");
                $this->newLine();

                // Display balance
                $formattedBalance = 'Rp ' . number_format($balance, 0, ',', '.');

                $this->table(
                    ['Supplier', 'Balance', 'Status'],
                    [[
                        strtoupper($supplierCode),
                        $formattedBalance,
                        $balance > 0 ? '✓ Available' : '⚠ Low/Empty'
                    ]]
                );

                // Warning if balance is low
                if ($balance < 100000) {
                    $this->newLine();
                    $this->warn("⚠ Warning: Balance is below Rp 100,000");
                    $this->line("  Consider topping up to avoid service interruption.");
                }

                return self::SUCCESS;
            } else {
                $this->error("✗ Failed to retrieve balance");
                $this->line("  The supplier may not support balance checking or API call failed.");
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("✗ Exception occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Check balance for all active suppliers
     */
    private function checkAllSuppliers(SupplierServiceFactory $factory): int
    {
        $this->info("Checking balance for all active suppliers...");
        $this->newLine();

        $activeServices = $factory->getActiveServices();

        if (empty($activeServices)) {
            $this->warn("No active suppliers found.");
            return self::FAILURE;
        }

        $balances = [];
        $totalBalance = 0;
        $failedCount = 0;

        foreach ($activeServices as $code => $service) {
            try {
                $balance = $service->getBalance();

                if ($balance !== null) {
                    $formattedBalance = 'Rp ' . number_format($balance, 0, ',', '.');
                    $status = $balance > 0 ? '✓ Available' : '⚠ Low/Empty';

                    $balances[] = [
                        strtoupper($code),
                        $formattedBalance,
                        $status
                    ];

                    $totalBalance += $balance;
                } else {
                    $balances[] = [
                        strtoupper($code),
                        '-',
                        '✗ Failed'
                    ];
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $balances[] = [
                    strtoupper($code),
                    '-',
                    '✗ Error'
                ];
                $failedCount++;
            }
        }

        // Display results
        $this->table(
            ['Supplier', 'Balance', 'Status'],
            $balances
        );

        // Show total
        if ($totalBalance > 0) {
            $this->newLine();
            $this->info("Total Balance: Rp " . number_format($totalBalance, 0, ',', '.'));
        }

        // Show warnings
        $lowBalance = array_filter($balances, function($item) {
            return strpos($item[2], 'Low') !== false;
        });

        if (!empty($lowBalance)) {
            $this->newLine();
            $this->warn("⚠ Some suppliers have low or empty balance!");
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
