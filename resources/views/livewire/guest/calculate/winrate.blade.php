<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.guest'), Title('Kalkulator Winrate - Rakyat Gaming')] class extends Component {
    public $totalMatches = 0;
    public $totalWins = 0;
    public $targetWinrate = 60;
    public $showResults = false;

    public function calculate()
    {
        // Sanitize inputs
        $this->totalMatches = max(0, intval($this->totalMatches ?? 0));
        $this->totalWins = max(0, intval($this->totalWins ?? 0));
        $this->targetWinrate = max(0, min(100, floatval($this->targetWinrate ?? 60)));

        // Make sure wins doesn't exceed total matches
        if ($this->totalWins > $this->totalMatches) {
            $this->totalWins = $this->totalMatches;
        }

        $this->showResults = true;
    }

    public function updated($propertyName)
    {
        // Reset results when input changes
        $this->showResults = false;
    }

    public function calculateCurrent()
    {
        $matches = intval($this->totalMatches ?? 0);
        $wins = intval($this->totalWins ?? 0);

        if ($matches == 0) {
            return 0;
        }
        return ($wins / $matches) * 100;
    }

    public function calculateNeeded()
    {
        $currentWinrate = $this->calculateCurrent();
        $target = floatval($this->targetWinrate ?? 60) / 100;
        $matches = intval($this->totalMatches ?? 0);
        $wins = intval($this->totalWins ?? 0);

        // Handle edge case: target is 100%
        if ($target >= 1) {
            return [
                'current_winrate' => $currentWinrate,
                'matches_needed' => 0,
                'wins_needed' => 0,
                'status' => 'impossible'
            ];
        }

        if ($currentWinrate >= ($target * 100)) {
            return [
                'current_winrate' => $currentWinrate,
                'matches_needed' => 0,
                'wins_needed' => 0,
                'status' => 'achieved'
            ];
        }

        // Rumus: (totalWins + x) / (totalMatches + x) = targetWinrate/100
        // totalWins + x = (targetWinrate/100) * (totalMatches + x)
        // totalWins + x = (targetWinrate/100) * totalMatches + (targetWinrate/100) * x
        // x - (targetWinrate/100) * x = (targetWinrate/100) * totalMatches - totalWins
        // x * (1 - targetWinrate/100) = (targetWinrate/100) * totalMatches - totalWins
        // x = ((targetWinrate/100) * totalMatches - totalWins) / (1 - targetWinrate/100)

        $winsNeeded = ceil((($target * $matches) - $wins) / (1 - $target));

        return [
            'current_winrate' => $currentWinrate,
            'matches_needed' => max(0, $winsNeeded),
            'wins_needed' => max(0, $winsNeeded),
            'status' => 'need_improvement'
        ];
    }    public function calculateLoss()
    {
        // Berapa kali boleh kalah untuk maintain winrate
        $currentWinrate = $this->calculateCurrent();
        $target = floatval($this->targetWinrate ?? 60) / 100;
        $matches = intval($this->totalMatches ?? 0);
        $wins = intval($this->totalWins ?? 0);

        // Handle edge cases
        if ($matches == 0 || $currentWinrate <= ($target * 100) || $target == 0) {
            return 0;
        }

        // (totalWins) / (totalMatches + x) = targetWinrate/100
        // totalWins = (targetWinrate/100) * (totalMatches + x)
        // totalWins = (targetWinrate/100) * totalMatches + (targetWinrate/100) * x
        // totalWins - (targetWinrate/100) * totalMatches = (targetWinrate/100) * x
        // x = (totalWins - (targetWinrate/100) * totalMatches) / (targetWinrate/100)

        $lossAllowed = floor(($wins - ($target * $matches)) / $target);

        return max(0, $lossAllowed);
    }
}; ?>

