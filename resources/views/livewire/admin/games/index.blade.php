<?php

use App\Models\Game;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Games')] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete(Game $game)
    {
        // $game->delete();
        Flux::toast(heading: 'Changes saved.', text: 'You can always update this in your settings.');
    }

    public function with(): array
    {
        return [
            'games' => Game::with('category')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('provider', 'like', "%{$this->search}%"))->latest()->paginate(12),
        ];
    }
};

?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Games</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Games Management</flux:heading>
            <flux:subheading>Kelola katalog game untuk layanan top-up</flux:subheading>
        </div>
        <flux:button href="{{ route('admin.games.create') }}" wire:navigate icon="plus" variant="primary">
            Tambah Game
        </flux:button>
    </div>

    <flux:card>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari game..." icon="magnifying-glass" />
    </flux:card>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($games as $game)
            <flux:card>
                <div class="aspect-square rounded-lg overflow-hidden mb-4">
                    @if ($game->image)
                        <img src="{{ Storage::url($game->image) }}" alt="{{ $game->name }}"
                            class="w-full h-full object-cover">
                    @else
                        <div
                            class="w-full h-full bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900 flex items-center justify-center">
                            <flux:icon.puzzle class="w-16 h-16 text-zinc-400" />
                        </div>
                    @endif
                </div>

                <flux:heading size="sm">{{ $game->name }}</flux:heading>
                <flux:subheading class="text-xs">{{ $game->provider }}</flux:subheading>

                @if($game->category)
                    <div class="mt-1">
                        <flux:badge color="zinc" size="sm">{{ $game->category->icon }} {{ $game->category->name }}</flux:badge>
                    </div>
                @endif

                <div class="mt-2">
                    <flux:badge :color="$game->is_active ? 'green' : 'red'" size="sm">
                        {{ $game->is_active ? 'Aktif' : 'Nonaktif' }}
                    </flux:badge>
                </div>

                <div class="flex gap-2 mt-4">
                    <flux:button href="{{ route('admin.games.edit', $game) }}" wire:navigate size="sm"
                        variant="primary" class="flex-1">
                        Edit
                    </flux:button>
                    <flux:button wire:click="delete({{ $game->id }})" wire:confirm="Yakin ingin menghapus game ini?"
                        size="sm" variant="danger" class="flex-1">
                        Hapus
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full">
                <flux:card>
                    <div class="text-center py-12 text-zinc-500">
                        <flux:icon.puzzle class="w-16 h-16 mx-auto mb-4" />
                        <p>Belum ada game. Silakan tambah game baru.</p>
                    </div>
                </flux:card>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $games->links() }}
    </div>
</div>
