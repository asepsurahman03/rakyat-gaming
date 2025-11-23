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

    public function mount()
    {
        $this->loadCarousels();
        $this->loadPopularGames();
        $this->loadCategories();
    }

    public function loadCarousels()
    {
        $this->carousels = Carousel::all();
    }

    public function loadPopularGames()
    {
        $this->popularGames = Game::where('is_active', true)
            ->withCount(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->limit(8)
            ->get();
    }

    public function loadCategories()
    {
        // Get unique game names for category display
        $this->categories = Game::where('is_active', true)
            ->withCount(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->limit(8)
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
    @if(count($carousels) > 0)
    <style>
        /* Neon animated border wrapper */
        .neon-border {
            background: linear-gradient(90deg,
                rgba(255,215,0,0.95),
                rgba(236, 235, 229, 0.95),
                rgba(255,180,0,0.95),
                rgba(253, 251, 251, 0.95));
            background-size: 300% 100%;
            animation: neon-slide 6s linear infinite;
            box-shadow: 0 8px 30px rgba(99,102,241,0.08), inset 0 0 18px rgba(99,102,241,0.03);
        }
        .neon-border:hover { animation-play-state: paused; } /* optional: pause on hover */
        @keyframes neon-slide {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* make inner container crisp when using small border padding */
        .neon-inner {
            border-radius: calc(1rem - 2px); /* adjust to match rounded-2xl minus border */
        }

        /* ensure Alpine x-cloak hides until ready */
        [x-cloak] { display: none !important; }
    </style>

    <div class="relative max-w-7xl mx-auto rounded-2xl mt-10">
        <!-- neon border: uses small padding so inner content keeps its bg -->
        <div class="neon-border rounded-2xl p-[2px]">
            <div class="relative rounded-2xl neon-inner overflow-hidden bg-white dark:bg-zinc-800" x-data="{ currentSlide: @entangle('currentSlide') }" x-cloak>
                <!-- Carousel Container -->
                <div class="relative h-[280px] md:h-[420px]">
                    @foreach($carousels as $index => $carousel)
                    <div
                        class="absolute inset-0 transition-all duration-700 ease-in-out transform"
                        x-show="currentSlide === {{ $index }}"
                        x-transition:enter="transition ease-out duration-700"
                        x-transition:enter-start="opacity-0 transform translate-x-full scale-95"
                        x-transition:enter-end="opacity-100 transform translate-x-0 scale-100"
                        x-transition:leave="transition ease-in duration-700"
                        x-transition:leave-start="opacity-100 transform translate-x-0 scale-100"
                        x-transition:leave-end="opacity-0 transform -translate-x-8 scale-95"
                    >
                        <div class="relative h-full w-full">
                            @if($carousel->image_path)
                                <img src="{{ Storage::url($carousel->image_path) }}" alt="{{ $carousel->title }}" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/80 via-transparent to-transparent"></div>
                                <div class="absolute left-6 bottom-6 text-white max-w-xl">
                                    @if($carousel->title)
                                        <flux:heading size="xl" class="text-white mb-2">{{ $carousel->title }}</flux:heading>
                                    @endif
                                    @if($carousel->subtitle)
                                        <flux:subheading class="text-white/90">{{ $carousel->subtitle }}</flux:subheading>
                                    @endif
                                </div>
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32 text-white opacity-50">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Navigation Buttons (improved placement & contrast) -->
                <button
                    wire:click="prevSlide"
                    aria-label="Previous slide"
                    class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full shadow-xl z-20 w-10 h-10 bg-white/80 dark:bg-zinc-900/70 flex items-center justify-center hover:scale-105 transition transform"
                >
                    <x-icon name="chevron-left" class="w-5 h-5 text-zinc-700 dark:text-zinc-200" />
                </button>

                <button
                    wire:click="nextSlide"
                    aria-label="Next slide"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full shadow-xl z-20 w-10 h-10 bg-white/80 dark:bg-zinc-900/70 flex items-center justify-center hover:scale-105 transition transform"
                >
                    <x-icon name="chevron-right" class="w-5 h-5 text-zinc-700 dark:text-zinc-200" />
                </button>

                <!-- Indicators -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                    @foreach($carousels as $index => $carousel)
                    <button
                        wire:click="goToSlide({{ $index }})"
                        class="rounded-full transition-all duration-300 focus:outline-none"
                        :class="currentSlide === {{ $index }} ? 'bg-indigo-600 w-8 h-2 rounded-full' : 'bg-white/40 w-3 h-3'"
                        aria-label="Slide {{ $index + 1 }}"
                    ></button>
                    @endforeach
                </div>

                <!-- Auto Play (Optional) -->
                <div
                    x-init="let t = setInterval(() => { $wire.nextSlide() }, 5000); $el.closest('.neon-border').addEventListener('mouseenter', () => clearInterval(t));"
                    class="hidden"
                ></div>
            </div>
        </div>
    </div>
    @else
    <!-- Hero Placeholder if no carousel -->
    <div class="min-h-[300px] bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
        <div class="text-center max-w-2xl px-4">
            <flux:heading size="xl" class="text-white mb-3">Top Up Game Termurah & Tercepat</flux:heading>
            <flux:subheading class="text-white/90 mb-4">Ribuan produk digital tersedia</flux:subheading>
            <flux:button variant="primary" size="lg">Mulai Belanja</flux:button>
        </div>
    </div>
    @endif

    <!-- Category Icons Section -->
    <div class="bg-white dark:bg-zinc-900 py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <flux:heading size="lg" class="mb-8">Kategori Populer</flux:heading>
            <div class="grid grid-cols-4 md:grid-cols-8 gap-4">
                @php
                    $colors = [
                        'from-primary to-secondary',
                        'from-orange-500 to-red-500',
                        'from-yellow-500 to-orange-600',
                        'from-purple-500 to-pink-500',
                        'from-red-600 to-black',
                        'from-green-600 to-teal-600',
                        'from-blue-600 to-blue-800',
                        'from-indigo-500 to-purple-600',
                    ];
                @endphp

                @forelse($categories as $index => $category)
                <a href="{{ route('game.detail', $category->slug) }}" wire:navigate class="flex flex-col items-center gap-2 cursor-pointer group hover:opacity-80 transition-opacity">
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-xl bg-gradient-to-br {{ $colors[$index % count($colors)] }} p-1 group-hover:scale-110 transition-transform shadow-lg">
                        @if($category->image)
                            <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="w-full h-full object-cover rounded-lg">
                        @else
                            <div class="w-full h-full bg-white dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-indigo-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <span class="text-xs md:text-sm text-center font-medium line-clamp-2">{{ $category->name }}</span>
                </a>
                @empty
                <!-- No categories available -->
                <div class="col-span-4 md:col-span-8 text-center py-8">
                    <flux:subheading class="text-zinc-500">Tidak ada game tersedia saat ini</flux:subheading>
                </div>
                @endforelse

                @if(count($categories) > 0)
                <!-- Lihat Semua -->
                <a href="#" class="flex flex-col items-center gap-2 cursor-pointer group hover:opacity-80 transition-opacity">
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-xl bg-gradient-to-br from-zinc-300 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800 p-1 group-hover:scale-110 transition-transform shadow-lg">
                        <div class="w-full h-full bg-white dark:bg-zinc-900 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                    </div>
                    <span class="text-xs md:text-sm text-center font-medium">Lihat Semua</span>
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Popular Games Section -->
    <div class="bg-zinc-50 dark:bg-zinc-800 py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">Game Populer</flux:heading>
                <a href="#" class="text-indigo-600 hover:text-indigo-700 hover:underline text-sm font-medium">Lihat Semua →</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4">
                @forelse($popularGames as $game)
                <a href="{{ route('game.detail', $game->slug) }}" wire:navigate class="group">
                    <flux:card class="hover:shadow-xl transition-all duration-300 cursor-pointer">
                    <div class="relative aspect-square overflow-hidden rounded-t-lg">
                        @if($game->image)
                            <img src="{{ Storage::url($game->image) }}" alt="{{ $game->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-white opacity-50">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                                </svg>
                            </div>
                        @endif
                        @if($game->products_count > 0)
                            <div class="absolute top-2 right-2">
                                <flux:badge size="sm" color="indigo">{{ $game->products_count }} Produk</flux:badge>
                            </div>
                        @endif
                    </div>
                    <div class="p-3">
                        <flux:heading size="sm" class="line-clamp-1">{{ $game->name }}</flux:heading>
                        <flux:subheading size="sm" class="line-clamp-1 mt-1">{{ $game->provider ?? 'Top Up Game' }}</flux:subheading>
                        <div class="flex justify-between items-center mt-3">
                            @if($game->products_count > 0)
                                <flux:badge size="sm" color="green">Tersedia</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Segera</flux:badge>
                            @endif
                            <flux:button size="xs" variant="primary">Beli</flux:button>
                        </div>
                    </div>
                    </flux:card>
                </a>
                @empty
                <div class="col-span-2 md:col-span-4 text-center py-12">
                    <div class="flex flex-col items-center gap-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-zinc-300 dark:text-zinc-700">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                        </svg>
                        <div>
                            <flux:heading size="md" class="mb-2">Belum Ada Game Tersedia</flux:heading>
                            <flux:subheading class="text-zinc-500">Game dan produk akan segera ditambahkan</flux:subheading>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-white dark:bg-zinc-900 py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <flux:heading size="lg" class="mb-8 text-center">Kenapa Pilih Kami?</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start gap-4">
                    <div class="bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-lg shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-indigo-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-1">Proses Cepat</flux:heading>
                        <flux:subheading size="sm">Transaksi otomatis dalam hitungan detik</flux:subheading>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-1">100% Aman</flux:heading>
                        <flux:subheading size="sm">Data dan transaksi Anda terjamin aman</flux:subheading>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-lg shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-1">Harga Terjangkau</flux:heading>
                        <flux:subheading size="sm">Dapatkan harga terbaik untuk top up game</flux:subheading>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
