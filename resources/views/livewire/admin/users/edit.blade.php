<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app'), Title('Edit User')] class extends Component {
    public User $user;

    public $name;
    public $email;
    public $password = '';
    public $password_confirmation = '';
    public $role;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
    }

    public function update()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'role' => 'required|in:admin,user',
        ];

        if ($this->password) {
            $rules['password'] = 'min:8|confirmed';
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($this->password) {
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);
        }

        session()->flash('message', 'User updated successfully!');
        return $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <flux:button wire:navigate href="{{ route('admin.users.index') }}" variant="ghost" size="sm" icon="arrow-left">
            Kembali
        </flux:button>
        <div>
            <flux:heading size="xl">Edit User</flux:heading>
            <flux:subheading>Update user information</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <flux:card>
                <form wire:submit="update" class="space-y-6">
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
                        <flux:label>New Password</flux:label>
                        <flux:input type="password" wire:model="password" placeholder="••••••••" icon="lock-closed" />
                        <flux:description>Leave blank to keep current</flux:description>
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Confirm New Password</flux:label>
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
                            Update User
                        </flux:button>
                        <flux:button wire:navigate href="{{ route('admin.users.index') }}" variant="ghost">
                            Cancel
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- User Info Cards -->
        <div class="lg:col-span-1 space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.user class="w-5 h-5" />
                        User Details
                    </div>
                </flux:heading>
                <flux:separator class="my-4" />
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">Joined:</span>
                        <div class="text-zinc-600 dark:text-zinc-400">{{ $user->created_at->format('d M Y') }}</div>
                    </div>
                    <div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">Last Updated:</span>
                        <div class="text-zinc-600 dark:text-zinc-400">{{ $user->updated_at->diffForHumans() }}</div>
                    </div>
                    @if($user->email_verified_at)
                        <div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">Email Verified:</span>
                            <flux:badge color="green" size="sm">Yes</flux:badge>
                        </div>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.information-circle class="w-5 h-5" />
                        Notes
                    </div>
                </flux:heading>
                <flux:separator class="my-4" />
                <ul class="list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                    <li>Leave password empty to keep current</li>
                    <li>Email must be unique</li>
                    <li>Admin role has full access</li>
                </ul>
            </flux:card>
        </div>
    </div>
</div>
