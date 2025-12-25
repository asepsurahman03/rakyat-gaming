# TAHAP 6: Generic Console Commands for Supplier Operations - COMPLETED ✓

## Overview
Tahap ini mengimplementasikan generic console commands untuk operasi supplier yang dapat digunakan untuk semua supplier (Digiflazz, OnoyStore, ByxStore, dll) melalui factory pattern. Commands ini menyediakan CLI interface untuk sync products, test connection, dan check balance.

## Completion Date
24 Desember 2024

## Changes Summary

### 1. SupplierSyncCommand
**File Created:** `app/Console/Commands/SupplierSyncCommand.php`

**Signature:**
```bash
php artisan supplier:sync {code} [--all]
```

**Features:**
- ✅ Sync products dari supplier API ke database lokal
- ✅ Support single supplier atau semua supplier dengan `--all` flag
- ✅ Progress bar untuk visual feedback
- ✅ Detailed statistics (synced count, new count, updated count)
- ✅ Error handling dengan informative messages
- ✅ Summary report untuk batch sync

**Usage Examples:**
```bash
# Sync dari Digiflazz
php artisan supplier:sync digiflazz

# Sync dari semua active suppliers
php artisan supplier:sync digiflazz --all
```

**Output:**
```
Starting product sync from digiflazz...

 1/1 [============================] 100%

✓ Successfully synced 32 products from digiflazz
  • New products: 5
  • Updated products: 27
```

### 2. SupplierTestCommand
**File Created:** `app/Console/Commands/SupplierTestCommand.php`

**Signature:**
```bash
php artisan supplier:test {code} [--all]
```

**Features:**
- ✅ Test koneksi ke supplier API
- ✅ Validate API credentials
- ✅ Check balance jika tersedia
- ✅ Display detailed connection info
- ✅ Support testing multiple suppliers
- ✅ Color-coded output (success=green, error=red, warning=yellow)

**Usage Examples:**
```bash
# Test Digiflazz connection
php artisan supplier:test digiflazz

# Test semua suppliers
php artisan supplier:test digiflazz --all
```

**Output:**
```
Testing connection to digiflazz...

✓ Connection successful!

  Message: Connection successful
  Data:
    • balance: 158376
    • formatted_balance: Rp 158.376
```

### 3. SupplierBalanceCommand
**File Created:** `app/Console/Commands/SupplierBalanceCommand.php`

**Signature:**
```bash
php artisan supplier:balance {code} [--all]
```

**Features:**
- ✅ Check saldo/deposit dari supplier
- ✅ Formatted currency display
- ✅ Table output untuk readability
- ✅ Low balance warning (< Rp 100,000)
- ✅ Total balance calculation untuk multiple suppliers
- ✅ Status indicator (Available/Low/Empty)

**Usage Examples:**
```bash
# Check balance Digiflazz
php artisan supplier:balance digiflazz

# Check balance semua suppliers
php artisan supplier:balance digiflazz --all
```

**Output:**
```
Checking balance for digiflazz...

✓ Balance retrieved successfully!

+-----------+------------+-------------+
| Supplier  | Balance    | Status      |
+-----------+------------+-------------+
| DIGIFLAZZ | Rp 158.376 | ✓ Available |
+-----------+------------+-------------+
```

### 4. Test Suite
**File Created:** `test-tahap6.php`

**Tests:**
1. ✅ Console commands registration
2. ✅ Command methods exist
3. ✅ supplier:test command execution
4. ✅ supplier:balance command execution
5. ✅ supplier:sync command execution
6. ✅ Error handling for invalid suppliers
7. ✅ Commands with --all flag
8. ✅ Command descriptions
9. ✅ Artisan list includes commands
10. ✅ Factory integration

**Test Results:**
```
✓ All command classes created
✓ Commands registered in Artisan
✓ supplier:test command working (exit code: 0)
✓ supplier:balance command working (exit code: 0)
✓ supplier:sync command working (exit code: 0)
✓ Error handling for invalid suppliers (exit code: 1)
✓ --all flag functionality working
✓ Factory integration confirmed
```

## Features Implemented

### For CLI Users:

1. **Product Sync Command**
   - Sync products from any supplier
   - Batch sync dengan --all flag
   - Progress indicator
   - Statistics report

