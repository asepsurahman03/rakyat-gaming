<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};
use Flux\Flux;

new #[Layout('components.layouts.app'), Title('Users Management')] class extends Component {
    use WithPagination;

    public $search = '';
    public $roleFilter = '';

    public function with(): array
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
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
            flux:toast(
    heading: 'User deleted successfully!',
    text: 'The user has been removed from the system.',
);
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Management Users</flux:heading>
            <flux:subheading>Kelola data pengguna aplikasi</flux:subheading>
        </div>
        <flux:button wire:navigate href="{{ route('admin.users.create') }}" icon="plus">
            Add User
        </flux:button>
    </div>
    <flux:toast />

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Total Users</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($totalUsers) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">All registered users</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Administrators</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($adminCount) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Admin access</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <flux:icon.shield-check class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400 font-medium">Regular Users</flux:heading>
                    <flux:heading size="2xl" class="mt-2">{{ number_format($userCount) }}</flux:heading>
                    <flux:subheading class="text-xs mt-1">Customer accounts</flux:subheading>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon.user class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="flex w-full gap-4">
            <div class="flex w-full">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari user berdasarkan nama atau email..." icon="magnifying-glass" />
            </div>
            <div class="w-full md:w-48">
                <flux:field>
                    <flux:select wire:model.live="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </flux:card>

    <!-- Users Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>User</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($users as $user)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 bg-zinc-200 dark:bg-zinc-700 rounded-full text-sm font-semibold">
                                    {{ strtoupper(collect(explode(' ', $user->name))->map(function ($part) {return mb_substr(trim($part), 0, 1);})->filter()->take(2)->implode('')) }}
                                </div>
                                <div>
                                    <div class="font-bold">{{ $user->name }}</div>
                                    @if ($user->id === auth()->id())
                                        <flux:badge color="blue" size="xs">You</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">{{ $user->email }}</div>
                            @if ($user->email_verified_at)
                                <flux:badge color="green" size="xs">Verified</flux:badge>
                            @else
                                <flux:badge color="amber" size="xs">Unverified</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($user->role === 'admin')
                                <flux:badge color="red">Admin</flux:badge>
                            @else
                                <flux:badge color="zinc">User</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">{{ $user->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $user->created_at->diffForHumans() }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button wire:navigate href="{{ route('admin.users.edit', $user) }}" variant="ghost" size="sm" icon="pencil" />
                                @if ($user->id !== auth()->id())
                                    <flux:button wire:click="deleteUser({{ $user->id }})" wire:confirm="Are you sure you want to delete this user?" variant="ghost" size="sm" icon="trash" class="text-red-600" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon.users class="w-12 h-12 text-zinc-300" />
                                <span class="text-zinc-500">No users found</span>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
