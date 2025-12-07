<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\Post;
use App\Models\Game;

new #[Layout('components.layouts.guest'), Title('Articles')] class extends Component {
    use WithPagination;

    public function title(): string
    {
        return __('messages.artikel');
    }

    public $gameFilter = '';
    public $search = '';

    public function with(): array
    {
        $query = Post::with(['game', 'author'])
            ->published()
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery
                        ->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('excerpt', 'like', '%' . $this->search . '%')
                        ->orWhere('content', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gameFilter, function ($q) {
                $q->where('game_id', $this->gameFilter);
            })
            ->latest();

        // Artikel Populer - berdasarkan views terbanyak
        $popularPosts = Post::with(['game', 'author'])
            ->published()
            ->orderBy('views', 'desc')
            ->limit(4)
            ->get();

        // Artikel Terbaru
        $latestPosts = Post::with(['game', 'author'])
            ->published()
            ->latest()
            ->limit(4)
            ->get();

        return [
            'posts' => $query->paginate(9),
            'games' => Game::where('is_active', true)
                ->whereHas('posts', function ($q) {
                    $q->published();
                })
                ->orderBy('name')
                ->get(),
            'popularPosts' => $popularPosts,
            'latestPosts' => $latestPosts,
        ];
    }

    public function updatingGameFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div class="bg-gradient-to-br from-[#470000] to-[#6d0000]">
    <div class="mb-4 md:mb-6 py-6">
        <div class="container mx-auto px-4 max-w-7xl">
            <flux:heading size="xl" class="text-yellow-500 mb-2">{{ __('messages.artikel') }}</flux:heading>
            <flux:subheading class="text-white/90">{{ __('messages.search_articles') }}</flux:subheading>
        </div>
    </div>

    <div class="pb-16 md:pb-24">
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Search & Filter -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <flux:input type="text" icon="magnifying-glass" wire:model.live.debounce.300ms="search"
                    :placeholder="__('messages.search_articles')" />
                <flux:select wire:model.live="gameFilter" :placeholder="__('messages.all_categories')" class="w-full md:max-w-xs">
                    <flux:select.option value="">{{ __('messages.all_categories') }}</flux:select.option>
                    @foreach ($games as $game)
                        <flux:select.option value="{{ $game->id }}">{{ $game->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Main Content -->
            <div class="mb-12">
                <flux:heading size="xl" class="mb-6">{{ __('messages.popular_articles') }}</flux:heading>

                @if($popularPosts->count() > 0)
                    <!-- Posts Grid: 2 Columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Column 1: Featured Post (Post #1) -->
                        <div>
                            @php $featuredPost = $popularPosts->first(); @endphp
                            <a href="{{ route('guest.posts.show', $featuredPost->slug) }}" wire:navigate class="group block h-full">
                                <flux:card class="h-full hover:shadow-xl transition-shadow group-hover:border-yellow-400">
                                    @if ($featuredPost->featured_image)
                                        <div class="aspect-video overflow-hidden rounded-lg">
                                            <img src="{{ Storage::url($featuredPost->featured_image) }}" alt="{{ $featuredPost->title }}"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                        </div>
                                    @else
                                        <div class="aspect-video bg-gradient-to-br from-indigo-600 to-purple-600 rounded-t-lg flex items-center justify-center">
                                            <x-icon name="document-text" class="w-16 h-16 text-white opacity-50" />
                                        </div>
                                    @endif

                                    <div class="p-6">
                                        <div class="flex items-center gap-2 mb-3">
                                            <flux:badge size="sm" color="yellow">{{ $featuredPost->game->name }}</flux:badge>
                                        </div>

                                        <flux:heading size="lg" class="mb-3">{{ $featuredPost->title }}</flux:heading>

                                        @if ($featuredPost->excerpt)
                                            <flux:subheading class="mb-4">{{ $featuredPost->excerpt }}</flux:subheading>
                                        @endif

                                        <div class="flex items-center justify-between text-sm text-zinc-500">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $featuredPost->author->name }}
                                            </span>
                                            <span>{{ $featuredPost->published_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </flux:card>
                            </a>
                        </div>

                        <!-- Column 2: Posts #2, #3, #4 (Horizontal Layout) -->
                        <div class="space-y-4">
                            @foreach($popularPosts->skip(1)->take(3) as $post)
                                <a href="{{ route('guest.posts.show', $post->slug) }}" wire:navigate class="group block">
                                    <flux:card class="hover:shadow-xl transition-shadow group-hover:border-yellow-400">
                                        <div class="flex gap-4">
                                            <!-- Image -->
                                            @if ($post->featured_image)
                                                <div class="flex-shrink-0 w-28 h-28 overflow-hidden rounded-lg">
                                                    <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                                </div>
                                            @else
                                                <div class="flex-shrink-0 w-28 h-28 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <x-icon name="document-text" class="w-8 h-8 text-white opacity-50" />
                                                </div>
                                            @endif

                                            <!-- Content -->
                                            <div class="flex-1 min-w-0 py-2">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <flux:badge size="sm" color="yellow">{{ $post->game->name }}</flux:badge>
                                                </div>

                                                <flux:heading size="sm" class="line-clamp-2 mb-2">{{ $post->title }}</flux:heading>
                                                <div class="sm:flex items-center justify-between text-xs text-zinc-500">
                                                    <flux:text class="mb-1.5 text-yellow-400">{{ $post->author->name }}</flux:text>
                                                    <span>{{ $post->published_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </flux:card>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <x-icon name="document-text" class="w-16 h-16 text-zinc-300 mx-auto mb-4" />
                        <flux:heading size="md" class="mb-2">Belum Ada Artikel Populer</flux:heading>
                        <flux:subheading>Artikel populer akan muncul di sini</flux:subheading>
                    </div>
                @endif
            </div>

            <!-- All Posts Section -->
            <div class="mb-12">
                <flux:heading size="xl" class="mb-6">{{ __('messages.all_articles') }}</flux:heading>
                <!-- Posts Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($posts as $post)
                        <a href="{{ route('guest.posts.show', $post->slug) }}" wire:navigate class="group">
                            <flux:card class="h-full hover:shadow-xl transition-shadow group-hover:border-yellow-400">
                                @if ($post->featured_image)
                                    <div class="aspect-video overflow-hidden rounded-t-lg">
                                        <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                @else
                                    <div
                                        class="aspect-video bg-gradient-to-br from-indigo-600 to-purple-600 rounded-t-lg flex items-center justify-center">
                                        <x-icon name="document-text" class="w-16 h-16 text-white opacity-50" />
                                    </div>
                                @endif

                                <div class="p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:badge size="sm" color="yellow">{{ $post->game->name }}
                                        </flux:badge>
                                    </div>

                                    <flux:heading size="sm" class="line-clamp-2 mb-2">{{ $post->title }}
                                    </flux:heading>

                                    @if ($post->excerpt)
                                        <flux:subheading size="sm" class="line-clamp-2 mb-3">
                                            {{ $post->excerpt }}</flux:subheading>
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
                            <flux:heading size="md" class="mb-2">{{ __('messages.no_articles') }}</flux:heading>
                            <flux:subheading>
                                @if ($search)
                                    {{ __('messages.no_articles_desc') }} "{{ $search }}"
                                @else
                                    {{ __('messages.no_articles_desc') }}
                                @endif
                            </flux:subheading>
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
</div>
