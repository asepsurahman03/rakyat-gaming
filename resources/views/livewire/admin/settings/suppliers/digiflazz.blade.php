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

    public function loadProfile(): void
    {
        // Just trigger a re-render to update profile info from with()
        $this->dispatch('$refresh');
    }

    public function with(): array
    {
        $totalProducts = 0;
        $activeProducts = 0;
        $balance = null;
        $profileInfo = null;

        if ($this->supplierConfig && $this->supplierConfig->is_active) {
            try {
                $service = app(DigiflazzSupplierService::class);
                $balanceData = $service->getBalance();

                if (is_array($balanceData) && isset($balanceData['balance'])) {
                    $balance = $balanceData['balance'];
                    $profileInfo = [
                        'username' => $balanceData['username'] ?? $this->username,
                        'balance' => $balance,
                    ];
                } else {
                    $balance = $balanceData;
                    $profileInfo = [
                        'username' => $this->username,
                        'balance' => $balance,
                    ];
                }

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
            'profileInfo' => $profileInfo,
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
        <span class="text-zinc-900 dark:text-zinc-100">Digiflazz</span>
    </div>

    <!-- Page Header dengan Logo -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div>
                <flux:heading size="xl">Digiflazz</flux:heading>
                <flux:subheading>Konfigurasi supplier produk digital & pulsa</flux:subheading>
            </div>
        </div>
        <flux:badge color="blue" size="lg" icon="cube">Digital Products</flux:badge>
    </div>

    <!-- Profile Info Card -->
    @if($profileInfo)
    <flux:card>
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                        <flux:icon.user class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $profileInfo['username'] }}</flux:heading>
                        <flux:subheading class="text-xs">Digiflazz Account</flux:subheading>
                    </div>
                </div>
                <div>
                    <flux:subheading class="text-xs mb-1">Saldo Akun</flux:subheading>
                    <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                        Rp {{ number_format($profileInfo['balance'], 0, ',', '.') }}
                    </flux:heading>
                </div>
            </div>
            <flux:button type="button" wire:click="loadProfile" variant="ghost" size="sm" icon="arrow-path">
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
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.signal class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo</flux:heading>
                    <flux:heading size="2xl" class="mt-2">
                        @if($balance !== null)
                            Rp {{ number_format($balance, 0, ',', '.') }}
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
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalProducts) }}</flux:heading>
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
                    wire:model="username"
                    label="Username"
                    type="text"
                    placeholder="Masukkan username Digiflazz"
                    required
                />
                <flux:error name="username" />
                <flux:subheading class="text-xs mt-1">Username yang didapatkan dari dashboard Digiflazz</flux:subheading>
            </div>

            <div>
                <flux:input
                    wire:model="api_url"
                    label="API URL"
                    type="url"
                    placeholder="https://api.digiflazz.com/v1"
                    required
                />
                <flux:error name="api_url" />
                <flux:subheading class="text-xs mt-1">Endpoint API Digiflazz</flux:subheading>
            </div>

            <div>
                <flux:input
                    wire:model="api_key"
                    label="API Key"
                    type="password"
                    placeholder="Masukkan API Key Digiflazz"
                    required
                />
                <flux:error name="api_key" />
                <flux:subheading class="text-xs mt-1">API Key untuk autentikasi ke Digiflazz API</flux:subheading>
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
                <flux:subheading class="text-xs mt-1">Centang untuk mengaktifkan Digiflazz sebagai supplier</flux:subheading>
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
        <strong>Penting:</strong> Pastikan Username, API Key dan Secret Key yang Anda masukkan benar.
        Kredensial yang salah dapat menyebabkan kegagalan dalam proses transaksi.
    </flux:callout>
</div>
