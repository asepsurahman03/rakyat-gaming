<?php

use Livewire\Volt\Component;
use App\Models\PaymentGateway;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Payment Gateway Configuration')] class extends Component {
    public $gateways;
    public $editingId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $merchant_code = '';
    public string $api_key = '';
    public string $callback_url = '';
    public string $return_url = '';
    public bool $is_sandbox = false;
    public bool $is_active = true;
    public float $fee_percent = 0;
    public float $fee_flat = 0;

    public function mount(): void
    {
        $this->loadGateways();
    }

    public function loadGateways(): void
    {
        $this->gateways = PaymentGateway::orderBy('name')->get();
    }

    public function edit($id): void
    {
        $gateway = PaymentGateway::findOrFail($id);
        $this->editingId = $id;
        $this->name = $gateway->name;
        $this->code = $gateway->code;
        $this->merchant_code = $gateway->merchant_code ?? '';
        $this->api_key = $gateway->api_key ?? '';
        $this->callback_url = $gateway->callback_url ?? '';
        $this->return_url = $gateway->return_url ?? '';
        $this->is_sandbox = $gateway->is_sandbox;
        $this->is_active = $gateway->is_active;
        $this->fee_percent = $gateway->fee_percent ?? 0;
        $this->fee_flat = $gateway->fee_flat ?? 0;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'code', 'merchant_code', 'api_key', 'callback_url', 'return_url', 'is_sandbox', 'is_active', 'fee_percent', 'fee_flat']);
        $this->is_active = true;
        $this->is_sandbox = false;
        $this->fee_percent = 0;
        $this->fee_flat = 0;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_gateways,code,' . $this->editingId,
            'merchant_code' => 'nullable|string',
            'api_key' => 'nullable|string',
            'callback_url' => 'nullable|url',
            'return_url' => 'nullable|url',
            'is_sandbox' => 'boolean',
            'is_active' => 'boolean',
            'fee_percent' => 'nullable|numeric|min:0|max:100',
            'fee_flat' => 'nullable|numeric|min:0',
        ]);

        if ($this->editingId) {
            $gateway = PaymentGateway::findOrFail($this->editingId);
            $gateway->update($validated);
            $message = 'Payment gateway updated successfully';
        } else {
            PaymentGateway::create($validated);
            $message = 'Payment gateway created successfully';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->cancelEdit();
        $this->loadGateways();
    }

    public function delete($id): void
    {
        $gateway = PaymentGateway::findOrFail($id);

        if ($gateway->orderPayments()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete payment gateway with existing transactions',
            ]);
            return;
        }

        $gateway->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment gateway deleted successfully',
        ]);

        $this->loadGateways();
    }

    public function toggleStatus($id): void
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->update(['is_active' => !$gateway->is_active]);

        $this->loadGateways();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment gateway status updated',
        ]);
    }
}; ?>

