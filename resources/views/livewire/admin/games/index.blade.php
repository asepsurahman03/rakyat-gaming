<?php

use App\Models\Game;
use function Livewire\Volt\{state, with};

state(['search' => '']);

with(fn () => [
    'games' => Game::query()
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('provider', 'like', "%{$this->search}%"))
        ->latest()
        ->paginate(12)
]);

$delete = function (Game $game) {
    $game->delete();
    $this->dispatch('success', message: 'Game berhasil dihapus');
};

?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li>Games</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-primary badge-lg">GAMES</span>
                Management
            </h1>
            <p class="text-base-content/60 mt-2">Kelola katalog game untuk layanan top-up</p>
        </div>
        <a href="{{ route('admin.games.create') }}" wire:navigate class="btn btn-primary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Game
        </a>
    </div>

    <!-- Search -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="form-control">
                <div class="input-group">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari game..."
                        class="input input-bordered w-full"
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Games Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($games as $game)
            <div class="card bg-base-100 shadow-xl">
                <figure class="aspect-square">
                    @if($game->image)
                        <img src="{{ Storage::url($game->image) }}" alt="{{ $game->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-white opacity-50">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                            </svg>
                        </div>
                    @endif
                </figure>
                <div class="card-body">
                    <h2 class="card-title text-sm">{{ $game->name }}</h2>
                    <p class="text-xs text-base-content/60">{{ $game->provider }}</p>
                    <div class="badge {{ $game->is_active ? 'badge-success' : 'badge-error' }} badge-sm">
                        {{ $game->is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                    <div class="card-actions justify-end mt-4">
                        <a href="{{ route('admin.games.edit', $game) }}" wire:navigate class="btn btn-sm btn-primary">Edit</a>
                        <button wire:click="delete({{ $game->id }})" wire:confirm="Yakin ingin menghapus game ini?" class="btn btn-sm btn-error">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Belum ada game. Silakan tambah game baru.</span>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $games->links() }}
    </div>
</div>