<div class="bg-gradient-to-br from-[#470000] to-[#6d0000] py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Kalkulator Winrate</h1>
        </div>

        @php
            $currentWR = $this->calculateCurrent();
            $result = $this->calculateNeeded();
            $lossAllowed = $this->calculateLoss();
            $safeMatches = intval($totalMatches ?? 0);
            $safeWins = intval($totalWins ?? 0);
            $totalLosses = $safeMatches - $safeWins;

            // Determine color based on winrate
            $wrColor = 'from-zinc-500 to-zinc-700';
            if ($currentWR >= 70) {
                $wrColor = 'from-purple-500 to-pink-600';
            } elseif ($currentWR >= 60) {
                $wrColor = 'from-blue-500 to-cyan-600';
            } elseif ($currentWR >= 50) {
                $wrColor = 'from-green-500 to-emerald-600';
            } elseif ($currentWR >= 40) {
                $wrColor = 'from-yellow-500 to-orange-500';
            } elseif ($currentWR > 0) {
                $wrColor = 'from-red-500 to-rose-700';
            }
        @endphp

        <!-- Calculator Card -->
        <flux:card class="mb-6">
            <!-- Input Fields -->
            <div class="mb-4">
                <flux:field>
                    <flux:label class="mb-2">Total Match</flux:label>
                    <flux:input
                        type="number"
                        wire:model="totalMatches"
                        min="0"
                        placeholder="0"
                        class="no-spinner text-center text-lg font-semibold" />
                </flux:field>

                <flux:field>
                    <flux:label class="mb-2">Total Win</flux:label>
                    <flux:input
                        type="number"
                        wire:model="totalWins"
                        min="0"
                        max="{{ $totalMatches ?? 0 }}"
                        placeholder="0"
                        class="no-spinner text-center text-lg font-semibold" />
                </flux:field>

                <flux:field>
                    <flux:label class="mb-2">Target WR %</flux:label>
                    <flux:input
                        type="number"
                        wire:model="targetWinrate"
                        min="0"
                        max="100"
                        step="0.1"
                        placeholder="60"
                        class="no-spinner text-center text-lg font-semibold" />
                </flux:field>
            </div>

            <!-- Calculate Button -->
            <div class="mb-6">
                <flux:button
                    wire:click="calculate"
                    variant="primary"
                    class="w-full">
                    Hitung Winrate
                </flux:button>
            </div>

            @if($showResults)
                <flux:separator class="mb-6" />

            <!-- Results -->
            <div class="space-y-4">
                <!-- Current Winrate -->
                <div class="bg-gradient-to-br {{ $wrColor }} rounded-xl p-6 text-white text-center">
                    <div class="text-sm font-medium opacity-90 mb-1">Winrate Saat Ini</div>
                    <div class="text-5xl font-bold mb-2">{{ number_format($currentWR, 2) }}%</div>
                    <div class="text-sm opacity-90">
                        {{ number_format($safeWins) }}W / {{ number_format(max(0, $totalLosses)) }}L
                    </div>
                </div>

                <!-- Result Info -->
                @if($result['status'] == 'achieved')
                    <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-500 rounded-lg p-4 text-center">
                        <div class="text-green-700 dark:text-green-300 font-bold text-lg mb-1">
                            🎉 Target {{ $targetWinrate }}% Tercapai!
                        </div>
                        @if($lossAllowed > 0)
                            <div class="text-green-600 dark:text-green-400 text-sm">
                                Kamu masih bisa kalah <span class="font-bold">{{ number_format($lossAllowed) }}x</span> untuk maintain WR
                            </div>
                        @endif
                    </div>
                @elseif($safeMatches > 0)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-500 rounded-lg p-4 text-center">
                        <div class="text-blue-700 dark:text-blue-300 font-bold text-lg mb-1">
                            🎯 Butuh Win Streak
                        </div>
                        <div class="text-blue-900 dark:text-blue-100 text-3xl font-bold">
                            {{ number_format($result['matches_needed']) }}x 🔥
                        </div>
                        <div class="text-blue-600 dark:text-blue-400 text-xs mt-1">
                            (tanpa kekalahan untuk mencapai {{ $targetWinrate }}%)
                        </div>
                    </div>
                @endif
            </div>
            @endif
        </flux:card>
    </div>
</div>
