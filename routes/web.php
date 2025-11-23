<?php

use App\Http\Controllers\DigiflazzController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// Language Switcher
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session()->put('locale', $locale);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('lang.switch');

Volt::route('/', 'guest.welcome')->name('home');
Volt::route('cek-transaksi', 'guest.cek-transaksi')->name('cek-transaksi');
Volt::route('game/{slug}', 'guest.game-detail')->name('game.detail');
Volt::route('checkout', 'guest.checkout')->name('checkout');

Volt::route('dashboard', 'admin.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Webhooks & Callbacks
Route::post('/webhook/digiflazz', [DigiflazzController::class, 'handle'])
    ->name('webhook.digiflazz');

Route::post('/payment/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback');

Route::get('/payment/return', [PaymentController::class, 'return'])
    ->name('payment.return');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Carousel Management
    Volt::route('carousel', 'carousel.index')->name('carousel.index');
    Volt::route('carousel/create', 'carousel.create')->name('carousel.create');
    Volt::route('carousel/{carousel}/edit', 'carousel.edit')->name('carousel.edit');

    // Game Management
    Volt::route('admin/games', 'admin.games.index')->name('admin.games.index');
    Volt::route('admin/games/create', 'admin.games.create')->name('admin.games.create');
    Volt::route('admin/games/{game}/edit', 'admin.games.edit')->name('admin.games.edit');

    // Product Management
    Volt::route('admin/products', 'admin.products.index')->name('admin.products.index');
    Volt::route('admin/products/create', 'admin.products.create')->name('admin.products.create');
    Volt::route('admin/products/{product}/edit', 'admin.products.edit')->name('admin.products.edit');

    // Order Management
    Volt::route('admin/orders', 'admin.orders.index')->name('admin.orders.index');
    Volt::route('admin/orders/{order}', 'admin.orders.show')->name('admin.orders.show');

    // Supplier Products
    Volt::route('admin/supplier-products', 'admin.supplier-products.index')->name('admin.supplier-products.index');

    // User Management
    Volt::route('admin/users', 'admin.users.index')->name('admin.users.index');
    Volt::route('admin/users/create', 'admin.users.create')->name('admin.users.create');
    Volt::route('admin/users/{user}/edit', 'admin.users.edit')->name('admin.users.edit');

    // Settings
    Volt::route('admin/settings/application', 'admin.settings.application')->name('admin.settings.application');
    Volt::route('admin/settings/suppliers', 'admin.settings.suppliers')->name('admin.settings.suppliers');
    Volt::route('admin/settings/payment-gateways', 'admin.settings.payment-gateways')->name('admin.settings.payment-gateways');
});
