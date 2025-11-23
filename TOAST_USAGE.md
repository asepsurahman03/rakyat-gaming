# Toast Usage Guide

Toast telah dikonfigurasi dengan benar di semua layouts (app, guest, auth).

## Cara Menggunakan Toast

### 1. Di Livewire Volt Component (PHP)

```php
use Flux;

// Success toast
Flux::toast('Data berhasil disimpan!');

// Warning toast  
Flux::toast(variant: 'warning', text: 'Peringatan: Data akan dihapus');

// Danger/Error toast
Flux::toast(variant: 'danger', text: 'Terjadi kesalahan!');

// Info toast
Flux::toast(variant: 'info', text: 'Informasi penting');

// Toast dengan heading
Flux::toast(
    heading: 'Berhasil!',
    text: 'Data berhasil disimpan ke database',
    variant: 'success'
);
```

### 2. Di Blade Template (Alpine.js)

```blade
<flux:button x-on:click="$flux.toast({ text: 'Toast dari Alpine!' })">
    Show Toast
</flux:button>

<!-- Dengan variant -->
<flux:button x-on:click="$flux.toast({ text: 'Error!', variant: 'danger' })">
    Show Error
</flux:button>
```

### 3. Contoh di Controller/Action

```php
// Setelah save/update/delete
public function save()
{
    // ... save logic ...
    
    Flux::toast('Product berhasil dibuat!');
    
    return redirect()->route('admin.products.index');
}

public function delete($id)
{
    try {
        Product::findOrFail($id)->delete();
        Flux::toast('Product berhasil dihapus!');
    } catch (\Exception $e) {
        Flux::toast(variant: 'danger', text: 'Gagal menghapus product!');
    }
    
    return redirect()->route('admin.products.index');
}
```

## Variants Available

- `success` (default) - hijau
- `warning` - kuning
- `danger` - merah
- `info` - biru

## Properties

- `text` - Pesan toast (required)
- `heading` - Judul toast (optional)
- `variant` - success/warning/danger/info (optional, default: success)
- `duration` - Durasi tampil dalam ms (optional, default: 3000)

## Troubleshooting

Jika toast tidak muncul:

1. Pastikan Vite assets sudah di-build: `npm run build`
2. Cek browser console untuk error JavaScript
3. Pastikan `@persist('toast')` ada di layout
4. Clear browser cache dan refresh
5. Pastikan menggunakan `use Flux;` di class Livewire

## File Configuration

Toast sudah dikonfigurasi di:
- `resources/js/app.js` - Import Livewire dan Flux
- `resources/views/components/layouts/app/sidebar.blade.php` - Toast untuk admin
- `resources/views/components/layouts/guest.blade.php` - Toast untuk guest
- `resources/views/components/layouts/auth/simple.blade.php` - Toast untuk auth
