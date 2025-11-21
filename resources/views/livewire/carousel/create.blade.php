<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title, Validate};

new #[Layout('components.layouts.app.sidebar'), Title('Tambah Carousel')] class extends Component {
    use WithFileUploads;

    #[Validate('required|image|max:2048')]
    public $image;

    #[Validate('required|string|max:255')]
    public $title = '';

    public $loading = false;

    #[Validate('nullable|string')]
    public $description = '';

    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:2048',
        ]);
    }

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

        session()->flash('success', 'Carousel berhasil ditambahkan!');
        $this->loading = false;

        return $this->redirect(route('carousel.index'), navigate: true);
    }
}; ?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6 bg-gradient-to-r from-primary to-secondary p-6 rounded-lg border border-success/20">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('carousel.index') }}" wire:navigate
                class="btn btn-circle btn-ghost btn-lg hover:bg-success/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold">Tambah Carousel Baru</h1>
                <p class="text-base-content/70 mt-1">Tambahkan banner carousel untuk website Anda</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="card bg-base-200/50 shadow-xl border border-success/20">
        <div class="card-body">
            <form wire:submit="save">
                <!-- Image Upload -->
                <div class="sm:flex gap-4">
                    <div class="form-control w-full mb-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                Judul Carousel<span class="text-error">*</span>
                            </legend>
                            <input type="text" class="input input-primary" wire:model="title"
                                placeholder="Type here" />
                            @error('title')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend"> <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Gambar Carousel <span class="text-error">*</span></legend>
                            <input type="file" wire:model="image" class="file-input file-input-primary"
                                accept="image/*" />
                            <label class="label">Max size 2MB</label>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend"> <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Deskripsi
                            </legend>
                            <textarea class="textarea h-24 textarea-primary" wire:model="description"
                                placeholder="Masukkan deskripsi carousel (opsional)"></textarea>
                            <div class="label">Optional</div>
                        </fieldset>
                        @error('image')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="w-full">
                        @if ($image)
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">Preview Image:</span>
                                </label>

                                {{-- Loader saat Upload --}}
                                <div wire:loading wire:target="image"
                                    class="h-64 flex items-center justify-center border border-dashed rounded-lg">
                                    <span class="loading loading-spinner loading-lg text-primary"></span>
                                </div>

                                {{-- Preview Gambar --}}
                                <div wire:loading.remove wire:target="image"
                                    class="rounded-lg overflow-hidden border-2 border-primary/20 shadow-lg">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                                        class="w-full h-auto object-cover" />
                                </div>
                            </div>
                        @else
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">Preview:</span>
                                </label>
                                <div
                                    class="rounded-lg border-2 border-dashed border-base-300 bg-base-100 h-64 flex items-center justify-center">
                                    <div class="text-center text-base-content/40">
                                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p>No image selected</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end">
                    <a href="{{ route('carousel.index') }}" wire:navigate class="btn btn-ghost btn-lg gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg gap-2 shadow-lg"
                        wire:loading.attr="disabled" wire:target="save,image">
                        <span wire:loading wire:target="save,image" class="hidden">
                            <span class="loading loading-spinner loading-sm"></span>
                            Menyimpan...
                        </span>
                        <span wire:loading.remove wire:target="save,image" class="flex gap-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Simpan Carousel
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
