<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi — LokerLens</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-navy-900 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <!-- Kolom Kiri: Panel Navy Tagline & Visual -->
        <div class="bg-navy-900 text-white p-8 sm:p-12 flex flex-col justify-between border-r border-navy-800 relative overflow-hidden">
            <!-- Decorative gradient element -->
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-teal-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <!-- Brand Logo (lingkaran lensa sesuai mockup Gambar 2.4 & design.md §6) -->
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
                        Lupa kata sandi? <span class="font-serif italic text-teal-300">Akses Anda tetap terlindungi.</span>
                    </h1>
                    <p class="text-sm text-gray-300 leading-relaxed pt-2">
                        Kami akan membantu Anda mendapatkan kembali akses ke akun LokerLens dengan mengirimkan tautan reset kata sandi yang aman.
                    </p>
                </div>
            </div>

            <!-- Kartu Akses Terlindungi -->
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

        <!-- Kolom Kanan: Panel Krem Form Lupa Kata Sandi -->
        <div class="bg-cream-50 p-8 sm:p-12 flex flex-col justify-between text-navy-900">
            <!-- Navigasi Atas -->
            <div class="flex justify-between items-center text-xs text-gray-500 mb-6">
                <a href="{{ route('login') }}" class="hover:text-navy-900 transition flex items-center gap-1 font-medium text-teal-700">
                    ← Kembali ke Halaman Masuk
                </a>
            </div>

            <div>
                <!-- Heading Form -->
                <div class="mb-6">
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-700 mb-1">
                        BANTUAN KATA SANDI
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-navy-900">
                        Atur Ulang Kata Sandi
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-2 leading-relaxed">
                        Masukkan alamat email yang terdaftar pada akun LokerLens Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi melalui email.
                    </p>
                </div>


                <!-- Banner Error Validasi -->
                @if ($errors->any())
                    <div class="mb-5 bg-red-50 border-l-4 border-risk-high p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="shrink-0 text-risk-high">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs sm:text-sm text-red-800 font-medium">
                                    {{ $errors->first('email') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Lupa Password -->
                {{-- novalidate: validasi oleh server supaya pesan resmi ("Email wajib diisi.", format email
                     tidak valid) tampil, bukan tooltip bawaan browser --}}
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            Alamat Email Terdaftar <span class="text-risk-high">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            placeholder="nama@email.com"
                            class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition {{ $errors->has('email') ? 'border-risk-high' : 'border-gray-300' }}" />
                    </div>

                    <!-- Tombol Kirim Tautan -->
                    <button type="submit" class="w-full bg-navy-900 hover:bg-navy-800 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center space-x-2 text-sm mt-4">
                        <span>Kirim Tautan Reset Kata Sandi</span>
                        <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-gray-500">
                    Ingat kata sandi Anda? <a href="{{ route('login') }}" class="text-teal-700 font-bold hover:underline">Masuk ke akun</a>
                </div>
            </div>

            <!-- Footer Teks -->
            <div class="pt-6 text-center text-xs text-gray-400">
                © 2026 LokerLens • Sistem Deteksi Penipuan Lowongan Kerja
            </div>
        </div>
    </div>
</body>
</html>
