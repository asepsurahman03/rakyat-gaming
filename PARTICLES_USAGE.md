# Particle Effect System

Sistem efek partikel yang dapat digunakan untuk menambahkan animasi partikel jatuh di halaman web.

## File
- **Location**: `resources/js/particles.js`
- **Class**: `ParticleSystem`

## Penggunaan

### 1. Tambahkan Canvas di HTML/Blade
```blade
<div class="relative">
    <!-- Canvas untuk particles -->
    <canvas id="particleCanvas" class="absolute inset-0 w-full h-full pointer-events-none z-0"></canvas>
    
    <!-- Konten Anda -->
    <div class="relative z-10">
        <!-- Content here -->
    </div>
</div>
```

### 2. Load Script dengan @vite
```blade
@push('scripts')
@vite('resources/js/particles.js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const particles = new ParticleSystem('particleCanvas', {
            // options
        });
    });
</script>
@endpush
```

### 3. Tambahkan @stack('scripts') di Layout
```blade
<!-- Di layout file (guest.blade.php atau app.blade.php) -->
@stack('scripts')
</body>
</html>
```

## Options

```javascript
{
    particleCount: 60,              // Jumlah partikel (default: 50)
    particleColor: 'rgba(255, 215, 0, 0.7)', // Warna partikel (default: golden)
    particleSize: {                 // Ukuran partikel
        min: 2, 
        max: 6 
    },
    speed: {                        // Kecepatan jatuh
        min: 0.5, 
        max: 2 
    },
    shapes: ['circle', 'star', 'diamond'], // Bentuk partikel
    glow: true                      // Efek glow (default: true)
}
```

## Shape Types
- `circle` - Lingkaran
- `star` - Bintang 5 sudut
- `diamond` - Belah ketupat

## Contoh Theme

### Golden Theme (Default)
```javascript
particleColor: 'rgba(255, 215, 0, 0.7)'
```

### Silver Theme
```javascript
particleColor: 'rgba(192, 192, 192, 0.7)'
```

### Blue Theme
```javascript
particleColor: 'rgba(99, 102, 241, 0.7)'
```

### Snow Effect
```javascript
particleColor: 'rgba(255, 255, 255, 0.9)',
shapes: ['circle'],
speed: { min: 0.3, max: 1 }
```

## Methods

- `init()` - Initialize particle system
- `createParticles()` - Create particle array
- `animate()` - Start animation loop
- `destroy()` - Clean up and stop animation

## Implementasi di Welcome Page

Particle effect sudah diterapkan di section carousel homepage dengan konfigurasi:
- 60 partikel emas dengan glow effect
- Mix shapes: circle, star, diamond
- Speed sedang untuk efek smooth
- Z-index diatur agar tidak mengganggu interaksi user

## Notes

- Canvas menggunakan `pointer-events-none` agar tidak block interaksi
- Responsive - otomatis resize saat window resize
- Performance optimized dengan requestAnimationFrame
- Particle di-reset otomatis saat keluar canvas
