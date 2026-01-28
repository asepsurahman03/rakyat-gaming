<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;
use App\Services\SupplierServiceFactory;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Services\Suppliers\DigiflazzSupplierService;

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

        RateLimiter::for('web', function (Request $request) {

            $ua = strtolower($request->userAgent());

            if (
                str_contains($ua, 'googlebot') ||
                str_contains($ua, 'bingbot') ||
                str_contains($request->path(), 'livewire')
            ) {
                return Limit::none();
            }

            return Limit::perMinute(100)->by($request->ip());
        });
    }
}
