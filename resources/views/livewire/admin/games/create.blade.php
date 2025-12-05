<?php

use App\Models\Game;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title};
use function Livewire\Volt\{state, rules, uses, computed};

uses([WithFileUploads::class]);

state([
    'name' => '',
    'slug' => '',
    'provider' => '',
    'description' => '',
    'image' => null,
    'banner' => null,
    'is_active' => true,
    'category_id' => null,
    'fieldForms' => [],
]);

$categories = computed(fn() => Category::where('is_active', true)->orderBy('sort_order')->get());

rules([
    'name' => 'required|string|max:255',
    'slug' => 'required|string|max:255|unique:games,slug',
    'provider' => 'required|string|max:255',
    'description' => 'nullable|string',
    'category_id' => 'required|exists:categories,id',
    'image' => 'nullable|image|max:2048',
    'banner' => 'nullable|image|max:2048',
    'is_active' => 'boolean',
    'fieldForms.*.label' => 'required|string|max:255',
    'fieldForms.*.name' => 'required|string|max:255',
    'fieldForms.*.type' => 'required|in:text,number,email,tel,textarea',
    'fieldForms.*.is_required' => 'boolean',
]);

$generateSlug = function () {
    $this->slug = Str::slug($this->name);
};

$addFieldForm = function () {
    $this->fieldForms[] = [
        'label' => '',
        'name' => '',
        'type' => 'text',
        'is_required' => true,
    ];
};

$removeFieldForm = function ($index) {
    unset($this->fieldForms[$index]);
    $this->fieldForms = array_values($this->fieldForms);
};

$save = function () {
    $validated = $this->validate();

    if ($this->image) {
        $validated['image'] = $this->image->store('games', 'public');
    }

    if ($this->banner) {
        $validated['banner'] = $this->banner->store('games', 'public');
    }

    $game = Game::create($validated);

    // Create field forms
    foreach ($this->fieldForms as $fieldForm) {
        $game->fieldForms()->create($fieldForm);
    }
    Flux::toast(
        heading: 'Game berhasil ditambahkan!',
        text: 'Data game baru telah disimpan ke sistem.',
        variant: 'success',
        duration: 3000,
        position: 'top-right',
    );

    $this->redirect(route('admin.games.index'), navigate: true);
};

?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.games.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Tambah Game Baru</flux:heading>
            <flux:subheading>Tambahkan game baru ke katalog</flux:subheading>
        </div>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Nama Game <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model.live="name" wire:change="generateSlug"
                            placeholder="e.g., Mobile Legends" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <!-- Slug -->
                <div>
                    <flux:field>
                        <flux:label>Slug <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="slug" placeholder="mobile-legends" />
                        <flux:error name="slug" />
                        <flux:description>URL: /game/{{ $slug ?: 'slug-here' }}</flux:description>
                    </flux:field>
                </div>

                <!-- Provider -->
                <div>
                    <flux:field>
                        <flux:label>Provider <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="provider" placeholder="e.g., Moonton" />
                        <flux:error name="provider" />
                    </flux:field>
                </div>

                <!-- Category -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Category <span class="text-red-600">*</span></flux:label>
                        <flux:select wire:model="category_id" placeholder="Pilih category...">
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category_id" />
                    </flux:field>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" placeholder="Deskripsi singkat tentang game..."
                            rows="3" />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <!-- Image -->
                <div x-data="{ imagePreview: null }">
                    <flux:field>
                        <flux:label>Gambar</flux:label>

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

                        <flux:error name="image" />
                    </flux:field>

                    <div x-show="imagePreview" class="mt-2">
                        <img :src="imagePreview" class="w-32 h-32 object-cover rounded-lg" alt="Preview">
                    </div>
                </div>

                <!-- Banner -->
                <div x-data="{ bannerPreview: null }">
                    <flux:field>
                        <flux:label>Banner</flux:label>
                        <input type="file" wire:model="banner" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => bannerPreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            "
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700" />
                        <flux:error name="banner" />
                        <flux:description>Max 2MB, format: JPG, PNG</flux:description>
                    </flux:field>

                    <div x-show="bannerPreview" class="mt-2">
                        <img :src="bannerPreview" class="w-full h-32 object-cover rounded-lg" alt="Preview">
                    </div>
                </div>

                <!-- Active Status -->
                <div>
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:switch wire:model="is_active" />
                            <flux:label>{{ $is_active ? 'Aktif' : 'Nonaktif' }}</flux:label>
                        </div>
                    </flux:field>
                </div>
            </div>

            <flux:separator />

            <!-- Field Forms Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Form Input Game</flux:heading>
                        <flux:subheading>Tambahkan field input yang diperlukan untuk pembelian produk game ini
                        </flux:subheading>
                    </div>
                    <flux:button type="button" wire:click="addFieldForm" size="sm" icon="plus">
                        Tambah Field
                    </flux:button>
                </div>

                @if (count($fieldForms) > 0)
                    <div class="space-y-4">
                        @foreach ($fieldForms as $index => $fieldForm)
                            <flux:card class="bg-zinc-50 dark:bg-zinc-900">
                                <div class="flex items-center justify-between mb-3">
                                    <flux:heading size="sm">Field #{{ $index + 1 }}</flux:heading>
                                    <flux:button type="button" wire:click="removeFieldForm({{ $index }})"
                                        variant="ghost" size="xs" icon="trash" class="text-red-600" />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Label <span class="text-red-600">*</span></flux:label>
                                        <flux:input wire:model="fieldForms.{{ $index }}.label"
                                            placeholder="e.g., User ID" size="sm" />
                                        <flux:error name="fieldForms.{{ $index }}.label" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Name (Field ID) <span class="text-red-600">*</span></flux:label>
                                        <flux:input wire:model="fieldForms.{{ $index }}.name"
                                            placeholder="e.g., user_id" size="sm" />
                                        <flux:error name="fieldForms.{{ $index }}.name" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Tipe Input <span class="text-red-600">*</span></flux:label>
                                        <flux:select wire:model="fieldForms.{{ $index }}.type" size="sm">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="email">Email</option>
                                            <option value="tel">Phone</option>
                                            <option value="textarea">Textarea</option>
                                        </flux:select>
                                        <flux:error name="fieldForms.{{ $index }}.type" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Wajib Diisi?</flux:label>
                                        <div class="flex items-center gap-3">
                                            <flux:checkbox wire:model="fieldForms.{{ $index }}.is_required" />
                                            <span
                                                class="text-sm">{{ $fieldForm['is_required'] ? 'Ya' : 'Tidak' }}</span>
                                        </div>
                                    </flux:field>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:separator />

            <div class="flex gap-2 justify-end">
                <flux:button wire:navigate href="{{ route('admin.games.index') }}" variant="ghost">Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove>Simpan</span>
                    <flux:icon.arrow-path wire:loading class="animate-spin" />
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
