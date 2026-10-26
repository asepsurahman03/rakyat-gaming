<div align="center">

# 🎮 RAKYAT GAMING
### *Platform Top Up Game & Produk Digital Terpercaya, Cepat, dan Otomatis 24/7*

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Digiflazz](https://img.shields.io/badge/Provider-Digiflazz%20API-0099FF?style=for-the-badge)](https://digiflazz.com)
[![Duitku](https://img.shields.io/badge/Payment-Duitku%20Gateway-00B14F?style=for-the-badge)](https://duitku.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

<br/>

**Rakyat Gaming** adalah web aplikasi top up game dan produk digital modern yang dibangun di atas ekosistem **Laravel 12** dan **Livewire 3**. Mengusung konsep *Cyber-Glassmorphism UI* dengan performa tinggi, sistem transaksi terotomatisasi penuh melalui **Digiflazz API** serta integrasi multi-pembayaran instan melalui **Duitku Payment Gateway**.

[🌐 Live Demo](https://github.com/asepsurahman03/rakyat-gaming) • [✨ Fitur Utama](#-fitur-utama) • [🚀 Panduan Instalasi](#-instalasi--setup) • [🔑 Akun Demo](#-demo-credentials) • [☁️ Deployment](#-deployment-ke-vercel)

---

</div>

<br/>

## 🌟 Fitur Utama

### 🛒 Untuk Pelanggan (Public & User)
- **⚡ Transaksi Otomatis 24/7**: Pengisian diamond/voucher game terhubung langsung ke server provider via **Digiflazz API**.
- **💳 Multi-Payment Gateway (Duitku)**:
  - **QRIS** (Semua e-wallet & mobile banking: GoPay, OVO, Dana, ShopeePay, LinkAja)
  - **Virtual Account Bank** (BCA, Mandiri, BNI, BRI, Permata, CIMB)
  - **Convenience Store** (Alfamart & Indomaret)
- **🔍 Cek Akun Game Otomatis**: Validasi User ID & Server Game secara realtime sebelum pembayaran untuk mencegah salah kirim.
- **📱 Desain Modern Cyber-Glassmorphism**: UI dark mode responsif yang nyaman digunakan di perangkat mobile maupun desktop.
- **📊 Pelacak Transaksi Real-time**: Lacak status pesanan secara live menggunakan invoice atau nomor WhatsApp.
- **🔔 Realtime Notifikasi**: Didukung oleh **Laravel Reverb WebSockets** untuk pembaruan status transaksi seketika.

### 🛡️ Untuk Administrator (Admin Panel)
- **📈 Dashboard Analitik & Statistik**: Monitoring total omset, keuntungan bersih, grafik penjualan harian/bulanan, dan status order.
- **🎮 Manajemen Produk & Layanan**:
  - Sinkronisasi otomatis katalog dan harga modal dari Digiflazz.
  - Pengaturan margin/keuntungan harga per kategori atau per produk.
  - Tambah, edit, nonaktifkan game/voucher dengan mudah.
- **💳 Manajemen Metode Pembayaran**: Konfigurasi biaya admin (*fee*) dan aktivasi channel pembayaran Duitku.
- **📑 Laporan & Export Data**: Cetak dan ekspor laporan transaksi ke format **Excel (.xlsx)** menggunakan `maatwebsite/excel`.
- **🪵 System & Webhook Logs**: Pantau respon callback webhook dari Digiflazz dan Duitku untuk audit keamanan.

---

## 🔑 Demo Credentials

Untuk memudahkan peninjauan dan demonstrasi fitur, sistem dilengkapi tombol **1-Klik Isi Form Otomatis** pada halaman login:

| Role | Badge | Email | Password | Akses |
| :--- | :--- | :--- | :--- | :--- |
| 👑 **Administrator** | `Akses Penuh` | `admin@gmail.com` | `password` | Manajemen Produk, Markup Harga, Laporan Keuangan, Log Webhook |
| 👤 **User Demo** | `Member` | `user@gmail.com` | `password` | Riwayat Pembelian, Profil, Tracking Pesanan |

> **Catatan:** Password bawaan untuk kedua akun demo adalah `password`.

---

## 🛠️ Tech Stack & Ekosistem

| Lapisan | Teknologi | Deskripsi |
| :--- | :--- | :--- |
| **Backend Framework** | [Laravel 12](https://laravel.com) | Framework PHP modern dengan arsitektur tangguh dan elegan |
| **Frontend Reactive** | [Livewire 3](https://livewire.laravel.com) + [Volt](https://livewire.laravel.com/docs/volt) | Interaktivitas dinamis komponen tanpa full page reload |
| **UI Components** | [Flux UI Pro](https://fluxui.dev) | Komponen UI premium untuk aplikasi web modern |
| **Styling** | [Tailwind CSS v4](https://tailwindcss.com) + [Vite](https://vitejs.dev) | Styling utility-first dengan Glassmorphism Dark Theme |
| **Realtime Engine** | [Laravel Reverb](https://laravel.com/docs/reverb) | WebSocket server berkecepatan tinggi |
| **Payment Gateway** | [Duitku SDK](https://duitku.com) (`royryando/laravel-duitku`) | Integrasi pembayaran multichannel instan Indonesia |
| **H2H Game Provider** | [Digiflazz API](https://digiflazz.com) (`triyatna/digiflazz-laravel-4buyer`) | PPOB & Game Top Up Host-to-Host otomatis |
| **Database** | [MySQL 8.x / MariaDB](https://mysql.com) | Relational Database Management System |

---

## 🚀 Instalasi & Setup Lokal

### Prasyarat:
- PHP >= 8.2 (ekstensi `curl`, `pdo_mysql`, `mbstring`, `openssl`, `zip` aktif)
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL / MariaDB Server (misal via XAMPP)

### Langkah-langkah:

1. **Clone Repository:**
   ```bash
   git clone https://github.com/asepsurahman03/rakyat-gaming.git
   cd rakyat-gaming
   ```

2. **Install Dependensi PHP & Frontend:**
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Konfigurasi Environment (`.env`):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database & API Credentials di `.env`:**
   ```env
   APP_NAME="Rakyat Gaming"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rakyat_gaming
   DB_USERNAME=root
   DB_PASSWORD=

   # Konfigurasi Digiflazz
   DIGIFLAZZ_USERNAME=your_username
   DIGIFLAZZ_KEY=your_production_key
   DIGIFLAZZ_MODE=development

   # Konfigurasi Duitku
   DUITKU_MERCHANT_CODE=your_merchant_code
   DUITKU_API_KEY=your_api_key
   DUITKU_SANDBOX_MODE=true
   ```

5. **Jalankan Migrasi & Database Seeder:**
   ```bash
   php artisan migrate --seed
   ```

6. **Jalankan Aplikasi:**
   ```bash
   # Jalankan web server Laravel
   php artisan serve

   # (Opsional) Jalankan WebSocket Reverb di terminal terpisah
   php artisan reverb:start
   ```

7. Buka browser dan akses: `http://localhost:8000`

---

## ☁️ Deployment ke Vercel

Project ini telah dikonfigurasi agar siap dideploy ke **Vercel** menggunakan community runtime PHP Serverless (`vercel-php`):

### 1. File Konfigurasi yang Tersedia:
- `vercel.json`: Konfigurasi runtime PHP, rewrite static asset (`/public/build`), dan routing serverless function.
- `api/index.php`: Serverless entry point yang menginisialisasi direktori `/tmp/storage` otomatis.
- `.vercelignore`: Pengecualian file unneeded untuk deployment optimal.

### 2. Cara Deploy via Vercel Dashboard:
1. Masuk ke [Vercel Dashboard](https://vercel.com/dashboard).
2. Klik tombol **"Add New"** > **"Project"**.
3. Hubungkan akun GitHub Anda dan pilih repository `asepsurahman03/rakyat-gaming`.
4. Tambahkan **Environment Variables** penting di menu Vercel Settings:
   - `APP_NAME` : `Rakyat Gaming`
   - `APP_ENV` : `production`
   - `APP_KEY` : *(Salin dari file `.env` Anda)*
   - `APP_DEBUG` : `false`
   - `APP_URL` : `https://your-domain.vercel.app`
   - `DB_CONNECTION` : `mysql`
   - `DB_HOST` : *(Host database online Anda, misal: TiDB Cloud / Aiven / Railway)*
   - `DB_PORT` : `3306`
   - `DB_DATABASE` : `nama_database`
   - `DB_USERNAME` : `username_db`
   - `DB_PASSWORD` : `password_db`
   - `SESSION_DRIVER` : `cookie`
   - `CACHE_STORE` : `array`
5. Klik **"Deploy"** dan tunggu proses build selesai! 🚀

---

## 📞 Kontak & Informasi Pengembang

- **Email**: [codetech.id@gmail.com](mailto:codetech.id@gmail.com)
- **Website**: [codetech.id](https://codetech.id)
- **Instagram**: [@codetech.id](https://instagram.com/codetech.id)

---

<div align="center">

Dibuat dengan ❤️ oleh **[Asep Surahman](https://github.com/asepsurahman03)** & **Codetech ID**<br/>
© 2026 **Rakyat Gaming**. All Rights Reserved.

</div>
