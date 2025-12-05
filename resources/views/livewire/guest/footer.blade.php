<?php

use App\Models\Setting;
use Livewire\Volt\Component;

new class extends Component {
    public $settings;

    public function mount()
    {
        $this->settings = [
            'site_name' => Setting::get('site_name', config('app.name', 'Warung Gaming')),
            'site_description' => Setting::get('site_description', 'Platform top-up game terpercaya'),
            'contact_email' => Setting::get('contact_email', 'info@warunggaming.com'),
            'contact_phone' => Setting::get('contact_phone', '+62 812-3456-7890'),
            'contact_whatsapp' => Setting::get('contact_whatsapp', '+62 812-3456-7890'),
            'address' => Setting::get('address', 'Jakarta, Indonesia'),
            'facebook' => Setting::get('facebook', '#'),
            'instagram' => Setting::get('instagram', '#'),
            'twitter' => Setting::get('twitter', '#'),
        ];
    }
}; ?>

<footer class="footer">
    <!-- Features Section -->
    <div class="w-full">
        <img src="{{ asset('images/RG BORDER WEBSITE.png') }}" alt="bg-animation" class="mx-auto w-full">
    </div>
    <div class="bg-black text-gray-300">
        <div class="max-w-7xl mx-auto w-full p-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 w-full">
                <!-- Brand Section -->
                <div class="space-y-4">
                    <div>
                        <h3 class="text-2xl font-bold text-primary">{{ $settings['site_name'] }}</h3>
                        <p class="text-sm opacity-70 mt-2">{{ $settings['site_description'] }}</p>
                    </div>
                    <div class="flex gap-3">
                        @if ($settings['facebook'] && $settings['facebook'] !== '#')
                            <a href="{{ $settings['facebook'] }}" target="_blank"
                                class="btn btn-ghost btn-circle btn-sm hover:bg-primary hover:text-primary-content"
                                aria-label="Facebook">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                                    class="w-5 h-5">
                                    <path
                                        d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z" />
                                </svg>
                            </a>
                        @endif
                        @if ($settings['instagram'] && $settings['instagram'] !== '#')
                            <a href="{{ $settings['instagram'] }}" target="_blank"
                                class="btn btn-ghost btn-circle btn-sm hover:bg-primary hover:text-primary-content"
                                aria-label="Instagram">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                                    class="w-5 h-5">
                                    <path
                                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                </svg>
                            </a>
                        @endif
                        @if ($settings['twitter'] && $settings['twitter'] !== '#')
                            <a href="{{ $settings['twitter'] }}" target="_blank"
                                class="btn btn-ghost btn-circle btn-sm hover:bg-primary hover:text-primary-content"
                                aria-label="Twitter">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                                    class="w-5 h-5">
                                    <path
                                        d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-bold text-lg mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ url('/') }}" wire:navigate
                                class="link link-hover opacity-70 hover:opacity-100">Home</a></li>
                        <li><a href="{{ route('cek-transaksi') }}" wire:navigate
                                class="link link-hover opacity-70 hover:opacity-100">Cek Transaksi</a></li>
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">Cara Order</a></li>
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">FAQ</a></li>
                    </ul>
                </div>

                <!-- Products -->
                <div>
                    <h4 class="font-bold text-lg mb-4">Produk</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">Top Up Mobile Legends</a>
                        </li>
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">Top Up Free Fire</a></li>
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">Top Up PUBG Mobile</a>
                        </li>
                        <li><a href="#" class="link link-hover opacity-70 hover:opacity-100">Lihat Semua Game</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="font-bold text-lg mb-4">Hubungi Kami</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary flex-shrink-0 mt-0.5"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <div>
                                <div class="text-xs opacity-60">Email</div>
                                <a href="mailto:{{ $settings['contact_email'] }}"
                                    class="link link-hover">{{ $settings['contact_email'] }}</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary flex-shrink-0 mt-0.5"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <div>
                                <div class="text-xs opacity-60">Telepon</div>
                                <a href="tel:{{ $settings['contact_phone'] }}"
                                    class="link link-hover">{{ $settings['contact_phone'] }}</a>
                            </div>
                        </li>
                        @if ($settings['contact_whatsapp'])
                            <li class="flex items-start gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success flex-shrink-0 mt-0.5"
                                    viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                                <div>
                                    <div class="text-xs opacity-60">WhatsApp</div>
                                    <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $settings['contact_whatsapp']) }}"
                                        target="_blank" class="link link-hover">{{ $settings['contact_whatsapp'] }}</a>
                                </div>
                            </li>
                        @endif
                        @if ($settings['address'])
                            <li class="flex items-start gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary flex-shrink-0 mt-0.5"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <div class="text-xs opacity-60">Alamat</div>
                                    <p class="text-sm">{{ $settings['address'] }}</p>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="flex w-full justify-between border-t border-gray-700 mt-10 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm opacity-70">
                    <p>&copy; 2025 {{ $settings['site_name'] }}. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</footer>
