<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Periksa Email Anda — LokerLens</title>
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
                        PEMULIHAN AKSES AKUN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-normal leading-tight">
                        Periksa kotak masuk, <span class="font-serif italic text-teal-300">tautan sedang menuju Anda.</span>
                    </h1>
                    <p class="text-sm text-gray-300 leading-relaxed pt-2">
                        Ikuti tautan di email tersebut untuk membuat kata sandi baru dan kembali mengakses akun LokerLens Anda.
                    </p>
                </div>
            </div>

            <div class="mt-8 bg-navy-800/80 backdrop-blur border border-navy-700/80 rounded-xl p-4 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-teal-600/20 text-teal-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white">Keamanan Terjamin</h4>
                    <p class="text-xs text-gray-400">Tautan reset hanya dapat digunakan sekali dan berlaku dalam batas waktu tertentu.</p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel Krem -->
        <div class="bg-cream-50 p-8 sm:p-12 flex flex-col justify-between text-navy-900">
            <div class="flex justify-between items-center text-xs text-gray-500 mb-6">
                <a href="{{ route('login') }}" class="hover:text-navy-900 transition flex items-center gap-1 font-medium text-teal-700">
                    ← Kembali ke Halaman Masuk
                </a>
            </div>

            <div>
                <!-- Ikon Amplop -->
                <div class="self-start shrink-0 rounded-full bg-teal-50 border border-teal-200 text-teal-700 flex items-center justify-center mb-5" style="width:56px;height:56px">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>

                <div class="mb-6">
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-700 mb-1">
                        EMAIL TERKIRIM
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-navy-900">
                        Periksa Email Anda
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-2 leading-relaxed">
                        Jika <strong class="text-navy-900">{{ $email }}</strong> terdaftar di LokerLens, kami telah mengirimkan tautan untuk mengatur ulang kata sandi. Cek juga folder spam bila email belum muncul.
                    </p>
                </div>

                <a href="{{ route('login') }}" class="w-full bg-navy-900 hover:bg-navy-800 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center space-x-2 text-sm">
                    <span>Kembali ke Halaman Masuk</span>
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>

                <div class="mt-6 text-center text-xs text-gray-500">
                    Email salah atau belum masuk? <a href="{{ route('password.request') }}" class="text-teal-700 font-bold hover:underline">Coba lagi</a>
                </div>
            </div>

            <div class="pt-6 text-center text-xs text-gray-400">
                © 2026 LokerLens • Sistem Deteksi Penipuan Lowongan Kerja
            </div>
        </div>
    </div>
</body>
</html>