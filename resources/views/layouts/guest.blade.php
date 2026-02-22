<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Sign in to StudAI Career — your AI-powered career platform.">
        <meta name="theme-color" content="#1A73E8">

        <title>{{ $title ?? config('app.name', 'StudAI Career') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-900">
            <div class="mb-4">
                <a href="/" class="flex items-center gap-2 text-2xl font-bold" style="color: #1A73E8;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    StudAI Career
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white dark:bg-gray-800 shadow-lg overflow-hidden sm:rounded-xl border border-gray-100 dark:border-gray-700">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-gray-400">&copy; {{ date('Y') }} StudAI Career. All rights reserved.</p>
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
