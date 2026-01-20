<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\FlazzSell;
use App\Models\Product;
use App\Models\Game;
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('FlazzSell')] class extends Component {
    use WithPagination;

    public $search = '';

    public function with(): array
    {
        $query = FlazzSell::withCount('flazzSellProducts')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest();

        return [
            'flazzSells' => $query->paginate(10),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleActive($id)
    {
        $flazzSell = FlazzSell::findOrFail($id);
        $flazzSell->update(['is_active' => !$flazzSell->is_active]);

        Flux::toast(
            heading: 'Status FlazzSell berhasil diubah!',
            text: 'Status aktif/nonaktif FlazzSell telah diperbarui.',
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );
    }

    public function delete($id)
    {
        $flazzSell = FlazzSell::findOrFail($id);
        $flazzSell->delete();

        Flux::modal('delete-flazz-sell-' . $id)->close();

        Flux::toast(
            heading: 'FlazzSell berhasil dihapus!',
            text: 'Data FlazzSell telah dihapus dari sistem.',
            variant: 'success',
            duration: 3000,
            position: 'top-right',
        );
    }
};

?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>FlazzSell</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">FlazzSell Event Management</flux:heading>
            <flux:subheading>Kelola event flash sale dengan multiple produk</flux:subheading>
        </div>
        <flux:button href="{{ route('admin.flazz-sells.create') }}" wire:navigate icon="plus" variant="primary">
            Tambah Event
        </flux:button>
    </div>

    <flux:card>
        <!-- Filters -->
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari event..."
                    icon="magnifying-glass" />
            </div>
        </div>

        <!-- Table -->
        <flux:table :paginate="$flazzSells">
            <flux:table.columns>
                <flux:table.column>Nama Event</flux:table.column>
                <flux:table.column>Jumlah Produk</flux:table.column>
                <flux:table.column>Mulai</flux:table.column>
                <flux:table.column>Berakhir</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($flazzSells as $flazzSell)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-semibold">{{ $flazzSell->name }}</div>
                            @if($flazzSell->description)
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($flazzSell->description, 50) }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="blue">{{ $flazzSell->flazz_sell_products_count }} produk</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $flazzSell->start_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $flazzSell->end_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($flazzSell->isActive())
                                <flux:badge color="green">Aktif</flux:badge>
                            @elseif ($flazzSell->end_at < now())
                                <flux:badge color="gray">Berakhir</flux:badge>
                            @elseif ($flazzSell->start_at > now())
                                <flux:badge color="blue">Terjadwal</flux:badge>
                            @else
                                <flux:badge color="red">Nonaktif</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" href="{{ route('admin.flazz-sells.edit', $flazzSell) }}"
                                    icon="pencil" variant="ghost" />
                                <flux:button size="sm" wire:click="toggleActive({{ $flazzSell->id }})"
                                    icon="{{ $flazzSell->is_active ? 'eye-slash' : 'eye' }}" variant="ghost" />
                                <flux:modal.trigger name="delete-flazz-sell-{{ $flazzSell->id }}">
                                    <flux:button size="sm" icon="trash" variant="ghost" color="red" />
                                </flux:modal.trigger>
                                <flux:modal name="delete-flazz-sell-{{ $flazzSell->id }}" :close-on-outside-click="false">
                                    <flux:heading>
                                        Hapus FlazzSell "{{ $flazzSell->name }}"?
                                    </flux:heading>
                                    <p class="mt-4">
                                        Apakah Anda yakin ingin menghapus event FlazzSell ini? Tindakan ini
                                        tidak dapat dibatalkan dan semua data terkait event ini akan dihapus.
                                    </p>
                                    <div class="mt-6 flex items-center justify-end gap-3">
                                        <flux:modal.close variant="secondary">
                                            <flux:button>Batal</flux:button>
                                        </flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $flazzSell->id }})"
                                            wire:loading.attr="disabled">
                                            Hapus
                                        </flux:button>
                                    </div>
                                </flux:modal>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-12">
                            <div class="text-zinc-500">
                                <flux:icon.fire class="w-16 h-16 mx-auto mb-4" />
                                <p>Belum ada FlazzSell Event. Silakan tambah event baru.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
