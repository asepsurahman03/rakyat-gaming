<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use App\Services\Suppliers\DigiflazzSupplierService;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Digiflazz - Supplier Configuration')] class extends Component {
    public $supplierConfig;

    // Form fields
    public string $username = '';
    public string $api_url = '';
    public string $api_key = '';
    public string $secret = '';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->loadConfig();
    }

    public function loadConfig(): void
    {
        // Get or create Digiflazz supplier
        $this->supplierConfig = Supplier::firstOrCreate(
            ['code' => 'digiflazz'],
            [
                'name' => 'Digiflazz',
                'code' => 'digiflazz',
                'api_url' => 'https://api.digiflazz.com/v1',
                'is_active' => true,
            ]
        );

        if ($this->supplierConfig) {
            $this->username = $this->supplierConfig->username ?? '';
            $this->api_url = $this->supplierConfig->api_url ?? 'https://api.digiflazz.com/v1';
            $this->api_key = $this->supplierConfig->api_key ?? '';
            $this->secret = $this->supplierConfig->secret ?? '';
            $this->is_active = (bool) $this->supplierConfig->is_active;
        } else {
            // Set default values
            $this->api_url = 'https://api.digiflazz.com/v1';
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'username' => 'required|string',
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'secret' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->supplierConfig) {
            $this->supplierConfig->update($validated);
            $message = 'Konfigurasi Digiflazz berhasil diupdate';
        } else {
            Supplier::create([
                'name' => 'Digiflazz',
                'code' => 'digiflazz',
                ...$validated
            ]);
            $message = 'Konfigurasi Digiflazz berhasil disimpan';
        }

        Flux::toast(
            heading: 'Sukses',
            text: $message,
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );

        $this->loadConfig();
    }

    public function testConnection(): void
    {
        $this->validate([
            'username' => 'required',
            'api_key' => 'required',
            'api_url' => 'required',
        ]);

        try {
            // Use DigiflazzSupplierService for testing
            $service = app(DigiflazzSupplierService::class);
            $result = $service->testConnection();

            if ($result['success']) {
                Flux::toast(
                    heading: 'Koneksi Berhasil',
                    text: $result['data']['formatted_balance'] ?? 'Koneksi ke Digiflazz berhasil',
                    variant: 'success',
                    duration: 5000,
                    position: 'top-right',
                );
            } else {
                Flux::toast(
                    heading: 'Koneksi Gagal',
                    text: $result['message'] ?? 'Gagal terhubung ke Digiflazz',
                    variant: 'error',
                    duration: 5000,
                    position: 'top-right',
                );
            }
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: $e->getMessage(),
                variant: 'error',
                duration: 5000,
                position: 'top-right',
            );
        }
    }
}; ?>

<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
        <a wire:navigate href="{{ route('dashboard') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">Dashboard</a>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span>Suppliers</span>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span class="text-zinc-900 dark:text-zinc-100">Digiflazz</span>
    </div>

    <!-- Page Header dengan Logo -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <flux:icon.bolt class="w-8 h-8 text-white" />
            </div>
            <div>
                <flux:heading size="xl">Digiflazz</flux:heading>
                <flux:subheading>Konfigurasi supplier produk digital & pulsa</flux:subheading>
            </div>
        </div>
        <flux:badge color="blue" size="lg" icon="cube">Digital Products</flux:badge>
    </div>

    <flux:card>
        <!-- Stats Section -->
        @if($supplierConfig)
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($supplierConfig->supplierProducts()->count()) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Total Produk</div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($supplierConfig->supplierProducts()->where('status', 'active')->count()) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Produk Aktif</div>
                </div>
                <div class="text-center p-4 {{ $is_active ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} rounded-lg border">
                    <div class="text-2xl font-bold {{ $is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Status Supplier</div>
                </div>
            </div>

            <flux:separator class="mb-6" />
        @else
            <flux:callout variant="info" icon="information-circle" class="mb-6">
                <strong>Belum Ada Konfigurasi</strong> Silakan isi form di bawah untuk mengkonfigurasi koneksi ke Digiflazz.
            </flux:callout>
        @endif

        <!-- Configuration Form -->
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Username <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="username" placeholder="Masukkan username Digiflazz" required />
                    <flux:error name="username" />
                    <flux:description>Username yang didapatkan dari dashboard Digiflazz</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>API Key <span class="text-red-500">*</span></flux:label>
                    <flux:input type="password" wire:model="api_key" placeholder="••••••••••••••••" required />
                    <flux:error name="api_key" />
                    <flux:description>API Key untuk autentikasi ke Digiflazz API</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Webhook Secret</flux:label>
                    <flux:input type="password" wire:model="secret" placeholder="••••••••••••••••" />
                    <flux:error name="secret" />
                    <flux:description>Secret key untuk validasi webhook callback (opsional, untuk keamanan tambahan)</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>API URL <span class="text-red-500">*</span></flux:label>
                    <flux:input type="url" wire:model="api_url" placeholder="https://api.digiflazz.com/v1" required />
                    <flux:error name="api_url" />
                    <flux:description>Endpoint API Digiflazz (biasanya tidak perlu diubah)</flux:description>
                </flux:field>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <flux:field>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>Status Aktif</flux:label>
                            <flux:description>Aktifkan koneksi untuk menggunakan supplier Digiflazz</flux:description>
                        </div>
                        <flux:switch wire:model="is_active" />
                    </div>
                </flux:field>
            </div>

            <flux:separator />

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <flux:button type="submit" variant="primary" icon="check" class="flex-1">
                    {{ $supplierConfig ? 'Update Konfigurasi' : 'Simpan Konfigurasi' }}
                </flux:button>
                <flux:button type="button" wire:click="testConnection" variant="ghost" icon="play" class="flex-1">
                    Test Koneksi
                </flux:button>
            </div>
        </form>

        @if($supplierConfig)
            <flux:separator class="my-6" />

            <!-- Info Section -->
            <div class="bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg">
                <flux:heading size="base" class="mb-4">Informasi Penting</flux:heading>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-start gap-2">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>Pastikan username dan API key sudah benar sebelum menggunakan</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>Gunakan fitur "Test Koneksi" untuk memverifikasi kredensial</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>Webhook Secret digunakan untuk memvalidasi callback dari Digiflazz (opsional tapi direkomendasikan)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>Status aktif harus diaktifkan agar sistem dapat melakukan transaksi</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>Sinkronisasi harga produk dilakukan secara periodik oleh sistem</span>
                    </li>
                </ul>
            </div>
        @endif
    </flux:card>
</div>
