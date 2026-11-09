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

    <!-- Footer (Gambar 2.4 §3.2): Layanan & Bantuan untuk Masyarakat; minimalis untuk peran lain -->
    @if(Auth::user()->isMasyarakat())
        <footer class="bg-navy-900 border-t border-navy-800 text-white mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white shadow-inner">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"></path>
                                </svg>
                            </div>
                            <span class="text-base font-bold tracking-tight text-white">Loker<span class="text-teal-400 font-extrabold">Lens</span></span>
                        </div>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            Sistem deteksi lowongan kerja mencurigakan untuk perlindungan pencari kerja Indonesia.
                        </p>
                        <p class="text-xs text-gray-500 mt-4">© 2026 LokerLens</p>
                    </div>

                    <!-- Layanan -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Layanan</h4>
                        <ul class="space-y-2.5">
                            <li>
                                <a href="{{ route('periksa-lowongan.index') }}" class="text-sm text-gray-300 hover:text-teal-400 transition">
                                    Ajukan Pengaduan
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('status-pengaduan.index') }}" class="text-sm text-gray-300 hover:text-teal-400 transition">
                                    Status Pengaduan
                                </a>
                            </li>
                            <li>
                                 <a href="{{ route('riwayat-pengaduan.index') }}"
                                class="px-3 py-2 text-sm flex items-center gap-1.5 {{ request()->routeIs('riwayat-pengaduan.*') ? 'text-navy-900 font-semibold' : 'text-gray-600 hover:text-navy-900' }}">
                                    Riwayat Pengaduan
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Bantuan -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Bantuan</h4>
                        <ul class="space-y-2.5">
                            <li>
                                <span class="text-sm text-gray-500 cursor-not-allowed" title="Fitur Sprint 2">
                                    Panduan Pengaduan
                                </span>
                            </li>
                            <li>
                                <span class="text-sm text-gray-500 cursor-not-allowed" title="Fitur Sprint 2">
                                    Cara Kerja
                                </span>
                            </li>
                            <li>
                                <span class="text-sm text-gray-500 cursor-not-allowed" title="Fitur Sprint 2">
                                    Pusat Bantuan
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-navy-800 mt-8 pt-4 text-xs text-gray-500 text-center">
                    Keamanan Digital • Sistem Deteksi Penipuan Lowongan Kerja
                </div>
            </div>
        </footer>
    @else
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
    @endif
</body>
</html>
