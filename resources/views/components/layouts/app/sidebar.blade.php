<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">

<head>
    @include('partials.head')
</head>

<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-4" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
            <!-- Navbar -->
            <nav class="navbar w-full bg-base-300 fixed top-0 z-30">
                <label for="my-drawer-4" aria-label="open sidebar" class="btn btn-square btn-ghost">
                    <!-- Sidebar toggle icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                        stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                        class="my-1.5 inline-block size-4">
                        <path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z">
                        </path>
                        <path d="M9 4v16"></path>
                        <path d="M14 10l2 2l-2 2"></path>
                    </svg>
                </label>
                <div class="px-4 flex justify-end w-full pr-20">
                    <div class="flex items-center gap-5">
                        <h3 class="font-semibold text-lg">
                            <span>{{ auth()->user()->name }}</span>
                        </h3>
                        <div>
                            <div class="dropdown dropdown-end">
                                <button tabindex="0" class="btn btn-ghost btn-circle" aria-label="Open user menu">
                                    <div class="avatar">
                                        <div class="bg-accent text-accent-content rounded-full w-10 h-10 flex items-center justify-center text-sm">
                                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                                        </div>
                                    </div>
                                </button>

                                <ul tabindex="0" class="menu menu-compact dropdown-content mt-2 p-2 shadow bg-base-100 rounded-box w-48">
                                    <li class="menu-title">
                                        <span class="text-xs opacity-60">{{ auth()->user()->email }}</span>
                                    </li>

                                    <li>
                                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2" wire:navigate>
                                            Profile
                                        </a>
                                    </li>

                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full text-left">Log out</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Page content here -->
            <div class="p-4 mt-10">
                {{ $slot }}
            </div>
        </div>

        <div class="drawer-side is-drawer-close:overflow-visible">
            <label for="my-drawer-4" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="flex min-h-full flex-col items-start bg-gradient-to-b from-base-300 via-base-200 to-base-200 is-drawer-close:w-14 is-drawer-open:w-64 border-r border-base-300">
                <!-- Sidebar content here -->
                <div class="w-full p-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 transition-all" wire:navigate>
                        <x-app-logo />
                    </a>
                </div>
                <ul class="menu w-full grow px-3 space-y-2">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('dashboard') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path>
                                <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Dashboard</span>
                        </a>
                    </li>

                    <!-- Carousel -->
                    <li>
                        <a href="{{ route('carousel.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('carousel.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Carousel">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Carousel</span>
                        </a>
                    </li>

                    <!-- Games -->
                    <li>
                        <a href="{{ route('admin.games.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.games.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Games">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Games</span>
                        </a>
                    </li>

                    <!-- Products -->
                    <li>
                        <a href="{{ route('admin.products.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.products.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Products">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Products</span>
                        </a>
                    </li>

                    <!-- Orders -->
                    <li>
                        <a href="{{ route('admin.orders.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Orders">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Orders</span>
                        </a>
                    </li>

                    <!-- Supplier Products -->
                    <li>
                        <a href="{{ route('admin.supplier-products.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.supplier-products.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Supplier Products">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Supplier Products</span>
                        </a>
                    </li>

                    <!-- Users Management -->
                    <li>
                        <a href="{{ route('admin.users.index') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.users.*') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Users">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Users</span>
                        </a>
                    </li>

                    <li class="menu-title mt-4">
                        <span class="is-drawer-close:hidden text-xs font-bold text-primary">CONFIGURATION</span>
                    </li>

                    <!-- Application Settings -->
                    <li>
                        <a href="{{ route('admin.settings.application') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.settings.application') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Application">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Application</span>
                        </a>
                    </li>

                    <!-- Supplier Settings -->
                    <li>
                        <a href="{{ route('admin.settings.suppliers') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.settings.suppliers') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Suppliers">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Suppliers</span>
                        </a>
                    </li>

                    <!-- Payment Gateway Settings -->
                    <li>
                        <a href="{{ route('admin.settings.payment-gateways') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('admin.settings.payment-gateways') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Payment Gateways">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Payment Gateways</span>
                        </a>
                    </li>

                    <li class="menu-title mt-4">
                        <span class="is-drawer-close:hidden text-xs font-bold text-primary">ACCOUNT</span>
                    </li>

                    <!-- Profile Settings -->
                    <li>
                        <a href="{{ route('profile.edit') }}" wire:navigate class="rounded-xl hover:bg-primary hover:text-primary-content transition-all {{ request()->routeIs('profile.edit') ? 'bg-primary text-primary-content shadow-lg' : '' }} is-drawer-close:tooltip is-drawer-close:tooltip-right" data-tip="Profile">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round"
                                stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"
                                class="inline-block size-5">
                                <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span class="is-drawer-close:hidden font-medium">Profile</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    {{-- <div class="drawer lg:drawer-open">
            <input id="my-drawer" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content">
                <!-- Navbar -->
                <div class="navbar bg-base-300 lg:hidden sticky top-0 z-30">
                    <div class="flex-none">
                        <label for="my-drawer" class="btn btn-square btn-ghost drawer-button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </label>
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('dashboard') }}" class="btn btn-ghost normal-case text-xl" wire:navigate>
                            <x-app-logo />
                        </a>
                    </div>
                    <div class="flex-none">
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-10">
                                        <span class="text-xs">{{ auth()->user()->initials() }}</span>
                                    </div>
                                </div>
                            </div>
                            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                                <li class="menu-title">
                                    <span>{{ auth()->user()->name }}</span>
                                    <span class="text-xs opacity-60">{{ auth()->user()->email }}</span>
                                </li>
                                <li><a href="{{ route('profile.edit') }}" wire:navigate>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ __('Settings') }}
                                </a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left" data-test="logout-button">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                            </svg>
                                            {{ __('Log Out') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Page content -->
                <div class="p-4 lg:p-8 min-h-screen">
                    {{ $slot }}
                </div>
            </div>

            <!-- Sidebar -->
            <div class="drawer-side z-20">
                <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <ul class="menu p-4 w-80 min-h-full bg-base-200 text-base-content">
                    <!-- Sidebar content here -->
                    <!-- Logo -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 mb-8 px-4" wire:navigate>
                        <x-app-logo />
                    </a>

                    <!-- Navigation Menu -->
                    <ul class="menu px-3 flex-1">
                        <li class="menu-title">
                            <span>{{ __('Platform') }}</span>
                        </li>
                        <li>
                            <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                {{ __('Dashboard') }}
                            </a>
                        </li>

                        <div class="divider my-2"></div>

                        <li>
                            <a href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                                {{ __('Repository') }}
                            </a>
                    </li>
                </ul>
            </div>
        </div> --}}
    @livewireScripts
</body>

</html>
