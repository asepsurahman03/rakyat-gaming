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
    // public $maintenance_mode = false;
    // public $maintenance_message;

    // Tracking & Analytics
    public $meta_pixel_code;
    public $meta_pixel_enabled = false;
    public $google_tag_code;
    public $google_tag_enabled = false;
    public $tiktok_pixel_code;
    public $tiktok_pixel_enabled = false;
    public $custom_head_code;
    public $custom_head_enabled = false;

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->app_name = config('app.name', 'Rakyat Gaming');
        $this->app_url = config('app.url');
        $this->app_description = env('APP_DESCRIPTION', '');
        $this->contact_email = env('CONTACT_EMAIL', '');
        $this->contact_phone = env('CONTACT_PHONE', '');
        $this->contact_whatsapp = env('CONTACT_WHATSAPP', '');
        $this->contact_address = env('CONTACT_ADDRESS', '');
        $this->meta_keywords = env('META_KEYWORDS', '');
        $this->meta_description = env('META_DESCRIPTION', '');
        // $this->maintenance_mode = env('APP_MAINTENANCE', false);
        // $this->maintenance_message = env('MAINTENANCE_MESSAGE', '');

        // Load tracking codes from database
        $this->meta_pixel_code = \App\Models\Setting::get('meta_pixel_code', '');
        $this->meta_pixel_enabled = \App\Models\Setting::get('meta_pixel_enabled', false);
        $this->meta_pixel_enabled = $this->meta_pixel_enabled === '1' ;
        $this->google_tag_code = \App\Models\Setting::get('google_tag_code', '');
        $this->google_tag_enabled = \App\Models\Setting::get('google_tag_enabled', false);
        $this->google_tag_enabled = $this->google_tag_enabled === '1' ;
        $this->tiktok_pixel_code = \App\Models\Setting::get('tiktok_pixel_code', '');
        $this->tiktok_pixel_enabled = \App\Models\Setting::get('tiktok_pixel_enabled', false);
        $this->tiktok_pixel_enabled = $this->tiktok_pixel_enabled === '1' ;
        $this->custom_head_code = \App\Models\Setting::get('custom_head_code', '');
        $this->custom_head_enabled = \App\Models\Setting::get('custom_head_enabled', false);
        $this->custom_head_enabled = $this->custom_head_enabled === '1' ;
    }

    public function saveSettings()
    {
        $this->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'contact_whatsapp' => 'nullable|string|max:20',
            'meta_pixel_code' => 'nullable|string|max:10000',
            'google_tag_code' => 'nullable|string|max:10000',
            'tiktok_pixel_code' => 'nullable|string|max:10000',
            'custom_head_code' => 'nullable|string|max:10000',
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
            // 'APP_MAINTENANCE' => $this->maintenance_mode ? 'true' : 'false',
            // 'MAINTENANCE_MESSAGE' => $this->maintenance_message,
        ]);

        // Save tracking codes to database
        \App\Models\Setting::set('meta_pixel_code', $this->meta_pixel_code ?? '');
        \App\Models\Setting::set('meta_pixel_enabled', $this->meta_pixel_enabled ?? false);
        \App\Models\Setting::set('google_tag_code', $this->google_tag_code ?? '');
        \App\Models\Setting::set('google_tag_enabled', $this->google_tag_enabled ?? false);
        \App\Models\Setting::set('tiktok_pixel_code', $this->tiktok_pixel_code ?? '');
        \App\Models\Setting::set('tiktok_pixel_enabled', $this->tiktok_pixel_enabled ?? false);
        \App\Models\Setting::set('custom_head_code', $this->custom_head_code ?? '');
        \App\Models\Setting::set('custom_head_enabled', $this->custom_head_enabled ?? false);

        Flux::toast(
            heading: 'Settings saved successfully!',
            text: 'Application settings have been updated.',
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );

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

        Flux::toast(
            heading: 'Cache cleared successfully!',
            text: 'Application cache has been cleared.',
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );
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
                    <flux:input wire:model="app_name" placeholder="Rakyat Gaming" />
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
                    <flux:input.group>
                        <flux:input.group.prefix>+62</flux:input.group.prefix>
                        <flux:input wire:model="contact_phone" placeholder="021-12345678" />
                    </flux:input.group>
                    <flux:error name="contact_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>WhatsApp</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>+62</flux:input.group.prefix>
                        <flux:input wire:model="contact_whatsapp" placeholder="628123456789" />
                    </flux:input.group>
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

        <!-- Tracking & Analytics -->
        <flux:card class="lg:col-span-2">
            <flux:heading size="lg" class="mb-2">
                <flux:icon.chart-bar class="inline size-6" />
                Tracking & Analytics
            </flux:heading>

            <flux:separator />

            <flux:subheading class="mt-4 mb-4">
                Kelola kode tracking dari berbagai platform media sosial dan analytics. Kode akan disisipkan di section &lt;head&gt; semua halaman.
            </flux:subheading>

            <div class="space-y-8 mt-6">
                <!-- Meta Pixel -->
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="base" class="mb-1">Meta Pixel (Facebook)</flux:heading>
                            <flux:subheading>Tracking code untuk Facebook Ads dan Meta Business Suite</flux:subheading>
                        </div>
                        <flux:switch wire:model="meta_pixel_enabled" />
                    </div>

                    <flux:field>
                        <flux:label>Meta Pixel Code</flux:label>
                        <flux:textarea
                            wire:model="meta_pixel_code"
                            placeholder="<!-- Meta Pixel Code -->&#10;<script>&#10;!function(f,b,e,v,n,t,s)&#10;{if(f.fbq)return;n=f.fbq=function(){n.callMethod?&#10;n.callMethod.apply(n,arguments):n.queue.push(arguments)};&#10;...&#10;</script>"
                            rows="6"
                            class="font-mono text-sm"
                        />
                        <flux:description>Paste kode Meta Pixel dari Facebook Events Manager</flux:description>
                        <flux:error name="meta_pixel_code" />
                    </flux:field>

                    @if($meta_pixel_code)
                    <div class="mt-3">
                        <flux:label>Preview</flux:label>
                        <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded p-3 font-mono text-xs overflow-x-auto">
                            <pre class="whitespace-pre-wrap break-words">{{ $meta_pixel_code }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Google Tag Manager / Analytics -->
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="base" class="mb-1">Google Tag Manager / Analytics</flux:heading>
                            <flux:subheading>Tracking code untuk Google Analytics atau Tag Manager</flux:subheading>
                        </div>
                        <flux:switch wire:model="google_tag_enabled" />
                    </div>

                    <flux:field>
                        <flux:label>Google Tag Code</flux:label>
                        <flux:textarea
                            wire:model="google_tag_code"
                            placeholder="<!-- Google tag (gtag.js) -->&#10;<script async src='https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX'></script>&#10;<script>&#10;  window.dataLayer = window.dataLayer || [];&#10;  function gtag(){dataLayer.push(arguments);}&#10;  gtag('js', new Date());&#10;  gtag('config', 'G-XXXXXXXXXX');&#10;</script>"
                            rows="6"
                            class="font-mono text-sm"
                        />
                        <flux:description>Paste kode dari Google Analytics atau Tag Manager</flux:description>
                        <flux:error name="google_tag_code" />
                    </flux:field>

                    @if($google_tag_code)
                    <div class="mt-3">
                        <flux:label>Preview</flux:label>
                        <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded p-3 font-mono text-xs overflow-x-auto">
                            <pre class="whitespace-pre-wrap break-words">{{ $google_tag_code }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- TikTok Pixel -->
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="base" class="mb-1">TikTok Pixel</flux:heading>
                            <flux:subheading>Tracking code untuk TikTok Ads Manager</flux:subheading>
                        </div>
                        <flux:switch wire:model="tiktok_pixel_enabled" />
                    </div>

                    <flux:field>
                        <flux:label>TikTok Pixel Code</flux:label>
                        <flux:textarea
                            wire:model="tiktok_pixel_code"
                            placeholder="<!-- TikTok Pixel Code -->&#10;<script>&#10;!function (w, d, t) {&#10;  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];&#10;  ttq.methods=[...];&#10;  ...&#10;</script>"
                            rows="6"
                            class="font-mono text-sm"
                        />
                        <flux:description>Paste kode TikTok Pixel dari TikTok Ads Manager</flux:description>
                        <flux:error name="tiktok_pixel_code" />
                    </flux:field>

                    @if($tiktok_pixel_code)
                    <div class="mt-3">
                        <flux:label>Preview</flux:label>
                        <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded p-3 font-mono text-xs overflow-x-auto">
                            <pre class="whitespace-pre-wrap break-words">{{ $tiktok_pixel_code }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Custom Head Scripts -->
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="base" class="mb-1">Custom Head Scripts</flux:heading>
                            <flux:subheading>Script kustom lainnya untuk disisipkan di &lt;head&gt;</flux:subheading>
                        </div>
                        <flux:switch wire:model="custom_head_enabled" />
                    </div>

                    <flux:field>
                        <flux:label>Custom Code</flux:label>
                        <flux:textarea
                            wire:model="custom_head_code"
                            placeholder="<!-- Custom Scripts -->&#10;<script>&#10;  // Your custom code here&#10;</script>"
                            rows="6"
                            class="font-mono text-sm"
                        />
                        <flux:description>Tambahkan script tracking lainnya atau kode custom</flux:description>
                        <flux:error name="custom_head_code" />
                    </flux:field>

                    @if($custom_head_code)
                    <div class="mt-3">
                        <flux:label>Preview</flux:label>
                        <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded p-3 font-mono text-xs overflow-x-auto">
                            <pre class="whitespace-pre-wrap break-words">{{ $custom_head_code }}</pre>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <flux:callout variant="warning" class="mt-6">
                <strong>Peringatan Keamanan:</strong> Hanya administrator yang dapat mengubah tracking codes. Pastikan kode yang dimasukkan berasal dari sumber terpercaya. Kode akan dirender tanpa sanitasi.
            </flux:callout>
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
