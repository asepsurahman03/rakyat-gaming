<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.guest'), Title('Kalkulator Zodiac Mobile Legends - Rakyat Gaming')] class extends Component {
    public $currentPoints = 0;
    public $targetPoints = 50;
    public $diamondsPerDraw = 25; // Harga 1x draw
    public $showResults = false;

    public function calculate()
    {
        // Sanitize inputs
        $this->currentPoints = max(0, intval($this->currentPoints ?? 0));
        $this->targetPoints = max(0, intval($this->targetPoints ?? 50));

        $this->showResults = true;
    }

    public function updated($propertyName)
    {
        // Reset results when input changes
        $this->showResults = false;
    }

    public function calculateNeeded()
    {
        $pointsNeeded = max(0, $this->targetPoints - $this->currentPoints);
        $drawsNeeded = $pointsNeeded; // 1 draw = 1 point di Zodiac

        return [
            'points_needed' => $pointsNeeded,
            'draws_needed' => $drawsNeeded,
            'diamonds_needed' => $drawsNeeded * $this->diamondsPerDraw,
            'draws_10x' => ceil($drawsNeeded / 10),
            'diamonds_10x' => ceil($drawsNeeded / 10) * 240, // 10x draw = 240 diamond
            'discount_10x' => (($drawsNeeded * 25) - (ceil($drawsNeeded / 10) * 240)),
        ];
    }
}; ?>

<div class="py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Kalkulator Zodiac</h1>
        </div>

        <!-- Calculator Card -->
        <flux:card class="mb-6">
            <!-- Input Fields -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <flux:label class="block text-sm font-medium mb-2">Poin Sekarang</flux:label>
                    <flux:input
                        type="number"
                        wire:model="currentPoints"
                        min="0"
                        placeholder="0"
                        class="no-spinner text-center text-lg font-semibold"/>
                </div>

                <div>
                    <flux:label class="block text-sm font-medium mb-2">Target Poin</flux:label>
                    <flux:input
                        type="number"
                        wire:model="targetPoints"
                        min="0"
                        placeholder="50"
                        class="no-spinner text-center text-lg font-semibold"/>
                </div>
            </div>

            <!-- Calculate Button -->
            <div class="mb-4">
                <flux:button
                    wire:click="calculate" variant="primary" class="w-full">
                    Hitung Diamond
                </flux:button>
            </div>

            @if($showResults)
                @php
                    $result = $this->calculateNeeded();
                @endphp

                <flux:separator class="mb-6" />

                <!-- Results -->
                <div class="space-y-4">
                    <!-- Points Needed -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white text-center">
                        <div class="text-sm font-medium opacity-90 mb-1">Poin yang Dibutuhkan</div>
                        <div class="text-5xl font-bold mb-2">{{ number_format($result['points_needed']) }}</div>
                        <div class="text-sm opacity-90">{{ number_format($currentPoints) }} → {{ number_format($targetPoints) }}</div>
                    </div>

                    <!-- Single Draw -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-500 rounded-lg p-4 text-center">
                        <div class="text-blue-700 dark:text-blue-300 font-bold text-sm mb-1">Single Draw</div>
                        <div class="text-blue-900 dark:text-blue-100 text-2xl font-bold">
                            {{ number_format($result['draws_needed']) }}x = 💎 {{ number_format($result['diamonds_needed']) }}
                        </div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">(25 💎/draw)</div>
                    </div>

                    <!-- Bundle 10x -->
                    @if($result['draws_needed'] >= 10)
                        <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-500 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <div class="text-green-700 dark:text-green-300 font-bold text-sm">Bundle 10x</div>
                                <flux:badge color="red" size="sm" class="font-bold">-{{ number_format($result['discount_10x']) }} 💎</flux:badge>
                            </div>
                            <div class="text-green-900 dark:text-green-100 text-2xl font-bold">
                                {{ number_format($result['draws_10x']) }}x = 💎 {{ number_format($result['diamonds_10x']) }}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-1">(240 💎/bundle)</div>
                        </div>
                    @endif
                </div>
            @endif
        </flux:card>
    </div>
</div>
