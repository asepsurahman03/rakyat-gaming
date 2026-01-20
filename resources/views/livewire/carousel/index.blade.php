<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Carousel Management')] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function closeModal()
    {
        $this->deleteId = null;
        $this->showDeleteModal = false;
    }

    public function delete($id)
    {
        $carousel = Carousel::findOrFail($id);
        $carousel->delete($id);

        Flux::modal('delete-carousel-' . $id)->close();

        Flux::toast(heading: 'Carousel berhasil dihapus!', text: 'Data carousel telah dihapus dari sistem.', variant: 'success', duration: 3000, position: 'top-right');
    }

    public function with(): array
    {
        return [
            'carousels' => Carousel::query()
                ->when($this->search, function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl" class="mb-2">Carousel Management</flux:heading>
            <flux:subheading>📸 Kelola banner carousel website dengan mudah</flux:subheading>
        </div>
        <flux:button href="{{ route('carousel.create') }}" wire:navigate variant="primary" icon="plus">
            Tambah Carousel
        </flux:button>
    </div>

    <!-- Search -->
    <flux:card class="mb-6">
        <flux:field>
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Cari carousel berdasarkan judul atau deskripsi..." icon="magnifying-glass" />
        </flux:field>
    </flux:card>

    <!-- Carousel List -->
    <flux:card>
        <flux:table :paginate="$carousels">
            <flux:table.columns>
                <flux:table.column>🖼️ Gambar</flux:table.column>
                <flux:table.column>📝 Judul</flux:table.column>
                <flux:table.column>📄 Deskripsi</flux:table.column>
                <flux:table.column>📅 Dibuat</flux:table.column>
                <flux:table.column>⚙️ Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($carousels as $carousel)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="w-32 h-20 rounded overflow-hidden">
                                @if ($carousel->image_path)
                                    <img src="{{ Storage::url($carousel->image_path) }}" alt="{{ $carousel->title }}"
                                        class="w-full h-full object-cover" />
                                @else
                                    <div
                                        class="bg-zinc-100 dark:bg-zinc-800 w-full h-full flex items-center justify-center">
                                        <span class="text-zinc-400 text-xs">No Image</span>
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-bold">{{ $carousel->title }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm opacity-70 max-w-xs truncate">
                                {{ $carousel->description ?? '-' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                {{ $carousel->created_at->format('d M Y') }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button href="{{ route('carousel.edit', $carousel) }}" wire:navigate size="sm"
                                    variant="filled" icon="pencil">
                                    Edit
                                </flux:button>
                                <flux:modal.trigger name="delete-carousel-{{ $carousel->id }}">
                                    <flux:button size="sm" variant="danger" icon="trash">
                                        Hapus
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="delete-carousel-{{ $carousel->id }}">
                                    <div class="space-y-6">
                                        <flux:heading>
                                            Hapus Carousel "{{ $carousel->title }}"?
                                        </flux:heading>
                                        <flux:text>
                                            Apakah Anda yakin ingin menghapus carousel ini? Tindakan ini tidak dapat
                                            dibatalkan.
                                        </flux:text>
                                        <div class="mt-6 flex items-center justify-end gap-3">
                                            <flux:modal.close variant="secondary">
                                                <flux:button>Batal</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $carousel->id }})"
                                                wire:loading.attr="disabled">
                                                Hapus
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="text-center py-8">
                                <flux:icon.photo class="size-12 mx-auto mb-2 text-zinc-400" />
                                <flux:subheading>Belum ada data carousel</flux:subheading>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

    </flux:card>
</div>
