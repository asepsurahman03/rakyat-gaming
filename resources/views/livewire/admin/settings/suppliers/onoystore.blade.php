<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use App\Services\Suppliers\OnoyStoreSupplierService;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('OnoyStore - Supplier Configuration')] class extends Component {
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
            ['code' => 'onoystore'],
            [
                'name' => 'OnoyStore',
                'code' => 'onoystore',
                'api_url' => 'https://api.onoystore.com',
                'is_active' => false,
            ],
        );

        if ($this->supplierConfig) {
            $this->api_url = $this->supplierConfig->api_url ?? 'https://api.onoystore.com';
            $this->api_key = $this->supplierConfig->api_key ?? '';
            $this->secret = $this->supplierConfig->secret ?? '';
            $this->is_active = (bool) $this->supplierConfig->is_active;
        } else {
            $this->api_url = 'https://api.onoystore.com';
        }
    }

    public function loadUserInfo(): void
    {
        if (empty($this->api_key) || empty($this->api_url) || !$this->is_active) {
            $this->userInfo = null;
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $this->api_key,
            ])->get($this->api_url . '/v1/public/user');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['statusCode']) && $data['statusCode'] == 200) {
                    $this->userInfo = [
                        'name' => $data['data']['user_name'] ?? '-',
                        'email' => $data['data']['user_email'] ?? '-',
                        'balance' => $data['data']['user_balance'] ?? 0,
                        'role' => $data['data']['role_name'] ?? '-',
                    ];
                    return;
                }
            }

        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Gagal memuat informasi user: ' . $e->getMessage(),
                variant: 'error',
                duration: 5000,
                position: 'top-right',
            );
            Log::error("Failed to load OnoyStore user info: " . $e->getMessage());
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
            $message = 'Konfigurasi OnoyStore berhasil diupdate';
        } else {
            Supplier::create([
                'name' => 'OnoyStore',
                'code' => 'onoystore',
                ...$validated,
            ]);
            $message = 'Konfigurasi OnoyStore berhasil disimpan';
        }

        Flux::toast(heading: 'Sukses', text: $message, variant: 'success', duration: 3000, position: 'top-right');

        $this->loadConfig();
        $this->loadUserInfo();
    }

    public function testConnection(): void
    {
        $this->validate([
            'api_key' => 'required',
            'api_url' => 'required',
        ]);

        // Temporarily save config to test
        $this->supplierConfig->update([
            'api_url' => $this->api_url,
            'api_key' => $this->api_key,
        ]);

        try {
            $service = app(OnoyStoreSupplierService::class);
            $result = $service->testConnection();

            if ($result['success']) {
                Log::info("message: " . ($result['data'] ?? 'No message'));
                $this->userInfo = [
                    'name' => $result['data']['user_name'] ?? '-',
                    'email' => $result['data']['user_email'] ?? '-',
                    'balance' => $result['data']['user_balance'] ?? 0,
                    'role' => $result['data']['role_name'] ?? '-',
                ];
                Flux::toast(heading: 'Koneksi Berhasil', text: $result['data']['formatted_balance'] ?? 'Koneksi ke OnoyStore berhasil', variant: 'success', duration: 5000, position: 'top-right');
            } else {

                Flux::toast(heading: 'Koneksi Gagal', text: $result['message'] ?? 'Gagal terhubung ke OnoyStore', variant: 'error', duration: 5000, position: 'top-right');
            }
        } catch (\Exception $e) {
            Flux::toast(heading: 'Error', text: $e->getMessage(), variant: 'error', duration: 5000, position: 'top-right');
        }
    }

    // public function testUserApi(): void
    // {
    //     $this->validate([
    //         'api_key' => 'required',
    //         'api_url' => 'required',
    //     ]);

    //     try {
    //         $response = \Illuminate\Support\Facades\Http::withHeaders([
    //             'Authorization' => $this->api_key,
    //         ])->get($this->api_url . '/v1/public/user');

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             if ($data['statusCode'] == 200) {
    //                 $userData = $data['data'];
    //                 $message = "User: {$userData['user_name']}\n";
    //                 $message .= "Email: {$userData['user_email']}\n";
    //                 $message .= "Saldo: Rp " . number_format($userData['user_balance'], 0, ',', '.') . "\n";
    //                 $message .= "Role: {$userData['role_name']}";

    //                 Flux::toast(
    //                     heading: 'Test API User Berhasil',
    //                     text: $message,
    //                     variant: 'success',
    //                     duration: 8000,
    //                     position: 'top-right',
    //                 );
    //             } else {
    //                 Flux::toast(
    //                     heading: 'Test API Gagal',
    //                     text: $data['message'] ?? 'Response tidak sesuai',
    //                     variant: 'error',
    //                     duration: 5000,
    //                     position: 'top-right',
    //                 );
    //             }
    //         } else {
    //             Flux::toast(
    //                 heading: 'Test API Gagal',
    //                 text: 'HTTP Status: ' . $response->status(),
    //                 variant: 'error',
    //                 duration: 5000,
    //                 position: 'top-right',
    //             );
    //         }
    //     } catch (\Exception $e) {
    //         Flux::toast(
    //             heading: 'Error',
    //             text: $e->getMessage(),
    //             variant: 'error',
    //             duration: 5000,
    //             position: 'top-right',
    //         );
    //     }
    // }

    public function with(): array
    {
        $totalProducts = 0;
        $activeProducts = 0;
        $balance = null;

        if ($this->supplierConfig && $this->supplierConfig->is_active) {
            try {
                $service = app(OnoyStoreSupplierService::class);
                $balance = $service->getBalance();

                $totalProducts = \App\Models\SupplierProduct::where('supplier_id', $this->supplierConfig->id)->count();
                $activeProducts = \App\Models\SupplierProduct::where('supplier_id', $this->supplierConfig->id)->where('status', 'active')->count();
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
        <a wire:navigate href="{{ route('dashboard') }}"
            class="hover:text-zinc-900 dark:hover:text-zinc-100">Dashboard</a>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span>Suppliers</span>
        <flux:icon.chevron-right class="w-4 h-4" />
        <span class="text-zinc-900 dark:text-zinc-100">OnoyStore</span>
    </div>

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div>
                <flux:heading size="xl">OnoyStore</flux:heading>
                <flux:subheading>Konfigurasi supplier produk game voucher</flux:subheading>
            </div>
        </div>
        <flux:badge color="purple" size="lg" icon="puzzle-piece">Game Products</flux:badge>
    </div>

    <!-- User Info Card -->
    @if ($userInfo)
        <flux:card class="mb-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div
                            class="flex items-center justify-center w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                            <flux:icon.user class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <flux:heading size="lg">{{ $userInfo['name'] }}</flux:heading>
                            <flux:subheading class="text-xs">{{ $userInfo['email'] }}</flux:subheading>
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
                            <flux:subheading class="text-xs mb-1">Role</flux:subheading>
                            <flux:badge color="purple" size="sm">{{ $userInfo['role'] }}</flux:badge>
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
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Status
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">
                        @if ($is_active)
                            <flux:badge color="lime" icon="check-circle">Aktif</flux:badge>
                        @else
                            <flux:badge color="zinc" icon="x-circle">Nonaktif</flux:badge>
                        @endif
                    </flux:heading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.signal class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Saldo
                    </flux:heading>
                    <flux:heading size="2xl" class="mt-2">
                        @if ($userInfo !== null)
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
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Produk
                    </flux:heading>
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
                <flux:input wire:model="api_url" label="API URL" type="url" placeholder="https://api.onoystore.com"
                    required />
                <flux:error name="api_url" />
                <flux:subheading class="text-xs mt-1">Endpoint API OnoyStore</flux:subheading>
            </div>

            <div>
                <flux:input wire:model="api_key" label="API Key" type="text" placeholder="Masukkan API Key OnoyStore"
                    required />
                <flux:error name="api_key" />
                <flux:subheading class="text-xs mt-1">API Key dari dashboard OnoyStore</flux:subheading>
            </div>

            <div>
                <flux:input wire:model="secret" label="Secret Key" type="password"
                    placeholder="Masukkan Secret Key (opsional)" />
                <flux:error name="secret" />
                <flux:subheading class="text-xs mt-1">Secret key untuk validasi webhook (opsional)</flux:subheading>
            </div>

            <flux:separator />

            <div>
                <flux:checkbox wire:model="is_active" label="Aktifkan Supplier" />
                <flux:subheading class="text-xs mt-1">Centang untuk mengaktifkan OnoyStore sebagai supplier
                </flux:subheading>
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
