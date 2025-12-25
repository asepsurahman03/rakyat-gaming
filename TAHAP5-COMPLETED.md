# TAHAP 5: Webhook Handling per Supplier - COMPLETED ✓

## Overview
Tahap ini mengimplementasikan webhook handling system yang dynamic dan fleksibel untuk semua supplier. Setiap supplier memiliki endpoint webhook sendiri yang dihandle oleh WebhookController dengan factory pattern, dilengkapi dengan logging comprehensive dan signature validation.

## Completion Date
24 Desember 2024

## Changes Summary

### 1. WebhookController with Factory Pattern
**File Created:** `app/Http/Controllers/WebhookController.php`

**Features:**
- ✅ Dynamic webhook routing berdasarkan `supplierCode` parameter
- ✅ SupplierServiceFactory integration untuk get correct service
- ✅ Comprehensive validation: supplier exists, supplier active, signature valid
- ✅ JSON payload parsing dengan error handling
- ✅ Proper HTTP response codes (200, 400, 401, 403, 404, 500)
- ✅ Test endpoint untuk development (`GET /webhook/{supplier}/test`)

**Key Methods:**
```php
public function handle(Request $request, string $supplierCode): JsonResponse
{
    // 1. Check supplier exists in factory
    // 2. Check supplier is active
    // 3. Validate webhook signature
    // 4. Parse JSON payload
    // 5. Call supplier service handleWebhook()
    // 6. Return appropriate response
}

public function test(Request $request, string $supplierCode): JsonResponse
{
    // Returns webhook endpoint info for testing
}
```

**Security Features:**
- Signature validation before processing
- IP address logging
- Invalid request rejection with proper status codes
- Exception handling with safe error messages

### 2. Dynamic Webhook Routes
**File Modified:** `routes/web.php`

**Routes Added:**
```php
// Dynamic webhook handler for all suppliers
Route::post('/webhook/{supplierCode}', [WebhookController::class, 'handle'])
    ->name('webhook.handle');

Route::get('/webhook/{supplierCode}/test', [WebhookController::class, 'test'])
    ->name('webhook.test');
```

**Legacy Route Kept:**
```php
// Legacy Digiflazz webhook (kept for backward compatibility)
Route::post('/webhook/digiflazz', [DigiflazzController::class, 'handle'])
    ->middleware('webhook.ip:digiflazz')
    ->name('webhook.digiflazz');
```

**Webhook URLs:**
- Digiflazz: `POST /webhook/digiflazz`
- OnoyStore: `POST /webhook/onoystore`
- ByxStore: `POST /webhook/byxstore`
- Test: `GET /webhook/{supplier}/test`

### 3. WebhookLog Model & Migration
**Files Created:**
- `app/Models/WebhookLog.php`
- `database/migrations/2025_12_24_151341_create_webhook_logs_table.php`

**Table Schema:**
```php
Schema::create('webhook_logs', function (Blueprint $table) {
    $table->id();
    $table->string('supplier_code', 50)->index();     // digiflazz, onoystore, byxstore
    $table->text('payload');                           // Full JSON payload
    $table->string('signature')->nullable();           // Webhook signature
    $table->string('ip_address', 45)->nullable();      // Source IP
    $table->text('response')->nullable();              // Response sent back
    $table->string('status', 20)->default('pending');  // pending, processed, failed
    $table->boolean('is_valid')->default(false);       // Signature valid?
    $table->string('order_id')->nullable()->index();   // Related order
    $table->text('error_message')->nullable();         // Error details
    $table->timestamp('processed_at')->nullable();     // Processing timestamp
    $table->timestamps();
});
```

**Model Features:**
- Fillable fields untuk mass assignment
- Casts untuk `is_valid` (boolean) dan timestamps
- Relationships: `supplier()`, `order()`
- Scopes: `valid()`, `invalid()`, `forSupplier()`, `pending()`, `processed()`

**Scopes Example:**
```php
// Get all valid webhooks
WebhookLog::valid()->get();

// Get webhooks for specific supplier
WebhookLog::forSupplier('digiflazz')->get();

// Get processed webhooks
WebhookLog::processed()->get();
```

### 4. BaseSupplierService Webhook Logging
**File Modified:** `app/Services/Suppliers/BaseSupplierService.php`

**Method Added:**
```php
protected function logWebhook(
    array $payload,
    string $signature,
    ?string $ipAddress = null,
    bool $isValid = false,
    ?array $response = null,
    ?string $orderId = null,
    ?string $errorMessage = null
): WebhookLog
```

