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
    'category_id' => '',
    'fieldForms' => [],
    'productGroups' => [],
    'autoGenerateSlug' => true,
    'inquiry' => '',
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
    'productGroups.*.name' => 'required|string|max:100',
    'productGroups.*.is_active' => 'boolean',
    'inquiry' => 'nullable|string|max:255',
]);

$generateSlug = function () {
    $this->slug = Str::slug($this->name);
};

$toggleAutoSlug = function () {
    $this->autoGenerateSlug = !$this->autoGenerateSlug;
    if ($this->autoGenerateSlug && $this->name) {
        $this->slug = Str::slug($this->name);
    }
};

$updated = function ($property) {
    if ($property === 'name' && $this->autoGenerateSlug) {
        $this->slug = Str::slug($this->name);
    }

    if ($property === 'slug') {
        $this->autoGenerateSlug = false;
    }
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

$addProductGroup = function () {
    $this->productGroups[] = [
        'name' => '',
        'is_active' => true,
        'sort_order' => count($this->productGroups),
    ];
};

$removeProductGroup = function ($index) {
    unset($this->productGroups[$index]);
    $this->productGroups = array_values($this->productGroups);
    // Update sort orders
    foreach ($this->productGroups as $i => $group) {
        $this->productGroups[$i]['sort_order'] = $i;
    }
};

$moveGroupUp = function ($index) {
    if ($index > 0) {
        $temp = $this->productGroups[$index];
        $this->productGroups[$index] = $this->productGroups[$index - 1];
        $this->productGroups[$index - 1] = $temp;

        // Update sort orders
        foreach ($this->productGroups as $i => $group) {
            $this->productGroups[$i]['sort_order'] = $i;
        }
    }
};

$moveGroupDown = function ($index) {
    if ($index < count($this->productGroups) - 1) {
        $temp = $this->productGroups[$index];
        $this->productGroups[$index] = $this->productGroups[$index + 1];
        $this->productGroups[$index + 1] = $temp;

        // Update sort orders
        foreach ($this->productGroups as $i => $group) {
            $this->productGroups[$i]['sort_order'] = $i;
        }
    }
};

$swapGroups = function ($fromIndex, $toIndex) {
    if ($fromIndex !== $toIndex && isset($this->productGroups[$fromIndex]) && isset($this->productGroups[$toIndex])) {
        $temp = $this->productGroups[$fromIndex];
        $this->productGroups[$fromIndex] = $this->productGroups[$toIndex];
        $this->productGroups[$toIndex] = $temp;

        // Update sort orders
        foreach ($this->productGroups as $i => $group) {
            $this->productGroups[$i]['sort_order'] = $i;
        }
    }
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

    // Create product groups
    foreach ($this->productGroups as $productGroup) {
        $game->productGroups()->create($productGroup);
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

<div>
    <div class="mb-6">
        <flux:heading size="xl">Tambah Game Baru</flux:heading>
        <flux:subheading>Tambahkan game baru ke katalog</flux:subheading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <flux:card>
                    <flux:field class="mb-4">
                        <flux:label>Nama Game</flux:label>
                        <flux:input wire:model.live="name" placeholder="e.g., Mobile Legends" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:label>Slug URL</flux:label>
                            <button type="button"
                                    wire:click="toggleAutoSlug"
                                    class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                {{ $autoGenerateSlug ? '🔓 Edit Manual' : '🔒 Auto Generate' }}
                            </button>
                        </div>
                        <flux:input
                            wire:model.live="slug"
                            placeholder="mobile-legends"
                            :readonly="$autoGenerateSlug"
                            class="{{ $autoGenerateSlug ? 'bg-zinc-50 dark:bg-zinc-900 text-zinc-500' : '' }}" />
                        <flux:error name="slug" />
                        <flux:description>
                            @if ($autoGenerateSlug)
                                ✨ Slug otomatis dibuat dari nama game
                            @else
                                ✏️ Mode manual: Edit slug sesuai keinginan
                            @endif
                        </flux:description>
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Provider</flux:label>
                        <flux:input wire:model="provider" placeholder="e.g., Moonton" />
                        <flux:error name="provider" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Inquiry Code (Opsional)</flux:label>
                        <flux:input wire:model="inquiry" placeholder="Masukan Inquiry" />
                        <flux:error name="inquiry" />
                    </flux:field>

                    <flux:field class="mb-4">
                        <flux:label>Deskripsi</flux:label>
                        <flux:textarea wire:model="description" rows="4" placeholder="Deskripsi singkat tentang game..." />
                        <flux:error name="description" />
                    </flux:field>
                </flux:card>

                <!-- Field Forms Section -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Form Input Game</flux:heading>
                            <flux:subheading>Field yang dibutuhkan untuk topup game ini</flux:subheading>
                        </div>
                        <flux:button type="button" wire:click="addFieldForm" size="sm" icon="plus">
                            Tambah Field
                        </flux:button>
                    </div>

                @if (count($fieldForms) > 0)
                    <div class="space-y-3">
                        @foreach ($fieldForms as $index => $fieldForm)
                            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium">Field #{{ $index + 1 }}</span>
                                    <button type="button" wire:click="removeFieldForm({{ $index }})"
                                        class="text-red-600 hover:text-red-700 text-sm">
                                        <flux:icon.trash class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Label</flux:label>
                                        <flux:input wire:model="fieldForms.{{ $index }}.label"
                                            placeholder="e.g., User ID" size="sm" />
                                        <flux:error name="fieldForms.{{ $index }}.label" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Name (Field ID)</flux:label>
                                        <flux:input wire:model="fieldForms.{{ $index }}.name"
                                            placeholder="e.g., user_id" size="sm" />
                                        <flux:error name="fieldForms.{{ $index }}.name" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Tipe Input</flux:label>
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
                                        <div class="flex items-center gap-3 mt-2">
                                            <flux:checkbox wire:model="fieldForms.{{ $index }}.is_required" />
                                            <span class="text-sm">{{ $fieldForm['is_required'] ? 'Ya' : 'Tidak' }}</span>
                                        </div>
                                    </flux:field>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-zinc-500">
                        <p class="text-sm">Belum ada field form. Klik "Tambah Field" untuk menambahkan.</p>
                    </div>
                @endif
                </flux:card>

                <!-- Product Groups Section -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Product Groups</flux:heading>
                            <flux:subheading>Kelompokkan produk game (contoh: Populer, Instan, Promo)</flux:subheading>
                        </div>
                        <flux:button type="button" wire:click="addProductGroup" size="sm" icon="plus">
                            Tambah Group
                        </flux:button>
                    </div>

                    @if (count($productGroups) > 0)
                        <div class="space-y-3"
                             x-data="{
                                 dragging: null,
                                 dragover: null,
                                 startDrag(index) {
                                     this.dragging = index;
                                 },
                                 endDrag() {
                                     this.dragging = null;
                                     this.dragover = null;
                                 },
                                 dragOver(index, event) {
                                     event.preventDefault();
                                     this.dragover = index;
                                 },
                                 drop(index) {
                                     if (this.dragging !== null && this.dragging !== index) {
                                         $wire.swapGroups(this.dragging, index);
                                     }
                                     this.endDrag();
                                 }
                             }">
                            @foreach ($productGroups as $index => $group)
                                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-move transition-all"
                                     draggable="true"
                                     @dragstart="startDrag({{ $index }})"
                                     @dragend="endDrag()"
                                     @dragover="dragOver({{ $index }}, $event)"
                                     @drop="drop({{ $index }})"
                                     :class="{
                                         'opacity-50': dragging === {{ $index }},
                                         'border-blue-500 border-2': dragover === {{ $index }} && dragging !== {{ $index }}
                                     }">
                                    <div class="flex items-start gap-3">
                                        <!-- Drag Handle -->
                                        <div class="mt-2 text-zinc-400 pointer-events-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                        </div>

                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-sm font-medium">Group #{{ $index + 1 }}</span>
                                                <button type="button" wire:click="removeProductGroup({{ $index }})"
                                                    class="text-red-600 hover:text-red-700 text-sm">
                                                    <flux:icon.trash class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <flux:field>
                                                    <flux:label>Nama Group</flux:label>
                                                    <flux:input wire:model="productGroups.{{ $index }}.name"
                                                        placeholder="e.g., Produk Populer" size="sm" />
                                                    <flux:error name="productGroups.{{ $index }}.name" />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Status</flux:label>
                                                    <div class="flex items-center gap-3 mt-2">
                                                        <flux:checkbox wire:model="productGroups.{{ $index }}.is_active" />
                                                        <span class="text-sm">{{ $group['is_active'] ? 'Aktif' : 'Nonaktif' }}</span>
                                                    </div>
                                                </flux:field>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <flux:description class="mt-3">
                            💡 Gunakan tombol ↑ ↓ untuk mengubah urutan tampilan group
                        </flux:description>
                    @else
                        <div class="text-center py-8 text-zinc-500">
                            <p class="text-sm">Belum ada product group. Klik "Tambah Group" untuk menambahkan.</p>
                        </div>
                    @endif
                </flux:card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <flux:card>
                    <flux:field class="mb-4">
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="category_id" placeholder="Pilih category...">
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category_id" />
                    </flux:field>

                    <flux:field class="mb-4" x-data="{ imagePreview: null }">
                        <flux:label>Gambar Game</flux:label>
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
                        <div x-show="imagePreview" class="mt-2">
                            <img :src="imagePreview" class="w-32 rounded" alt="Preview">
                        </div>
                    </flux:field>

                    <flux:field class="mb-4" x-data="{ bannerPreview: null }">
                        <flux:label>Banner Game</flux:label>
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
                        <div x-show="bannerPreview" class="mt-2">
                            <img :src="bannerPreview" class="w-full rounded" alt="Preview">
                        </div>
                    </flux:field>

                    <flux:separator class="mb-4" />

                    <flux:field class="mb-4">
                        <div class="flex items-center gap-3">
                            <flux:checkbox wire:model="is_active" />
                            <flux:label>Aktifkan Game</flux:label>
                        </div>
                    </flux:field>
                </flux:card>

                <flux:card>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">Simpan</flux:button>
                        <flux:button href="{{ route('admin.games.index') }}" wire:navigate variant="ghost">Batal</flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
    </form>
</div>
