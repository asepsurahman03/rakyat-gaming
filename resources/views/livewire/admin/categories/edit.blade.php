<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Validate};
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app'), Title('Edit Category')] class extends Component {
    public Category $category;

    #[Validate('required|string|max:255')]
    public $name = '';

    public $slug = '';

    #[Validate('nullable|string|max:10')]
    public $icon = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('boolean')]
    public $is_active = true;

    #[Validate('integer|min:0')]
    public $sort_order = 0;

    public function mount(Category $category)
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->icon = $category->icon;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->sort_order = $category->sort_order;
    }

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($this->category->id)],
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function save()
    {
        $this->validate();

        $this->category->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(variant: 'success', heading: 'Berhasil!', text: 'Category berhasil diperbarui.', duration: 3000, position: 'top-right');

        return $this->redirect(route('admin.categories.index'), navigate: true);
    }
};

?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.categories.index') }}" wire:navigate>Categories</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <flux:heading size="xl">Edit Category</flux:heading>
        <flux:subheading>Perbarui informasi kategori game</flux:subheading>
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

            @if($category->games()->count() > 0)
                <flux:callout variant="info">
                    <flux:callout.heading>Category ini memiliki {{ $category->games()->count() }} game</flux:callout.heading>
                </flux:callout>
            @endif
        </flux:card>

        <div class="flex gap-3 justify-end">
            <flux:button href="{{ route('admin.categories.index') }}" wire:navigate variant="ghost">
                Batal
            </flux:button>
            <flux:button type="submit" variant="primary">
                Simpan Perubahan
            </flux:button>
        </div>
    </form>
</div>
