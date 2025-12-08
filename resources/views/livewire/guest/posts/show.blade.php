<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\Post;

new #[Layout('components.layouts.guest'), Title('Artikel')] class extends Component {
    public Post $post;
    public $shareUrl = '';

    public function mount(Post $post)
    {
        // Only show published posts
        if (!$post->is_published || !$post->published_at || $post->published_at->isFuture()) {
            abort(404);
        }

        $this->post = $post->load(['game', 'author']);

        // Increment views
        $post->incrementViews();

        $this->shareUrl = route('guest.posts.show', $post->slug);
    }

    public function with(): array
    {
        // Get related posts from same game
        $relatedPosts = Post::with(['game', 'author'])
            ->published()
            ->where('game_id', $this->post->game_id)
            ->where('id', '!=', $this->post->id)
            ->latest()
            ->limit(3)
            ->get();

        return [
            'relatedPosts' => $relatedPosts,
        ];
    }

    public function getTitle(): string
    {
        return $this->post->title;
    }
}; ?>

<div class="bg-gradient-to-bl from-[#7E0000] to-[#470000] py-12">
    <!-- Hero Section -->
        <div class="container mx-auto px-4 max-w-7xl mb-4 md:mb-6">
            <div class="mb-4">
                <flux:badge color="yellow" size="lg">{{ $post->game->name }}</flux:badge>
            </div>
            <flux:heading size="2xl" class="text-white mb-4">{{ $post->title }}</flux:heading>

            <div class="flex items-center gap-4 text-white/90 text-sm">
                <div class="flex items-center gap-2">
                    <x-icon name="user" class="w-4 h-4" />
                    <span>{{ $post->author->name }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-icon name="calendar" class="w-4 h-4" />
                    <span>{{ $post->published_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

    <!-- Content Section -->
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <flux:card>
                        @if ($post->featured_image)
                            <div class="mb-6">
                                <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                    class="w-full rounded-lg">
                            </div>
                        @endif

                        <div class="prose prose-zinc dark:prose-invert max-w-none
                            [&>p]:mb-4
                            [&>h1]:mt-6 [&>h1]:mb-4
                            [&>h2]:mt-6 [&>h2]:mb-4
                            [&>h3]:mt-6 [&>h3]:mb-4
                            [&>ul]:my-4 [&>ul]:list-disc [&>ul]:pl-8 [&>ul]:space-y-2
                            [&>ol]:my-4 [&>ol]:list-decimal [&>ol]:pl-8 [&>ol]:space-y-2
                            [&>ul>li]:ml-0 [&>ol>li]:ml-0
                            [&_ul]:list-disc [&_ol]:list-decimal">
                            {!! $post->content !!}
                        </div>

                        @if ($post->tags && count($post->tags) > 0)
                            <flux:separator class="my-6" />
                            <div class="flex flex-wrap gap-2">
                                @foreach ($post->tags as $tag)
                                    <flux:badge color="zinc">{{ $tag }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </flux:card>

                    <!-- Share Buttons -->
                    <flux:card class="mt-6">
                        <flux:heading size="sm" class="mb-4">{{ __('messages.share_article') }}</flux:heading>
                        <div class="flex flex-wrap gap-3">
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                <span>Facebook</span>
                            </a>

                            <!-- Twitter -->
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}"
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                                <span>Twitter</span>
                            </a>

                            <!-- WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($post->title . ' - ' . $shareUrl) }}"
                                target="_blank"
                                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                                <span>WhatsApp</span>
                            </a>

                            <!-- Copy Link -->
                            <button
                                onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); alert('Link berhasil disalin!');"
                                class="flex items-center gap-2 px-4 py-2 bg-zinc-600 text-white rounded-lg hover:bg-zinc-700 transition">
                                <x-icon name="link" class="w-5 h-5" />
                                <span>Copy Link</span>
                            </button>
                        </div>
                    </flux:card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Game Info -->
                    <flux:card>
                        <flux:heading size="sm" class="mb-4">{{ __('messages.about_game') }}</flux:heading>
                        @if ($post->game->image)
                            <img src="{{ Storage::url($post->game->image) }}" alt="{{ $post->game->name }}"
                                class="w-full rounded-lg mb-3">
                        @endif
                        <flux:heading size="md" class="mb-2">{{ $post->game->name }}</flux:heading>
                        @if ($post->game->description)
                            <flux:subheading size="sm" class="text-justify">{{ $post->game->description }}</flux:subheading>
                        @endif
                        <flux:button href="{{ route('game.detail', $post->game->slug) }}" wire:navigate
                            variant="primary" class="w-full mt-4">
                            {{ __('messages.view_details') }}
                        </flux:button>
                    </flux:card>

                    <!-- Related Posts -->
                    @if ($relatedPosts->count() > 0)
                        <flux:card>
                            <flux:heading size="sm" class="mb-4">{{ __('messages.related_articles') }}</flux:heading>
                            <div class="space-y-4">
                                @foreach ($relatedPosts as $related)
                                    <a href="{{ route('guest.posts.show', $related->slug) }}" wire:navigate
                                        class="block group">
                                        <div class="flex gap-3">
                                            @if ($related->featured_image)
                                                <img src="{{ Storage::url($related->featured_image) }}"
                                                    alt="{{ $related->title }}" class="w-20 object-cover rounded">
                                            @else
                                                <div
                                                    class="w-20 h-20 bg-zinc-200 dark:bg-zinc-700 rounded flex items-center justify-center shrink-0">
                                                    <x-icon name="document-text" class="w-6 h-6 text-zinc-400" />
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <div
                                                    class="text-sm font-medium line-clamp-2 group-hover:text-yellow-600 transition">
                                                    {{ $related->title }}
                                                </div>
                                                <div class="text-xs text-zinc-500 mt-1">
                                                    {{ $related->published_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    @if (!$loop->last)
                                        <flux:separator />
                                    @endif
                                @endforeach
                            </div>
                        </flux:card>
                    @endif
                </div>
            </div>
        </div>
</div>
