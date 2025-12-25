<?php

namespace App\Services;

use App\Services\Contracts\SupplierServiceInterface;
use App\Services\Suppliers\DigiflazzSupplierService;
use App\Services\Suppliers\OnoyStoreSupplierService;
use App\Services\Suppliers\ByxStoreSupplierService;
use Illuminate\Support\Facades\Log;

class SupplierServiceFactory
{
    /**
     * Registry of available supplier services
     * Map supplier code to service class
     *
     * @var array<string, class-string<SupplierServiceInterface>>
     */
    protected array $registry = [
        'digiflazz' => DigiflazzSupplierService::class,
        'onoystore' => OnoyStoreSupplierService::class,
        'byxstore' => ByxStoreSupplierService::class,
    ];

    /**
     * Cache for instantiated services
     *
     * @var array<string, SupplierServiceInterface>
     */
    protected array $instances = [];

    /**
     * Make (resolve) a supplier service by code
     *
     * @param string $supplierCode
     * @return SupplierServiceInterface
     * @throws \InvalidArgumentException
     */
    public function make(string $supplierCode): SupplierServiceInterface
    {
        // Return cached instance if exists
        if (isset($this->instances[$supplierCode])) {
            return $this->instances[$supplierCode];
        }

        // Check if supplier is registered
        if (!isset($this->registry[$supplierCode])) {
            Log::error("Supplier service not found in registry", [
                'supplier_code' => $supplierCode,
                'available_suppliers' => array_keys($this->registry),
            ]);

            throw new \InvalidArgumentException(
                "Supplier service not found for code: {$supplierCode}. " .
                "Available suppliers: " . implode(', ', array_keys($this->registry))
            );
        }

        // Get service class from registry
        $serviceClass = $this->registry[$supplierCode];

        // Instantiate and cache
        $this->instances[$supplierCode] = app($serviceClass);

        Log::info("Supplier service instantiated", [
            'supplier_code' => $supplierCode,
            'service_class' => $serviceClass,
        ]);

        return $this->instances[$supplierCode];
    }

    /**
     * Register a new supplier service
     *
     * @param string $supplierCode
     * @param class-string<SupplierServiceInterface> $serviceClass
     * @return void
     */
    public function register(string $supplierCode, string $serviceClass): void
    {
        if (!is_subclass_of($serviceClass, SupplierServiceInterface::class)) {
            throw new \InvalidArgumentException(
                "Service class must implement SupplierServiceInterface: {$serviceClass}"
            );
        }

        $this->registry[$supplierCode] = $serviceClass;

        Log::info("Supplier service registered", [
            'supplier_code' => $supplierCode,
            'service_class' => $serviceClass,
        ]);
    }

    /**
     * Check if a supplier service is registered
     *
     * @param string $supplierCode
     * @return bool
     */
    public function has(string $supplierCode): bool
    {
        return isset($this->registry[$supplierCode]);
    }

    /**
     * Get all registered supplier codes
     *
     * @return array<string>
     */
    public function getRegistered(): array
    {
        return array_keys($this->registry);
    }

    /**
     * Clear cached instances
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->instances = [];
        Log::debug("Supplier service cache cleared");
    }

    /**
     * Get all active supplier services
     * Returns only suppliers that are active in database
     *
     * @return array<string, SupplierServiceInterface>
     */
    public function getActiveServices(): array
    {
        $activeServices = [];
        $suppliers = \App\Models\Supplier::where('is_active', true)->get();

        foreach ($suppliers as $supplier) {
            if ($this->has($supplier->code)) {
                try {
                    $service = $this->make($supplier->code);
                    if ($service->isActive()) {
                        $activeServices[$supplier->code] = $service;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to instantiate supplier service", [
                        'supplier_code' => $supplier->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $activeServices;
    }
}
