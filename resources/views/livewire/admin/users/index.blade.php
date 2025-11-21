<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;

new #[Layout('components.layouts.app.sidebar'), Title('Users Management')]
class extends Component {
    use WithPagination;

    public $search = '';
    public $roleFilter = '';

    public function with(): array
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        return [
            'users' => $query->latest()->paginate(20),
            'totalUsers' => User::count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'userCount' => User::where('role', 'user')->count(),
        ];
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if ($user && $user->id !== auth()->id()) {
            $user->delete();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'User deleted successfully!'
            ]);
        }
    }
}; ?>

<div>
    <!-- Breadcrumbs -->
    <div class="text-sm breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('dashboard') }}" wire:navigate>Dashboard</a></li>
            <li>Users</li>
        </ul>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <span class="badge badge-secondary badge-lg">USERS</span>
                Management
            </h1>
            <p class="text-base-content/60 mt-2">Kelola data pengguna aplikasi</p>
        </div>
        <a href="{{ route('admin.users.create') }}" wire:navigate class="btn btn-primary gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add User
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="stats stats-vertical lg:stats-horizontal shadow w-full mb-6">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="stat-title">Total Users</div>
            <div class="stat-value text-primary">{{ number_format($totalUsers) }}</div>
            <div class="stat-desc">All registered users</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-error">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="stat-title">Administrators</div>
            <div class="stat-value text-error">{{ number_format($adminCount) }}</div>
            <div class="stat-desc">Admin access</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-success">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div class="stat-title">Regular Users</div>
            <div class="stat-value text-success">{{ number_format($userCount) }}</div>
            <div class="stat-desc">Customer accounts</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <div class="input-group">
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by name or email..."
                            class="input input-bordered w-full"
                        />
                    </div>
                </div>

                <select wire:model.live="roleFilter" class="select select-bordered w-full">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-10">
                                            <span class="text-sm">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-bold">{{ $user->name }}</div>
                                        @if($user->id === auth()->id())
                                            <div class="badge badge-info badge-xs">You</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">{{ $user->email }}</div>
                                @if($user->email_verified_at)
                                    <div class="badge badge-success badge-xs">Verified</div>
                                @else
                                    <div class="badge badge-warning badge-xs">Unverified</div>
                                @endif
                            </td>
                            <td>
                                @if($user->role === 'admin')
                                    <div class="badge badge-error">Admin</div>
                                @else
                                    <div class="badge badge-ghost">User</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm">{{ $user->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-base-content/60">{{ $user->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" wire:navigate class="btn btn-sm btn-ghost">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <button
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
                                            class="btn btn-sm btn-ghost text-error">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="text-base-content/60">No users found</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
