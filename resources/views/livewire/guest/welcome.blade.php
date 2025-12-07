<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\Carousel;
use App\Models\Game;

new #[Layout('components.layouts.guest'), Title('Welcome - Top Up Game Termurah & Tercepat')] class extends Component {
    public $carousels = [];
    public $currentSlide = 0;
    public $popularGames = [];
    public $searchQuery = '';
    public $categories = [];
    public $allGames = [];
    public $filterGameId = '';
    public $latestPosts = [];
    public $flazzSellProducts = [];
    public $loading = false;
    public $timeRemaining = '';
    public $event = null;

    public function mount()
    {
        $this->loadCarousels();
        $this->loadFlazzSellProducts();
        $this->loadPopularGames();
        $this->loadCategories();
        $this->loadAllGames();
        $this->loadLatestPosts();
    }

    public function loadCarousels()
    {
        $this->carousels = Carousel::all();
    }

    public function loadFlazzSellProducts()
    {
        // Get active FlazzSell events with their products
        $activeEvents = \App\Models\FlazzSell::active()
            ->with(['flazzSellProducts.product.game'])
            ->get();

        $event =
            $activeEvents
                ->filter(function ($e) {
                    return isset($e->start_at) && $e->start_at->gt(now());
                })
                ->sortBy('start_at')
                ->first() ?? $activeEvents->first();
        if ($event) {
            $this->timeRemaining = $event->getTimeRemaining();
            $this->event = $event;
        }

        // Collect all products from all active events
        $products = collect();
        foreach ($activeEvents as $event) {
            foreach ($event->flazzSellProducts as $flazzSellProduct) {
                if ($flazzSellProduct->isAvailable() && $flazzSellProduct->product->is_active) {
                    $products->push([
                        'event' => $event,
                        'flazzSellProduct' => $flazzSellProduct,
                        'product' => $flazzSellProduct->product,
                    ]);
                }
            }
        }

        $this->flazzSellProducts = $products->take(6);
    }

    public function loadPopularGames()
    {
        // Get popular games based on product sales (order count)
        $this->popularGames = Game::where('is_active', true)
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
                'orders', // Count total orders for this game
            ])
            ->having('products_count', '>', 0)
            ->orderBy('orders_count', 'desc') // Sort by most orders
            ->orderBy('name', 'asc')
            ->limit(8)
            ->get();
    }

    public function loadCategories()
    {
        $this->loading = true;
        // Get unique game names for category display
        $this->categories = Game::where('is_active', true)
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->limit(8)
            ->get();
        $this->loading = false;
    }

    public function loadAllGames()
    {
        $this->loading = true;
        // Get all games for filter section
        $this->allGames = Game::where('is_active', true)
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->get();
        $this->loading = false;
    }

    public function loadLatestPosts()
    {
        // Get latest 3 published posts
        $this->latestPosts = \App\Models\Post::with(['game', 'author'])
            ->published()
            ->latest()
            ->limit(3)
            ->get();
    }

    public function nextSlide()
    {
        if (count($this->carousels) > 0) {
            $this->currentSlide = ($this->currentSlide + 1) % count($this->carousels);
        }
    }

    public function prevSlide()
    {
        if (count($this->carousels) > 0) {
            $this->currentSlide = ($this->currentSlide - 1 + count($this->carousels)) % count($this->carousels);
        }
    }

    public function goToSlide($index)
    {
        $this->currentSlide = $index;
    }

    public function search()
    {
        // Implement search functionality
    }
}; ?>

