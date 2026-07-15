<?php

use Livewire\Volt\Component;
use App\Models\PaymentGateway;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Ipaymu Configuration')] class extends Component {
    public $tripayConfig;

    // Form fields
    public string $api_key = '';
    public string $api_secret = ''; // privateKey
    public string $merchant_code = ''; // merchantCode
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
        // Ambil konfigurasi Tripay pertama atau buat default
        $this->tripayConfig = PaymentGateway::where('code', 'tripay')->first();

        if ($this->tripayConfig) {
            $this->api_key = $this->tripayConfig->api_key ?? '';
            $this->api_secret = $this->tripayConfig->api_secret ?? ''; // privateKey
            $this->merchant_code = $this->tripayConfig->merchant_code ?? ''; // merchantCode
            $this->callback_url = $this->tripayConfig->callback_url ?? '';
            $this->redirect_url = $this->tripayConfig->redirect_url ?? '';
            $this->is_sandbox = (bool) $this->tripayConfig->is_sandbox;
            $this->is_active = (bool) $this->tripayConfig->is_active;
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
            'merchant_code' => 'required|string',
            'callback_url' => 'required|url',
            'redirect_url' => 'required|url',
            'is_sandbox' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $paymentGatewayActived = PaymentGateway::where('is_active', true)->where('code', '!=', 'tripay')->first();

        if ($paymentGatewayActived) {
            $paymentGatewayActived->update([
                'is_active' => false,
            ]);
        }

        if ($this->tripayConfig) {
            $this->tripayConfig->update($validated);
            $message = 'Konfigurasi Tripay berhasil diupdate';
        } else {
            PaymentGateway::create([
                'name' => 'Tripay',
                'code' => 'tripay',
                ...$validated
            ]);
            $message = 'Konfigurasi Tripay berhasil disimpan';
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
            'merchant_code' => 'required',
            'is_sandbox' => 'required',
        ]);

        $tripayService = new \App\Services\TripayService();
        $result = $tripayService->testConection(
            $this->api_key,
            $this->api_secret,
            $this->merchant_code,
            $this->is_sandbox
        );

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
    <flux:heading size="xl" class="mb-2">Konfigurasi Tripay Payment Gateway</flux:heading>
    <flux:subheading class="mb-6">Pengaturan koneksi ke payment gateway Tripay untuk memproses pembayaran</flux:subheading>

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
                            <flux:heading size="lg">Tripay Payment Gateway</flux:heading>
                            <flux:subheading class="text-sm">Indonesian Payment Solution</flux:subheading>
                        </div>
                    </div>
                    @if($tripayConfig)
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
                @if(!$tripayConfig)
                    <flux:callout variant="info" icon="information-circle" class="mb-6">
                        <strong>Belum Ada Konfigurasi</strong> Silakan isi form di bawah untuk mengkonfigurasi koneksi ke Tripay.
                    </flux:callout>
                @endif

                <!-- Configuration Form -->
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <flux:field class="md:col-span-2">
                            <flux:label>API Key <span class="text-red-500">*</span></flux:label>
                            <flux:input type="password" wire:model="api_key" placeholder="••••••••••••••••" required />
                            <flux:error name="api_key" />
                            <flux:description>API Key untuk autentikasi ke Tripay API</flux:description>
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>Private Key <span class="text-red-500">*</span></flux:label>
                            <flux:input type="password" wire:model="api_secret" placeholder="••••••••••••••••" required />
                            <flux:error name="api_secret" />
                            <flux:description>Private Key yang didapatkan dari dashboard</flux:description>
                        </flux:field>
                        
                        <flux:field class="md:col-span-2">
                            <flux:label>Merchant Code <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="merchant_code" placeholder="Masukkan Merchant Code" required />
                            <flux:error name="merchant_code" />
                            <flux:description>Merchant Code yang didapatkan dari dashboard</flux:description>
                        </flux:field>

                        <flux:field class="md:col-span-2">
                            <flux:label>Callback URL <span class="text-red-500">*</span></flux:label>
                            <flux:input type="url" wire:model="callback_url" placeholder="{{ route('payment.callback') }}" required readonly />
                            <flux:error name="callback_url" />
                            <flux:description>URL untuk menerima notifikasi status pembayaran dari Tripay (POST method)</flux:description>
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
                                    <flux:description>Aktifkan untuk testing dengan data sandbox</flux:description>
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
                            {{ $tripayConfig ? 'Update Konfigurasi' : 'Simpan Konfigurasi' }}
                        </flux:button>
                        <flux:button type="button" wire:click="testConnection" variant="ghost" icon="play" class="flex-1">
                            Test Koneksi
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:card>
    </div>
</div>
