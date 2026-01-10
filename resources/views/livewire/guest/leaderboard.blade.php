<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\Leaderboard;

new #[Layout('components.layouts.guest'), Title('Leaderboard')] class extends Component {
    public function title(): string
    {
        return __('messages.leaderboard_title') . ' - ' . __('messages.welcome_title');
    }

    public function getLeaderboardData($period)
    {
        $data = Leaderboard::where('period', $period)
            ->where('is_active', true)
            ->orderBy('rank', 'asc')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->user_id ?? $item->id,
                    'name' => $this->censorName($item->name),
                    'totalSpent' => (float) $item->total_spent,
                    'txCount' => $item->transaction_count,
                    'lastItem' => $item->last_item ?? '-',
                    'lastTime' => $item->last_transaction_time ? $item->last_transaction_time->diffForHumans() : '-',
                ];
            })
            ->toArray();

        return $data;
    }

    public function censorName($name)
    {
        $words = explode(' ', $name);
        $censoredWords = array_map(function($word) {
            $length = mb_strlen($word);
            if ($length <= 3) {
                return $word;
            }
            return mb_substr($word, 0, 3) . str_repeat('*', $length - 3);
        }, $words);

        return implode(' ', $censoredWords);
    }

    public function with(): array
    {
        return [
            'weeklyData' => $this->getLeaderboardData('week'),
            'monthlyData' => $this->getLeaderboardData('month'),
            'allTimeData' => $this->getLeaderboardData('all'),
        ];
    }
}; ?>

