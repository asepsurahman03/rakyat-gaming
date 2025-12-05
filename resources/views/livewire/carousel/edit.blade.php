<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title, Validate};

new #[Layout('components.layouts.app'), Title('Edit Carousel')] class extends Component {
    use WithFileUploads;

    public Carousel $carousel;

    #[Validate('nullable|image|max:2048')]
    public $image;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('nullable|string')]
    public $description = '';

    public function mount(Carousel $carousel)
    {
        $this->carousel = $carousel;
        $this->title = $carousel->title;
        $this->description = $carousel->description;
    }

    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:2048',
        ]);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description,
        ];

        // Upload new image if provided
        if ($this->image) {
            // Delete old image
            if ($this->carousel->image_path && \Storage::disk('public')->exists($this->carousel->image_path)) {
                \Storage::disk('public')->delete($this->carousel->image_path);
            }

            $data['image_path'] = $this->image->store('carousels', 'public');
        }

        // Update carousel
        $this->carousel->update($data);

        session()->flash('success', 'Carousel berhasil diupdate!');

        return $this->redirect(route('carousel.index'), navigate: true);
    }

    public function deleteImage()
    {
        if ($this->carousel->image_path && \Storage::disk('public')->exists($this->carousel->image_path)) {
            \Storage::disk('public')->delete($this->carousel->image_path);
            $this->carousel->update(['image_path' => null]);

            session()->flash('success', 'Gambar berhasil dihapus!');

            return $this->redirect(route('carousel.edit', $this->carousel), navigate: true);
        }
    }
}; ?>

<div class="py-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('carousel.index') }}" wire:navigate variant="ghost" icon="arrow-left" circle />
        <div>
            <flux:heading size="xl">Edit Carousel</flux:heading>
            <flux:subheading>Ubah data banner carousel</flux:subheading>
        </div>
    </div>

    <!-- Form -->
    <flux:card>
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ imagePreview: null }">
                <!-- Left Column - Form Fields -->
                <div class="space-y-6">
                    <flux:field>
                        <flux:label>
                            {{ $carousel->image_path ? 'Ganti Gambar' : 'Upload Gambar' }}
                            @if (!$carousel->image_path)
                                <span class="text-red-500">*</span>
                            @endif
                        </flux:label>
                        <input type="file" wire:model="image" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => imagePreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" />
                        <flux:description>Max 2MB (JPG, PNG, GIF)</flux:description>
                        <flux:error name="image" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Judul <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="title" placeholder="Masukkan judul carousel" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" placeholder="Masukkan deskripsi carousel (opsional)" rows="4" />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <!-- Right Column - Image Preview -->
                <div>
                    @if ($carousel->image_path && !$image)
                        <flux:field>
                            <flux:label>
                                <flux:icon.photo class="inline size-5" />
                                Gambar Saat Ini
                            </flux:label>
                            <div class="relative">
                                <div class="rounded-lg overflow-hidden border-2 border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ Storage::url($carousel->image_path) }}"
                                         alt="{{ $carousel->title }}"
                                         class="w-full h-auto" />
                                </div>
                                <div class="absolute top-2 right-2">
                                    <flux:button
                                        type="button"
                                        wire:click="deleteImage"
                                        wire:confirm="Apakah Anda yakin ingin menghapus gambar ini?"
                                        variant="danger"
                                        size="sm"
                                        icon="trash"
                                    >
                                        Hapus Gambar
                                    </flux:button>
                                </div>
                            </div>
                        </flux:field>
                    @endif

                    <div x-show="imagePreview" style="display: none;">
                        <flux:field>
                            <flux:label>Preview Gambar Baru:</flux:label>
                            <div class="rounded-lg overflow-hidden border-2 border-zinc-200 dark:border-zinc-700">
                                <img :src="imagePreview" alt="Preview" class="w-full h-auto" />
                            </div>
                        </flux:field>
                    </div>

                    @if (!$carousel->image_path)
                        <div x-show="!imagePreview">
                            <flux:field>
                                <flux:label>Preview:</flux:label>
                                <div class="rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 h-64 flex items-center justify-center">
                                    <div class="text-center text-zinc-400">
                                        <flux:icon.photo class="size-16 mx-auto mb-2" />
                                        <flux:subheading>No image</flux:subheading>
                                    </div>
                                </div>
                            </flux:field>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-end mt-6">
                <flux:button href="{{ route('carousel.index') }}" wire:navigate variant="ghost" icon="x-mark">
                    Batal
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                    wire:loading.attr="disabled"
                    wire:target="save,image"
                >
                    <span wire:loading.remove wire:target="save,image">
                        Update Carousel
                    </span>
                    <span wire:loading wire:target="save,image" class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        Mengupdate...
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
