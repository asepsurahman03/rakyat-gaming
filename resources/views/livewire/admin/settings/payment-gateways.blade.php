<?php

use Livewire\Volt\Component;
use App\Models\PaymentGateway;

new class extends Component {
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

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a>Settings</a></li>
            <li>Payment Gateways</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <span class="badge badge-error badge-lg">SETTINGS</span>
            Payment Gateway Configuration
        </h1>
        <p class="text-base-content/60 mt-2">Konfigurasi metode pembayaran untuk transaksi</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl sticky top-4">
                <div class="card-body">
                    <h2 class="card-title">
                        @if ($editingId)
                            <div class="badge badge-warning">EDIT</div>
                            Edit Gateway
                        @else
                            <div class="badge badge-primary">NEW</div>
                            Add Gateway
                        @endif
                    </h2>
                    <div class="divider"></div>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text">Name *</span>
                            </label>
                            <input type="text" wire:model="name" class="input input-bordered w-full"
                                placeholder="e.g. Duitku" required />
                            @error('name')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">Code *</span>
                            </label>
                            <input type="text" wire:model="code" class="input input-bordered w-full"
                                placeholder="e.g. DUITKU" required />
                            @error('code')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">Merchant Code</span>
                            </label>
                            <input type="text" wire:model="merchant_code" class="input input-bordered w-full"
                                placeholder="Merchant ID" />
                            @error('merchant_code')
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
                                <span class="label-text">Callback URL</span>
                            </label>
                            <input type="url" wire:model="callback_url" class="input input-bordered w-full"
                                placeholder="https://yoursite.com/payment/callback" />
                            @error('callback_url')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text">Return URL</span>
                            </label>
                            <input type="url" wire:model="return_url" class="input input-bordered w-full"
                                placeholder="https://yoursite.com/payment/return" />
                            @error('return_url')
                                <span class="text-error text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="label">
                                    <span class="label-text">Fee Percent (%)</span>
                                </label>
                                <input type="number" wire:model="fee_percent" class="input input-bordered w-full"
                                    placeholder="0.00" step="0.01" min="0" max="100" />
                                @error('fee_percent')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Fee Flat (Rp)</span>
                                </label>
                                <input type="number" wire:model="fee_flat" class="input input-bordered w-full"
                                    placeholder="0" step="1" min="0" />
                                @error('fee_flat')
                                    <span class="text-error text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label cursor-pointer justify-start gap-4">
                                <input type="checkbox" wire:model="is_sandbox" class="checkbox checkbox-warning" />
                                <div>
                                    <span class="label-text font-medium">Sandbox Mode</span>
                                    <div class="text-xs text-gray-500">For testing purposes</div>
                                </div>
                            </label>
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
                            <span class="badge badge-lg badge-primary">{{ count($gateways) }}</span>
                            Payment Methods
                        </h3>
                    </div>

                    <div class="space-y-4">
                        @forelse($gateways as $gateway)
                            <div
                                class="card bg-base-200 border-2 border-base-300 hover:border-primary hover:shadow-xl transition-all duration-300">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-semibold text-lg">{{ $gateway->name }}</h4>
                                            <span class="badge badge-ghost">{{ $gateway->code }}</span>
                                            @if ($gateway->is_active)
                                                <span class="badge badge-success badge-sm">Active</span>
                                            @else
                                                <span class="badge badge-error badge-sm">Inactive</span>
                                            @endif
                                            @if ($gateway->is_sandbox)
                                                <span class="badge badge-warning badge-sm">Sandbox</span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                            <div>
                                                @if ($gateway->merchant_code)
                                                    <div class="mb-1">
                                                        <span class="font-medium">Merchant:</span>
                                                        {{ $gateway->merchant_code }}
                                                    </div>
                                                @endif

                                                @if ($gateway->callback_url)
                                                    <div class="mb-1 flex items-start gap-2">
                                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                                        </svg>
                                                        <span
                                                            class="truncate text-xs">{{ $gateway->callback_url }}</span>
                                                    </div>
                                                @endif

                                                @if ($gateway->return_url)
                                                    <div class="flex items-start gap-2">
                                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                        </svg>
                                                        <span
                                                            class="truncate text-xs">{{ $gateway->return_url }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div>
                                                @if ($gateway->fee_percent > 0 || $gateway->fee_flat > 0)
                                                    <div class="mb-1">
                                                        <span class="font-medium">Fees:</span>
                                                    </div>
                                                    @if ($gateway->fee_percent > 0)
                                                        <div class="text-xs">• {{ $gateway->fee_percent }}%</div>
                                                    @endif
                                                    @if ($gateway->fee_flat > 0)
                                                        <div class="text-xs">• Rp
                                                            {{ number_format($gateway->fee_flat, 0, ',', '.') }}</div>
                                                    @endif
                                                @endif

                                                <div class="flex items-center gap-2 mt-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{{ $gateway->orderPayments()->count() }} transactions</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <button wire:click="toggleStatus({{ $gateway->id }})"
                                            class="btn btn-sm btn-ghost" title="Toggle Status">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </button>

                                        <button wire:click="edit({{ $gateway->id }})" class="btn btn-sm btn-ghost"
                                            title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button wire:click="delete({{ $gateway->id }})"
                                            wire:confirm="Are you sure you want to delete this payment gateway?"
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
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <p class="text-gray-500">No payment gateways configured yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
