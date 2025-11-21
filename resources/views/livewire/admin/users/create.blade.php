<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app.sidebar'), Title('Create User')]
class extends Component {
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

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li><a href="{{ route('admin.users.index') }}" wire:navigate>Users</a></li>
            <li>Create</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <span class="badge badge-primary badge-lg">NEW</span>
            Create User
        </h1>
        <p class="text-base-content/60 mt-2">Add a new user to the system</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <form wire:submit="save">
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
                                <span class="label-text font-semibold">Password</span>
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
                                <span class="label-text font-semibold">Confirm Password</span>
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
                                Create User
                            </button>
                            <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-ghost">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title text-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        User Information
                    </h3>
                    <div class="divider my-2"></div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="font-semibold text-base-content/80">Role Types:</div>
                            <ul class="list-disc list-inside text-base-content/60 mt-1">
                                <li><strong>Admin:</strong> Full system access</li>
                                <li><strong>User:</strong> Customer access only</li>
                            </ul>
                        </div>
                        <div>
                            <div class="font-semibold text-base-content/80">Password Requirements:</div>
                            <ul class="list-disc list-inside text-base-content/60 mt-1">
                                <li>Minimum 8 characters</li>
                                <li>Must match confirmation</li>
                            </ul>
                        </div>
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-xs">Email must be unique</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
