<?php

use App\Models\Leaderboard;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Leaderboard Management')] class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public bool $isEdit = false;

    public $leaderboardId = null;
    public $period = 'week';
    public $user_id = null;
    public $name = '';
    public $total_spent = 0;
    public $transaction_count = 0;
    public $last_item = '';
    public $last_transaction_time = '';
    public $is_active = true;
    public $rank = 0;

    public $search = '';
    public $filterPeriod = '';
    public $filterActive = '';

    public function mount()
    {
        //
    }

    public function with(): array
    {
        $query = Leaderboard::query();

        // Filter pencarian
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_item', 'like', '%' . $this->search . '%');
            });
        }

        // Filter berdasarkan periode
        if ($this->filterPeriod) {
            $query->where('period', $this->filterPeriod);
        }

        // Filter berdasarkan status aktif
        if ($this->filterActive !== '') {
            $query->where('is_active', (bool) $this->filterActive);
        }

        $leaderboards = $query->orderBy('period', 'asc')
                             ->orderBy('rank', 'asc')
                             ->orderBy('total_spent', 'desc')
                             ->paginate(15);

        return [
            'leaderboards' => $leaderboards,
            'headers' => $this->getHeaders(),
        ];
    }

    public function getHeaders(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-16'],
            ['key' => 'period', 'label' => 'Periode', 'class' => 'w-24'],
            ['key' => 'rank', 'label' => 'Rank', 'class' => 'w-20'],
            ['key' => 'name', 'label' => 'Nama Pembeli', 'class' => ''],
            ['key' => 'total_spent', 'label' => 'Total Belanja', 'class' => 'w-32'],
            ['key' => 'transaction_count', 'label' => 'Transaksi', 'class' => 'w-24 text-center'],
            ['key' => 'last_item', 'label' => 'Item Terakhir', 'class' => ''],
            ['key' => 'is_active', 'label' => 'Status', 'class' => 'w-24 text-center'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-32'],
        ];
    }

    public function create()
    {
        $this->reset(['leaderboardId', 'period', 'user_id', 'name', 'total_spent', 'transaction_count', 'last_item', 'last_transaction_time', 'is_active']);
        $this->period = 'week';
        $this->is_active = true;
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $leaderboard = Leaderboard::findOrFail($id);

        $this->leaderboardId = $leaderboard->id;
        $this->period = $leaderboard->period;
        $this->user_id = $leaderboard->user_id;
        $this->name = $leaderboard->name;
        $this->total_spent = $leaderboard->total_spent;
        $this->transaction_count = $leaderboard->transaction_count;
        $this->last_item = $leaderboard->last_item;
        $this->last_transaction_time = $leaderboard->last_transaction_time ? $leaderboard->last_transaction_time->format('Y-m-d\TH:i') : '';
        $this->is_active = $leaderboard->is_active;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'period' => 'required|in:week,month,all',
            'name' => 'required|string|max:255',
            'total_spent' => 'required|numeric|min:0',
            'transaction_count' => 'required|integer|min:0',
            'last_item' => 'nullable|string|max:255',
            'last_transaction_time' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        try {
            // Set default waktu transaksi jika tidak diisi
            if (empty($validated['last_transaction_time'])) {
                $validated['last_transaction_time'] = now();
            }

            if ($this->isEdit && $this->leaderboardId) {
                $leaderboard = Leaderboard::findOrFail($this->leaderboardId);
                $leaderboard->update($validated);

                // Recalculate rank untuk periode ini
                $this->recalculateRanks($leaderboard->period);

                Flux::toast(
                    heading: 'Sukses',
                    text: 'Data leaderboard berhasil diperbarui!',
                    variant: 'success',
                    position: 'top-right',
                    duration: 5000
                );
            } else {
                // Create tanpa rank, akan dihitung otomatis
                $validated['rank'] = 0;
                $leaderboard = Leaderboard::create($validated);

                // Hitung rank otomatis berdasarkan total_spent pada periode tersebut
                $this->recalculateRanks($leaderboard->period);

                Flux::toast(
                    heading: 'Sukses',
                    text: 'Data leaderboard berhasil ditambahkan!',
                    variant: 'success',
                    position: 'top-right',
                    duration: 5000
                );
            }

            $this->showModal = false;
            $this->reset(['leaderboardId', 'period', 'user_id', 'name', 'total_spent', 'transaction_count', 'last_item', 'last_transaction_time', 'is_active']);
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Terjadi kesalahan: ' . $e->getMessage(),
                variant: 'error',
                position: 'top-right',
                duration: 5000
            );
        }
    }

    public function recalculateRanks($period)
    {
        // Ambil semua leaderboard pada periode yang sama, urutkan berdasarkan total_spent DESC
        $leaderboards = Leaderboard::where('period', $period)
            ->orderBy('total_spent', 'desc')
            ->orderBy('created_at', 'asc') // Jika sama, yang lebih dulu dibuat dapat rank lebih tinggi
            ->get();

        // Update rank berdasarkan urutan
        $rank = 1;
        foreach ($leaderboards as $item) {
            $item->rank = $rank++;
            $item->save();
        }
    }

    public function delete($id)
    {
        try {
            $leaderboard = Leaderboard::findOrFail($id);
            $period = $leaderboard->period;
            $leaderboard->delete();

            // Recalculate ranks setelah delete
            $this->recalculateRanks($period);

            Flux::toast(
                heading: 'Sukses',
                text: 'Data leaderboard berhasil dihapus!',
                variant: 'success',
                position: 'top-right',
                duration: 5000
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Terjadi kesalahan: ' . $e->getMessage(),
                variant: 'error',
                position: 'top-right',
                duration: 5000
            );
        }
    }

    public function toggleActive($id)
    {
        try {
            $leaderboard = Leaderboard::findOrFail($id);
            $leaderboard->is_active = !$leaderboard->is_active;
            $leaderboard->save();
            Flux::toast(
                heading: 'Sukses',
                text: 'Status berhasil diubah!',
                variant: 'success',
                position: 'top-right',
                duration: 5000
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Terjadi kesalahan: ' . $e->getMessage(),
                variant: 'error',
                position: 'top-right',
                duration: 5000
            );
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterPeriod', 'filterActive']);
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Leaderboard</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Leaderboard Management</flux:heading>
            <flux:subheading>Kelola data leaderboard untuk menampilkan top buyers</flux:subheading>
        </div>
        <flux:button wire:click="create" icon="plus" variant="primary">
            Tambah Data
        </flux:button>
    </div>

    <!-- Search -->
    <flux:card>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama pembeli atau item..." icon="magnifying-glass" />
    </flux:card>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:select label="Filter Periode" wire:model.live="filterPeriod" variant="listbox">
            <flux:select.option value="">Semua Periode</flux:select.option>
            <flux:select.option value="week">Mingguan</flux:select.option>
            <flux:select.option value="month">Bulanan</flux:select.option>
            <flux:select.option value="all">Sepanjang Waktu</flux:select.option>
        </flux:select>

        <flux:select label="Filter Status" wire:model.live="filterActive" variant="listbox">
            <flux:select.option value="">Semua Status</flux:select.option>
            <flux:select.option value="1">Aktif</flux:select.option>
            <flux:select.option value="0">Tidak Aktif</flux:select.option>
        </flux:select>

        <div class="flex items-end">
            <flux:button wire:click="clearFilters" variant="ghost" class="w-full">
                Reset Filter
            </flux:button>
        </div>
    </div>

    <!-- Table -->
    <flux:card class="overflow-hidden">
        <flux:table :paginate="$leaderboards">
            <flux:table.columns>
                <flux:table.column class="w-16">#</flux:table.column>
                <flux:table.column class="w-24">Periode</flux:table.column>
                <flux:table.column class="w-20">Rank</flux:table.column>
                <flux:table.column>Nama Pembeli</flux:table.column>
                <flux:table.column class="w-32">Total Belanja</flux:table.column>
                <flux:table.column class="w-24 text-center">Transaksi</flux:table.column>
                <flux:table.column>Item Terakhir</flux:table.column>
                <flux:table.column class="w-24 text-center">Status</flux:table.column>
                <flux:table.column class="w-32">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($leaderboards as $leaderboard)
                    <flux:table.row :key="$leaderboard->id">
                        <flux:table.cell>{{ $leaderboard->id }}</flux:table.cell>

                        <flux:table.cell>
                            @if($leaderboard->period === 'week')
                                <flux:badge color="blue" size="sm">Mingguan</flux:badge>
                            @elseif($leaderboard->period === 'month')
                                <flux:badge color="green" size="sm">Bulanan</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">All Time</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-center font-bold text-lg">
                                @if($leaderboard->rank === 1)
                                    <span class="text-yellow-500">🥇 {{ $leaderboard->rank }}</span>
                                @elseif($leaderboard->rank === 2)
                                    <span class="text-gray-400">🥈 {{ $leaderboard->rank }}</span>
                                @elseif($leaderboard->rank === 3)
                                    <span class="text-orange-500">🥉 {{ $leaderboard->rank }}</span>
                                @else
                                    {{ $leaderboard->rank }}
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div>
                                <div class="font-semibold">{{ $leaderboard->name }}</div>
                                @if($leaderboard->user_id)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">User ID: {{ $leaderboard->user_id }}</div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-semibold">Rp {{ number_format($leaderboard->total_spent, 0, ',', '.') }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-center">
                                <flux:badge color="zinc" size="sm">{{ $leaderboard->transaction_count }}x</flux:badge>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                {{ $leaderboard->last_item ?? '-' }}
                                @if($leaderboard->last_transaction_time)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $leaderboard->last_transaction_time->diffForHumans() }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-center">
                                <flux:switch wire:click="toggleActive({{ $leaderboard->id }})" :checked="$leaderboard->is_active" size="sm" />
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button wire:click="edit({{ $leaderboard->id }})" icon="pencil" size="sm" variant="ghost" />
                                <flux:modal.trigger :name="'delete-' . $leaderboard->id">
                                    <flux:button icon="trash" size="sm" variant="ghost" class="text-red-600" />
                                </flux:modal.trigger>
                            </div>

                            <flux:modal :name="'delete-' . $leaderboard->id" class="min-w-[22rem] space-y-6">
                                <div>
                                    <flux:heading size="lg">Hapus Data Leaderboard</flux:heading>
                                    <flux:subheading>
                                        Apakah Anda yakin ingin menghapus <strong>{{ $leaderboard->name }}</strong>?
                                    </flux:subheading>
                                </div>

                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Batal</flux:button>
                                    </flux:modal.close>
                                    <flux:button wire:click="delete({{ $leaderboard->id }})" variant="danger">Hapus</flux:button>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form -->
    <flux:modal :name="'leaderboard-form'" wire:model="showModal" class="space-y-6 min-w-[40rem]">
        <div>
            <flux:heading size="lg">{{ $isEdit ? 'Edit Data Leaderboard' : 'Tambah Data Leaderboard' }}</flux:heading>
            <flux:subheading>{{ $isEdit ? 'Perbarui informasi leaderboard' : 'Tambahkan data baru ke leaderboard' }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select label="Periode" wire:model="period" variant="listbox">
                <flux:select.option value="week">Mingguan</flux:select.option>
                <flux:select.option value="month">Bulanan</flux:select.option>
                <flux:select.option value="all">Sepanjang Waktu</flux:select.option>
            </flux:select>

            <flux:input label="Nama Pembeli" wire:model="name" />

            <flux:input label="Total Belanja (Rp)" wire:model="total_spent" type="number" step="0.01" />

            <flux:input label="Jumlah Transaksi" wire:model="transaction_count" type="number" />

            <flux:input label="Item Terakhir" wire:model="last_item" class="md:col-span-2" />

            <flux:input label="Waktu Transaksi Terakhir (Opsional)" wire:model="last_transaction_time" type="datetime-local" class="md:col-span-2" placeholder="Kosongkan untuk waktu saat ini" />

            <div class="md:col-span-2">
                <flux:checkbox label="Aktif di Leaderboard" wire:model="is_active" />
            </div>

            <div class="md:col-span-2">
                <flux:subheading class="text-xs text-zinc-500 dark:text-zinc-400">
                    * Rank akan dihitung otomatis berdasarkan total belanja terbanyak pada periode yang dipilih
                </flux:subheading>
            </div>
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary">{{ $isEdit ? 'Update' : 'Simpan' }}</flux:button>
        </div>
    </flux:modal>
</div>
