<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.sidebar'), Title('Application Settings')]
class extends Component {
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

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a>Settings</a></li>
            <li>Application</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-primary badge-lg">SETTINGS</span>
                Application Configuration
            </h1>
            <p class="text-base-content/60 mt-2">Konfigurasi umum aplikasi dan informasi kontak</p>
        </div>
        <button wire:click="clearCache" class="btn btn-outline gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Clear Cache
        </button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    General Settings
                </h2>
                <div class="divider"></div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Application Name</span>
                    </label>
                    <input type="text" wire:model="app_name" class="input input-bordered" placeholder="Warung Gaming" />
                    @error('app_name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Application URL</span>
                    </label>
                    <input type="url" wire:model="app_url" class="input input-bordered" placeholder="https://example.com" />
                    @error('app_url') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Application Description</span>
                    </label>
                    <textarea wire:model="app_description" class="textarea textarea-bordered h-24" placeholder="Deskripsi aplikasi..."></textarea>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input type="checkbox" wire:model="maintenance_mode" class="toggle toggle-warning" />
                        <span class="label-text font-semibold">Maintenance Mode</span>
                    </label>
                </div>

                @if($maintenance_mode)
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Maintenance Message</span>
                        </label>
                        <textarea wire:model="maintenance_message" class="textarea textarea-bordered h-20" placeholder="We are currently under maintenance..."></textarea>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contact Information
                </h2>
                <div class="divider"></div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Email</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-sm btn-neutral">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input type="email" wire:model="contact_email" class="input input-bordered join-item w-full" placeholder="admin@example.com" />
                    </div>
                    @error('contact_email') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Phone</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-sm btn-neutral">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </span>
                        <input type="text" wire:model="contact_phone" class="input input-bordered join-item w-full" placeholder="021-12345678" />
                    </div>
                    @error('contact_phone') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">WhatsApp</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item btn btn-sm btn-success text-white">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </span>
                        <input type="text" wire:model="contact_whatsapp" class="input input-bordered join-item w-full" placeholder="628123456789" />
                    </div>
                    @error('contact_whatsapp') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Address</span>
                    </label>
                    <textarea wire:model="contact_address" class="textarea textarea-bordered h-20" placeholder="Alamat lengkap..."></textarea>
                </div>
            </div>
        </div>

        <!-- SEO Settings -->
        <div class="card bg-base-100 shadow-xl lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    SEO Settings
                </h2>
                <div class="divider"></div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Meta Keywords</span>
                        </label>
                        <input type="text" wire:model="meta_keywords" class="input input-bordered" placeholder="topup, game, mobile legends, etc" />
                        <label class="label">
                            <span class="label-text-alt text-base-content/60">Pisahkan dengan koma</span>
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Meta Description</span>
                        </label>
                        <textarea wire:model="meta_description" class="textarea textarea-bordered h-24" placeholder="Deskripsi untuk search engine..."></textarea>
                        <label class="label">
                            <span class="label-text-alt text-base-content/60">Maksimal 160 karakter</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end gap-3 mt-6">
        <button wire:click="loadSettings" class="btn btn-ghost gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Reset
        </button>
        <button wire:click="saveSettings" class="btn btn-primary gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Save Settings
        </button>
    </div>
</div>
