<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun LokerLens — Mulai Langkah Aman Anda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-navy-900 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <!-- Kolom Kiri: Panel Navy Tagline & Visual (Bagian 6.1) -->
        <div class="bg-navy-900 text-white p-8 sm:p-12 flex flex-col justify-between border-r border-navy-800 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-teal-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <!-- Brand Logo -->
                <div class="flex items-center space-x-2 mb-12">
                    <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">
                        Loker<span class="text-teal-400 font-extrabold">Lens</span>
                    </span>
                </div>

                <div class="space-y-4">
                    <p class="text-xs uppercase tracking-widest font-semibold text-teal-400">
                        LANGKAH AWAL YANG LEBIH AMAN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-normal leading-tight">
                        Mulai langkah aman Anda <span class="font-serif italic text-teal-300">bersama LokerLens.</span>
                    </h1>
                    <p class="text-sm text-gray-300 leading-relaxed pt-2">
                        Buat akun untuk melaporkan lowongan mencurigakan dan mengikuti proses penanganannya dengan lebih mudah.
                    </p>
                </div>
            </div>

            <!-- Kartu Akses Terlindungi -->
            <div class="mt-8 bg-navy-800/80 backdrop-blur border border-navy-700/80 rounded-xl p-4 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-teal-600/20 text-teal-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white">Akses Terlindungi</h4>
                    <p class="text-xs text-gray-400">Data Anda dirahasiakan dan dilindungi sesuai standar keamanan siber.</p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel Krem Form Register (Bagian 6.1) -->
        <div class="bg-cream-50 p-8 sm:p-12 flex flex-col justify-between text-navy-900">
            <!-- Navigasi Atas -->
            <div class="flex justify-end items-center text-xs text-gray-500 mb-4">
                <span>
                    Sudah punya akun? <a href="{{ route('login') }}" class="text-teal-700 font-bold hover:underline">Masuk</a>
                </span>
            </div>

            <div>
                <!-- Heading Form -->
                <div class="mb-5">
                    <h2 class="text-2xl font-bold tracking-tight text-navy-900">
                        Buat akun LokerLens
                    </h2>
                    <p class="text-xs text-gray-600 mt-1">
                        Daftar akun pencari kerja untuk mulai memeriksa dan melaporkan lowongan.
                    </p>
                </div>

                <!-- Form Register -->
                <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
                    @csrf

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Nama Lengkap <span class="text-risk-high">*</span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                            placeholder="Contoh: Adela Ulin Nadhroh"
                            class="w-full px-3.5 py-2 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition {{ $errors->has('name') ? 'border-risk-high' : 'border-gray-300' }}" />
                        @error('name')
                            <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Email <span class="text-risk-high">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                            placeholder="adelulinn@gmail.com"
                            class="w-full px-3.5 py-2 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition {{ $errors->has('email') ? 'border-risk-high' : 'border-gray-300' }}" />
                        @error('email')
                            <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kata Sandi dengan Toggle Mata -->
                    <div x-data="{ show: false }">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Kata Sandi <span class="text-risk-high">*</span> (Min. 8 karakter)
                        </label>
                        <div class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3.5 py-2 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition pr-10 {{ $errors->has('password') ? 'border-risk-high' : 'border-gray-300' }}" />
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Konfirmasi Kata Sandi -->
                    <div x-data="{ show: false }">
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Konfirmasi Kata Sandi <span class="text-risk-high">*</span>
                        </label>
                        <div class="relative">
                            <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                placeholder="Ulangi kata sandi"
                                class="w-full px-3.5 py-2 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition pr-10 border-gray-300" />
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Persetujuan Kebijakan Privasi & Syarat Layanan (Wajib dicentang) -->
                    <div class="pt-1">
                        <label class="flex items-start">
                            <input type="checkbox" name="terms" required class="w-4 h-4 mt-0.5 text-teal-600 rounded border-gray-300 focus:ring-teal-500">
                            <span class="ml-2 text-xs text-gray-600">
                                Saya menyetujui <a href="#" class="text-teal-700 font-semibold hover:underline">Kebijakan Privasi</a> dan <a href="#" class="text-teal-700 font-semibold hover:underline">Syarat Layanan</a> LokerLens.
                            </span>
                        </label>
                    </div>

                    <!-- Tombol Buat Akun (dengan panah →) -->
                    <button type="submit" class="w-full bg-navy-900 hover:bg-navy-800 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center space-x-2 text-sm mt-3">
                        <span>Buat Akun</span>
                        <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Footer Teks -->
            <div class="pt-4 text-center text-xs text-gray-400">
                © 2026 LokerLens • Sistem Deteksi Penipuan Lowongan Kerja
            </div>
        </div>
    </div>
</body>
</html>
