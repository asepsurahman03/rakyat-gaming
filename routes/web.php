<?php

use App\Http\Controllers\DigiflazzController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

// API Search Games
Route::get('/api/search-games', function (Illuminate\Http\Request $request) {
    $query = $request->input('q', '');

    if (strlen($query) < 2) {
        return response()->json(['games' => []]);
    }

    try {
        $games = \App\Models\Game::with('category')
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhereHas('category', function($subQuery) use ($query) {
                      $subQuery->where('name', 'like', '%' . $query . '%');
                  });
            })
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get()
            ->map(function($game) {
                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'slug' => $game->slug,
                    'category' => $game->category ? $game->category->name : 'Game',
                    'image_url' => $game->image ? Storage::url($game->image) : asset('images/placeholder-game.png'),
                ];
            });

        return response()->json(['games' => $games, 'count' => $games->count()]);
    } catch (\Exception $e) {
        return response()->json(['games' => [], 'error' => $e->getMessage()], 500);
    }
});

Volt::route('/', 'guest.welcome')->name('home');
Volt::route('cek-transaksi', 'guest.cek-transaksi')->name('cek-transaksi');
Volt::route('leaderboard', 'guest.leaderboard')->name('leaderboard');
Volt::route('checkout', 'guest.checkout')->name('checkout');
Volt::route('game/{slug}', 'guest.game-detail')->name('game.detail');
Volt::route('magicwhele', 'guest.calculate.magicwhele')->name('magicwhele');
Volt::route('winrate', 'guest.calculate.winrate')->name('winrate');
Volt::route('zodiak', 'guest.calculate.zodiak')->name('zodiak');
Volt::route('terms-and-conditions', 'guest.terms-and-conditions')->name('terms-and-conditions');
Volt::route('refund-policy', 'guest.refund-policy')->name('refund-policy');
Volt::route('faq', 'guest.faq')->name('faq');

// Guest Posts/Articles
Volt::route('artikel', 'guest.posts.index')->name('guest.posts.index');
Volt::route('artikel/{post:slug}', 'guest.posts.show')->name('guest.posts.show');

// Webhooks & Callbacks (No auth required, IP restricted)
// Dynamic webhook handler for all suppliers
Route::post('/webhook/{supplierCode}', [WebhookController::class, 'handle'])
    ->middleware('webhook.ip:suppliers')
    ->name('webhook.handle');

// Legacy Digiflazz webhook (kept for backward compatibility)
Route::post('/webhook/digiflazz', [DigiflazzController::class, 'handle'])
    ->middleware('webhook.ip:digiflazz')
    ->name('webhook.digiflazz');

Route::post('/payment/callback', [PaymentController::class, 'callback'])
    ->middleware('webhook.ip:ipaymu')
    ->name('payment.callback');

Route::get('/payment/return', [PaymentController::class, 'return'])
    ->name('payment.return');

Volt::route('/payment/detail', 'guest.payment-detail')
    ->name('payment.detail');

// User Dashboard Routes (Only for authenticated users with role 'user')
Route::middleware(['auth', 'user'])->group(function () {
    Volt::route('/user/dashboard', 'user.dashboard')->name('user.dashboard');
    Volt::route('/user/transactions', 'user.transactions')->name('user.transactions');
    Volt::route('/user/profile', 'user.profile')->name('user.profile');
});

// Admin Dashboard Routes (Only for authenticated users with role 'admin')
Route::middleware(['auth', 'admin'])->group(function () {
    Volt::route('/admin/dashboard', 'admin.dashboard')->name('dashboard');
    Volt::route('/admin/profile', 'admin.profile')->name('admin.profile');

    // Category Management
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories.index');
    Volt::route('admin/categories/create', 'admin.categories.create')->name('admin.categories.create');
    Volt::route('admin/categories/{category}/edit', 'admin.categories.edit')->name('admin.categories.edit');

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

    // Posts/Articles Management
    Volt::route('admin/posts', 'admin.posts.index')->name('admin.posts.index');
    Volt::route('admin/posts/create', 'admin.posts.create')->name('admin.posts.create');
    Volt::route('admin/posts/{post}/edit', 'admin.posts.edit')->name('admin.posts.edit');

    // FlazzSell Management
    Volt::route('admin/flazz-sells', 'admin.flazz-sells.index')->name('admin.flazz-sells.index');
    Volt::route('admin/flazz-sells/create', 'admin.flazz-sells.create')->name('admin.flazz-sells.create');
    Volt::route('admin/flazz-sells/{flazzSell}/edit', 'admin.flazz-sells.edit')->name('admin.flazz-sells.edit');

    // Carousel Management
    Volt::route('carousel', 'carousel.index')->name('carousel.index');
    Volt::route('carousel/create', 'carousel.create')->name('carousel.create');
    Volt::route('carousel/{carousel}/edit', 'carousel.edit')->name('carousel.edit');

    // Live Chat Management
    Volt::route('admin/chat', 'admin.chat.index')->name('admin.chat.index');

    // Settings
    Volt::route('admin/settings/application', 'admin.settings.application')->name('admin.settings.application');

    // Supplier Settings (Multi-Supplier Support)
    Volt::route('admin/settings/suppliers/digiflazz', 'admin.settings.suppliers.digiflazz')->name('admin.settings.suppliers.digiflazz');
    Volt::route('admin/settings/suppliers/onoystore', 'admin.settings.suppliers.onoystore')->name('admin.settings.suppliers.onoystore');
    Volt::route('admin/settings/suppliers/byxstore', 'admin.settings.suppliers.byxstore')->name('admin.settings.suppliers.byxstore');

    Volt::route('admin/settings/payment-gateways', 'admin.settings.payment-gateways')->name('admin.settings.payment-gateways');
});

// Shared Auth Routes (Both admin and user can access)
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('profile', 'settings.profile')->name('profile');
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
});

