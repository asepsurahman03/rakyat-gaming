<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new #[Layout('components.layouts.guest'), Title('Leaderboard')] class extends Component {
    public function title(): string
    {
        return __('messages.leaderboard_title') . ' - ' . __('messages.welcome_title');
    }
}; ?>

<div>
    <div x-data="leaderboard()" x-init="init()"
        class="bg-gradient-to-bl from-[#7E0000] to-[#470000] min-h-screen py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Hero Section -->
            <div class="text-center mb-12">
                <!-- Trophy Icon -->
                <div class="relative inline-block mb-6">
                    <div class="absolute inset-0 bg-yellow-400/20 blur-3xl rounded-full"></div>
                    <svg xmlns="http://www.w3.o/2000/svg" class="w-24 h-24 md:w-32 md:h-32 mx-auto text-yellow-400 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                    </svg>
                </div>
                <flux:heading size="2xl" class="mb-4 text-white">🏆 {{ __('messages.leaderboard_title') }}</flux:heading>
                <flux:subheading class="text-lg text-white/80 max-w-2xl mx-auto">
                    {{ __('messages.leaderboard_subtitle') }}
                </flux:subheading>
            </div>

            <!-- Time Filter Tabs -->
            <div class="flex justify-center mb-8">
                <div class="inline-flex bg-white/10 backdrop-blur-sm rounded-xl p-1 gap-1">
                    <button @click="period = 'week'; updateLeaderboard()"
                        :class="period === 'week' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-6 py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        {{ __('messages.weekly') }}
                    </button>
                    <button @click="period = 'month'; updateLeaderboard()"
                        :class="period === 'month' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-6 py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('messages.monthly') }}
                    </button>
                    <button @click="period = 'all'; updateLeaderboard()"
                        :class="period === 'all' ? 'bg-yellow-400 text-black' : 'text-white hover:bg-white/10'"
                        class="px-6 py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('messages.all_time') }}
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <flux:card class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90 mb-1">{{ __('messages.total_transactions') }}</div>
                            <div class="text-3xl font-bold" x-text="stats.totalTransactions"></div>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="bg-gradient-to-br from-green-500 to-green-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90 mb-1">{{ __('messages.total_sales') }}</div>
                            <div class="text-2xl font-bold" x-text="formatCurrency(stats.totalRevenue)"></div>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="bg-gradient-to-br from-purple-500 to-purple-600 text-white border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90 mb-1">{{ __('messages.active_buyers') }}</div>
                            <div class="text-3xl font-bold" x-text="stats.activeBuyers"></div>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Leaderboard Table -->
            <flux:card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-100 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.rank') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.buyer') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.total_spent') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.transactions') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.last_item') }}</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">{{ __('messages.time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(buyer, index) in currentBuyers" :key="buyer.id">
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors"
                                    :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': index < 3 }">
                                    <!-- Rank -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <template x-if="index === 0">
                                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    1
                                                </div>
                                            </template>
                                            <template x-if="index === 1">
                                                <div class="w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    2
                                                </div>
                                            </template>
                                            <template x-if="index === 2">
                                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                                    3
                                                </div>
                                            </template>
                                            <template x-if="index > 2">
                                                <div class="w-10 h-10 bg-zinc-200 dark:bg-zinc-700 rounded-full flex items-center justify-center font-semibold" x-text="index + 1">
                                                </div>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Buyer Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm" x-text="buyer.name.charAt(0)">
                                            </div>
                                            <div>
                                                <div class="font-semibold" x-text="buyer.name"></div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    <span x-text="'ID: ' + buyer.id"></span>
                                                    {{-- <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2"
                                                        :class="{
                                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': buyer.badge === 'VIP',
                                                            'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400': buyer.badge === 'Loyal',
                                                            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': buyer.badge === 'Pro',
                                                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': buyer.badge === 'Aktif',
                                                            'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300': buyer.badge === 'Member'
                                                        }"
                                                        x-text="buyer.badge">
                                                    </span> --}}
                                                </div>
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
                                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="font-medium" x-text="buyer.txCount + 'x'"></span>
                                        </div>
                                    </td>

                                    <!-- Last Item -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                            </svg>
                                            <span class="text-sm" x-text="buyer.lastItem"></span>
                                        </div>
                                    </td>

                                    <!-- Time -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="buyer.lastTime"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </flux:card>

            <!-- Footer Note -->
            <div class="text-center mt-8 text-white/60 text-sm">
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

            // Realistic dummy data for different periods
            const weeklyData = [
                { id: '2401', name: 'Budi Santoso', totalSpent: 2450000, txCount: 18, lastItem: 'Mobile Legends 5000 Diamond', lastTime: '5 menit lalu', badge: 'VIP' },
                { id: '2402', name: 'Rina Wijaya', totalSpent: 1980000, txCount: 15, lastItem: 'Free Fire 2000 Diamond', lastTime: '12 menit lalu', badge: 'Loyal' },
                { id: '2403', name: 'Ahmad Fauzi', totalSpent: 1750000, txCount: 12, lastItem: 'PUBG UC 3600', lastTime: '25 menit lalu', badge: 'Pro' },
                { id: '2404', name: 'Siti Nurhaliza', totalSpent: 1420000, txCount: 11, lastItem: 'Genshin Impact Welkin', lastTime: '45 menit lalu', badge: 'Aktif' },
                { id: '2405', name: 'Denny Prakoso', totalSpent: 1150000, txCount: 9, lastItem: 'Valorant VP 2400', lastTime: '1 jam lalu', badge: 'Pro' },
                { id: '2406', name: 'Maya Putri', totalSpent: 985000, txCount: 8, lastItem: 'Mobile Legends Weekly Pass', lastTime: '1 jam lalu', badge: 'Member' },
                { id: '2407', name: 'Ricky Harun', totalSpent: 870000, txCount: 7, lastItem: 'Honkai Star Rail Pass', lastTime: '2 jam lalu', badge: 'Member' },
                { id: '2408', name: 'Dewi Lestari', totalSpent: 750000, txCount: 6, lastItem: 'Free Fire Bundle Elite', lastTime: '3 jam lalu', badge: 'Aktif' },
                { id: '2409', name: 'Eko Wijaya', totalSpent: 620000, txCount: 5, lastItem: 'PUBG Royale Pass', lastTime: '4 jam lalu', badge: 'Member' },
                { id: '2410', name: 'Lisa Andriani', totalSpent: 540000, txCount: 4, lastItem: 'Mobile Legends 1000 Diamond', lastTime: '5 jam lalu', badge: 'Member' },
            ];

            const monthlyData = [
                { id: '1801', name: 'Andi Wijaya', totalSpent: 8950000, txCount: 64, lastItem: 'Mobile Legends 5000 Diamond', lastTime: '15 menit lalu', badge: 'VIP' },
                { id: '1802', name: 'Putri Rahayu', totalSpent: 7320000, txCount: 52, lastItem: 'Free Fire Monthly Pass', lastTime: '32 menit lalu', badge: 'VIP' },
                { id: '1803', name: 'Bambang Susilo', totalSpent: 6480000, txCount: 47, lastItem: 'Genshin Impact Crystal', lastTime: '48 menit lalu', badge: 'Loyal' },
                { id: '1804', name: 'Wulan Dari', totalSpent: 5850000, txCount: 41, lastItem: 'PUBG UC 8100', lastTime: '1 jam lalu', badge: 'Loyal' },
                { id: '1805', name: 'Hendra Gunawan', totalSpent: 4920000, txCount: 35, lastItem: 'Valorant VP 5000', lastTime: '2 jam lalu', badge: 'Pro' },
                { id: '1806', name: 'Sari Indah', totalSpent: 4350000, txCount: 32, lastItem: 'Mobile Legends Starlight', lastTime: '2 jam lalu', badge: 'Pro' },
                { id: '1807', name: 'Toni Hermawan', totalSpent: 3780000, txCount: 28, lastItem: 'Free Fire 5000 Diamond', lastTime: '3 jam lalu', badge: 'Aktif' },
                { id: '1808', name: 'Mega Sari', totalSpent: 3240000, txCount: 24, lastItem: 'Honkai Impact Crystal', lastTime: '4 jam lalu', badge: 'Aktif' },
                { id: '1809', name: 'Dodi Setiawan', totalSpent: 2850000, txCount: 21, lastItem: 'PUBG Royale Pass', lastTime: '5 jam lalu', badge: 'Member' },
                { id: '1810', name: 'Nina Marlina', totalSpent: 2460000, txCount: 18, lastItem: 'Mobile Legends 3000 Diamond', lastTime: '6 jam lalu', badge: 'Member' },
            ];

            const allTimeData = [
                { id: '0101', name: 'Agus Setiawan', totalSpent: 45750000, txCount: 287, lastItem: 'Mobile Legends 10000 Diamond', lastTime: '20 menit lalu', badge: 'VIP' },
                { id: '0102', name: 'Diana Kusuma', totalSpent: 38920000, txCount: 245, lastItem: 'Free Fire Mega Bundle', lastTime: '38 menit lalu', badge: 'VIP' },
                { id: '0103', name: 'Fadli Rahman', totalSpent: 32450000, txCount: 198, lastItem: 'Genshin Impact Bundle', lastTime: '52 menit lalu', badge: 'VIP' },
                { id: '0104', name: 'Indah Permata', totalSpent: 28750000, txCount: 176, lastItem: 'PUBG UC 16200', lastTime: '1 jam lalu', badge: 'Loyal' },
                { id: '0105', name: 'Yudi Pratama', totalSpent: 24680000, txCount: 152, lastItem: 'Valorant Premium Bundle', lastTime: '2 jam lalu', badge: 'Loyal' },
                { id: '0106', name: 'Ratna Sari', totalSpent: 21350000, txCount: 138, lastItem: 'Mobile Legends Premium', lastTime: '2 jam lalu', badge: 'Loyal' },
                { id: '0107', name: 'Irfan Hakim', totalSpent: 18920000, txCount: 124, lastItem: 'Free Fire Elite Pass', lastTime: '3 jam lalu', badge: 'Pro' },
                { id: '0108', name: 'Sinta Dewi', totalSpent: 16450000, txCount: 109, lastItem: 'Honkai Premium Pass', lastTime: '4 jam lalu', badge: 'Pro' },
                { id: '0109', name: 'Wahyu Nugroho', totalSpent: 14780000, txCount: 98, lastItem: 'PUBG Season Pass', lastTime: '5 jam lalu', badge: 'Pro' },
                { id: '0110', name: 'Lilis Suryani', totalSpent: 12950000, txCount: 87, lastItem: 'Mobile Legends 5000 Diamond', lastTime: '6 jam lalu', badge: 'Aktif' },
            ];

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

                    switch(this.period) {
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