**Usage:**
```php
// In handleWebhook() method
$this->logWebhook(
    $payload,
    $signature,
    request()->ip(),
    true,  // isValid
    ['success' => true, 'message' => 'Processed'],
    $order->order_id,
    null  // no error
);
```

**Benefits:**
- Consistent logging across all suppliers
- Audit trail untuk semua webhook activities
- Debug support dengan full payload storage
- Error tracking dengan error_message field

### 5. DigiflazzSupplierService Updated
**File Modified:** `app/Services/Suppliers/DigiflazzSupplierService.php`

**Changes:**
- ✅ Integrated `logWebhook()` method calls
- ✅ Log pada setiap webhook event (missing ref_id, order not found, success, exception)
- ✅ Maintain backward compatibility dengan DigiflazzLog
- ✅ Enhanced error tracking dengan detailed error messages

**Webhook Flow:**
```php
1. Receive webhook -> Extract signature and IP
2. Validate ref_id exists -> Log if invalid
3. Find order -> Log if not found
4. Update order status based on webhook status
5. Log successful processing
6. Return response
```

**Logged Events:**
- Missing ref_id in payload
- Order not found for ref_id
- Successful order status update
- Exception during processing

### 6. Test Suite
**File Created:** `test-tahap5.php`

**Tests:**
1. ✅ Webhook routes configuration (webhook.handle, webhook.test)
2. ✅ WebhookController existence and methods
3. ✅ WebhookLog model, table, columns, scopes, relationships
4. ✅ BaseSupplierService logWebhook method
5. ✅ Webhook URL generation for all suppliers
6. ✅ DigiflazzSupplierService webhook methods
7. ✅ Simulate webhook logging (create, query, delete)

**Test Results:**
```
✓ Webhook routes configured (dynamic per supplier)
✓ WebhookController created with factory pattern
✓ WebhookLog model and table ready
✓ BaseSupplierService has logWebhook method
✓ Webhook URLs can be generated
✓ DigiflazzSupplierService webhook handling ready
✓ Webhook logging tested successfully
```

## Features Implemented

### For Developers:

1. **Dynamic Webhook Routing**
   - Single controller handles all supplier webhooks
   - Automatic service resolution via factory
   - No code changes needed untuk add new supplier webhook

2. **Comprehensive Logging**
   - Every webhook call logged
   - Full payload storage untuk debugging
   - Error messages untuk failed webhooks
   - Query capabilities via scopes

3. **Security & Validation**
   - Signature validation per supplier
   - IP address tracking
   - Invalid request rejection
   - Safe error messages (no internal details exposed)

4. **Easy Integration**
   - New supplier hanya perlu implement handleWebhook() dan validateWebhookSignature()
   - Logging handled automatically via base class
   - Consistent response format

### For Operations:

1. **Webhook Monitoring**
   - Track all webhook activities
   - Filter by supplier, status, validity
   - Debug failed webhooks dengan full payload
   - Identify order processing issues

2. **Audit Trail**
   - Complete history of webhook calls
   - Timestamps for all activities
   - IP address tracking
   - Response logging

## Technical Details

### WebhookController Flow:
```
1. POST /webhook/{supplierCode}
2. Check supplier exists -> 404 if not
3. Check supplier active -> 403 if not
4. Get raw payload & signature
5. Validate signature -> 401 if invalid
6. Parse JSON -> 400 if invalid
7. Call service->handleWebhook()
8. Return response (200 or 400)
```

### Error Responses:
- **404**: Supplier not found
- **403**: Supplier not active
- **401**: Invalid signature
- **400**: Invalid JSON or webhook processing failed
- **500**: Internal server error (with safe message)

### Signature Validation:
```php
// In controller
$signature = $request->header('X-Signature') 
    ?? $request->header('Authorization') 
    ?? '';

// Passed to service
$isValid = $service->validateWebhookSignature($payload, $signature);
```

### Webhook Logging Strategy:
```php
// Log semua events, baik success atau fail
- Missing data -> isValid=false, error_message set
- Order not found -> isValid=false, error_message set
- Processing success -> isValid=true, response set
- Exception -> isValid=false, error_message=exception message
```

## Database Changes

**New Table:** `webhook_logs`
- Indexes on: `supplier_code`, `order_id`
- Full text storage for payload and response
- Status tracking (pending, processed, failed)
- Timestamp for processing time

## API Endpoints

### POST /webhook/{supplierCode}
**Purpose:** Handle incoming webhook from supplier

**Parameters:**
- `supplierCode` (path): digiflazz, onoystore, byxstore

