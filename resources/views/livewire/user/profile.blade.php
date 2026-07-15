<?php

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.user'), Title('Profile')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $whatsapp = '';
    public $image;
    public $currentImage = null;

    // Password fields
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->profile) {
            $this->whatsapp = $user->profile->whatsapp ?? '';
            $this->currentImage = $user->profile->image;
        }
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
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Handle profile data
        $profileData = [
            'whatsapp' => $validated['whatsapp'] ?? null,
        ];

        // Handle image upload
        if ($this->image) {
            // Delete old image if exists
            if ($user->profile && $user->profile->image) {
                Storage::disk('public')->delete($user->profile->image);
            }

            $profileData['image'] = $this->image->store('profiles', 'public');
            $this->currentImage = $profileData['image'];
            $this->image = null;
        }

        // Create or update profile
        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }

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
            $this->redirectIntended(default: route('user.dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Delete the user account.
     */
    public function deleteUser(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);
        $user = Auth::user();
        Auth::logout();
        $user->delete();
        Flux::toast(
            heading: 'Akun berhasil dihapus!',
            text: 'Akun Anda telah dihapus dari sistem.',
            variant: 'success',
            duration: 3000,
            position: 'top-right'
        );
    }
}; ?>

<div class="max-w-5xl mx-auto p-6 space-y-6">
    <!-- Header with Avatar -->
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex items-center gap-6">
            <div class="relative">
                @if ($currentImage)
                    <img src="{{ Storage::url($currentImage) }}" alt="{{ auth()->user()->name }}"
                        class="w-24 h-24 rounded-full border-4 border-white shadow-xl object-cover">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&size=120&background=000&color=fff&bold=true"
                        alt="{{ auth()->user()->name }}" class="w-24 h-24 rounded-full border-4 border-white shadow-xl">
                @endif
                <div class="absolute bottom-0 right-0 w-6 h-6 bg-green-500 rounded-full border-2 border-white"></div>
            </div>
            <div>
                <h1 class="text-3xl font-bold">{{ auth()->user()->name }}</h1>
                <p class="text-yellow-100 text-lg">{{ auth()->user()->email }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <flux:icon.shield-check class="w-5 h-5" />
                    <span class="text-sm">Member sejak {{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Information Card -->
            <flux:card class="border border-zinc-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div>
                        <flux:heading size="lg">Informasi Profile</flux:heading>
                        <flux:subheading>Perbarui informasi akun Anda</flux:subheading>
                    </div>
                </div>

                <form wire:submit="updateProfileInformation" class="space-y-5">
                    <!-- Photo Upload -->
                    <flux:field>
                        <flux:label for="image" class="font-semibold">Foto Profile</flux:label>
                        <div class="flex items-center gap-4">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                                    class="w-20 h-20 rounded-full object-cover border-2 border-yellow-400">
                            @elseif($currentImage)
                                <img src="{{ Storage::url($currentImage) }}" alt="Current"
                                    class="w-20 h-20 rounded-full object-cover border-2 border-zinc-300">
                            @else
                                <div
                                    class="w-20 h-20 rounded-full bg-zinc-100 flex items-center justify-center border-2 border-zinc-300">
                                    <flux:icon.user class="w-10 h-10 text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" wire:model="image" accept="image/*" id="image"
                                    class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-yellow-100 file:text-yellow-700 hover:file:bg-yellow-200 dark:file:bg-yellow-900/30 dark:file:text-yellow-400 dark:hover:file:bg-yellow-900/50" />
                                <flux:description>JPG, PNG max 2MB</flux:description>
                            </div>
                        </div>
                        <flux:error name="image" />
                        <div wire:loading wire:target="image"
                            class="text-sm text-zinc-200 flex items-center gap-2 mt-2">
                            <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                            Mengupload...
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label for="name" class="font-semibold">Nama Lengkap</flux:label>
                        <flux:input wire:model="name" id="name" type="text" required autofocus icon="user"
                            placeholder="Masukkan nama lengkap Anda" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="email" class="font-semibold">Alamat Email</flux:label>
                        <flux:input wire:model="email" id="email" type="email" required icon="envelope"
                            placeholder="email@example.com" />
                        <flux:error name="email" />

                        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                            <div
                                class="mt-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <flux:icon.exclamation-triangle
                                        class="w-5 h-5 text-yellow-600 dark:text-yellow-500 mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                            Email Belum Terverifikasi
                                        </p>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                            Silahkan verifikasi email Anda untuk mengaktifkan semua fitur.
                                        </p>
                                        <button wire:click.prevent="sendVerification"
                                            class="mt-2 text-sm font-medium text-yellow-600 dark:text-yellow-400 hover:text-yellow-700 dark:hover:text-yellow-300 underline">
                                            Kirim ulang link verifikasi
                                        </button>
                                    </div>
                                </div>

                                @if (session('status') === 'verification-link-sent')
                                    <div
                                        class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <p class="text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                                            <flux:icon.check-circle class="w-4 h-4" />
                                            Link verifikasi telah dikirim ke email Anda!
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </flux:field>

                    <flux:field>
                        <flux:label for="whatsapp" class="font-semibold">Nomor WhatsApp</flux:label>
                        <flux:input wire:model="whatsapp" id="whatsapp" type="text" icon="phone"
                            placeholder="628123456789" />
                        <flux:error name="whatsapp" />
                        <flux:description>Format: 628xxxxxxxxxx (tanpa +)</flux:description>
                    </flux:field>

                    <div class="pt-4 border-t border-zinc-200">
                        <flux:button type="submit" variant="primary" icon="check" class="w-full sm:w-auto">
                            Simpan Perubahan
                        </flux:button>
                    </div>
                </form>
            </flux:card>

            <!-- Change Password Card -->
            <flux:card class="border border-zinc-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <flux:icon.lock-closed class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">Ubah Password</flux:heading>
                        <flux:subheading>Pastikan akun Anda menggunakan password yang kuat</flux:subheading>
                    </div>
                </div>

                <form wire:submit="updatePassword" class="space-y-5">
                    <flux:field>
                        <flux:label for="current_password" class="font-semibold">Password Saat Ini</flux:label>
                        <flux:input wire:model="current_password" id="current_password" type="password"
                            icon="lock-closed" placeholder="Masukkan password saat ini" />
                        <flux:error name="current_password" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="password" class="font-semibold">Password Baru</flux:label>
                        <flux:input wire:model="password" id="password" type="password" icon="key"
                            placeholder="Minimal 8 karakter" />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="password_confirmation" class="font-semibold">Konfirmasi Password Baru
                        </flux:label>
                        <flux:input wire:model="password_confirmation" id="password_confirmation" type="password"
                            icon="key" placeholder="Ulangi password baru" />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    <div class="pt-4 border-t border-zinc-200">
                        <flux:button type="submit" variant="primary" icon="check" class="w-full sm:w-auto">
                            Update Password
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- Right Column - Quick Info & Actions -->
        <div class="space-y-6">
            <!-- Account Stats -->
            <flux:card class="border border-zinc-200 shadow-sm">
                <flux:heading size="lg" class="mb-4">Statistik Akun</flux:heading>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200">
                        <div class="flex items-center gap-3">
                            <flux:icon.shopping-bag class="w-5 h-5 text-zinc-200 " />
                            <span class="text-sm font-medium">Total Transaksi</span>
                        </div>
                        <span class="text-lg font-bold text-yellow-600">{{ auth()->user()->orders()->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200">
                        <div class="flex items-center gap-3">
                            <flux:icon.clock class="w-5 h-5 text-zinc-200" />
                            <span class="text-sm font-medium ">Member Sejak</span>
                        </div>
                        <span class="text-sm font-semibold">{{ auth()->user()->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </flux:card>

            <!-- Quick Actions -->
            <flux:card class="border border-zinc-200 shadow-sm">
                <flux:heading size="lg" class="mb-4">Aksi Cepat</flux:heading>
                <div class="space-y-3">
                    <a href="{{ route('user.transactions') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-lg transition-colors group border border-zinc-200">
                        <flux:icon.document-text
                            class="w-5 h-5 text-zinc-200 group-hover:text-yellow-600" />
                        <span class="text-sm font-medium">Lihat Transaksi</span>
                    </a>
                    <a href="{{ route('user.dashboard') }}" wire:navigate   
                        class="flex items-center gap-3 p-3 rounded-lg transition-colors group border border-zinc-200">
                        <flux:icon.home class="w-5 h-5 text-zinc-200 group-hover:text-yellow-600" />
                        <span class="text-sm font-medium">Ke Dashboard</span>
                    </a>
                    <a href="/" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-lg transition-colors group border border-zinc-200">
                        <flux:icon.shopping-cart
                            class="w-5 h-5 text-zinc-200 group-hover:text-yellow-600" />
                        <span class="text-sm font-medium">Mulai Belanja</span>
                    </a>
                </div>
            </flux:card>

            <!-- Danger Zone -->
            <flux:card class="border-2 border-red-200 shadow-sm">
                <div class="flex items-center gap-2 mb-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-zinc-200" />
                    <flux:heading size="lg" class="text-zinc-200">Zona Berbahaya</flux:heading>
                </div>
                <flux:subheading class="mb-4 text-zinc-200">
                    Tindakan ini tidak dapat dibatalkan
                </flux:subheading>

                <flux:modal.trigger name="confirm-user-deletion">
                    <flux:button variant="danger" icon="trash" class="w-full">
                        Hapus Akun
                    </flux:button>
                </flux:modal.trigger>

                <flux:modal name="confirm-user-deletion" class="max-w-md">
                    <form wire:submit="deleteUser" class="space-y-6">
                        <div class="text-center">
                            <div
                                class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                <flux:icon.exclamation-triangle class="w-8 h-8 text-red-600" />
                            </div>
                            <flux:heading size="lg">Hapus Akun Permanen?</flux:heading>
                            <flux:text class="mt-3 text-zinc-200">
                                Tindakan ini akan menghapus semua data Anda secara permanen termasuk riwayat transaksi,
                                profile, dan informasi lainnya. Data yang sudah dihapus tidak dapat dikembalikan.
                            </flux:text>
                        </div>

                        <flux:field>
                            <flux:label for="password" class="font-semibold">Konfirmasi dengan Password</flux:label>
                            <flux:input wire:model="password" id="password" type="password" icon="lock-closed"
                                placeholder="Masukkan password Anda" />
                            <flux:error name="password" />
                        </flux:field>

                        <div class="flex gap-3 justify-end">
                            <flux:modal.close>
                                <flux:button variant="ghost" class="w-full">Batal</flux:button>
                            </flux:modal.close>

                            <flux:button type="submit" variant="danger" icon="trash" >
                                Ya, Hapus Akun
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            </flux:card>
        </div>
    </div>
</div>
