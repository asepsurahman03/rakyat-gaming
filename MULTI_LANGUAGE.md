# Multi-Language Support

Aplikasi sekarang mendukung 2 bahasa: **Indonesia (id)** dan **English (en)**

## Cara Menggunakan

### 1. Di Blade Template
Gunakan helper `__()` untuk menerjemahkan teks:

```blade
{{ __('messages.home') }}
{{ __('messages.dashboard') }}
{{ __('messages.save') }}
```

### 2. Ganti Bahasa
User bisa mengganti bahasa melalui dropdown di navbar guest atau klik route:
- `/lang/id` - Ganti ke Bahasa Indonesia
- `/lang/en` - Ganti ke English

Bahasa akan disimpan di session dan otomatis digunakan di semua halaman.

### 3. Tambah Terjemahan Baru

**File Bahasa:**
- `lang/id/messages.php` - Terjemahan Indonesia
- `lang/en/messages.php` - Terjemahan English

**Contoh menambah key baru:**

```php
// lang/id/messages.php
return [
    'welcome' => 'Selamat Datang',
    'new_key' => 'Teks Baru',
];

// lang/en/messages.php
return [
    'welcome' => 'Welcome',
    'new_key' => 'New Text',
];
```

Lalu gunakan di blade:
```blade
{{ __('messages.welcome') }}
{{ __('messages.new_key') }}
```

## Default Language
Default bahasa adalah **Indonesia (id)**. Bisa diubah di `config/app.php`:

```php
'locale' => env('APP_LOCALE', 'id'),
```

## Halaman yang Sudah Diterjemahkan
✅ Guest Layout (navbar, footer)
✅ Admin Sidebar (semua menu)
✅ Profile Settings
✅ Appearance Settings

## Todo
- Terjemahkan halaman auth (login, register, forgot password)
- Terjemahkan halaman admin (CRUD forms)
- Terjemahkan halaman guest (welcome, game detail, checkout)
- Terjemahkan pesan toast dan notifikasi
