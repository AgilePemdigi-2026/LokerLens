@use('App\Constants\LokerLensCopy')

{{-- Profil Saya (issue #25): ubah nama/email dan kata sandi untuk semua peran.
     Fitur "Hapus Akun" sengaja tidak ada: menghapus akun ikut menghapus riwayat kasus (cascade). --}}
@php
    // Gaya isian sama dengan halaman Masuk/Daftar: teks gelap di latar putih supaya terbaca jelas
    $kelasIsian = 'w-full px-3.5 py-2.5 rounded-lg border text-sm text-navy-900 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition';
@endphp

<x-app-layout>
    <div class="max-w-3xl mx-auto py-4 space-y-6">
        <!-- Header Halaman -->
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">{{ LokerLensCopy::PROFIL_JUDUL }}</h1>
            <p class="text-sm text-gray-300 mt-1">{{ LokerLensCopy::PROFIL_DESKRIPSI }}</p>
        </div>

        <!-- Kartu 1: Informasi Akun (nama & email) -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight">{{ LokerLensCopy::PROFIL_INFO_JUDUL }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ LokerLensCopy::PROFIL_INFO_DESKRIPSI }}</p>
            </div>

            @if (session('status') === 'profile-updated')
                <div class="mb-5 px-4 py-3 rounded-lg bg-teal-50 border border-teal-200 text-teal-800 text-sm font-medium">
                    {{ LokerLensCopy::PROFIL_TERSIMPAN }}
                </div>
            @endif

            {{-- novalidate: validasi oleh server supaya pesan bahasa Indonesia tampil di bawah isian --}}
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5" novalidate>
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                        {{ LokerLensCopy::PROFIL_LABEL_NAMA }} <span class="text-risk-high">*</span>
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name"
                        class="{{ $kelasIsian }} {{ $errors->has('name') ? 'border-risk-high' : 'border-gray-300' }}" />
                    @error('name')
                        <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                        {{ LokerLensCopy::PROFIL_LABEL_EMAIL }} <span class="text-risk-high">*</span>
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                        class="{{ $kelasIsian }} {{ $errors->has('email') ? 'border-risk-high' : 'border-gray-300' }}" />
                    @error('email')
                        <p class="text-xs text-risk-high mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Peran hanya ditampilkan, tidak bisa diubah sendiri -->
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">{{ LokerLensCopy::PROFIL_LABEL_PERAN }}</span>
                    <span class="inline-block px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">{{ $user->role }}</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold shadow transition">
                        {{ LokerLensCopy::PROFIL_BTN_SIMPAN }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Kartu 2: Ubah Kata Sandi (error bag "updatePassword" dari PasswordController) -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight">{{ LokerLensCopy::PROFIL_SANDI_JUDUL }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ LokerLensCopy::PROFIL_SANDI_DESKRIPSI }}</p>
            </div>

            @if (session('status') === 'password-updated')
                <div class="mb-5 px-4 py-3 rounded-lg bg-teal-50 border border-teal-200 text-teal-800 text-sm font-medium">
                    {{ LokerLensCopy::PROFIL_SANDI_TERSIMPAN }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
                @csrf
                @method('put')

                @foreach ([
                    ['current_password', LokerLensCopy::PROFIL_LABEL_SANDI_LAMA, 'current-password'],
                    ['password', LokerLensCopy::PROFIL_LABEL_SANDI_BARU, 'new-password'],
                    ['password_confirmation', LokerLensCopy::PROFIL_LABEL_SANDI_ULANG, 'new-password'],
                ] as [$nama, $label, $autocomplete])
                    <div>
                        <label for="update_password_{{ $nama }}" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1">
                            {{ $label }} <span class="text-risk-high">*</span>
                        </label>
                        <input id="update_password_{{ $nama }}" name="{{ $nama }}" type="password" required autocomplete="{{ $autocomplete }}"
                            class="{{ $kelasIsian }} {{ $errors->updatePassword->has($nama) ? 'border-risk-high' : 'border-gray-300' }}" />
                        @foreach ($errors->updatePassword->get($nama) as $pesan)
                            <p class="text-xs text-risk-high mt-1">{{ $pesan }}</p>
                        @endforeach
                    </div>
                @endforeach

                <div class="pt-2">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold shadow transition">
                        {{ LokerLensCopy::PROFIL_BTN_SIMPAN_SANDI }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