<div class="py-6">
    <!-- Page Header -->
    <flux:heading size="xl" class="mb-2">Payment Gateway Configuration</flux:heading>
    <flux:subheading class="mb-6">Konfigurasi metode pembayaran untuk transaksi</flux:subheading>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <flux:card class="sticky top-4">
                <div class="mb-4">
                    @if ($editingId)
                        <flux:badge color="amber" size="sm">EDIT</flux:badge>
                        <flux:heading size="lg" class="mt-2">Edit Gateway</flux:heading>
                    @else
                        <flux:badge color="blue" size="sm">NEW</flux:badge>
                        <flux:heading size="lg" class="mt-2">Add Gateway</flux:heading>
                    @endif
                </div>

                <flux:separator />

                <form wire:submit="save" class="space-y-6 mt-6">
                    <flux:field>
                        <flux:label>Name <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="e.g. Duitku" required />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Code <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="code" placeholder="e.g. DUITKU" required />
                        <flux:error name="code" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Merchant Code</flux:label>
                        <flux:input wire:model="merchant_code" placeholder="Merchant ID" />
                        <flux:error name="merchant_code" />
                    </flux:field>

                    <flux:field>
                        <flux:label>API Key</flux:label>
                        <flux:input type="password" wire:model="api_key" placeholder="••••••••" />
                        <flux:error name="api_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Callback URL</flux:label>
                        <flux:input type="url" wire:model="callback_url" placeholder="https://yoursite.com/payment/callback" />
                        <flux:error name="callback_url" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Return URL</flux:label>
                        <flux:input type="url" wire:model="return_url" placeholder="https://yoursite.com/payment/return" />
                        <flux:error name="return_url" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Fee Percent (%)</flux:label>
                            <flux:input type="number" wire:model="fee_percent" placeholder="0.00" step="0.01" min="0" max="100" />
                            <flux:error name="fee_percent" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Fee Flat (Rp)</flux:label>
                            <flux:input type="number" wire:model="fee_flat" placeholder="0" step="1" min="0" />
                            <flux:error name="fee_flat" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Sandbox Mode</flux:label>
                        <flux:switch wire:model="is_sandbox" />
                        <flux:description>{{ $is_sandbox ? 'Enabled' : 'Disabled' }} - For testing purposes</flux:description>
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
                        <flux:badge color="blue" size="lg">{{ count($gateways) }}</flux:badge>
                        <flux:heading size="lg">Payment Methods</flux:heading>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($gateways as $gateway)
                        <flux:card class="hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                                        <flux:heading size="base">{{ $gateway->name }}</flux:heading>
                                        <flux:badge color="zinc" size="sm">{{ $gateway->code }}</flux:badge>
                                        @if ($gateway->is_active)
                                            <flux:badge color="green" size="sm">Active</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm">Inactive</flux:badge>
                                        @endif
                                        @if ($gateway->is_sandbox)
                                            <flux:badge color="amber" size="sm">Sandbox</flux:badge>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        <div class="space-y-1">
                                            @if ($gateway->merchant_code)
                                                <div>
                                                    <span class="font-medium">Merchant:</span>
                                                    {{ $gateway->merchant_code }}
                                                </div>
                                            @endif

                                            @if ($gateway->callback_url)
                                                <div class="flex items-start gap-2">
                                                    <flux:icon.arrow-left class="size-4 mt-0.5 flex-shrink-0" />
                                                    <span class="truncate text-xs">{{ $gateway->callback_url }}</span>
                                                </div>
                                            @endif

                                            @if ($gateway->return_url)
                                                <div class="flex items-start gap-2">
                                                    <flux:icon.arrow-right class="size-4 mt-0.5 flex-shrink-0" />
                                                    <span class="truncate text-xs">{{ $gateway->return_url }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="space-y-1">
                                            @if ($gateway->fee_percent > 0 || $gateway->fee_flat > 0)
                                                <div>
                                                    <span class="font-medium">Fees:</span>
                                                </div>
                                                @if ($gateway->fee_percent > 0)
                                                    <div class="text-xs">• {{ $gateway->fee_percent }}%</div>
                                                @endif
                                                @if ($gateway->fee_flat > 0)
                                                    <div class="text-xs">• Rp {{ number_format($gateway->fee_flat, 0, ',', '.') }}</div>
                                                @endif
                                            @endif

                                            <div class="flex items-center gap-2">
                                                <flux:icon.credit-card class="size-4" />
                                                <span>{{ $gateway->orderPayments()->count() }} transactions</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <flux:button wire:click="toggleStatus({{ $gateway->id }})" size="sm" variant="ghost" icon="arrow-path" title="Toggle Status" />
                                    <flux:button wire:click="edit({{ $gateway->id }})" size="sm" variant="ghost" icon="pencil" title="Edit" />
                                    <flux:button
                                        wire:click="delete({{ $gateway->id }})"
                                        wire:confirm="Are you sure you want to delete this payment gateway?"
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
                            <flux:icon.credit-card class="size-16 mx-auto mb-4 text-zinc-400" />
                            <flux:subheading>No payment gateways configured yet</flux:subheading>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
