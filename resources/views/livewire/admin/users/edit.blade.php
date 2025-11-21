<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app.sidebar'), Title('Edit User')]
class extends Component {
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

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a href="{{ route('admin.users.index') }}" wire:navigate>Users</a></li>
            <li>Edit</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <span class="badge badge-warning badge-lg">EDIT</span>
            Edit User
        </h1>
        <p class="text-base-content/60 mt-2">Update user information</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <form wire:submit="update">
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text font-semibold">Name</span>
                            </label>
                            <input
                                type="text"
                                wire:model="name"
                                class="input input-bordered @error('name') input-error @enderror"
                                placeholder="John Doe"
                            />
                            @error('name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text font-semibold">Email</span>
                            </label>
                            <input
                                type="email"
                                wire:model="email"
                                class="input input-bordered @error('email') input-error @enderror"
                                placeholder="user@example.com"
                            />
                            @error('email') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text font-semibold">New Password</span>
                                <span class="label-text-alt text-base-content/60">Leave blank to keep current</span>
                            </label>
                            <input
                                type="password"
                                wire:model="password"
                                class="input input-bordered @error('password') input-error @enderror"
                                placeholder="••••••••"
                            />
                            @error('password') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text font-semibold">Confirm New Password</span>
                            </label>
                            <input
                                type="password"
                                wire:model="password_confirmation"
                                class="input input-bordered"
                                placeholder="••••••••"
                            />
                        </div>

                        <div class="form-control mb-6">
                            <label class="label">
                                <span class="label-text font-semibold">Role</span>
                            </label>
                            <select wire:model="role" class="select select-bordered @error('role') select-error @enderror">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            @error('role') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-primary gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Update User
                            </button>
                            <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-ghost">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl mb-4">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        User Details
                    </h3>
                    <div class="divider my-2"></div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="font-semibold">Joined:</span>
                            <div class="text-base-content/60">{{ $user->created_at->format('d M Y') }}</div>
                        </div>
                        <div>
                            <span class="font-semibold">Last Updated:</span>
                            <div class="text-base-content/60">{{ $user->updated_at->diffForHumans() }}</div>
                        </div>
                        @if($user->email_verified_at)
                            <div>
                                <span class="font-semibold">Email Verified:</span>
                                <div class="badge badge-success badge-sm">Yes</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Notes
                    </h3>
                    <div class="divider my-2"></div>
                    <ul class="list-disc list-inside text-sm text-base-content/60 space-y-1">
                        <li>Leave password empty to keep current</li>
                        <li>Email must be unique</li>
                        <li>Admin role has full access</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
