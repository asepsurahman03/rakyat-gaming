<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Validate};
use Livewire\WithFileUploads;
use App\Models\Post;
use App\Models\Game;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app'), Title('Tambah Artikel')] class extends Component {
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('nullable|string|max:255|unique:posts,slug')]
    public $slug = '';

    #[Validate('required|exists:games,id')]
    public $game_id = '';

    #[Validate('nullable|string|max:500')]
    public $excerpt = '';

    #[Validate('required|string')]
    public $content = '';

    #[Validate('nullable|image|max:2048')]
    public $featured_image = null;

    public $tags = '';

    #[Validate('boolean')]
    public $is_published = false;

    public function mount()
    {
        //
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

    public function save()
    {
        $validated = $this->validate();

        $imagePath = null;
        if ($this->featured_image) {
            $imagePath = $this->featured_image->store('posts', 'public');
        }

        $tagsArray = [];
        if (!empty($this->tags)) {
            $tagsArray = array_map('trim', explode(',', $this->tags));
        }

        $post = Post::create([
            'game_id' => $validated['game_id'],
            'author_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'featured_image' => $imagePath,
            'tags' => $tagsArray,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published ? now() : null,
        ]);

        Flux::toast('Artikel berhasil dibuat!');

        return $this->redirect(route('admin.posts.index'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Tambah Artikel Baru</flux:heading>
        <flux:subheading>Buat artikel baru untuk game</flux:subheading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <flux:card>
                    <flux:field class="mb-4">
                        <flux:label>Judul Artikel</flux:label>
                        <flux:input wire:model.live.debounce.300ms="title" placeholder="Masukkan judul artikel..." />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="url-artikel" />
                        <flux:error name="slug" />
                        <flux:description>Biarkan kosong untuk generate otomatis dari judul</flux:description>
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Excerpt (Ringkasan)</flux:label>
                        <flux:textarea wire:model="excerpt" rows="3" placeholder="Ringkasan singkat artikel..." />
                        <flux:error name="excerpt" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Konten</flux:label>
                        <flux:textarea wire:model="content" rows="15"
                            placeholder="Tulis konten artikel di sini..." />
                        <flux:error name="content" />
                    </flux:field>
                </flux:card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <flux:card>
                    <flux:field class="mb-4">
                        <flux:label>Game</flux:label>
                        <flux:select wire:model="game_id" placeholder="Pilih Game">
                            @foreach ($games as $game)
                                <option value="{{ $game->id }}">{{ $game->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="game_id" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Tags</flux:label>
                        <flux:input wire:model="tags" placeholder="tag1, tag2, tag3" />
                        <flux:description>Pisahkan dengan koma</flux:description>
                    </flux:field>

                    <flux:field class="mb-4" x-data="{ imagePreview: null }">
                        <flux:label>Featured Image</flux:label>
                        <input type="file" wire:model="featured_image" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => imagePreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" />
                        <flux:error name="featured_image" />
                        <div x-show="imagePreview" class="mt-2">
                            <img :src="imagePreview" class="w-full rounded" alt="Preview">
                        </div>
                    </flux:field>

                    <flux:separator class="mb-4" />

                    <flux:field class="mb-4">
                        <div class="flex items-center gap-3">
                            <flux:checkbox wire:model="is_published" />
                            <flux:label>Publikasikan Sekarang</flux:label>
                        </div>
                    </flux:field>
                </flux:card>

                <flux:card>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">Simpan</flux:button>
                        <flux:button href="{{ route('admin.posts.index') }}" wire:navigate variant="ghost">Batal
                        </flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
    </form>
</div>
