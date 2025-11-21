<?php

use Livewire\Volt\Component;
use App\Models\Supplier;

new class extends Component {
    public $suppliers;
    public $editingId = null;

    // Form fields
    public string $name = '';
    public string $api_url = '';
    public string $api_key = '';
    public string $api_username = '';
    public string $webhook_secret = '';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->loadSuppliers();
    }

    public function loadSuppliers(): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();
    }

    public function edit($id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->editingId = $id;
        $this->name = $supplier->name;
        $this->api_url = $supplier->api_url ?? '';
        $this->api_key = $supplier->api_key ?? '';
        $this->api_username = $supplier->api_username ?? '';
        $this->webhook_secret = $supplier->webhook_secret ?? '';
        $this->is_active = $supplier->is_active;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'api_url', 'api_key', 'api_username', 'webhook_secret', 'is_active']);
        $this->is_active = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_username' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $supplier->update($validated);
            $message = 'Supplier updated successfully';
        } else {
            Supplier::create($validated);
            $message = 'Supplier created successfully';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->cancelEdit();
        $this->loadSuppliers();
    }

    public function delete($id): void
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->supplierProducts()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete supplier with existing products',
            ]);
            return;
        }

        $supplier->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Supplier deleted successfully',
        ]);

        $this->loadSuppliers();
    }

    public function toggleStatus($id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => !$supplier->is_active]);

        $this->loadSuppliers();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Supplier status updated',
        ]);
    }
}; ?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a>Settings</a></li>
            <li>Suppliers</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <span class="badge badge-warning badge-lg">SETTINGS</span>
            Supplier Configuration
        </h1>
        <p class="text-base-content/60 mt-2">Kelola konfigurasi supplier untuk produk digital</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl sticky top-4">
                <div class="card-body">
                    <h2 class="card-title">
                        @if ($editingId)
                            <div class="badge badge-warning">EDIT</div>
                            Edit Supplier
                        @else
                            <div class="badge badge-primary">NEW</div>
                            Add Supplier
                        @endif
                    </h2>
                    <div class="divider"></div>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text">Name *</span>
                            </label>
                            <input type="text" wire:model="name" class="input input-bordered w-full"
                                placeholder="e.g. Digiflazz" required />
                            @error('name')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">API URL</span>
                            </label>
                            <input type="url" wire:model="api_url" class="input input-bordered w-full"
                                placeholder="https://api.example.com/v1" />
                            @error('api_url')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">API Username</span>
                            </label>
                            <input type="text" wire:model="api_username" class="input input-bordered w-full"
                                placeholder="Username" />
                            @error('api_username')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">API Key</span>
                            </label>
                            <input type="password" wire:model="api_key" class="input input-bordered w-full"
                                placeholder="••••••••" />
                            @error('api_key')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">Webhook Secret</span>
                            </label>
                            <input type="password" wire:model="webhook_secret" class="input input-bordered w-full"
                                placeholder="••••••••" />
                            @error('webhook_secret')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label cursor-pointer justify-start gap-4">
                                <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary" />
                                <span class="label-text">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary flex-1">
                                {{ $editingId ? 'Update' : 'Create' }}
                            </button>
                            @if ($editingId)
                                <button type="button" wire:click="cancelEdit" class="btn btn-ghost">
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-2xl border border-base-300">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold flex items-center gap-3">
                            <span class="badge badge-lg badge-primary">{{ count($suppliers) }}</span>
                            Active Suppliers
                        </h3>
                    </div>

                    <div class="space-y-4">
                        @forelse($suppliers as $supplier)
                            <div
                                class="card bg-base-200 border-2 border-base-300 hover:border-primary hover:shadow-xl transition-all duration-300">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-semibold text-lg">{{ $supplier->name }}</h4>
                                            @if ($supplier->is_active)
                                                <span class="badge badge-success badge-sm">Active</span>
                                            @else
                                                <span class="badge badge-error badge-sm">Inactive</span>
                                            @endif
                                        </div>

                                        <div class="space-y-1 text-sm text-gray-600">
                                            @if ($supplier->api_url)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                    <span class="truncate">{{ $supplier->api_url }}</span>
                                                </div>
                                            @endif

                                            @if ($supplier->api_username)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <span>{{ $supplier->api_username }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                </svg>
                                                <span>{{ $supplier->supplierProducts()->count() }} products</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <button wire:click="toggleStatus({{ $supplier->id }})"
                                            class="btn btn-sm btn-ghost" title="Toggle Status">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </button>

                                        <button wire:click="edit({{ $supplier->id }})" class="btn btn-sm btn-ghost"
                                            title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button wire:click="delete({{ $supplier->id }})"
                                            wire:confirm="Are you sure you want to delete this supplier?"
                                            class="btn btn-sm btn-ghost text-error" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-gray-500">No suppliers configured yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
