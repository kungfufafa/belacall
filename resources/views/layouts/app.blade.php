<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #f8fafc; /* Slate 50 */
            color: #1e293b; /* Slate 800 */
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">
    <!-- Header -->
    <header class="border-b border-gray-200 bg-white/80 backdrop-blur-md fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                        <div class="size-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                             <img src="{{ asset('icon_belacall.png') }}" alt="Logo" class="w-6 h-6 object-contain">
                        </div>
                        <span class="text-xl font-bold text-gray-900 tracking-tight">
                            BELACALL
                        </span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex gap-8">
                    <a href="{{ route('report.create') }}" class="text-sm font-medium text-gray-600 hover:text-green-600 transition-colors {{ request()->routeIs('report.create') ? 'text-green-600' : '' }}">
                        Lapor
                    </a>
                    <a href="{{ route('report.tracking.view') }}" class="text-sm font-medium text-gray-600 hover:text-green-600 transition-colors {{ request()->routeIs('report.tracking.view') ? 'text-green-600' : '' }}">
                        Tracking
                    </a>
                </nav>

                <!-- Action Button -->
                <div class="flex items-center">
                    <a href="{{ route('report.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none ring-offset-background bg-green-600 text-white hover:bg-green-700 h-10 px-5 shadow-xs">
                        Buat Laporan
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow pt-24 pb-12 relative overflow-hidden">

        <div class="relative z-10">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 bg-white mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class=" md:flex md:items-center md:justify-between">
                <div class="flex justify-center md:justify-start space-x-6 md:order-2">
                    <span class="text-gray-500 hover:text-gray-900 transition-colors cursor-pointer text-sm">Privacy</span>
                    <span class="text-gray-500 hover:text-gray-900 transition-colors cursor-pointer text-sm">Terms</span>
                </div>
                <div class="mt-8 md:mt-0 md:order-1">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} BELACALL - Pemerintah Desa. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
