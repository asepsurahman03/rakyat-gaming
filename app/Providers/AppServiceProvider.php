<?php

namespace App\Providers;

use App\Listeners\SendWelcomeNotification;
use App\Services\SupplierServiceFactory;
use App\Services\Suppliers\DigiflazzSupplierService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SupplierServiceFactory as singleton
        $this->app->singleton(SupplierServiceFactory::class, function ($app) {
            return new SupplierServiceFactory();
        });

        // Register individual supplier services
        $this->app->singleton(DigiflazzSupplierService::class, function ($app) {
            return new DigiflazzSupplierService();
        });

        // Future supplier services will be registered here:
        // $this->app->singleton(OnoyStoreSupplierService::class, function ($app) {
        //     return new OnoyStoreSupplierService();
        // });
        // $this->app->singleton(ByxStoreSupplierService::class, function ($app) {
        //     return new ByxStoreSupplierService();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            Registered::class,
            SendWelcomeNotification::class,
        );
    }
}
