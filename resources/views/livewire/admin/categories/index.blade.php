<?php

use App\Models\Category;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Categories')] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete(Category $category)
    {
        if ($category->games()->count() > 0) {
            Flux::toast(variant: 'danger', heading: 'Gagal menghapus!', text: 'Category masih memiliki game terkait.');
            return;
        }

        $category->delete();
        Flux::toast(variant: 'success', heading: 'Berhasil!', text: 'Category berhasil dihapus.');
    }

    public function toggleStatus(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        Flux::toast(variant: 'success', heading: 'Status diubah!', text: 'Status category berhasil diperbarui.');
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->withCount('games')
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->paginate(12),
        ];
    }
};

?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Categories</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Category Management</flux:heading>
            <flux:subheading>Kelola kategori game untuk organisasi yang lebih baik</flux:subheading>
        </div>
        <flux:button href="{{ route('admin.categories.create') }}" wire:navigate icon="plus" variant="primary">
            Tambah Category
        </flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success" inline x-data="{ visible: true }" x-show="visible">
            <flux:callout.heading>{{ session('success') }}</flux:callout.heading>
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    @endif

    <flux:card>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari category..." icon="magnifying-glass" />
    </flux:card>

    <flux:card class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-16">Icon</flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column class="w-24">Games</flux:table.column>
                <flux:table.column class="w-24">Order</flux:table.column>
                <flux:table.column class="w-24">Status</flux:table.column>
                <flux:table.column class="w-48">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($categories as $category)
                    <flux:table.row>
                        <flux:table.cell class="text-2xl">{{ $category->icon }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $category->name }}</div>
                            @if($category->description)
                                <div class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $category->description }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $category->slug }}</code>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $category->games_count }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ $category->sort_order }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$category->is_active ? 'green' : 'red'" size="sm">
                                {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button 
                                    wire:click="toggleStatus({{ $category->id }})" 
                                    size="sm" 
                                    variant="ghost"
                                    :icon="$category->is_active ? 'eye-slash' : 'eye'">
                                </flux:button>
                                <flux:button 
                                    href="{{ route('admin.categories.edit', $category) }}" 
                                    wire:navigate 
                                    size="sm" 
                                    variant="primary">
                                    Edit
                                </flux:button>
                                <flux:button 
                                    wire:click="delete({{ $category->id }})" 
                                    wire:confirm="Yakin ingin menghapus category ini?"
                                    size="sm" 
                                    variant="danger">
                                    Hapus
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="text-center py-12 text-zinc-500">
                                <flux:icon.squares-2x2 class="w-16 h-16 mx-auto mb-4" />
                                <p>Belum ada category. Silakan tambah category baru.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
</div>