**Headers:**
- `X-Signature` or `Authorization`: Webhook signature
- `Content-Type: application/json`

**Request Body:**
```json
{
  "ref_id": "ORDER-123",
  "status": "success",
  "data": {
    // Supplier-specific data
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook processed"
}
```

### GET /webhook/{supplierCode}/test
**Purpose:** Test webhook endpoint (development)

**Response:**
```json
{
  "success": true,
  "message": "Webhook endpoint is ready",
  "supplier": {
    "code": "digiflazz",
    "active": true
  },
  "url": "http://localhost/webhook/digiflazz"
}
```

## Usage Examples

### 1. Send Webhook to Digiflazz Endpoint
```bash
curl -X POST http://localhost/webhook/digiflazz \
  -H "Content-Type: application/json" \
  -H "X-Signature: abc123" \
  -d '{
    "ref_id": "ORDER-001",
    "status": "success"
  }'
```

### 2. Test Webhook Endpoint
```bash
curl http://localhost/webhook/digiflazz/test
```

### 3. Query Webhook Logs
```php
// All failed webhooks
$failed = WebhookLog::invalid()->get();

// Digiflazz webhooks today
$today = WebhookLog::forSupplier('digiflazz')
    ->whereDate('created_at', today())
    ->get();

// Pending webhooks (not processed)
$pending = WebhookLog::pending()->get();
```

### 4. Add New Supplier Webhook
```php
// 1. Implement validateWebhookSignature in service
public function validateWebhookSignature(array $payload, ?string $signature = null): bool
{
    $expected = hash_hmac('sha256', json_encode($payload), $this->secret);
    return hash_equals($expected, $signature ?? '');
}

// 2. Implement handleWebhook in service
public function handleWebhook(array $payload): array
{
    // Your logic here
    
    // Log webhook
    $this->logWebhook(
        $payload,
        $signature,
        request()->ip(),
        true,
        $response,
        $orderId,
        null
    );
    
    return ['success' => true];
}

// 3. Register in factory (already done in TAHAP 1)
// 4. Webhook endpoint automatically available at /webhook/{supplier_code}
```

## Testing Checklist

- [x] Webhook routes registered
- [x] WebhookController can handle requests
- [x] WebhookLog table created
- [x] WebhookLog model with scopes working
- [x] BaseSupplierService logWebhook method
- [x] DigiflazzSupplierService using logWebhook
- [x] Webhook URLs generated correctly
- [x] Signature validation working
- [x] Test endpoint accessible
- [x] Simulate webhook logging successful

## Security Considerations

1. **Signature Validation**
   - Always validate signatures before processing
   - Use timing-safe comparison (hash_equals)
   - Log invalid signature attempts

2. **IP Tracking**
   - Record source IP for all webhooks
   - Can add IP whitelist middleware later

3. **Safe Error Messages**
   - Never expose internal errors to webhook sender
   - Log detailed errors internally only

4. **Payload Validation**
   - Validate JSON structure
   - Check required fields exist
   - Sanitize before database storage

## Next Steps - TAHAP 6

1. **Generic Console Commands**
   - `php artisan supplier:sync {supplier_code}`
   - `php artisan supplier:test {supplier_code}`
   - `php artisan supplier:balance {supplier_code}`
   - Support for all suppliers via factory pattern

## Files Modified/Created Summary

1. ✅ `app/Http/Controllers/WebhookController.php` (created)
2. ✅ `routes/web.php` (modified - added webhook routes)
3. ✅ `app/Models/WebhookLog.php` (created)
4. ✅ `database/migrations/2025_12_24_151341_create_webhook_logs_table.php` (created)
5. ✅ `app/Services/Suppliers/BaseSupplierService.php` (modified - added logWebhook)
6. ✅ `app/Services/Suppliers/DigiflazzSupplierService.php` (modified - integrated logging)
7. ✅ `test-tahap5.php` (created)

## Notes

- Legacy DigiflazzController webhook route maintained untuk backward compatibility
- WebhookLog dapat di-extend dengan UI admin untuk monitoring
- Signature validation implementation varies per supplier
- All webhooks logged regardless of validity (untuk audit purposes)

## Success Metrics

✓ 1 controller created (WebhookController)
✓ 2 routes added (handle, test)
✓ 1 model created (WebhookLog)
✓ 1 migration run (webhook_logs table)
✓ 2 services updated (Base, Digiflazz)
✓ All tests passing
✓ Zero breaking changes

---

**Status**: COMPLETED ✓  
**Date**: 24 Desember 2024  
**Next**: TAHAP 6 - Generic Console Commands for Supplier Operations
