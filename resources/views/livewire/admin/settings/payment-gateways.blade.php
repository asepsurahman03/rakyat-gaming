<?php

use Livewire\Volt\Component;
use App\Models\PaymentGateway;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Ipaymu Configuration')] class extends Component {
    public $ipaymuConfig;

    // Form fields
    public string $api_key = '';
    public string $api_secret = ''; // VA Number
    public string $callback_url = '';
    public string $redirect_url = '';
    public bool $is_sandbox = true;
    public bool $is_active = true;

    public function mount(): void
    {
        $this->loadConfig();
    }

    public function loadConfig(): void
    {
        // Ambil konfigurasi Ipaymu pertama atau buat default
        $this->ipaymuConfig = PaymentGateway::where('code', 'ipaymu')->first();

        if ($this->ipaymuConfig) {
            $this->api_key = $this->ipaymuConfig->api_key ?? '';
            $this->api_secret = $this->ipaymuConfig->api_secret ?? ''; // VA number
            $this->callback_url = $this->ipaymuConfig->callback_url ?? '';
            $this->redirect_url = $this->ipaymuConfig->redirect_url ?? '';
            $this->is_sandbox = (bool) $this->ipaymuConfig->is_sandbox;
            $this->is_active = (bool) $this->ipaymuConfig->is_active;
        } else {
            // Set default URLs
            $this->callback_url = route('payment.callback');
            $this->redirect_url = route('payment.return');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'callback_url' => 'required|url',
            'redirect_url' => 'required|url',
            'is_sandbox' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($this->ipaymuConfig) {
            $this->ipaymuConfig->update($validated);
            $message = 'Konfigurasi Ipaymu berhasil diupdate';
        } else {
            PaymentGateway::create([
                'name' => 'Ipaymu',
                'code' => 'ipaymu',
                ...$validated
            ]);
            $message = 'Konfigurasi Ipaymu berhasil disimpan';
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
            'api_key' => 'required',
            'api_secret' => 'required',
        ]);

        $ipaymuService = new \App\Services\IpaymuService();
        $result = $ipaymuService->testConnection();

        if ($result['success']) {
            Flux::toast(
                heading: 'Koneksi Berhasil!',
                text: $result['message'],
                variant: 'success',
                duration: 5000,
                position: 'top-right',
            );
        } else {
            Flux::toast(
                heading: 'Koneksi Gagal',
                text: $result['message'],
                variant: 'danger',
                duration: 5000,
                position: 'top-right',
            );
        }
    }
}; ?>

<div class="py-6">
    <!-- Page Header -->
    <flux:heading size="xl" class="mb-2">Konfigurasi Ipaymu Payment Gateway</flux:heading>
    <flux:subheading class="mb-6">Pengaturan koneksi ke payment gateway Ipaymu untuk memproses pembayaran</flux:subheading>

    <div class="max-w-3xl mx-auto">
        <flux:card>
            <div class="p-6 md:p-8">
                <!-- Status Badge -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <div>
                            <flux:heading size="lg">Ipaymu Payment Gateway</flux:heading>
                            <flux:subheading class="text-sm">Indonesian Payment Solution</flux:subheading>
                        </div>
                    </div>
                    @if($ipaymuConfig)
                        @if($is_active)
                            <flux:badge color="green" size="lg">Active</flux:badge>
                        @else
                            <flux:badge color="red" size="lg">Inactive</flux:badge>
                        @endif
                    @else
                        <flux:badge color="zinc" size="lg">Not Configured</flux:badge>
                    @endif
                </div>

                <flux:separator class="my-6" />

                <!-- Info Callout -->
                @if(!$ipaymuConfig)
                    <flux:callout variant="info" icon="information-circle" class="mb-6">
                        <strong>Belum Ada Konfigurasi</strong> Silakan isi form di bawah untuk mengkonfigurasi koneksi ke Ipaymu.
                    </flux:callout>
                @endif

                <!-- Configuration Form -->
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field class="md:col-span-2">
                            <flux:label>VA Number <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="api_secret" placeholder="Masukkan VA Number dari Ipaymu" required />
                            <flux:error name="api_secret" />
                            <flux:description>VA Number yang didapatkan dari dashboard Ipaymu</flux:description>
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>API Key <span class="text-red-500">*</span></flux:label>
                            <flux:input type="password" wire:model="api_key" placeholder="••••••••••••••••" required />
                            <flux:error name="api_key" />
                            <flux:description>API Key untuk autentikasi ke Ipaymu API</flux:description>
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>Callback URL <span class="text-red-500">*</span></flux:label>
                            <flux:input type="url" wire:model="callback_url" placeholder="{{ route('payment.callback') }}" required readonly />
                            <flux:error name="callback_url" />
                            <flux:description>URL untuk menerima notifikasi status pembayaran dari Ipaymu (POST method)</flux:description>
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>Redirect URL <span class="text-red-500">*</span></flux:label>
                            <flux:input type="url" wire:model="redirect_url" placeholder="{{ route('payment.return') }}" required readonly />
                            <flux:error name="redirect_url" />
                            <flux:description>URL untuk redirect customer setelah pembayaran (GET method)</flux:description>
                        </flux:field>
                    </div>

                    <flux:separator />

                    <div class="space-y-4">
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:label>Mode Sandbox</flux:label>
                                    <flux:description>Aktifkan untuk testing dengan data sandbox Duitku</flux:description>
                                </div>
                                <flux:switch wire:model="is_sandbox" />
                            </div>
                        </flux:field>

                        <flux:field>
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:label>Status Aktif</flux:label>
                                    <flux:description>Aktifkan payment gateway untuk menerima pembayaran</flux:description>
                                </div>
                                <flux:switch wire:model="is_active" />
                            </div>
                        </flux:field>
                    </div>

                    <flux:separator />

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <flux:button type="submit" variant="primary" icon="check" class="flex-1">
                            {{ $ipaymuConfig ? 'Update Konfigurasi' : 'Simpan Konfigurasi' }}
                        </flux:button>
                        <flux:button type="button" wire:click="testConnection" variant="ghost" icon="play" class="flex-1">
                            Test Koneksi
                        </flux:button>
                    </div>
                </form>

                @if($ipaymuConfig)
                    <flux:separator class="my-6" />

                    <!-- Statistics -->
                    <div class="bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg">
                        <flux:heading size="base" class="mb-4">Statistik</flux:heading>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ PaymentGateway::where('code', '!=', 'ipaymu')->count() }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Metode Pembayaran</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ $ipaymuConfig->orderPayments()->count() }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Total Transaksi</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ $is_sandbox ? 'Sandbox' : 'Production' }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Mode</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Additional Info Card -->
        <flux:card class="mt-6">
            <div class="p-6">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div class="flex-1">
                        <flux:heading size="base" class="mb-2">Informasi Penting</flux:heading>
                        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Pastikan VA Number dan API Key sesuai dengan akun Ipaymu Anda</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Callback URL (notifyUrl) dan Redirect URL harus didaftarkan di dashboard Ipaymu</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Gunakan mode Sandbox untuk testing sebelum production</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Metode pembayaran individual (QRIS, VA Bank, COD, dll) dapat dikelola di menu Payment Methods</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</div>
