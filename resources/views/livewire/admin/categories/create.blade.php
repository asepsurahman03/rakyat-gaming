<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Validate};
use Flux\Flux;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app'), Title('Create Category')] class extends Component {
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255|unique:categories,slug')]
    public $slug = '';

    #[Validate('nullable|string|max:10')]
    public $icon = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('boolean')]
    public $is_active = true;

    #[Validate('integer|min:0')]
    public $sort_order = 0;

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate();

        Category::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(variant: 'success', heading: 'Berhasil!', text: 'Category berhasil ditambahkan.');

        return $this->redirect(route('admin.categories.index'), navigate: true);
    }
};

?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.categories.index') }}" wire:navigate>Categories</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <flux:heading size="xl">Tambah Category</flux:heading>
        <flux:subheading>Buat kategori baru untuk mengelompokkan game</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Name *</flux:label>
                    <flux:input wire:model.live="name" placeholder="Contoh: MOBA" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug *</flux:label>
                    <flux:input wire:model="slug" placeholder="moba" />
                    <flux:error name="slug" />
                    <flux:description>Otomatis terisi dari nama</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Icon (Emoji)</flux:label>
                    <flux:input wire:model="icon" placeholder="⚔️" />
                    <flux:error name="icon" />
                    <flux:description>Gunakan emoji untuk icon category</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input type="number" wire:model="sort_order" placeholder="0" />
                    <flux:error name="sort_order" />
                    <flux:description>Urutan tampilan (angka kecil lebih dulu)</flux:description>
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" placeholder="Deskripsi singkat tentang category..." rows="3" />
                <flux:error name="description" />
            </flux:field>

            <flux:separator />

            <flux:field>
                <flux:checkbox wire:model="is_active">
                    <flux:label>Aktifkan Category</flux:label>
                    <flux:description>Category aktif akan ditampilkan di halaman customer</flux:description>
                </flux:checkbox>
            </flux:field>
        </flux:card>

        <div class="flex gap-3 justify-end">
            <flux:button href="{{ route('admin.categories.index') }}" wire:navigate variant="ghost">
                Batal
            </flux:button>
            <flux:button type="submit" variant="primary">
                Simpan Category
            </flux:button>
        </div>
    </form>
</div>