<div>
    <!-- Hero Carousel Section -->
    @if (count($carousels) > 0)
        <style>
            /* Neon animated border wrapper */
            .neon-border {
                background: linear-gradient(90deg,
                        rgba(255, 215, 0, 0.95),
                        rgba(236, 235, 229, 0.95),
                        rgba(255, 180, 0, 0.95),
                        rgba(253, 251, 251, 0.95));
                background-size: 300% 100%;
                animation: neon-slide 6s linear infinite;
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.08), inset 0 0 18px rgba(99, 102, 241, 0.03);
            }

            .neon-border:hover {
                animation-play-state: paused;
            }

            /* optional: pause on hover */
            @keyframes neon-slide {
                0% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }

                100% {
                    background-position: 0% 50%;
                }
            }

            /* make inner container crisp when using small border padding */
            .neon-inner {
                border-radius: calc(1rem - 2px);
                /* adjust to match rounded-2xl minus border */
            }

            /* ensure Alpine x-cloak hides until ready */
            [x-cloak] {
                display: none !important;
            }

            /* Smooth crossfade carousel */
            .carousel-container {
                position: relative;
            }

            .carousel-slide {
                opacity: 0;
                transition: opacity 800ms cubic-bezier(0.4, 0, 0.2, 1);
                display: none;
            }

            .carousel-slide.active {
                opacity: 1;
                display: block;
            }
        </style>

        {{-- section carousel --}}
        <div class="bg-gradient-to-bl from-[#7E0000] to-[#470000] px-4 md:px-6 py-10 md:py-20 relative">
            <!-- Particle Canvas - wire:ignore to prevent Livewire from touching it -->
            <div wire:ignore class="absolute inset-0 w-full h-full pointer-events-none z-0">
                <canvas id="particleCanvas" class="w-full h-full"></canvas>
            </div>

            {{-- carousel --}}
            <div class="relative max-w-7xl mx-auto rounded-2xl z-10">
                <!-- neon border: uses small padding so inner content keeps its bg -->
                <div class="neon-border rounded-2xl p-[2px]">
                    <div class="relative rounded-2xl neon-inner overflow-hidden bg-zinc-900 dark:bg-zinc-800"
                        x-data="{ currentSlide: @entangle('currentSlide') }" x-cloak>
                        <!-- Carousel Container -->
                        <div class="relative w-full carousel-container">
                            @foreach ($carousels as $index => $carousel)
                                <div class="carousel-slide" :class="{ 'active': currentSlide === {{ $index }} }">
                                    <div class="relative w-full">
                                        @if ($carousel->image_path)
                                            <img src="{{ Storage::url($carousel->image_path) }}"
                                                alt="{{ $carousel->title }}" class="w-full h-auto">
                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-zinc-900/80 via-transparent to-transparent">
                                            </div>
                                        @else
                                            <div
                                                class="w-full aspect-video bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-32 h-32 text-white opacity-50">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Navigation Buttons (improved placement & contrast) -->
                        <button wire:click="prevSlide" aria-label="Previous slide"
                            class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full shadow-xl z-20 w-10 h-10 bg-white/80 dark:bg-zinc-900/70 flex items-center justify-center hover:scale-105 transition transform">
                            <x-icon name="chevron-left" class="w-5 h-5 text-zinc-700 dark:text-zinc-200" />
                        </button>

                        <button wire:click="nextSlide" aria-label="Next slide"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full shadow-xl z-20 w-10 h-10 bg-white/80 dark:bg-zinc-900/70 flex items-center justify-center hover:scale-105 transition transform">
                            <x-icon name="chevron-right" class="w-5 h-5 text-zinc-700 dark:text-zinc-200" />
                        </button>

                        <!-- Indicators -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                            @foreach ($carousels as $index => $carousel)
                                <button wire:click="goToSlide({{ $index }})"
                                    class="rounded-full transition-all duration-300 focus:outline-none"
                                    :class="currentSlide === {{ $index }} ? 'bg-indigo-600 w-8 h-2 rounded-full' :
                                        'bg-white/40 w-3 h-3'"
                                    aria-label="Slide {{ $index + 1 }}"></button>
                            @endforeach
                        </div>

                        <!-- Auto Play -->
                        <div x-data="{
                            autoplayInterval: null,
                            startAutoplay() {
                                this.autoplayInterval = setInterval(() => {
                                    $wire.nextSlide();
                                }, 5000);
                            },
                            stopAutoplay() {
                                if (this.autoplayInterval) {
                                    clearInterval(this.autoplayInterval);
                                    this.autoplayInterval = null;
                                }
                            }
                        }" x-init="startAutoplay()" @mouseenter="stopAutoplay()"
                            @mouseleave="startAutoplay()" class="absolute inset-0 pointer-events-none"></div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Hero Placeholder if no carousel -->
        <div class="min-h-[300px] bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
            <div class="text-center max-w-2xl px-4">
                <flux:heading size="xl" class="text-white mb-3">{{ config('app.name') }} - Top Up Game Termurah &
                    Tercepat</flux:heading>
                <flux:subheading class="text-white/90 mb-4">Ribuan produk digital tersedia</flux:subheading>
                <flux:button variant="primary">Mulai Belanja</flux:button>
            </div>
        </div>
    @endif

    <!-- FlazzSell Section -->
    @if (count($flazzSellProducts) > 0)
        <div class="bg-zinc-900 py-10 md:py-20">
            <div class="container mx-auto px-4 max-w-7xl">
                <div class="w-full sm:flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <flux:icon.fire class="w-8 h-8 text-red-500 animate-pulse" />
                            <flux:heading size="xl" class="text-white">{{ __('messages.flash_sale') }} - {{ __('messages.discount') }}!</flux:heading>
                        </div>
                        <flux:text class="text-white/90 mb-6">{{ __('messages.flash_sale_description') }}
                        </flux:text>
                    </div>
                    <!-- Countdown Timer -->
                    <div class="pt-3">
                        <div class="flex gap-1.5" x-data="{
                            days: {{ $timeRemaining['days'] }},
                            hours: {{ $timeRemaining['hours'] }},
                            minutes: {{ $timeRemaining['minutes'] }},
                            seconds: {{ $timeRemaining['seconds'] }},
                            endTime: new Date('{{ $event->end_at->toIso8601String() }}').getTime()
                        }" x-init="setInterval(() => {
                            let now = new Date().getTime();
                            let distance = endTime - now;

                            if (distance < 0) {
                                days = hours = minutes = seconds = 0;
                                return;
                            }

                            days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        }, 1000)">
                            <div class="flex-1 rounded px-2 py-1.5 text-center">
                                <div class="font-bold text-zinc-50 text-2xl md:text-4xl" x-text="days"></div>
                                <div class="text-xs text-zinc-300">Hari</div>
                            </div>
                            <h1 class="font-bold text-zinc-50 text-2xl md:text-4xl">:</h1>
                            <div class="flex-1 rounded px-2 py-1.5 text-center">
                                <div class="font-bold text-zinc-50 text-2xl md:text-4xl" x-text="hours"></div>
                                <div class="text-xs text-zinc-300">Jam</div>
                            </div>
                            <h1 class="font-bold text-zinc-50 text-2xl md:text-4xl">:</h1>
                            <div class="flex-1 rounded px-2 py-1.5 text-center">
                                <div class="font-bold text-zinc-50 text-2xl md:text-4xl" x-text="minutes"></div>
                                <div class="text-xs text-zinc-300">Menit</div>
                            </div>
                            <h1 class="font-bold text-zinc-50 text-2xl md:text-4xl">:</h1>
                            <div class="flex-1 rounded px-2 py-1.5 text-center">
                                <div class="font-bold text-zinc-50 text-2xl md:text-4xl" x-text="seconds"></div>
                                <div class="text-xs text-zinc-300">Detik</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Scrollable Flash Sale Products -->
                <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory hide-scrollbar">
                    @foreach ($flazzSellProducts as $item)
                        @php
                            $event = $item['event'];
                            $flazzSellProduct = $item['flazzSellProduct'];
                            $product = $item['product'];
                            $discountedPrice = $flazzSellProduct->getDiscountedPrice();
                            $timeRemaining = $event->getTimeRemaining();
                        @endphp
                        <a href="{{ route('game.detail', $product->game->slug) }}" wire:navigate
                            class="group flex-shrink-0 w-[280px] sm:w-[320px] snap-start">
                            <div
                                class="relative border-2 border-red-400 dark:border-red-600 rounded-xl overflow-hidden hover:shadow-2xl hover:shadow-red-500/50 transition-all duration-300 h-full">

                                <!-- Product Image with Overlay -->
                                <div class="relative aspect-video overflow-hidden bg-zinc-900">
                                    @if ($product->game->image)
                                        <img src="{{ Storage::url($product->game->image) }}"
                                            alt="{{ $product->game->name }}"
                                            class="w-full h-full object-cover object-top group-hover:scale-110 transition-transform duration-300">
                                    @else
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-red-600 to-orange-600 flex items-center justify-center">
                                            <flux:icon.fire class="w-16 h-16 text-white opacity-50" />
                                        </div>
                                    @endif

                                    <!-- Dark Gradient Overlay -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent">
                                    </div>

                                    <!-- Event Badge -->
                                    <div class="absolute top-3 right-3 z-10">
                                        <div
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg mb-1.5">
                                            {{ $event->name }}
                                        </div>
                                        <div class="flex justify-end">
                                            <div
                                                class="bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg animate-pulse">
                                                -{{ number_format($flazzSellProduct->discount_percentage, 0) }}%
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Info Overlay -->
                                    <div class="absolute bottom-0 left-0 right-0 p-4 z-10">
                                        <!-- Product Name -->
                                        <div class="mb-3">
                                            <h3 class="text-sm font-bold text-white line-clamp-1 drop-shadow-lg">
                                                {{ $product->game->name }}
                                            </h3>
                                            <p class="text-xs text-zinc-200 line-clamp-1 mt-1 drop-shadow-lg">
                                                {{ $product->name }}
                                            </p>
                                        </div>

                                        <!-- Price -->
                                        <div class="mb-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs text-zinc-300 line-through drop-shadow-lg">
                                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                                </span>
                                                <span
                                                    class="text-xs bg-green-600 text-white px-2 py-0.5 rounded-full font-semibold shadow-lg">
                                                    Hemat
                                                    {{ number_format($flazzSellProduct->getSavings(), 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="text-2xl font-bold text-yellow-400 drop-shadow-lg">
                                                Rp {{ number_format($discountedPrice, 0, ',', '.') }}
                                            </div>
                                        </div>

                                        <!-- Quota Info -->
                                        @if ($flazzSellProduct->quota)
                                            <div class="bg-black/40 backdrop-blur-sm rounded-lg p-2">
                                                <div class="flex items-center justify-between text-xs mb-1">
                                                    <span class="text-white font-medium">Stok tersisa:</span>
                                                    <span
                                                        class="font-bold text-orange-400">{{ $flazzSellProduct->quota - $flazzSellProduct->sold_count }}/{{ $flazzSellProduct->quota }}</span>
                                                </div>
                                                <div class="w-full bg-zinc-700 rounded-full h-2">
                                                    <div class="bg-gradient-to-r from-orange-500 to-red-600 h-2 rounded-full transition-all shadow-lg"
                                                        style="width: {{ (($flazzSellProduct->quota - $flazzSellProduct->sold_count) / $flazzSellProduct->quota) * 100 }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="bg-gradient-to-br from-[#470000] to-[#6d0000]">
        <!-- Popular Games Section (Berdasarkan Penjualan Terbanyak) -->
        <div class="container mx-auto px-4 max-w-7xl py-10 md:py-20">
            <flux:heading size="xl" class="mb-2 text-white">🔥 {{ __('messages.popular_games') }}</flux:heading>
            <flux:text class="text-white/90 mb-6">{{ __('messages.search_game_placeholder') }}
            </flux:text>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @forelse($popularGames as $game)
                    <a href="{{ route('game.detail', $game->slug) }}" wire:navigate class="group"
                        x-data="{ showInfo: false }" @touchstart="showInfo = true"
                        @touchend="setTimeout(() => showInfo = false, 2000)">
                        <div
                            class="relative overflow-hidden rounded-lg cursor-pointer transition-shadow duration-300 hover:shadow-xl">
                            <div class="w-full h-28 md:h-50 bg-zinc-900 overflow-hidden">
                                @if ($game->image)
                                    <img src="{{ Storage::url($game->image) }}" alt="{{ $game->name }}"
                                        class="w-full h-full object-cover object-top transform transition duration-500 ease-in-out group-hover:scale-105" />
                                @else
                                    <div
                                        class="w-full h-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                                        <x-icon name="puzzle-piece" class="w-16 h-16 text-white opacity-50" />
                                    </div>
                                @endif
                            </div>

                            <!-- Hover overlay: fades up and reveals name + provider -->
                            <div class="absolute inset-0 flex items-end">
                                <div class="w-full p-3 transition-all duration-300 transform translate-y-3 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 bg-gradient-to-t from-yellow-400/70 to-transparent"
                                    :class="showInfo && 'translate-y-0 opacity-100'">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-white text-sm font-semibold line-clamp-1">
                                                {{ $game->name }}</h3>
                                            <p class="text-zinc-200 text-xs line-clamp-1 mt-1">
                                                {{ $game->provider ?? config('app.name') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12">
                        <x-icon name="puzzle-piece" class="w-16 h-16 text-white/30 mx-auto mb-4" />
                        <flux:heading size="md" class="text-white mb-2">Belum Ada Game Populer</flux:heading>
                        <flux:subheading class="text-white/80">Game akan segera ditambahkan</flux:subheading>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Section Semua Game --}}
        <div class="container mx-auto px-4 max-w-7xl mb-10 md:mb-20">
            <flux:heading size="xl" class="mb-2 text-white">{{ __('messages.all_games') }}</flux:heading>
            <flux:text class="text-gray-300 mb-6">{{ __('messages.search_game_placeholder') }}</flux:text>

            <!-- Horizontal Scrollable Filter -->
            <div class="mb-8" x-data="{ filterGameId: @entangle('filterGameId') }">
                <div class="flex gap-2 overflow-x-auto pb-4 hide-scrollbar">
                    <button @click="filterGameId = ''"
                        :class="filterGameId === '' ? 'bg-yellow-400 text-white' :
                            'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="px-3 py-2.5 rounded-lg font-medium whitespace-nowrap transition flex-shrink-0">
                        {{ __('messages.all_games') }}
                    </button>
                    @foreach ($allGames as $game)
                        <button @click="filterGameId = '{{ $game->id }}'"
                            :class="filterGameId === '{{ $game->id }}' ? 'bg-yellow-400 text-white' :
                                'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                            class="px-3 py-2.5 rounded-lg font-medium whitespace-nowrap transition flex-shrink-0">
                            {{ $game->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Games Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @php
                    $filteredGames = $filterGameId ? $allGames->where('id', $filterGameId) : $allGames;
                @endphp

                @forelse($filteredGames as $game)
                    <a href="{{ route('game.detail', $game->slug) }}" wire:navigate class="group"
                        x-data="{ showInfo: false }" @touchstart="showInfo = true"
                        @touchend="setTimeout(() => showInfo = false, 2000)">
                        <div class="relative transition-all duration-300 cursor-pointer border-2 border-transparent group-hover:border-yellow-400 group-hover:shadow-xl rounded-xl"
                            :class="showInfo ? 'border-yellow-400 shadow-xl' : ''">
                            <div class="relative w-full overflow-hidden rounded-xl">
                                @if ($game->image)
                                    <img src="{{ Storage::url($game->image) }}" alt="{{ $game->name }}"
                                        class="w-full h-auto object-contain group-hover:scale-105 transition-transform duration-300"
                                        :class="showInfo ? 'scale-105' : ''" />
                                @else
                                    <div
                                        class="w-full aspect-square bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                                        <x-icon name="puzzle-piece" class="w-12 text-white opacity-50" />
                                    </div>
                                @endif
                            </div>

                            <div class="absolute inset-0 flex items-end pointer-events-none">
                                <div class="w-full p-3 flex items-end transition-all duration-300 transform translate-y-3 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 pointer-events-auto bg-gradient-to-t from-yellow-400 to-transparent rounded-b-lg"
                                    :class="showInfo && 'translate-y-0 opacity-100'">
                                    <div class="min-h-[2rem]">
                                        <h3 class="text-sm font-semibold text-white">{{ $game->name }}</h3>
                                        <p class="text-xs text-gray-300 mt-1">
                                            {{ $game->provider ?? config('app.name') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12">
                        <x-icon name="puzzle-piece" class="w-16 h-16 text-zinc-300 dark:text-zinc-700 mx-auto mb-4" />
                        <flux:heading size="md" class="mb-2">Game Tidak Ditemukan</flux:heading>
                        <flux:subheading class="text-zinc-500">Coba filter game lain</flux:subheading>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 4. ARTIKEL TERBARU (Maksimal 3) -->
        @if (count($latestPosts) > 0)
            <div class="container mx-auto px-4 max-w-7xl mb-10 md:mb-20">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <flux:heading size="xl" class="text-white mb-2">{{ __('messages.latest_articles') }}</flux:heading>
                        <flux:text class="text-white/90">{{ __('messages.read_more') }}</flux:text>
                    </div>
                    <flux:button href="{{ route('guest.posts.index') }}" wire:navigate variant="primary"
                        class="text-white hover:bg-white/10">
                        {{ __('messages.view_all') }}
                    </flux:button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($latestPosts as $post)
                        <a href="{{ route('guest.posts.show', $post->slug) }}" wire:navigate class="group">
                            <div
                                class="hover:shadow-2xl shadow-yellow-400 hover:border hover:border-yellow-400 rounded-xl transition-all duration-300 h-full">
                                @if ($post->featured_image)
                                    <div class="aspect-video overflow-hidden rounded-t-xl">
                                        <img src="{{ Storage::url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                @else
                                    <div
                                        class="aspect-video bg-gradient-to-br from-zinc-700 to-zinc-900 rounded-t-lg flex items-center justify-center">
                                        <x-icon name="document-text" class="w-16 h-16 text-white opacity-30" />
                                    </div>
                                @endif

                                <div class="p-4 bg-zinc-900 rounded-b-xl flex flex-col justify-between">
                                    <flux:heading size="sm" class="line-clamp-2 mb-2 text-yellow-400 transition">
                                        {{ $post->title }}
                                    </flux:heading>

                                    @if ($post->excerpt)
                                        <flux:subheading size="sm" class="line-clamp-2 mb-3">
                                            {{ $post->excerpt }}</flux:subheading>
                                    @endif

                                    <div class="flex items-center justify-between text-xs text-zinc-500">
                                        <span>{{ $post->published_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@assets
    @vite('resources/js/particles.js')
@endassets

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait a bit for the canvas to be ready
        setTimeout(() => {
            const canvas = document.getElementById('particleCanvas');
            if (!canvas) {
                console.error('Particle canvas not found');
                return;
            }

            console.log('Initializing particle system...');

            // Check if ParticleSystem is available
            if (typeof ParticleSystem === 'undefined') {
                console.error('ParticleSystem class not loaded');
                return;
            }

            // Initialize particle system with golden theme
            try {
                const particleSystem = new ParticleSystem('particleCanvas', {
                    particleCount: 80,
                    particleColor: 'rgba(255, 215, 0, 0.7)', // Golden color
                    particleSize: {
                        min: 1,
                        max: 3
                    },
                    speed: {
                        min: 0.5,
                        max: 1
                    },
                    shapes: ['circle', 'star', 'diamond'],
                    glow: true
                });
                console.log('Particles initialized successfully');
            } catch (error) {
                console.error('Error initializing particles:', error);
            }
        }, 100);
    });
</script>
