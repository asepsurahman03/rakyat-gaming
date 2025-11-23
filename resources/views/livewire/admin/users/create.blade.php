<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app'), Title('Create User')] class extends Component {
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = 'user';

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,user',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        session()->flash('message', 'User created successfully!');
        return $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.users.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Create User</flux:heading>
            <flux:subheading>Add a new user to the system</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <flux:card>
                <form wire:submit="save" class="space-y-6">
                    <flux:field>
                        <flux:label>Name <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="name" placeholder="John Doe" icon="user" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email <span class="text-red-600">*</span></flux:label>
                        <flux:input type="email" wire:model="email" placeholder="user@example.com" icon="envelope" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Password <span class="text-red-600">*</span></flux:label>
                        <flux:input type="password" wire:model="password" placeholder="••••••••" icon="lock-closed" />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Confirm Password <span class="text-red-600">*</span></flux:label>
                        <flux:input type="password" wire:model="password_confirmation" placeholder="••••••••" icon="shield-check" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Role <span class="text-red-600">*</span></flux:label>
                        <flux:select wire:model="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </flux:select>
                        <flux:error name="role" />
                    </flux:field>

                    <flux:separator />

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary" icon="check">
                            Create User
                        </flux:button>
                        <flux:button wire:navigate href="{{ route('admin.users.index') }}" variant="ghost">
                            Cancel
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- Info Card -->
        <div class="lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.information-circle class="w-5 h-5" />
                        User Information
                    </div>
                </flux:heading>
                <flux:separator class="my-4" />
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="font-semibold text-zinc-700 dark:text-zinc-300">Role Types:</div>
                        <ul class="list-disc list-inside text-zinc-600 dark:text-zinc-400 mt-1 space-y-1">
                            <li><strong>Admin:</strong> Full system access</li>
                            <li><strong>User:</strong> Customer access only</li>
                        </ul>
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-700 dark:text-zinc-300">Password Requirements:</div>
                        <ul class="list-disc list-inside text-zinc-600 dark:text-zinc-400 mt-1 space-y-1">
                            <li>Minimum 8 characters</li>
                            <li>Must match confirmation</li>
                        </ul>
                    </div>
                    
                </div>
            </flux:card>
        </div>
    </div>
</div>
