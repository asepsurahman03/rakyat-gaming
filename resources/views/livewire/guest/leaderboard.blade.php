<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new #[Layout('components.layouts.guest'), Title('leaderBoard - Top Up Game Termurah & Tercepat')] class extends Component {
    //
}; ?>

<div class="bg-gradient-to-bl from-red-600 to-black">
    <div x-data="leaderboard()" x-init="init()"
        class="max-w-7xl mx-auto min-h-screen flex flex-col items-center justify-start py-10 space-y-6">
        <!-- Hero Section -->
        <div class="py-12">
            <div class="container mx-auto px-4 max-w-4xl text-center">
                <div class="text-gray-800 text-center">
                    <!-- Large SVG trophy icon for leaderboard -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-28 h-28 md:w-36 md:h-36 mx-auto text-yellow-400 drop-shadow-md" viewBox="0 0 64 64" fill="currentColor" role="img" aria-label="Trophy">
                        <title>Trophy</title>
                        <!-- cup -->
                        <path d="M16 8h32v6a14 14 0 01-14 14H30A14 14 0 0116 14V8z" />
                        <!-- left handle -->
                        <path d="M12 10a6 6 0 00-4 5.5V22a2 2 0 002 2h2V12z" />
                        <!-- right handle -->
                        <path d="M52 10a6 6 0 014 5.5V22a2 2 0 01-2 2h-2V12z" />
                        <!-- stem -->
                        <rect x="26" y="36" width="12" height="6" rx="1" />
                        <!-- base -->
                        <rect x="20" y="44" width="24" height="6" rx="1" />
                        <!-- accent: small star for leaderboard -->
                        <path d="M32 18l1.9 3.9 4.3.6-3.1 3 0.7 4.3L32 30l-3.8 2 0.7-4.3-3.1-3 4.3-.6L32 18z" fill="white" opacity="0.9"/>
                    </svg>
                    <h1 class="text-3xl md:text-4xl font-bold mb-3 text-yellow-400 text-shadow">Leaderboard
                    </h1>
                    <p class="text-lg opacity-90 text-gray-300 text-shadow">Lorem, ipsum dolor sit amet consectetur adipisicing elit. Voluptates reiciendis temporibus amet iste sequi neque dicta excepturi ad, ducimus distinctio.</p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-3/4 lg:w-2/3">


            <div class="bg-white shadow rounded overflow-hidden">
                <template x-for="(b,index) in buyers" :key="b.id">
                    <div class="flex items-center gap-4 px-4 py-3 border-b last:border-b-0"
                        :class="{ 'bg-green-50': b.movedUp, 'bg-red-50': b.movedDown }">
                        <div class="w-10 text-right font-semibold text-gray-700">
                            <span x-text="index + 1"></span>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-baseline justify-between">
                                <div>
                                    <div class="font-medium text-gray-800" x-text="b.name"></div>
                                    <div class="text-xs text-gray-500" x-text="'#' + b.id + ' · ' + b.tag"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Transaksi: <span x-text="b.txCount"></span></div>
                                    <div class="text-xs text-gray-400" x-text="b.lastTime"></div>
                                </div>
                            </div>

                            <div class="text-sm text-gray-600 mt-1">
                                Terakhir: <span class="font-medium text-gray-800" x-text="b.lastItem"></span>
                            </div>
                        </div>

                        <div class="w-36 text-right">
                            <div class="text-lg font-bold" x-text="formatCurrency(b.totalSpent)"></div>
                            <div class="text-sm"
                                :class="b.movedUp ? 'text-green-600' : (b.movedDown ? 'text-red-600' : 'text-gray-500')">
                                <span x-text="b.deltaText"></span>
                            </div>
                        </div>
                    </div>
                </template>
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

            return {
                speed: 1200,
                running: true,
                intervalId: null,

                // initial fake buyers
                buyers: [{
                        id: 201,
                        name: 'Rizky',
                        totalSpent: 1250000,
                        lastItem: 'Diamond 500',
                        lastTime: 'baru saja',
                        txCount: 12,
                        tag: 'VIP',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    },
                    {
                        id: 202,
                        name: 'Sari',
                        totalSpent: 980000,
                        lastItem: 'Voucher 100',
                        lastTime: '1 menit lalu',
                        txCount: 9,
                        tag: 'Loyal',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    },
                    {
                        id: 203,
                        name: 'Andi',
                        totalSpent: 860000,
                        lastItem: 'Topup 50K',
                        lastTime: '2 menit lalu',
                        txCount: 8,
                        tag: 'Pro',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    },
                    {
                        id: 204,
                        name: 'Maya',
                        totalSpent: 740000,
                        lastItem: 'Bundle Skin',
                        lastTime: '5 menit lalu',
                        txCount: 7,
                        tag: 'Aktif',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    },
                    {
                        id: 205,
                        name: 'Dedy',
                        totalSpent: 610000,
                        lastItem: 'Topup 20K',
                        lastTime: '10 menit lalu',
                        txCount: 6,
                        tag: 'Baru',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    },
                    {
                        id: 206,
                        name: 'Rina',
                        totalSpent: 450000,
                        lastItem: 'Voucher 50',
                        lastTime: '20 menit lalu',
                        txCount: 4,
                        tag: 'Baru',
                        deltaText: '',
                        movedUp: false,
                        movedDown: false
                    }
                ],

                items: ['Topup 20K', 'Topup 50K', 'Diamond 500', 'Diamond 1000', 'Voucher 50', 'Bundle Skin',
                    'Voucher 100'],

                init() {
                    // initial sort
                    this.buyers.sort((a, b) => b.totalSpent - a.totalSpent);
                    this.clearDeltas();
                    this.startInterval();
                },

                formatCurrency(v) {
                    return currency.format(v);
                },

                startInterval() {
                    this.stopInterval();
                    if (this.running) {
                        this.intervalId = setInterval(() => this.tick(), this.speed);
                    }
                },

                stopInterval() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                },

                restartInterval() {
                    // called when speed changed
                    this.startInterval();
                },

                toggle() {
                    this.running = !this.running;
                    if (this.running) this.startInterval();
                    else this.stopInterval();
                },

                tick() {
                    // remember previous ranks
                    const prevRank = new Map();
                    this.buyers.forEach((b, i) => prevRank.set(b.id, i));

                    // Randomly choose 1-3 buyers to simulate new purchases
                    const changes = 1 + Math.floor(Math.random() * 3);
                    for (let i = 0; i < changes; i++) {
                        const idx = Math.floor(Math.random() * this.buyers.length);
                        const b = this.buyers[idx];

                        // simulate purchase amount between 5k..200k, bias to smaller frequent buys
                        const amount = Math.round((Math.random() * Math.random()) * 200000) + 5000;
                        b.totalSpent = b.totalSpent + amount;
                        b.txCount = (b.txCount || 0) + 1;
                        b.lastItem = this.items[Math.floor(Math.random() * this.items.length)];
                        b.lastTime = 'baru saja';

                        // store delta amount for display
                        b._lastDelta = amount;
                    }

                    // re-sort by totalSpent desc
                    this.buyers.sort((a, b) => b.totalSpent - a.totalSpent);

                    // compute movement and deltaText
                    this.buyers.forEach((b, newIndex) => {
                        const oldIndex = prevRank.get(b.id);
                        const diff = (oldIndex !== undefined) ? oldIndex - newIndex : 0;
                        if (diff > 0) {
                            b.deltaText = '▲ +' + this.formatCurrency(b._lastDelta || 0);
                            b.movedUp = true;
                            b.movedDown = false;
                        } else if (diff < 0) {
                            b.deltaText = '▼ +' + this.formatCurrency(b._lastDelta || 0);
                            b.movedUp = false;
                            b.movedDown = true;
                        } else {
                            // no rank change, but may still show last purchase amount briefly
                            if (b._lastDelta) {
                                b.deltaText = '+' + this.formatCurrency(b._lastDelta);
                            } else {
                                b.deltaText = '—';
                            }
                            b.movedUp = b.movedDown = false;
                        }
                    });

                    // subtle auto-fade of highlight and clear temporary delta amounts after short time
                    setTimeout(() => {
                        this.clearDeltas();
                        this.buyers.forEach(b => {
                            delete b._lastDelta;
                        });
                    }, 900);
                },

                clearDeltas() {
                    this.buyers.forEach(b => {
                        b.movedUp = false;
                        b.movedDown = false;
                        // keep deltaText briefly; will be cleared when _lastDelta removed
                    });
                }
            }
        }
    </script>
</div>
