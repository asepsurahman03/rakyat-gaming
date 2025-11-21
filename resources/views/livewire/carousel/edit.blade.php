<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title, Validate};

new #[Layout('components.layouts.app.sidebar'), Title('Edit Carousel')]
class extends Component {
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

<div class="p-6">
    <!-- Header -->
    <div class="mb-6 bg-gradient-to-r from-info/10 to-warning/10 p-6 rounded-lg border border-info/20">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('carousel.index') }}" wire:navigate class="btn btn-circle btn-ghost btn-lg hover:bg-info/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
            </a>
            <div class="w-12 h-12 bg-info rounded-lg flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-info-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold">Edit Carousel</h1>
                <p class="text-base-content/70 mt-1">✏️ Ubah data banner carousel</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="card bg-base-100 shadow-xl border border-info/20">
        <div class="card-body">
            <form wire:submit="save">
                <!-- Current Image -->
                @if ($carousel->image_path && !$image)
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-semibold flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                🖼️ Gambar Saat Ini
                            </span>
                        </label>
                        <div class="relative w-full max-w-md">
                            <div class="rounded-lg overflow-hidden border border-base-300">
                                <img src="{{ Storage::url($carousel->image_path) }}" alt="{{ $carousel->title }}" class="w-full h-auto" />
                            </div>
                            <button
                                type="button"
                                wire:click="deleteImage"
                                wire:confirm="Apakah Anda yakin ingin menghapus gambar ini?"
                                class="btn btn-error btn-sm gap-1 absolute top-2 right-2 shadow-lg"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Hapus Gambar
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Image Upload -->
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text font-semibold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            {{ $carousel->image_path ? '🔄 Ganti Gambar' : '💾 Upload Gambar' }}
                            @if(!$carousel->image_path)
                                <span class="text-error">*</span>
                            @endif
                        </span>
                    </label>
                    <input
                        type="file"
                        wire:model="image"
                        class="file-input file-input-bordered file-input-info w-full"
                        accept="image/*"
                    />
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Max 2MB (JPG, PNG, GIF)</span>
                    </label>
                    @error('image')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror

                    <!-- Image Preview -->
                    @if ($image)
                        <div class="mt-4">
                            <p class="text-sm font-semibold mb-2">Preview Gambar Baru:</p>
                            <div class="w-full max-w-md rounded-lg overflow-hidden border border-base-300">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-full h-auto" />
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Title -->
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text font-semibold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            Judul <span class="text-error">*</span>
                        </span>
                    </label>
                    <input
                        type="text"
                        wire:model="title"
                        placeholder="Masukkan judul carousel"
                        class="input input-bordered w-full @error('title') input-error @else input-info @enderror"
                    />
                    @error('title')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Description -->
                <div class="form-control w-full mb-6">
                    <label class="label">
                        <span class="label-text font-semibold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Deskripsi
                        </span>
                    </label>
                    <textarea
                        wire:model="description"
                        placeholder="Masukkan deskripsi carousel (opsional)"
                        class="textarea textarea-bordered h-24 @error('description') textarea-error @else textarea-info @enderror"
                    ></textarea>
                    @error('description')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end">
                    <a href="{{ route('carousel.index') }}" wire:navigate class="btn btn-ghost btn-lg gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Batal
                    </a>
                    <button type="submit" class="btn btn-warning btn-lg gap-2 shadow-lg" wire:loading.attr="disabled" wire:target="save,image">
                        <span wire:loading.remove wire:target="save,image">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                            </svg>
                            Update Carousel
                        </span>
                        <span wire:loading wire:target="save,image" class="flex items-center gap-2">
                            <span class="loading loading-spinner loading-sm"></span>
                            Mengupdate...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
