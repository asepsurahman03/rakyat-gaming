<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Supplier Configuration')] class extends Component {
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

<div class="py-6">
    <!-- Page Header -->
    <flux:heading size="xl" class="mb-2">Supplier Configuration</flux:heading>
    <flux:subheading class="mb-6">Kelola konfigurasi supplier untuk produk digital</flux:subheading>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <flux:card class="sticky top-4">
                <div class="mb-4">
                    @if ($editingId)
                        <flux:badge color="amber" size="sm">EDIT</flux:badge>
                        <flux:heading size="lg" class="mt-2">Edit Supplier</flux:heading>
                    @else
                        <flux:badge color="blue" size="sm">NEW</flux:badge>
                        <flux:heading size="lg" class="mt-2">Add Supplier</flux:heading>
                    @endif
                </div>

                <flux:separator />

                <form wire:submit="save" class="space-y-6 mt-6">
                        <flux:field>
                            <flux:label>Name <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="name" placeholder="e.g. Digiflazz" required />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>API URL</flux:label>
                            <flux:input type="url" wire:model="api_url" placeholder="https://api.example.com/v1" />
                            <flux:error name="api_url" />
                        </flux:field>

                        <flux:field>
                            <flux:label>API Username</flux:label>
                            <flux:input wire:model="api_username" placeholder="Username" />
                            <flux:error name="api_username" />
                        </flux:field>

                        <flux:field>
                            <flux:label>API Key</flux:label>
                            <flux:input type="password" wire:model="api_key" placeholder="••••••••" />
                            <flux:error name="api_key" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Webhook Secret</flux:label>
                            <flux:input type="password" wire:model="webhook_secret" placeholder="••••••••" />
                            <flux:error name="webhook_secret" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:switch wire:model="is_active" />
                            <flux:description>{{ $is_active ? 'Active' : 'Inactive' }}</flux:description>
                        </flux:field>

                        <div class="flex gap-2">
                            <flux:button type="submit" variant="primary" class="flex-1">
                                {{ $editingId ? 'Update' : 'Create' }}
                            </flux:button>
                            @if ($editingId)
                                <flux:button type="button" wire:click="cancelEdit" variant="ghost">
                                    Cancel
                                </flux:button>
                            @endif
                        </div>
                    </form>
            </flux:card>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-2">
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <flux:badge color="blue" size="lg">{{ count($suppliers) }}</flux:badge>
                        <flux:heading size="lg">Active Suppliers</flux:heading>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($suppliers as $supplier)
                        <flux:card class="hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <flux:heading size="base">{{ $supplier->name }}</flux:heading>
                                        @if ($supplier->is_active)
                                            <flux:badge color="green" size="sm">Active</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm">Inactive</flux:badge>
                                        @endif
                                    </div>

                                    <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        @if ($supplier->api_url)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.link class="size-4" />
                                                <span class="truncate">{{ $supplier->api_url }}</span>
                                            </div>
                                        @endif

                                        @if ($supplier->api_username)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.user class="size-4" />
                                                <span>{{ $supplier->api_username }}</span>
                                            </div>
                                        @endif

                                        <div class="flex items-center gap-2">
                                            <flux:icon.cube class="size-4" />
                                            <span>{{ $supplier->supplierProducts()->count() }} products</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <flux:button wire:click="toggleStatus({{ $supplier->id }})" size="sm" variant="ghost" icon="arrow-path" title="Toggle Status" />
                                    <flux:button wire:click="edit({{ $supplier->id }})" size="sm" variant="ghost" icon="pencil" title="Edit" />
                                    <flux:button
                                        wire:click="delete({{ $supplier->id }})"
                                        wire:confirm="Are you sure you want to delete this supplier?"
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700"
                                        title="Delete"
                                    />
                                </div>
                            </div>
                        </flux:card>
                    @empty
                        <div class="text-center py-12">
                            <flux:icon.cube class="size-16 mx-auto mb-4 text-zinc-400" />
                            <flux:subheading>No suppliers configured yet</flux:subheading>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