<div>
    <div x-data="leaderboard()" x-init="init()" class="min-h-screen py-6 sm:py-12">
        <div class="container mx-auto px-4 sm:px-6 max-w-7xl">
            <!-- Hero Section -->
            <div class="text-center mb-8 sm:mb-12">
                <!-- Trophy Icon -->
                <div class="relative inline-block mb-4 sm:mb-6">
                    <div class="absolute inset-0 bg-yellow-400/20 blur-3xl rounded-full"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-yellow-400 relative"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold mb-3 text-yellow-400">{{ __('messages.leaderboard_title') }}
                </h1>
                <flux:subheading class="text-sm sm:text-base lg:text-lg text-white/80 max-w-2xl mx-auto px-4">
                    {{ __('messages.leaderboard_subtitle') }}
                </flux:subheading>
            </div>

            <!-- Time Filter Tabs -->
            <div class="flex justify-center mb-6 sm:mb-8 overflow-x-auto hide-scrollbar">
                <div class="inline-flex bg-white/10 backdrop-blur-sm rounded-xl p-1 gap-1">
                    <button @click="period = 'week'; updateLeaderboard()"
                        :class="period === 'week' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-3 sm:px-6 py-2 sm:py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 text-xs sm:text-base whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        {{ __('messages.weekly') }}
                    </button>
                    <button @click="period = 'month'; updateLeaderboard()"
                        :class="period === 'month' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-3 sm:px-6 py-2 sm:py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 text-xs sm:text-base whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('messages.monthly') }}
                    </button>
                    <button @click="period = 'all'; updateLeaderboard()"
                        :class="period === 'all' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-3 sm:px-6 py-2 sm:py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-1 sm:gap-2 text-xs sm:text-base whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('messages.all_time') }}
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <flux:card class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs sm:text-sm opacity-90 mb-1">{{ __('messages.total_transactions') }}
                            </div>
                            <div class="text-2xl sm:text-3xl font-bold" x-text="stats.totalTransactions"></div>
                        </div>
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="bg-gradient-to-br from-green-500 to-green-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs sm:text-sm opacity-90 mb-1">{{ __('messages.total_sales') }}</div>
                            <div class="text-xl sm:text-2xl font-bold" x-text="formatCurrency(stats.totalRevenue)">
                            </div>
                        </div>
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="bg-gradient-to-br from-purple-500 to-purple-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs sm:text-sm opacity-90 mb-1">{{ __('messages.active_buyers') }}</div>
                            <div class="text-2xl sm:text-3xl font-bold" x-text="stats.activeBuyers"></div>
                        </div>
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Leaderboard Table -->
            <flux:card class="overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-100 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.buyer') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.total_spent') }}
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.transactions') }}
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.last_item') }}
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(buyer, index) in currentBuyers" :key="buyer.id">
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors"
                                    :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': index < 3 }">
                                    <!-- Buyer Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <template x-if="index === 0">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    1
                                                </div>
                                            </template>
                                            <template x-if="index === 1">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    2
                                                </div>
                                            </template>
                                            <template x-if="index === 2">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    3
                                                </div>
                                            </template>
                                            <template x-if="index > 2">
                                                <div class="w-10 h-10 bg-zinc-200 dark:bg-zinc-700 rounded-full flex items-center justify-center font-semibold"
                                                    x-text="index + 1">
                                                </div>
                                            </template>
                                            <div>
                                                <div class="font-semibold" x-text="buyer.name"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Total Spent -->
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-lg"
                                            :class="index < 3 ? 'text-yellow-600 dark:text-yellow-400' : ''"
                                            x-text="formatCurrency(buyer.totalSpent)">
                                        </div>
                                    </td>

                                    <!-- Transaction Count -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="font-medium" x-text="buyer.txCount + 'x'"></span>
                                        </div>
                                    </td>

                                    <!-- Last Item -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.gem class="w-4 h-4 text-indigo-500"/>
                                            <span class="text-sm" x-text="buyer.lastItem"></span>
                                        </div>
                                    </td>

                                    <!-- Time -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="buyer.lastTime"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile List -->
                <div class="md:hidden divide-y divide-zinc-200 dark:divide-zinc-700">
                    <template x-for="(buyer, index) in currentBuyers" :key="buyer.id">
                        <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors"
                            :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': index < 3 }">
                            <div class="flex items-center gap-3">


                                <!-- User Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        {{-- <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold text-xs flex-shrink-0" x-text="buyer.name.charAt(0)">
                                        </div> --}}
                                        <div class="font-semibold truncate" x-text="buyer.name"></div>
                                    </div>
                                </div>

                                <!-- Total Spent -->
                                <div class="flex-shrink-0 text-right">
                                    <div class="font-bold text-base sm:text-lg"
                                        :class="index < 3 ? 'text-yellow-600 dark:text-yellow-400' : ''"
                                        x-text="formatCurrency(buyer.totalSpent)">
                                    </div>
                                    <div
                                        class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-end gap-1 mt-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span x-text="buyer.txCount + 'x'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </flux:card>

            <!-- Footer Note -->
            <div class="text-center mt-6 sm:mt-8 text-white/60 text-xs sm:text-sm px-4">
                <p>💡 {{ __('messages.leaderboard_footer') }}</p>
            </div>
        </div>
    </div>

    <script>
        function leaderboard() {
            const currency = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            });

            // Load data from database (passed from PHP)
            const weeklyDataFromDB = @json($weeklyData ?? []);
            const monthlyDataFromDB = @json($monthlyData ?? []);
            const allTimeDataFromDB = @json($allTimeData ?? []);

            // Fallback dummy data if database is empty
            const weeklyDataDummy = [{
                    id: '2401',
                    name: 'Budi Santoso',
                    totalSpent: 2450000,
                    txCount: 18,
                    lastItem: 'Mobile Legends 5000 Diamond',
                    lastTime: '5 menit lalu'
                },
                {
                    id: '2402',
                    name: 'Rina Wijaya',
                    totalSpent: 1980000,
                    txCount: 15,
                    lastItem: 'Free Fire 2000 Diamond',
                    lastTime: '12 menit lalu'
                },
                {
                    id: '2403',
                    name: 'Ahmad Fauzi',
                    totalSpent: 1750000,
                    txCount: 12,
                    lastItem: 'PUBG UC 3600',
                    lastTime: '25 menit lalu'
                },
                {
                    id: '2404',
                    name: 'Siti Nurhaliza',
                    totalSpent: 1420000,
                    txCount: 11,
                    lastItem: 'Genshin Impact Welkin',
                    lastTime: '45 menit lalu'
                },
                {
                    id: '2405',
                    name: 'Denny Prakoso',
                    totalSpent: 1150000,
                    txCount: 9,
                    lastItem: 'Valorant VP 2400',
                    lastTime: '1 jam lalu'
                },
                {
                    id: '2406',
                    name: 'Maya Putri',
                    totalSpent: 985000,
                    txCount: 8,
                    lastItem: 'Mobile Legends Weekly Pass',
                    lastTime: '1 jam lalu'
                },
                {
                    id: '2407',
                    name: 'Ricky Harun',
                    totalSpent: 870000,
                    txCount: 7,
                    lastItem: 'Honkai Star Rail Pass',
                    lastTime: '2 jam lalu'
                },
                {
                    id: '2408',
                    name: 'Dewi Lestari',
                    totalSpent: 750000,
                    txCount: 6,
                    lastItem: 'Free Fire Bundle Elite',
                    lastTime: '3 jam lalu'
                },
                {
                    id: '2409',
                    name: 'Eko Wijaya',
                    totalSpent: 620000,
                    txCount: 5,
                    lastItem: 'PUBG Royale Pass',
                    lastTime: '4 jam lalu'
                },
                {
                    id: '2410',
                    name: 'Lisa Andriani',
                    totalSpent: 540000,
                    txCount: 4,
                    lastItem: 'Mobile Legends 1000 Diamond',
                    lastTime: '5 jam lalu'
                },
            ];

            const monthlyDataDummy = [{
                    id: '1801',
                    name: 'Andi Wijaya',
                    totalSpent: 8950000,
                    txCount: 64,
                    lastItem: 'Mobile Legends 5000 Diamond',
                    lastTime: '15 menit lalu'
                },
                {
                    id: '1802',
                    name: 'Putri Rahayu',
                    totalSpent: 7320000,
                    txCount: 52,
                    lastItem: 'Free Fire Monthly Pass',
                    lastTime: '32 menit lalu'
                },
                {
                    id: '1803',
                    name: 'Bambang Susilo',
                    totalSpent: 6480000,
                    txCount: 47,
                    lastItem: 'Genshin Impact Crystal',
                    lastTime: '48 menit lalu'
                },
                {
                    id: '1804',
                    name: 'Wulan Dari',
                    totalSpent: 5850000,
                    txCount: 41,
                    lastItem: 'PUBG UC 8100',
                    lastTime: '1 jam lalu'
                },
                {
                    id: '1805',
                    name: 'Hendra Gunawan',
                    totalSpent: 4920000,
                    txCount: 35,
                    lastItem: 'Valorant VP 5000',
                    lastTime: '2 jam lalu'
                },
                {
                    id: '1806',
                    name: 'Sari Indah',
                    totalSpent: 4350000,
                    txCount: 32,
                    lastItem: 'Mobile Legends Starlight',
                    lastTime: '2 jam lalu'
                },
                {
                    id: '1807',
                    name: 'Toni Hermawan',
                    totalSpent: 3780000,
                    txCount: 28,
                    lastItem: 'Free Fire 5000 Diamond',
                    lastTime: '3 jam lalu'
                },
                {
                    id: '1808',
                    name: 'Mega Sari',
                    totalSpent: 3240000,
                    txCount: 24,
                    lastItem: 'Honkai Impact Crystal',
                    lastTime: '4 jam lalu'
                },
                {
                    id: '1809',
                    name: 'Dodi Setiawan',
                    totalSpent: 2850000,
                    txCount: 21,
                    lastItem: 'PUBG Royale Pass',
                    lastTime: '5 jam lalu'
                },
                {
                    id: '1810',
                    name: 'Nina Marlina',
                    totalSpent: 2460000,
                    txCount: 18,
                    lastItem: 'Mobile Legends 3000 Diamond',
                    lastTime: '6 jam lalu'
                },
            ];

            const allTimeDataDummy = [{
                    id: '0101',
                    name: 'Agus Setiawan',
                    totalSpent: 45750000,
                    txCount: 287,
                    lastItem: 'Mobile Legends 10000 Diamond',
                    lastTime: '20 menit lalu'
                },
                {
                    id: '0102',
                    name: 'Diana Kusuma',
                    totalSpent: 38920000,
                    txCount: 245,
                    lastItem: 'Free Fire Mega Bundle',
                    lastTime: '38 menit lalu'
                },
                {
                    id: '0103',
                    name: 'Fadli Rahman',
                    totalSpent: 32450000,
                    txCount: 198,
                    lastItem: 'Genshin Impact Bundle',
                    lastTime: '52 menit lalu'
                },
                {
                    id: '0104',
                    name: 'Indah Permata',
                    totalSpent: 28750000,
                    txCount: 176,
                    lastItem: 'PUBG UC 16200',
                    lastTime: '1 jam lalu'
                },
                {
                    id: '0105',
                    name: 'Yudi Pratama',
                    totalSpent: 24680000,
                    txCount: 152,
                    lastItem: 'Valorant Premium Bundle',
                    lastTime: '2 jam lalu'
                },
                {
                    id: '0106',
                    name: 'Ratna Sari',
                    totalSpent: 21350000,
                    txCount: 138,
                    lastItem: 'Mobile Legends Premium',
                    lastTime: '2 jam lalu'
                },
                {
                    id: '0107',
                    name: 'Irfan Hakim',
                    totalSpent: 18920000,
                    txCount: 124,
                    lastItem: 'Free Fire Elite Pass',
                    lastTime: '3 jam lalu'
                },
                {
                    id: '0108',
                    name: 'Sinta Dewi',
                    totalSpent: 16450000,
                    txCount: 109,
                    lastItem: 'Honkai Premium Pass',
                    lastTime: '4 jam lalu'
                },
                {
                    id: '0109',
                    name: 'Wahyu Nugroho',
                    totalSpent: 14780000,
                    txCount: 98,
                    lastItem: 'PUBG Season Pass',
                    lastTime: '5 jam lalu'
                },
                {
                    id: '0110',
                    name: 'Lilis Suryani',
                    totalSpent: 12950000,
                    txCount: 87,
                    lastItem: 'Mobile Legends 5000 Diamond',
                    lastTime: '6 jam lalu'
                },
            ];

            // Use database data if available, otherwise use dummy data
            const weeklyData = weeklyDataFromDB.length > 0 ? weeklyDataFromDB : weeklyDataDummy;
            const monthlyData = monthlyDataFromDB.length > 0 ? monthlyDataFromDB : monthlyDataDummy;
            const allTimeData = allTimeDataFromDB.length > 0 ? allTimeDataFromDB : allTimeDataDummy;

            return {
                period: 'week',
                currentBuyers: [],
                stats: {
                    totalTransactions: 0,
                    totalRevenue: 0,
                    activeBuyers: 0
                },

                init() {
                    this.updateLeaderboard();
                },

                formatCurrency(v) {
                    return currency.format(v);
                },

                updateLeaderboard() {
                    let data = [];

                    switch (this.period) {
                        case 'week':
                            data = weeklyData;
                            break;
                        case 'month':
                            data = monthlyData;
                            break;
                        case 'all':
                            data = allTimeData;
                            break;
                    }

                    this.currentBuyers = [...data];
                    this.updateStats();
                },

                updateStats() {
                    this.stats.totalTransactions = this.currentBuyers.reduce((sum, buyer) => sum + buyer.txCount, 0);
                    this.stats.totalRevenue = this.currentBuyers.reduce((sum, buyer) => sum + buyer.totalSpent, 0);
                    this.stats.activeBuyers = this.currentBuyers.length;
                }
            }
        }
    </script>
</div>
