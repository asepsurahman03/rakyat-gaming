<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Tambah Carousel')] class extends Component {
    use WithFileUploads;

    public $image;

    public $title = '';

    public $loading = false;

    public $description = '';

    public function rules(): array
    {
        return [
            'image' => 'required|image|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    // public function updatedImage()
    // {
    //     $this->validate([
    //         'image' => 'image|max:2048',
    //     ]);
    // }

    public function save()
    {
        $this->loading = true;
        $this->validate();

        // Upload image
        $imagePath = $this->image->store('carousels', 'public');

        // Create carousel
        Carousel::create([
            'image_path' => $imagePath,
            'title' => $this->title,
            'description' => $this->description,
        ]);
        Flux::toast(
            heading: 'Carousel berhasil ditambahkan!',
            text: 'Data carousel baru telah disimpan ke sistem.',
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );
        $this->loading = false;

        return $this->redirect(route('carousel.index'), navigate: true);
    }
}; ?>

<div class="py-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('carousel.index') }}" wire:navigate variant="ghost" icon="arrow-left" circle />
        <div>
            <flux:heading size="xl">Tambah Carousel Baru</flux:heading>
            <flux:subheading>Tambahkan banner carousel untuk website Anda</flux:subheading>
        </div>
    </div>

    <!-- Form -->
    <flux:card>
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ imagePreview: null }">
                <!-- Left Column - Form Fields -->
                <div class="space-y-6">
                    <flux:field>
                        <flux:label>Judul Carousel <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="title" placeholder="Masukkan judul carousel" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Gambar Carousel <span class="text-red-500">*</span></flux:label>
                        <input type="file" wire:model="image" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => imagePreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                            class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200 dark:file:bg-neutral-800 dark:file:text-neutral-300 dark:hover:file:bg-neutral-700" />
                        <flux:description>Max size 2MB</flux:description>
                        <flux:error name="image" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" placeholder="Masukkan deskripsi carousel (opsional)" rows="4" />
                        <flux:description>Optional</flux:description>
                    </flux:field>
                </div>

                <!-- Right Column - Image Preview -->
                <div>
                    <div x-show="imagePreview" style="display: none;">
                        <flux:field>
                            <flux:label>Preview Image:</flux:label>
                            <div class="rounded-lg overflow-hidden border-2 border-zinc-200 dark:border-zinc-700">
                                <img :src="imagePreview" alt="Preview" class="w-full h-auto object-cover" />
                            </div>
                        </flux:field>
                    </div>

                    <div x-show="!imagePreview">
                        <flux:field>
                            <flux:label>Preview:</flux:label>
                            <div class="rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 h-64 flex items-center justify-center">
                                <div class="text-center text-zinc-400">
                                    <flux:icon.photo class="size-16 mx-auto mb-2" />
                                    <flux:subheading>No image selected</flux:subheading>
                                </div>
                            </div>
                        </flux:field>
                    </div>
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
                        Simpan Carousel
                    </span>
                    <span wire:loading wire:target="save,image" class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        Menyimpan...
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
