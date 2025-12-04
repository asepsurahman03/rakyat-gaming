<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use App\Services\DigiflazzService;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Digiflazz Configuration')] class extends Component {
    public $digiflazzConfig;

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
        $this->digiflazzConfig = Supplier::firstOrCreate(
            ['code' => 'digiflazz'],
            [
                'name' => 'Digiflazz',
                'code' => 'digiflazz',
                'api_url' => 'https://api.digiflazz.com/v1',
                'is_active' => true,
            ]
        );

        if ($this->digiflazzConfig) {
            $this->username = $this->digiflazzConfig->username ?? '';
            $this->api_url = $this->digiflazzConfig->api_url ?? 'https://api.digiflazz.com/v1';
            $this->api_key = $this->digiflazzConfig->api_key ?? '';
            $this->secret = $this->digiflazzConfig->secret ?? '';
            $this->is_active = (bool) $this->digiflazzConfig->is_active;
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

        if ($this->digiflazzConfig) {
            $this->digiflazzConfig->update($validated);
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
            // Generate signature: md5(username + apiKey + depo)
            $sign = md5($this->username . $this->api_key . 'depo');

            $payload = [
                'username' => $this->username,
                'cmd' => 'deposit',
                'sign' => $sign,
            ];

            $endpoint = rtrim($this->api_url, '/') . '/cek-saldo';

            $response = \Illuminate\Support\Facades\Http::asJson()
                ->post($endpoint, $payload);

            $body = $response->json();

            if ($response->ok() && isset($body['data'])) {
                $saldo = number_format($body['data']['deposit'] ?? 0, 0, ',', '.');
                Flux::toast(
                    heading: 'Koneksi Berhasil',
                    text: 'Saldo Digiflazz: Rp ' . $saldo,
                    variant: 'success',
                    duration: 5000,
                    position: 'top-right',
                );
            } else {
                Flux::toast(
                    heading: 'Koneksi Gagal',
                    text: 'Gagal terhubung ke Digiflazz: ' . ($body['data']['message'] ?? 'Invalid credentials'),
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
    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('dashboard') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Konfigurasi Digiflazz</flux:heading>
            <flux:subheading>Pengaturan koneksi API Digiflazz untuk supplier produk digital</flux:subheading>
        </div>
    </div>

    <flux:card>
        <!-- Info Section -->
        @if($digiflazzConfig)
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($digiflazzConfig->supplierProducts()->count()) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Total Produk</div>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($digiflazzConfig->supplierProducts()->where('status', 'active')->count()) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Produk Aktif</div>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-2xl font-bold {{ $is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Status Koneksi</div>
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
                    {{ $digiflazzConfig ? 'Update Konfigurasi' : 'Simpan Konfigurasi' }}
                </flux:button>
                <flux:button type="button" wire:click="testConnection" variant="ghost" icon="play" class="flex-1">
                    Test Koneksi
                </flux:button>
            </div>
        </form>

        @if($digiflazzConfig)
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
