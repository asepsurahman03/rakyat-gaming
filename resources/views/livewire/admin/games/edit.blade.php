<?php

use App\Models\Game;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use function Livewire\Volt\{state, mount, rules, uses};

uses([WithFileUploads::class]);

state([
    'game',
    'name' => '',
    'slug' => '',
    'provider' => '',
    'description' => '',
    'image' => null,
    'is_active' => true,
    'existingImage' => null,
]);

mount(function (Game $game) {
    $this->game = $game;
    $this->name = $game->name;
    $this->slug = $game->slug;
    $this->provider = $game->provider;
    $this->description = $game->description;
    $this->is_active = $game->is_active;
    $this->existingImage = $game->image;
});

rules(fn () => [
    'name' => 'required|string|max:255',
    'slug' => 'required|string|max:255|unique:games,slug,' . $this->game->id,
    'provider' => 'required|string|max:255',
    'description' => 'nullable|string',
    'image' => 'nullable|image|max:2048',
    'is_active' => 'boolean',
]);

$generateSlug = function () {
    $this->slug = Str::slug($this->name);
};

$save = function () {
    $validated = $this->validate();

    if ($this->image) {
        // Delete old image
        if ($this->game->image) {
            Storage::disk('public')->delete($this->game->image);
        }
        $validated['image'] = $this->image->store('games', 'public');
    } else {
        unset($validated['image']);
    }

    $this->game->update($validated);

    $this->redirect(route('admin.games.index'), navigate: true);
    session()->flash('success', 'Game berhasil diperbarui');
};

?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.games.index') }}" wire:navigate class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>
        <div>
            <h1 class="text-2xl font-bold">Edit Game</h1>
            <p class="text-base-content/60">Edit informasi game</p>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="form-control md:col-span-2">
                        <label class="label">
                            <span class="label-text font-semibold">Nama Game <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="text"
                            wire:model.live="name"
                            wire:change="generateSlug"
                            class="input input-bordered @error('name') input-error @enderror"
                            placeholder="e.g., Mobile Legends"
                        >
                        @error('name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Slug -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Slug <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="text"
                            wire:model="slug"
                            class="input input-bordered @error('slug') input-error @enderror"
                            placeholder="mobile-legends"
                        >
                        @error('slug') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        <label class="label">
                            <span class="label-text-alt">URL: /game/{{ $slug ?: 'slug-here' }}</span>
                        </label>
                    </div>

                    <!-- Provider -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Provider <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="text"
                            wire:model="provider"
                            class="input input-bordered @error('provider') input-error @enderror"
                            placeholder="e.g., Moonton"
                        >
                        @error('provider') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-control md:col-span-2">
                        <label class="label">
                            <span class="label-text font-semibold">Deskripsi</span>
                        </label>
                        <textarea
                            wire:model="description"
                            class="textarea textarea-bordered @error('description') textarea-error @enderror"
                            rows="3"
                            placeholder="Deskripsi singkat tentang game..."
                        ></textarea>
                        @error('description') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Image -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Gambar</span>
                        </label>

                        @if ($existingImage && !$image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($existingImage) }}" class="w-32 h-32 object-cover rounded-lg" alt="Current image">
                                <p class="text-xs text-base-content/60 mt-1">Gambar saat ini</p>
                            </div>
                        @endif

                        <input
                            type="file"
                            wire:model="image"
                            accept="image/*"
                            class="file-input file-input-bordered @error('image') file-input-error @enderror"
                        >
                        @error('image') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        <label class="label">
                            <span class="label-text-alt">Max 2MB, format: JPG, PNG. Kosongkan jika tidak ingin mengubah.</span>
                        </label>

                        @if ($image)
                            <div class="mt-2">
                                <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg" alt="Preview">
                                <p class="text-xs text-base-content/60 mt-1">Preview gambar baru</p>
                            </div>
                        @endif
                    </div>

                    <!-- Active Status -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Status</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" wire:model="is_active" class="toggle toggle-success" />
                            <span class="label-text">{{ $is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </label>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="flex gap-2 justify-end">
                    <a href="{{ route('admin.games.index') }}" wire:navigate class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading.remove>Simpan Perubahan</span>
                        <span wire:loading class="loading loading-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
