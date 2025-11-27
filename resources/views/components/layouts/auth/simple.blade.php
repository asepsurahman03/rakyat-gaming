<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-gradient-to-br from-black to-red-500">
    <div class="flex min-h-screen flex-col justify-center items-center px-6 py-12">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <a href="/" wire:navigate class="inline-block">
                    <img src="{{ asset('images/logo.png') }}" alt="logo" class="h-10 md:h-20 w-auto mx-auto">
                </a>
                <h2 class="mt-6 text-3xl font-bold text-zinc-100">{{ $title ?? config('app.name', 'Laravel') }}</h2>
            </div>

            <div class="p-8 bg-zinc-100/10 rounded-lg backdrop-blur-md">
                {{ $slot }}
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast />
    @endpersist
</body>
</html>
