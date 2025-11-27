<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app'), Title('Profile')] class extends Component {
    public string $name = '';
    public string $email = '';
    
    // Password fields
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            heading: 'Profile berhasil diupdate!',
            text: 'Informasi profile Anda telah diperbarui.',
            variant: 'success',
            duration: 3000,
            position: 'top-right'
        );
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        Flux::toast(
            heading: 'Password berhasil diubah!',
            text: 'Password Anda telah diperbarui.',
            variant: 'success',
            duration: 3000,
            position: 'top-right'
        );
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Profile Settings</flux:heading>
        <flux:subheading>Update your account's profile information and email address</flux:subheading>
    </div>

    <flux:card class="space-y-6">
        <form wire:submit="updateProfileInformation" class="space-y-6">
            <flux:field>
                <flux:label for="name">Name</flux:label>
                <flux:input wire:model="name" id="name" name="name" type="text" required autofocus
                    autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label for="email">Email</flux:label>
                <flux:input wire:model="email" id="email" name="email" type="email" required
                    autocomplete="username" />
                <flux:error name="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div class="mt-2">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Your email address is unverified.

                            <button wire:click.prevent="sendVerification"
                                class="underline text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-zinc-800">
                                Click here to re-send the verification email.
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                                A new verification link has been sent to your email address.
                            </p>
                        @endif
                    </div>
                @endif
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>

    <!-- Update Password -->
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Update Password</flux:heading>
            <flux:subheading>Ensure your account is using a long, random password to stay secure</flux:subheading>
        </div>

        <form wire:submit="updatePassword" class="space-y-6">
            <flux:field>
                <flux:label for="current_password">Current Password</flux:label>
                <flux:input wire:model="current_password" id="current_password" name="current_password" type="password"
                    autocomplete="current-password" />
                <flux:error name="current_password" />
            </flux:field>

            <flux:field>
                <flux:label for="password">New Password</flux:label>
                <flux:input wire:model="password" id="password" name="password" type="password"
                    autocomplete="new-password" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label for="password_confirmation">Confirm Password</flux:label>
                <flux:input wire:model="password_confirmation" id="password_confirmation" name="password_confirmation"
                    type="password" autocomplete="new-password" />
                <flux:error name="password_confirmation" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>

    <!-- Delete Account Section -->
    <flux:card class="space-y-6">
        <flux:heading size="lg">Delete Account</flux:heading>
        <flux:subheading>Once your account is deleted, all of its resources and data will be permanently
            deleted.</flux:subheading>

        <flux:modal.trigger name="confirm-user-deletion">
            <flux:button variant="danger">Delete Account</flux:button>
        </flux:modal.trigger>

        <flux:modal name="confirm-user-deletion" class="min-w-[22rem]">
            <form wire:submit="deleteUser" class="space-y-6">
                <div>
                    <flux:heading size="lg">Are you sure you want to delete your account?</flux:heading>
                    <flux:text class="mt-2">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Please enter your password to confirm you would like to permanently delete your account.
                    </flux:text>
                </div>

                <flux:field>
                    <flux:label for="password">Password</flux:label>
                    <flux:input wire:model="password" id="password" name="password" type="password"
                        placeholder="Password" />
                    <flux:error name="password" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete Account</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:card>
</div>
