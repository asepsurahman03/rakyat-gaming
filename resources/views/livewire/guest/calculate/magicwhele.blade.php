<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.guest'), Title('Kalkulator Magic Wheel - Rakyat Gaming')] class extends Component {
    public $currentPoints = 0;
    public $targetPoints = 200;
    public $diamondsPerSpin = 60;
    public $showResults = false;

    public function calculate()
    {
        // Sanitize inputs
        $this->currentPoints = max(0, intval($this->currentPoints ?? 0));
        $this->targetPoints = max(0, intval($this->targetPoints ?? 200));

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
        $spinsNeeded = ceil($pointsNeeded / 5); // Setiap spin = 5 poin

        return [
            'points_needed' => $pointsNeeded,
            'spins_needed' => $spinsNeeded,
            'diamonds_needed' => $spinsNeeded * $this->diamondsPerSpin,
            'spins_5x' => ceil($spinsNeeded / 5),
            'diamonds_5x' => ceil($spinsNeeded / 5) * 270,
            'spins_50x' => ceil($spinsNeeded / 50),
            'diamonds_50x' => ceil($spinsNeeded / 50) * 2400,
        ];
    }
}; ?>

<div class="py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Kalkulator Magic Wheel</h1>
        </div>

        <!-- Calculator Card -->
        <flux:card class="mb-6">
            <!-- Input Fields -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <flux:label class="mb-2">Poin Sekarang</flux:label>
                    <flux:input
                        type="number"
                        wire:model="currentPoints"
                        min="0"
                        placeholder="0"
                        class="no-spinner text-center text-lg font-semibold" />
                </div>

                <div>
                    <flux:label class="mb-2">Target Poin</flux:label>
                    <flux:input
                        type="number"
                        wire:model="targetPoints"
                        min="0"
                        placeholder="200"
                        class="no-spinner text-center text-lg font-semibold" />
                </div>
            </div>

            <!-- Calculate Button -->
            <div class="mb-4">
                <flux:button wire:click="calculate" variant="primary" class="w-full">
                    Hitung Diamond
                </flux:button>
            </div>

            @if ($showResults)
                @php
                    $result = $this->calculateNeeded();
                @endphp

                <flux:separator class="mb-6" />

                <!-- Results -->
                <div class="space-y-4">
                    <!-- Points Needed -->
                    <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-6 text-white text-center">
                        <div class="text-sm font-medium opacity-90 mb-1">Poin yang Dibutuhkan</div>
                        <div class="text-5xl font-bold mb-2">{{ number_format($result['points_needed']) }}</div>
                        <div class="text-sm opacity-90">{{ number_format($currentPoints) }} →
                            {{ number_format($targetPoints) }}</div>
                    </div>
                    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium">Progress</span>
                            <span class="text-zinc-600 dark:text-zinc-400">
                                {{ number_format($currentPoints) }} / {{ number_format($targetPoints) }} poin
                            </span>
                        </div>
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-4">
                            <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-4 rounded-full transition-all duration-300"
                                style="width: {{ min(100, ($currentPoints / max($targetPoints, 1)) * 100) }}%">
                            </div>
                        </div>
                        <div class="text-xs text-center mt-2 text-zinc-600 dark:text-zinc-400">
                            {{ number_format(min(100, ($currentPoints / max($targetPoints, 1)) * 100), 1) }}% tercapai
                        </div>
                    </div>
                </div>
            @endif
        </flux:card>

        <!-- Results Display -->
        @if ($showResults)
            <flux:card>
                <div class="grid grid-cols-1 gap-3">
                    <!-- Single Spin -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-500 rounded-lg p-4 text-center">
                        <div class="text-blue-700 dark:text-blue-300 font-bold text-sm mb-1">Single Spin</div>
                        <div class="text-blue-900 dark:text-blue-100 text-2xl font-bold">
                            {{ number_format($result['spins_needed']) }}x = 💎
                            {{ number_format($result['diamonds_needed']) }}
                        </div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">(60 💎/spin)</div>
                    </div>

                    <!-- Bundle 5x -->
                    @if ($result['spins_needed'] >= 5)
                        <div
                            class="bg-green-50 dark:bg-green-900/20 border-2 border-green-500 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <div class="text-green-700 dark:text-green-300 font-bold text-sm">Bundle 5x</div>
                                <flux:badge color="red" size="sm" class="font-bold">-10%</flux:badge>
                            </div>
                            <div class="text-green-900 dark:text-green-100 text-2xl font-bold">
                                {{ number_format($result['spins_5x']) }}x = 💎
                                {{ number_format($result['diamonds_5x']) }}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-1">(270 💎/bundle)</div>
                        </div>
                    @endif

                    <!-- Bundle 50x -->
                    @if ($result['spins_needed'] >= 50)
                        <div
                            class="bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-500 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <div class="text-purple-700 dark:text-purple-300 font-bold text-sm">Bundle 50x</div>
                                <flux:badge color="red" size="sm" class="font-bold">-20%</flux:badge>
                            </div>
                            <div class="text-purple-900 dark:text-purple-100 text-2xl font-bold">
                                {{ number_format($result['spins_50x']) }}x = 💎
                                {{ number_format($result['diamonds_50x']) }}
                            </div>
                            <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">(2,400 💎/bundle)</div>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif
    </div>
</div>
