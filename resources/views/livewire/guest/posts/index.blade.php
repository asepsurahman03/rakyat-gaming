<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\Post;
use App\Models\Game;

new #[Layout('components.layouts.guest'), Title('Artikel')] class extends Component {
    use WithPagination;

    public $gameFilter = '';

    public function with(): array
    {
        $query = Post::with(['game', 'author'])
            ->published()
            ->when($this->gameFilter, function ($q) {
                $q->where('game_id', $this->gameFilter);
            })
            ->latest();

        return [
            'posts' => $query->paginate(12),
            'games' => Game::where('is_active', true)
                ->whereHas('posts', function ($q) {
                    $q->published();
                })
                ->orderBy('name')
                ->get(),
        ];
    }

    public function updatingGameFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="bg-gradient-to-br from-[#470000] to-[#6d0000]">
    <div class=" py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <flux:heading size="xl" class="text-yellow-500 mb-2">Artikel & Berita</flux:heading>
            <flux:subheading class="text-white/90">Temukan tips, trik, dan berita terbaru seputar game</flux:subheading>
        </div>
    </div>

    <div class="py-12">
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Filter -->
            <div class="mb-8">
                <flux:select wire:model.live="gameFilter" placeholder="Semua Game" class="max-w-xs">
                    <option value="">Semua Game</option>
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Posts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($posts as $post)
                    <a href="{{ route('guest.posts.show', $post->slug) }}" wire:navigate class="group">
                        <flux:card class="h-full hover:shadow-xl transition-shadow">
                            @if ($post->featured_image)
                                <div class="aspect-video overflow-hidden rounded-t-lg">
                                    <img src="{{ Storage::url($post->featured_image) }}"
                                         alt="{{ $post->title }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                </div>
                            @else
                                <div class="aspect-video bg-gradient-to-br from-indigo-600 to-purple-600 rounded-t-lg flex items-center justify-center">
                                    <x-icon name="document-text" class="w-16 h-16 text-white opacity-50" />
                                </div>
                            @endif

                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:badge size="sm" color="indigo">{{ $post->game->name }}</flux:badge>
                                    <flux:badge size="sm" color="zinc">
                                        <x-icon name="eye" class="w-3 h-3" />
                                        {{ number_format($post->views) }}
                                    </flux:badge>
                                </div>

                                <flux:heading size="sm" class="line-clamp-2 mb-2">{{ $post->title }}</flux:heading>

                                @if ($post->excerpt)
                                    <flux:subheading size="sm" class="line-clamp-2 mb-3">{{ $post->excerpt }}</flux:subheading>
                                @endif

                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <span>{{ $post->author->name }}</span>
                                    <span>{{ $post->published_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </flux:card>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12">
                        <x-icon name="document-text" class="w-16 h-16 text-zinc-300 mx-auto mb-4" />
                        <flux:heading size="md" class="mb-2">Belum Ada Artikel</flux:heading>
                        <flux:subheading>Artikel akan segera ditambahkan</flux:subheading>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