2. **Connection Test Command**
   - Validate API credentials
   - Quick connectivity check
   - Display balance if available
   - Multi-supplier testing

3. **Balance Check Command**
   - Real-time balance checking
   - Formatted currency display
   - Low balance alerts
   - Aggregate reporting

### For Developers:

1. **Factory Pattern Integration**
   - Commands work dengan semua suppliers
   - No code changes untuk add new supplier
   - Automatic service resolution

2. **Consistent CLI Interface**
   - Same command structure untuk all suppliers
   - Predictable output format
   - Standard exit codes

3. **Error Handling**
   - Validation untuk supplier existence
   - Check supplier active status
   - Graceful exception handling
   - Helpful error messages

## Technical Details

### Command Structure:
```php
class SupplierXxxCommand extends Command
{
    protected $signature = 'supplier:xxx {code} {--all}';
    protected $description = '...';
    
    public function handle(SupplierServiceFactory $factory): int
    {
        // 1. Validate supplier exists
        // 2. Get service from factory
        // 3. Check supplier is active
        // 4. Execute operation
        // 5. Display results
        // 6. Return exit code
    }
}
```

### Exit Codes:
- **0 (SUCCESS)**: Operation completed successfully
- **1 (FAILURE)**: Operation failed (invalid supplier, inactive, API error, etc.)

### Factory Integration:
```php
// Get specific supplier service
$service = $factory->make('digiflazz');

// Get all active suppliers
$activeServices = $factory->getActiveServices();

// Check supplier exists
if ($factory->has('digiflazz')) {
    // ...
}

// Get registered suppliers
$registered = $factory->getRegistered();
```

### Output Formatting:
```php
// Info (green)
$this->info("✓ Success message");

// Error (red)
$this->error("✗ Error message");

// Warning (yellow)
$this->warn("⚠ Warning message");

// Line (white)
$this->line("  • Detail item");

// Table
$this->table(['Header1', 'Header2'], $data);

// Progress bar
$this->withProgressBar($items, function($item) {
    // Process item
});
```

## Command Details

### 1. supplier:sync

**Purpose:** Sync products dari supplier API ke database

**Arguments:**
- `code`: Supplier code (required) - digiflazz, onoystore, byxstore

**Options:**
- `--all`: Sync dari semua active suppliers

**Process Flow:**
1. Validate supplier exists in factory
2. Get supplier service
3. Check supplier is active
4. Call service->syncProducts()
5. Display progress bar
6. Show statistics
7. Return exit code

**Error Cases:**
- Supplier not found → Show available suppliers
- Supplier inactive → Show error message
- Sync failed → Show error from API response
- Exception → Show exception message

### 2. supplier:test

**Purpose:** Test koneksi ke supplier API

**Arguments:**
- `code`: Supplier code (required)

**Options:**
- `--all`: Test semua suppliers (registered, bukan hanya active)

**Process Flow:**
1. Validate supplier exists
2. Get supplier service
3. Check supplier is active
4. Call service->testConnection()
5. Display result with details
6. Return exit code

**Output Data:**
- Connection status (success/failed)
- Message from API
- Balance (if available)
- Additional data from API

### 3. supplier:balance

**Purpose:** Check balance/deposit dari supplier

**Arguments:**
- `code`: Supplier code (required)

**Options:**
- `--all`: Check balance untuk semua active suppliers

**Process Flow:**
1. Validate supplier exists
2. Get supplier service
3. Check supplier is active
4. Call service->getBalance()
5. Format currency
6. Display in table
7. Show warning if low balance
8. Return exit code

**Balance Thresholds:**
- **< Rp 100,000**: Low balance warning
- **= 0**: Empty balance
- **> 0**: Available

## Usage Scenarios

### Daily Operations:

```bash
# Morning routine: check all balances
php artisan supplier:balance digiflazz --all

# Test connections after configuration change
php artisan supplier:test digiflazz --all

# Sync products (scheduled via cron)
php artisan supplier:sync digiflazz
```

### Troubleshooting:

```bash
# Test specific supplier connection
php artisan supplier:test digiflazz

# Check if API key is valid
php artisan supplier:balance digiflazz

# Re-sync products after API issue
php artisan supplier:sync digiflazz
```

