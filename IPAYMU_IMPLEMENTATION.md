# Implementasi Payment Methods Ipaymu

## Fitur yang Diimplementasikan

### 1. Caching Payment Methods ✅
- **Cache Duration**: 15 menit (configurable)
- **Cache Key**: Berdasarkan amount untuk diferensiasi
- **Benefit**: Mengurangi API calls ke Ipaymu, response time lebih cepat

### 2. Filtering Payment Methods ✅
- **Exclude Channels**: Filter untuk mengecualikan channel tertentu (misal: COD)
- **Min/Max Amount**: Filter berdasarkan batas minimum/maksimum payment method
- **Amount Compatibility**: Otomatis filter channel yang tidak support amount tertentu

### 3. Real-time dari API ✅
- Tetap menggunakan API Ipaymu sebagai sumber data utama
- Cache hanya untuk optimasi, bukan replacement
- Data selalu fresh setelah cache expired

## Files yang Diupdate

### 1. Service Layer
- **File**: `app/Services/IpaymuService.php`
- **Methods Baru**:
  - `getPaymentMethods($amount, $filters, $useCache, $cacheTtl)` - Get dengan cache & filter
  - `getPaymentMethodsFresh($amount, $filters)` - Force fresh API call
  - `clearPaymentMethodsCache($amount)` - Clear cache
  - `filterPaymentMethods($methods, $filters, $amount)` - Filter logic

### 2. View Components
- **Files**: 
  - `resources/views/livewire/guest/game-detail.blade.php`
  - `resources/views/livewire/guest/checkout.blade.php`
  
- **Perubahan**:
  - Update `selectProduct()` untuk menggunakan caching dan filtering
  - Tambah error handling untuk payment methods
  - Update UI untuk menampilkan fee dan cache status
  - Update field mapping (`code` & `name` vs `paymentMethod` & `paymentName`)

### 3. Test Script
- **File**: `test-ipaymu.php`
- **Test Coverage**:
  - API connection test (tanpa cache)
  - Cache functionality test (dengan response time comparison)
  - Filter functionality test
  - Clear cache test

## Cara Penggunaan

### Basic Usage
```php
$ipaymuService = app(IpaymuService::class);

// Get payment methods dengan cache (default)
$result = $ipaymuService->getPaymentMethods(50000);

if ($result['success']) {
    $methods = $result['methods'];
    $isCached = $result['cached']; // true/false
    $source = $result['source'] ?? 'api'; // 'api' or 'hardcoded'
}
```

### Fallback Mechanism
Jika Ipaymu API tidak mendukung endpoint untuk list payment methods (error 405), sistem akan otomatis menggunakan hardcoded payment methods sebagai fallback:
- Virtual Account (fee: Rp 4.000)
- QRIS (fee: 0.7% dari amount)
- Bank Transfer (fee: Rp 5.000)
- Convenience Store (fee: Rp 2.500)
- COD (fee: Rp 0)

Log akan menunjukkan `source: 'hardcoded'` jika menggunakan fallback.

### With Filters
```php
// Filter dengan exclude dan range amount
$filters = [
    'exclude' => ['cod', 'alfamart'],
    'min_amount' => 10000,
    'max_amount' => 1000000
];

$result = $ipaymuService->getPaymentMethods(50000, $filters);
```

### Force Fresh API Call
```php
// Bypass cache, langsung hit API
$result = $ipaymuService->getPaymentMethodsFresh(50000);

// Atau
$result = $ipaymuService->getPaymentMethods(50000, [], false);
```

### Custom Cache TTL
```php
// Cache selama 30 menit
$result = $ipaymuService->getPaymentMethods(50000, [], true, 30);
```

### Clear Cache
```php
// Clear cache untuk amount tertentu
$ipaymuService->clearPaymentMethodsCache(50000);
```

## Response Format

```php
[
    'success' => true,
    'methods' => [
        [
            'code' => 'va',
            'name' => 'Virtual Account',
            'image' => 'https://...',
            'fee' => 4000,
            'total' => 54000,
            'minimum' => 10000,
            'maximum' => 5000000,
            'active' => true,
        ],
        // ... more methods
    ],
    'cached' => false, // true jika dari cache
    'source' => 'api', // 'api' atau 'hardcoded' (jika API tidak support)
]
```

## Testing

Jalankan test script:
```bash
php test-ipaymu.php
```

Test akan menampilkan:
1. Konfigurasi Ipaymu (VA, API Key, Sandbox)
2. Database configuration
3. API Connection test (response time)
4. Cache test (response time comparison)
5. Filter test
6. Clear cache test

## Konfigurasi

### Environment Variables (.env)
```env
IPAYMU_VA=your_va_number
IPAYMU_API_KEY=your_api_key
IPAYMU_SANDBOX=false
IPAYMU_CALLBACK_URL=${APP_URL}/payment/callback
IPAYMU_REDIRECT_URL=${APP_URL}/payment/return
```

### Database
PaymentGateway dengan code `ipaymu` harus ada di database:
```bash
php artisan db:seed --class=PaymentGatewaySeeder
```

## Benefits

1. **Performance**: Response time lebih cepat dengan caching (15 menit)
2. **Reliability**: Mengurangi dependency ke API Ipaymu untuk setiap request
3. **Flexibility**: Filter payment methods sesuai kebutuhan
4. **User Experience**: Loading lebih cepat, tampilan lebih informatif (fee, cache status)
5. **Cost Effective**: Mengurangi jumlah API calls ke Ipaymu

## Notes

- Cache akan otomatis expired setelah 15 menit
- Setiap amount memiliki cache key sendiri
- Filter diterapkan ke cached data maupun fresh data
- Error handling sudah diimplementasikan di semua layer
- UI menampilkan info jika data dari cache
- **Fallback mechanism**: Jika API Ipaymu tidak support list payment methods (error 405), sistem otomatis menggunakan hardcoded payment methods
- Hardcoded payment methods mencakup: VA, QRIS, Bank Transfer, Convenience Store, dan COD
- Fee pada hardcoded methods adalah estimasi, sesuaikan dengan rate Ipaymu aktual Anda

## Troubleshooting

### Error 405 (Method Not Allowed)
Jika mendapat error 405, ini berarti:
1. Endpoint `/payment` tidak tersedia di API Ipaymu
2. Sistem akan otomatis fallback ke hardcoded payment methods
3. Pastikan fee di `getHardcodedPaymentMethods()` sesuai dengan rate Ipaymu Anda

### Update Hardcoded Payment Methods
Edit method `getHardcodedPaymentMethods()` di [IpaymuService.php](app/Services/IpaymuService.php) untuk:
- Menambah/mengurangi payment channels
- Update fee sesuai rate terbaru
- Adjust minimum/maximum amounts
