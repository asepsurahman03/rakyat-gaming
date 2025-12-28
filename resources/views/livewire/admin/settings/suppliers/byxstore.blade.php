<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use App\Services\Suppliers\ByxStoreSupplierService;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('ByxStore - Supplier Configuration')] class extends Component {
    public $supplierConfig;

    // Form fields
    public string $api_url = '';
    public string $api_key = '';
    public string $secret = '';
    public bool $is_active = false;

    // User info
    public ?array $userInfo = null;

    public function mount(): void
    {
        $this->loadConfig();
        $this->loadUserInfo();
    }

    public function loadConfig(): void
    {
        $this->supplierConfig = Supplier::firstOrCreate(
            ['code' => 'byxstore'],
            [
                'name' => 'ByxStore',
                'code' => 'byxstore',
                'api_url' => 'https://api.byxstore.com',
                'is_active' => false,
            ]
        );

        if ($this->supplierConfig) {
            $this->api_url = $this->supplierConfig->api_url ?? 'https://api.byxstore.com';
            $this->api_key = $this->supplierConfig->api_key ?? '';
            $this->secret = $this->supplierConfig->secret ?? '';
            $this->is_active = (bool) $this->supplierConfig->is_active;
        } else {
            $this->api_url = 'https://api.byxstore.com';
        }
    }

    public function loadUserInfo(): void
    {
        if (empty($this->api_key) || empty($this->api_url)) {
            $this->userInfo = null;
            return;
        }

        try {
            // Temporarily update supplier config for testing
            $tempSupplier = Supplier::where('code', 'byxstore')->first();
            if ($tempSupplier) {
                $tempSupplier->api_key = $this->api_key;
                $tempSupplier->api_url = $this->api_url;
                $tempSupplier->is_active = $this->is_active;
            }

            $service = app(ByxStoreSupplierService::class);
            $result = $service->testConnection();

            if ($result['success'] && isset($result['data'])) {
                $this->userInfo = [
                    'name' => $result['data']['username'] ?? 'ByxStore User',
                    'balance' => $result['data']['balance'] ?? 0,
                    'product_count' => $result['data']['product_count'] ?? 0,
                ];
                return;
            }
        } catch (\Exception $e) {
            Log::error("Failed to load ByxStore user info: " . $e->getMessage());
            Flux::toast(
                heading: 'Error',
                text: 'Gagal memuat informasi user: ' . $e->getMessage(),
                variant: 'error',
                duration: 5000,
                position: 'top-right',
            );
        }

        $this->userInfo = null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'secret' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->supplierConfig) {
            $this->supplierConfig->update($validated);
            $message = 'Konfigurasi ByxStore berhasil diupdate';
        } else {
            Supplier::create([
                'name' => 'ByxStore',
                'code' => 'byxstore',
                ...$validated
            ]);
            $message = 'Konfigurasi ByxStore berhasil disimpan';
        }

        Flux::toast(
            heading: 'Sukses',
            text: $message,
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );

        $this->loadConfig();
        $this->loadUserInfo();
    }

    public function testConnection(): void
    {
        $this->validate([
            'api_key' => 'required',
            'api_url' => 'required',
        ]);

        try {
            // Temporarily update supplier config for testing
            $tempSupplier = Supplier::where('code', 'byxstore')->first();
            if ($tempSupplier) {
                $tempSupplier->api_key = $this->api_key;
                $tempSupplier->api_url = $this->api_url;
            }

            $service = app(ByxStoreSupplierService::class);
            $result = $service->testConnection();

            if ($result['success']) {
                $this->userInfo = [
                    'name' => $result['data']['username'] ?? 'ByxStore User',
                    'balance' => $result['data']['balance'] ?? 0,
                    'product_count' => $result['data']['product_count'] ?? 0,
                ];

                Flux::toast(
                    heading: 'Koneksi Berhasil',
                    text: $result['data']['formatted_balance'] ?? 'Koneksi ke ByxStore berhasil',
                    variant: 'success',
                    duration: 5000,
                    position: 'top-right',
                );
            } else {
                Flux::toast(
                    heading: 'Koneksi Gagal',
                    text: $result['message'] ?? 'Gagal terhubung ke ByxStore',
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

    public function with(): array
    {
        $totalProducts = 0;
        $activeProducts = 0;
        $balance = null;

        if ($this->supplierConfig) {
            try {
                // Get balance from service
                if ($this->supplierConfig->is_active && !empty($this->supplierConfig->api_key)) {
                    $service = app(ByxStoreSupplierService::class);
                    $balance = $service->getBalance();
                }

                // Get product count from database
                $totalProducts = \App\Models\SupplierProduct::where('supplier_id', $this->supplierConfig->id)->count();
                $activeProducts = \App\Models\SupplierProduct::where('supplier_id', $this->supplierConfig->id)
                    ->where('status', 'active')
                    ->count();
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        return [
            'balance' => $balance,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
        <a wire:navigate href="{{ route('dashboard') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">Dashboard</a>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span>Suppliers</span>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span class="text-zinc-900 dark:text-zinc-100">ByxStore</span>
    </div>

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div>
                <flux:heading size="xl">ByxStore</flux:heading>
                <flux:subheading>Konfigurasi supplier produk digital</flux:subheading>
            </div>
        </div>
        <flux:badge color="orange" size="lg" icon="bolt">Digital Products</flux:badge>
    </div>

    <!-- Profile Info Card -->
    @if($userInfo)
    <flux:card>
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex items-center justify-center w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                        <flux:icon.user class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $userInfo['name'] }}</flux:heading>
                        <flux:subheading class="text-xs">ByxStore Account</flux:subheading>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:subheading class="text-xs mb-1">Saldo Akun</flux:subheading>
                        <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                            Rp {{ number_format($userInfo['balance'], 0, ',', '.') }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs mb-1">Layanan Tersedia</flux:subheading>
                        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                            {{ number_format($userInfo['product_count'] ?? 0) }}
                        </flux:heading>
                    </div>
                </div>
            </div>
            <flux:button type="button" wire:click="loadUserInfo" variant="ghost" size="sm" icon="arrow-path">
                Refresh
            </flux:button>
        </div>
    </flux:card>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Status</flux:heading>
                    <flux:heading size="2xl" class="mt-2">
                        @if($is_active)
                            <flux:badge color="lime" icon="check-circle">Aktif</flux:badge>
                        @else
                            <flux:badge color="zinc" icon="x-circle">Nonaktif</flux:badge>
                        @endif
                    </flux:heading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <flux:icon.signal class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo</flux:heading>
                    <flux:heading size="2xl" class="mt-2">
                        @if($userInfo !== null)
                            Rp {{ number_format($userInfo['balance'], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </flux:heading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Produk</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($userInfo['product_count'] ?? 0) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">{{ number_format($activeProducts) }} aktif</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.cube class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Configuration Form -->
    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">Konfigurasi API</flux:heading>
            <flux:separator />

            <div>
                <flux:input
                    wire:model="api_url"
                    label="API URL"
                    type="url"
                    placeholder="https://api.byxstore.com"
                    required
                />
                <flux:error name="api_url" />
                <flux:subheading class="text-xs mt-1">Endpoint API ByxStore</flux:subheading>
            </div>

            <div>
                <flux:input
                    wire:model="api_key"
                    label="API Key"
                    type="text"
                    placeholder="Masukkan API Key ByxStore"
                    required
                />
                <flux:error name="api_key" />
                <flux:subheading class="text-xs mt-1">API Key dari dashboard ByxStore</flux:subheading>
            </div>

            <div>
                <flux:input
                    wire:model="secret"
                    label="Secret Key"
                    type="password"
                    placeholder="Masukkan Secret Key (opsional)"
                />
                <flux:error name="secret" />
                <flux:subheading class="text-xs mt-1">Secret key untuk validasi webhook (opsional)</flux:subheading>
            </div>

            <flux:separator />

            <div>
                <flux:checkbox wire:model="is_active" label="Aktifkan Supplier" />
                <flux:subheading class="text-xs mt-1">Centang untuk mengaktifkan ByxStore sebagai supplier</flux:subheading>
            </div>

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" icon="check">
                    Simpan Konfigurasi
                </flux:button>
                <flux:button type="button" wire:click="testConnection" variant="outline" icon="signal">
                    Test Koneksi
                </flux:button>
            </div>
        </form>
    </flux:card>

    <flux:callout variant="warning" icon="exclamation-triangle" class="mt-6">
        <strong>Penting:</strong> Pastikan API Key dan Secret Key yang Anda masukkan benar.
        Kredensial yang salah dapat menyebabkan kegagalan dalam proses transaksi.
    </flux:callout>
</div>
