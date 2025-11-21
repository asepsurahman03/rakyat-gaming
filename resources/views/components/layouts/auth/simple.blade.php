<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen">
        <div class="hero min-h-screen bg-base-200">
            <div class="hero-content flex-col">
                <div class="text-center mb-6">
                    <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-2" wire:navigate>
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-lg w-16">
                                {{-- <x-app-logo-icon class="w-10 h-10" /> --}}
                            </div>
                        </div>
                        <h1 class="text-2xl font-bold">{{ config('app.name', 'Laravel') }}</h1>
                    </a>
                </div>
                <div class="card w-full max-w-sm shadow-2xl bg-base-100">
                    <div class="card-body">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
