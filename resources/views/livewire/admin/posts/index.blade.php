<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Post;
use App\Models\Game;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $gameFilter = '';

    public function with(): array
    {
        $query = Post::with(['game', 'author'])
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            })
            ->when($this->gameFilter, function ($q) {
                $q->where('game_id', $this->gameFilter);
            })
            ->latest();

        return [
            'posts' => $query->paginate(10),
            'games' => Game::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGameFilter()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $post = Post::findOrFail($id);

        if ($post->featured_image) {
            Storage::delete($post->featured_image);
        }

        $post->delete();

        Flux::toast('Artikel berhasil dihapus!');
    }

    public function togglePublish($id)
    {
        $post = Post::findOrFail($id);
        $post->is_published = !$post->is_published;

        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        Flux::toast($post->is_published ? 'Artikel dipublikasikan!' : 'Artikel di-unpublish!');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Artikel</flux:heading>
        <flux:button href="{{ route('admin.posts.create') }}" wire:navigate icon="plus">Tambah Artikel</flux:button>
    </div>

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari artikel..." icon="magnifying-glass" />
            </div>
            <div>
                <flux:select wire:model.live="gameFilter" placeholder="Semua Game">
                    <option value="">Semua Game</option>
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}">{{ $game->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Gambar</flux:table.column>
                <flux:table.column>Judul</flux:table.column>
                <flux:table.column>Game</flux:table.column>
                <flux:table.column>Penulis</flux:table.column>
                <flux:table.column>Views</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($posts as $post)
                    <flux:table.row :key="$post->id">
                        <flux:table.cell>
                            @if ($post->featured_image)
                                <img src="{{ Storage::url($post->featured_image) }}"
                                     alt="{{ $post->title }}"
                                     class="w-16 h-16 object-cover rounded">
                            @else
                                <div class="w-16 h-16 bg-zinc-200 dark:bg-zinc-700 rounded flex items-center justify-center">
                                    <x-icon name="document-text" class="w-6 h-6 text-zinc-400" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $post->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $post->created_at->diffForHumans() }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $post->game->name }}</flux:table.cell>
                        <flux:table.cell>{{ $post->author->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc">
                                <x-icon name="eye" class="w-3 h-3" />
                                {{ number_format($post->views) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$post->is_published ? 'green' : 'zinc'">
                                {{ $post->is_published ? 'Published' : 'Draft' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost"
                                             wire:click="togglePublish({{ $post->id }})"
                                             :icon="$post->is_published ? 'eye-slash' : 'eye'">
                                </flux:button>
                                <flux:button size="sm" variant="ghost"
                                             href="{{ route('admin.posts.edit', $post) }}"
                                             wire:navigate icon="pencil">
                                </flux:button>
                                <flux:button size="sm" variant="danger"
                                             wire:click="delete({{ $post->id }})"
                                             wire:confirm="Yakin ingin menghapus artikel ini?"
                                             icon="trash">
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2">
                                <x-icon name="document-text" class="w-12 h-12 text-zinc-300" />
                                <flux:subheading>Belum ada artikel</flux:subheading>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $posts->links() }}
        </div>
    </flux:card>
</div>
