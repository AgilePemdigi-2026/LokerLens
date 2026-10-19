<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LokerLens') }} — Sistem Deteksi Lowongan Kerja Mencurigakan</title>

    <!-- Google Fonts: Plus Jakarta Sans & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-slate-900 text-gray-100 min-h-screen flex flex-col selection:bg-teal-600 selection:text-white">
    <!-- Navbar Header -->
    @include('layouts.navigation')

    <!-- Flash Notifications / Alert Banner -->
    @if(session('success'))
        <div class="bg-teal-700 text-white px-4 py-3 text-center text-sm font-medium shadow-md">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-risk-high text-white px-4 py-3 text-center text-sm font-medium shadow-md">
            {{ session('error') }}
        </div>
    @endif

    <!-- Page Content -->
    <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
        {{ $slot }}
    </main>

    <!-- Footer Tipis Resmi (Bagian 4.1 Panduan) -->
    <footer class="bg-navy-900 border-t border-navy-800 py-4 px-6 text-xs text-gray-400 mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>
                © 2026 LokerLens
            </div>
            <div class="text-gray-400 font-medium">
                Keamanan Digital • Sistem Deteksi Penipuan Lowongan Kerja
            </div>
        </div>
    </footer>
</body>
</html>
