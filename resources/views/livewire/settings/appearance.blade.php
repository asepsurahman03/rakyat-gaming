<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:dropdown x-data align="end">
        <flux:button variant="subtle" square class="group" aria-label="Preferred color scheme">
            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-white dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-white dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
            <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
        </flux:button>

        <flux:menu>
            <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
            <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
            <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</section>
