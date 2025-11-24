<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Validate};
use Livewire\WithFileUploads;
use App\Models\Post;
use App\Models\Game;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app'), Title('Edit Artikel')] class extends Component {
    use WithFileUploads;

    public Post $post;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('nullable|string|max:255')]
    public $slug = '';

    #[Validate('required|exists:games,id')]
    public $game_id = '';

    #[Validate('nullable|string|max:500')]
    public $excerpt = '';

    #[Validate('required|string')]
    public $content = '';

    #[Validate('nullable|image|max:2048')]
    public $featured_image = null;

    public $existing_image = '';
    public $tags = '';

    #[Validate('boolean')]
    public $is_published = false;

    public function mount(Post $post)
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->game_id = $post->game_id;
        $this->excerpt = $post->excerpt;
        $this->content = $post->content;
        $this->existing_image = $post->featured_image;
        $this->tags = $post->tags ? implode(', ', $post->tags) : '';
        $this->is_published = $post->is_published;
    }

    public function with(): array
    {
        return [
            'games' => Game::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function updatedTitle()
    {
        if (empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function removeImage()
    {
        if ($this->existing_image) {
            Storage::delete($this->existing_image);
            $this->existing_image = '';
        }
    }

    public function save()
    {
        $this->validate([
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $this->post->id,
        ]);

        $validated = $this->validate();

        $imagePath = $this->existing_image;

        if ($this->featured_image) {
            // Delete old image
            if ($this->existing_image) {
                Storage::delete($this->existing_image);
            }
            $imagePath = $this->featured_image->store('posts', 'public');
        }

        $tagsArray = [];
        if (!empty($this->tags)) {
            $tagsArray = array_map('trim', explode(',', $this->tags));
        }

        $this->post->update([
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'featured_image' => $imagePath,
            'tags' => $tagsArray,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published && !$this->post->published_at ? now() : $this->post->published_at,
        ]);

        Flux::toast('Artikel berhasil diupdate!');

        return $this->redirect(route('admin.posts.index'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Edit Artikel</flux:heading>
        <flux:subheading>Edit artikel: {{ $post->title }}</flux:subheading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <flux:card>
                    <flux:field>
                        <flux:label>Judul Artikel</flux:label>
                        <flux:input wire:model.live.debounce.300ms="title" placeholder="Masukkan judul artikel..." />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="url-artikel" />
                        <flux:error name="slug" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Excerpt (Ringkasan)</flux:label>
                        <flux:textarea wire:model="excerpt" rows="3" placeholder="Ringkasan singkat artikel..." />
                        <flux:error name="excerpt" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Konten</flux:label>
                        <flux:textarea wire:model="content" rows="15" placeholder="Tulis konten artikel di sini..." />
                        <flux:error name="content" />
                    </flux:field>
                </flux:card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <flux:card>
                    <flux:field>
                        <flux:label>Game</flux:label>
                        <flux:select wire:model="game_id" placeholder="Pilih Game">
                            @foreach ($games as $game)
                                <option value="{{ $game->id }}">{{ $game->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="game_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tags</flux:label>
                        <flux:input wire:model="tags" placeholder="tag1, tag2, tag3" />
                        <flux:description>Pisahkan dengan koma</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Featured Image</flux:label>

                        @if ($existing_image && !$featured_image)
                            <div class="mb-2 relative">
                                <img src="{{ Storage::url($existing_image) }}" class="w-full rounded" alt="Current">
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="danger"
                                    wire:click="removeImage"
                                    class="absolute top-2 right-2">
                                    Hapus
                                </flux:button>
                            </div>
                        @endif

                        <flux:input type="file" wire:model="featured_image" accept="image/*" />
                        <flux:error name="featured_image" />

                        @if ($featured_image)
                            <div class="mt-2">
                                <img src="{{ $featured_image->temporaryUrl() }}" class="w-full rounded" alt="Preview">
                            </div>
                        @endif
                    </flux:field>

                    <flux:separator />

                    <flux:field>
                        <flux:checkbox wire:model="is_published">
                            <flux:label>Publikasikan</flux:label>
                        </flux:checkbox>
                    </flux:field>
                </flux:card>

                <flux:card>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" class="flex-1">Update</flux:button>
                        <flux:button href="{{ route('admin.posts.index') }}" wire:navigate variant="ghost">Batal</flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
    </form>
</div>
