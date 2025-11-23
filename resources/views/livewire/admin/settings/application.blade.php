<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Application Configuration')] class extends Component {
    public $app_name;
    public $app_url;
    public $app_description;
    public $contact_email;
    public $contact_phone;
    public $contact_whatsapp;
    public $contact_address;
    public $meta_keywords;
    public $meta_description;
    public $maintenance_mode = false;
    public $maintenance_message;

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->app_name = config('app.name', 'Warung Gaming');
        $this->app_url = config('app.url');
        $this->app_description = env('APP_DESCRIPTION', '');
        $this->contact_email = env('CONTACT_EMAIL', '');
        $this->contact_phone = env('CONTACT_PHONE', '');
        $this->contact_whatsapp = env('CONTACT_WHATSAPP', '');
        $this->contact_address = env('CONTACT_ADDRESS', '');
        $this->meta_keywords = env('META_KEYWORDS', '');
        $this->meta_description = env('META_DESCRIPTION', '');
        $this->maintenance_mode = env('APP_MAINTENANCE', false);
        $this->maintenance_message = env('MAINTENANCE_MESSAGE', '');
    }

    public function saveSettings()
    {
        $this->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'contact_whatsapp' => 'nullable|string|max:20',
        ]);

        // Update .env file
        $this->updateEnvFile([
            'APP_NAME' => $this->app_name,
            'APP_URL' => $this->app_url,
            'APP_DESCRIPTION' => $this->app_description,
            'CONTACT_EMAIL' => $this->contact_email,
            'CONTACT_PHONE' => $this->contact_phone,
            'CONTACT_WHATSAPP' => $this->contact_whatsapp,
            'CONTACT_ADDRESS' => $this->contact_address,
            'META_KEYWORDS' => $this->meta_keywords,
            'META_DESCRIPTION' => $this->meta_description,
            'APP_MAINTENANCE' => $this->maintenance_mode ? 'true' : 'false',
            'MAINTENANCE_MESSAGE' => $this->maintenance_message,
        ]);

        session()->flash('message', 'Application settings updated successfully!');

        // Reload settings
        $this->loadSettings();
    }

    protected function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $value = str_replace('"', '\"', $value);
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}=\"{$value}\"";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    public function clearCache()
    {
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');

        session()->flash('message', 'Cache cleared successfully!');
        $this->loadSettings();
    }
}; ?>

<div class="py-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-2">Application Configuration</flux:heading>
            <flux:subheading>Konfigurasi umum aplikasi dan informasi kontak</flux:subheading>
        </div>
        <flux:button wire:click="clearCache" variant="outline" icon="trash">
            Clear Cache
        </flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <flux:card>
            <flux:heading size="lg" class="mb-2">
                <flux:icon.cog-6-tooth class="inline size-6" />
                General Settings
            </flux:heading>

            <flux:separator />

            <div class="space-y-6 mt-6">
                <flux:field>
                    <flux:label>Application Name <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="app_name" placeholder="Warung Gaming" />
                    <flux:error name="app_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Application URL <span class="text-red-500">*</span></flux:label>
                    <flux:input type="url" wire:model="app_url" placeholder="https://example.com" />
                    <flux:error name="app_url" />
                </flux:field>

                <flux:field>
                    <flux:label>Application Description</flux:label>
                    <flux:textarea wire:model="app_description" placeholder="Deskripsi aplikasi..." rows="3" />
                </flux:field>

                <flux:field>
                    <flux:label>Maintenance Mode</flux:label>
                    <flux:switch wire:model.live="maintenance_mode" />
                    <flux:description>{{ $maintenance_mode ? 'Enabled' : 'Disabled' }}</flux:description>
                </flux:field>

                @if($maintenance_mode)
                    <flux:field>
                        <flux:label>Maintenance Message</flux:label>
                        <flux:textarea wire:model="maintenance_message" placeholder="We are currently under maintenance..." rows="3" />
                    </flux:field>
                @endif
            </div>
        </flux:card>

        <!-- Contact Information -->
        <flux:card>
            <flux:heading size="lg" class="mb-2">
                <flux:icon.envelope class="inline size-6" />
                Contact Information
            </flux:heading>

            <flux:separator />

            <div class="space-y-6 mt-6">
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="contact_email" placeholder="admin@example.com" />
                    <flux:error name="contact_email" />
                </flux:field>

                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="contact_phone" placeholder="021-12345678" />
                    <flux:error name="contact_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>WhatsApp</flux:label>
                    <flux:input wire:model="contact_whatsapp" placeholder="628123456789" />
                    <flux:error name="contact_whatsapp" />
                </flux:field>

                <flux:field>
                    <flux:label>Address</flux:label>
                    <flux:textarea wire:model="contact_address" placeholder="Alamat lengkap..." rows="3" />
                </flux:field>
            </div>
        </flux:card>

        <!-- SEO Settings -->
        <flux:card class="lg:col-span-2">
            <flux:heading size="lg" class="mb-2">
                <flux:icon.magnifying-glass class="inline size-6" />
                SEO Settings
            </flux:heading>

            <flux:separator />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <flux:field>
                    <flux:label>Meta Keywords</flux:label>
                    <flux:input wire:model="meta_keywords" placeholder="topup, game, mobile legends, etc" />
                    <flux:description>Pisahkan dengan koma</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Meta Description</flux:label>
                    <flux:textarea wire:model="meta_description" placeholder="Deskripsi untuk search engine..." rows="3" />
                    <flux:description>Maksimal 160 karakter</flux:description>
                </flux:field>
            </div>
        </flux:card>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end gap-3 mt-6">
        <flux:button wire:click="loadSettings" variant="ghost" icon="arrow-path">
            Reset
        </flux:button>
        <flux:button wire:click="saveSettings" variant="primary" icon="check">
            Save Settings
        </flux:button>
    </div>
</div>
