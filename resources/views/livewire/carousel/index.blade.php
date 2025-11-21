<?php

use App\Models\Carousel;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.sidebar'), Title('Carousel Management')] class extends Component {
    use WithPagination;

    public $search = '';
    public $deleteId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('open-delete-modal');
    }

    public function delete()
    {
        if ($this->deleteId) {
            $carousel = Carousel::find($this->deleteId);

            if ($carousel) {
                // Delete image file if exists
                if ($carousel->image_path && \Storage::disk('public')->exists($carousel->image_path)) {
                    \Storage::disk('public')->delete($carousel->image_path);
                }

                $carousel->delete();

                session()->flash('success', 'Carousel berhasil dihapus!');
            }

            $this->deleteId = null;
        }
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

<div>
    <!-- Header -->
    <div class="hero bg-gradient-to-r from-primary to-secondary rounded-box mb-6 mt-10">

        <div
            class="flex w-full justify-between items-center mb-6 p-6">
            <div class="text-shadow-lg">
                <h1 class="text-3xl font-bold flex items-center gap-3">
                    Carousel Management
                </h1>
                <p class="text-base-content/70 mt-2 ml-1">📸 Kelola banner carousel website dengan mudah</p>
            </div>
            <a href="{{ route('carousel.create') }}" wire:navigate
                class="btn btn-success btn-lg gap-2 shadow-xl hover:shadow-2xl transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Tambah Carousel
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Search -->
    <div class="card bg-base-200/50 shadow-xl mb-6 border border-info/20">
        <div class="card-body">
            <div class="flex items-center gap-4">
                <label class="input input-bordered input-primary  w-full">
                    <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none"
                            stroke="currentColor">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </g>
                    </svg>
                    <input type="search" class="grow" placeholder="Cari carousel berdasarkan judul atau deskripsi..." wire:model.live.debounce.300ms="search"/>
                </label>
            </div>
        </div>
    </div>

    <!-- Carousel List -->
    <div class="card bg-base-200/50 shadow-xl border border-success/20">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead class="bg-primary/10">
                        <tr class="text-base">
                            <th class="font-bold">🖼️ Gambar</th>
                            <th class="font-bold">📝 Judul</th>
                            <th class="font-bold">📄 Deskripsi</th>
                            <th class="font-bold">📅 Dibuat</th>
                            <th class="text-right font-bold">⚙️ Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($carousels as $carousel)
                            <tr>
                                <td>
                                    <div class="avatar">
                                        <div class="h-24 w-auto rounded">
                                            @if ($carousel->image_path)
                                                <img src="{{ Storage::url($carousel->image_path) }}"
                                                    alt="{{ $carousel->title }}" />
                                            @else
                                                <div class="bg-base-300 w-full h-full flex items-center justify-center">
                                                    <span class="text-base-content/40">No Image</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold">{{ $carousel->title }}</div>
                                </td>
                                <td>
                                    <div class="text-sm opacity-70 max-w-xs truncate">
                                        {{ $carousel->description ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm">
                                        {{ $carousel->created_at->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex gap-2 justify-end">
                                        <a href="{{ route('carousel.edit', $carousel) }}" wire:navigate
                                            class="btn btn-sm btn-info gap-1 shadow-md hover:shadow-lg transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path
                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Edit
                                        </a>
                                        <button wire:click="confirmDelete({{ $carousel->id }})"
                                            onclick="delete_modal.showModal()"
                                            class="btn btn-sm btn-error gap-1 shadow-md hover:shadow-lg transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8">
                                    <div class="text-base-content/40">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p>Belum ada data carousel</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $carousels->links() }}
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete_modal" class="modal">
        <div class="modal-box border-2 border-error/30">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-error/20 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-error" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-error">⚠️ Konfirmasi Hapus</h3>
            </div>
            <div class="bg-error/5 p-4 rounded-lg mb-4">
                <p class="text-base-content/80">Apakah Anda yakin ingin menghapus carousel ini?</p>
                <p class="text-sm text-error mt-2 font-semibold">⚠️ Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-ghost btn-lg">Batal</button>
                </form>
                <button wire:click="delete" onclick="delete_modal.close()" class="btn btn-error btn-lg gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Ya, Hapus
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</div>