### New Supplier Setup:

```bash
# 1. Test connection first
php artisan supplier:test onoystore

# 2. Check balance
php artisan supplier:balance onoystore

# 3. Sync initial products
php artisan supplier:sync onoystore
```

## Automation & Scheduling

### Cron Jobs:

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Sync products every 6 hours
    $schedule->command('supplier:sync digiflazz --all')
             ->everySixHours()
             ->withoutOverlapping();
    
    // Check balances daily at 8 AM
    $schedule->command('supplier:balance digiflazz --all')
             ->dailyAt('08:00');
    
    // Test connections weekly
    $schedule->command('supplier:test digiflazz --all')
             ->weekly()
             ->mondays()
             ->at('09:00');
}
```

### Laravel Horizon/Queue:

```php
// Dispatch sync job
dispatch(new SyncSupplierProducts('digiflazz'));

// Or call from controller
Artisan::call('supplier:sync', ['code' => 'digiflazz']);
```

## Error Messages & Troubleshooting

### "Supplier not found or not registered"
- **Cause**: Supplier code typo atau service belum registered di factory
- **Solution**: Check available suppliers, register service di AppServiceProvider

### "Supplier is not active or not properly configured"
- **Cause**: Supplier status inactive atau API credentials missing
- **Solution**: Update supplier settings di admin panel, set is_active=true

### "Failed to sync products"
- **Cause**: API error, network issue, authentication failed
- **Solution**: Check API key, test connection, check logs

### "Failed to retrieve balance"
- **Cause**: Supplier doesn't support balance API atau credentials invalid
- **Solution**: Verify API credentials, check supplier documentation

## Testing Checklist

- [x] Commands registered in Artisan
- [x] Help text displayed with `--help`
- [x] Commands work with valid supplier code
- [x] Commands reject invalid supplier code
- [x] Commands check supplier active status
- [x] --all flag works for batch operations
- [x] Progress bars display correctly
- [x] Tables format properly
- [x] Exit codes correct (0=success, 1=failure)
- [x] Error messages helpful and clear

## Performance Considerations

1. **Sync Command**
   - Progress bar untuk feedback
   - Batch processing di service layer
   - Transaction wrapping untuk data integrity

2. **Test Command**
   - Quick timeout untuk failed connections
   - Parallel testing when using --all (future enhancement)

3. **Balance Command**
   - Cached results (future enhancement)
   - Aggregate calculation for --all

## Security Considerations

1. **API Credentials**
   - Never displayed in output
   - Stored securely in database
   - Logged separately (not in command output)

2. **Error Messages**
   - Generic errors for external visibility
   - Detailed errors logged internally

3. **Access Control**
   - Commands should be restricted to authorized users
   - Consider adding confirmation for destructive operations

## Next Steps - TAHAP 7

1. **Implement OnoyStore Service**
   - Extend BaseSupplierService
   - Implement API methods
   - Register in factory
   - Test with commands

2. **Implement ByxStore Service**
   - Similar to OnoyStore
   - Different API specifications
   - Complete integration

3. **Testing**
   - Test all commands dengan new suppliers
   - Verify factory resolution
   - End-to-end testing

## Files Created Summary

1. ✅ `app/Console/Commands/SupplierSyncCommand.php` (created)
2. ✅ `app/Console/Commands/SupplierTestCommand.php` (created)
3. ✅ `app/Console/Commands/SupplierBalanceCommand.php` (created)
4. ✅ `test-tahap6.php` (created)

## Notes

- Commands automatically available di `php artisan list`
- Factory pattern makes adding new suppliers trivial
- All commands follow Laravel command conventions
- Output formatted for both human and machine reading
- Exit codes follow standard Unix conventions

## Success Metrics

✓ 3 commands created (sync, test, balance)
✓ All commands working with Digiflazz
✓ --all flag implemented for batch operations
✓ Error handling comprehensive
✓ Factory integration seamless
✓ Test suite passing (10/10 tests)
✓ Balance: Rp 158,376 (Digiflazz)
✓ Products synced: 32

---

**Status**: COMPLETED ✓  
**Date**: 24 Desember 2024  
**Next**: TAHAP 7 - Implement OnoyStore and ByxStore Services
