<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kata Sandi Berhasil Diubah — LokerLens</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-navy-900 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <!-- Kolom Kiri: Panel Navy -->
        <div class="bg-navy-900 text-white p-8 sm:p-12 flex flex-col justify-between border-r border-navy-800 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-teal-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <div class="flex items-center space-x-2 mb-12">
                    <div class="w-9 h-9 rounded-full bg-teal-600 flex items-center justify-center text-white shadow-inner">
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
                        AKSES AKUN DIPULIHKAN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-normal leading-tight">
                        Kata sandi diperbarui, <span class="font-serif italic text-teal-300">akun Anda aman kembali.</span>
                    </h1>
                    <p class="text-sm text-gray-300 leading-relaxed pt-2">
                        Anda sekarang dapat masuk ke LokerLens menggunakan kata sandi yang baru.
                    </p>
                </div>
            </div>

            <div class="mt-8 bg-navy-800/80 backdrop-blur border border-navy-700/80 rounded-xl p-4 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-teal-600/20 text-teal-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white">Tautan Sudah Tidak Berlaku</h4>
                    <p class="text-xs text-gray-400">Tautan reset yang tadi Anda pakai tidak dapat digunakan lagi.</p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel Krem -->
        <div class="bg-cream-50 p-8 sm:p-12 flex flex-col justify-between text-navy-900">
            <div class="mb-6 h-4"></div>

            <div>
                <!-- Ikon Centang -->
                <div class="self-start shrink-0 rounded-full bg-teal-50 border border-teal-200 text-teal-700 flex items-center justify-center mb-5" style="width:56px;height:56px">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <div class="mb-6">
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-700 mb-1">
                        BERHASIL
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-navy-900">
                        Kata Sandi Berhasil Diubah
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-2 leading-relaxed">
                        Kata sandi akun LokerLens Anda telah diperbarui. Silakan masuk menggunakan kata sandi yang baru.
                    </p>
                </div>

                <a href="{{ route('login') }}" class="w-full bg-navy-900 hover:bg-navy-800 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center space-x-2 text-sm">
                    <span>Masuk ke Akun</span>
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>

            <div class="pt-6 text-center text-xs text-gray-400">
                © 2026 LokerLens • Sistem Deteksi Penipuan Lowongan Kerja
            </div>
        </div>
    </div>
</body>
</html>