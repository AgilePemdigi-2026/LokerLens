<x-app-layout>
    <div class="space-y-10 py-2">
        <!-- 1. Hero Section (Gambar 2.4 - Bagian 7.1) -->
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-4 max-w-xl z-10">
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400">
                    AJUKAN LAPORAN PENIPUAN KERJA
                </p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight">
                    Waspadai lowongan mencurigakan, <span class="font-serif italic text-teal-300">lindungi langkah kariermu.</span>
                </h1>
                <p class="text-sm text-gray-300 leading-relaxed pt-1">
                    Kenali tanda bahaya pemungutan biaya rekrutmen ilegal, verifikasi legalitas perusahaan secara cerdas, dan laporkan langsung ke dinas ketenagakerjaan.
                </p>

                <!-- Dua Tombol Pintasan (Bagian 7.1) -->
                <div class="flex flex-wrap items-center gap-3 pt-3">
                    <a href="{{ route('periksa-lowongan.index') }}"
                       class="px-6 py-3 rounded-control bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition flex items-center space-x-2">
                        <span>Ajukan Pengaduan</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                    <a href="#pengaduan-terbaru"
                       class="px-6 py-3 rounded-control bg-navy-800 hover:bg-navy-700 text-gray-200 font-semibold text-xs transition border border-navy-700">
                        Cek Status Pengaduan
                    </a>
                </div>
            </div>

            <!-- Kartu Kecil Visual Kanan -->
            <div class="bg-navy-800/90 border border-navy-700 p-6 rounded-xl text-center max-w-xs w-full shadow-inner z-10">
                <div class="w-12 h-12 rounded-xl bg-teal-600/30 text-teal-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h3 class="text-base font-bold text-white">Kenali tanda curiganya.</h3>
                <p class="text-xs text-gray-400 mt-1 leading-relaxed">
                    Laporkan dengan percaya diri dan kawal penanganannya bersama tim pengawas ketenagakerjaan resmi.
                </p>
            </div>
        </div>

        <!-- 2. Layanan Utama (Bagian 7.1) -->
        <div>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-white tracking-tight">
                    Satu tempat untuk melapor, memantau, dan meninjau.
                </h2>
                <p class="text-xs sm:text-sm text-gray-400 mt-1">
                    Akses terpadu perlindungan informasi ketenagakerjaan digital.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Kartu 01: Ajukan Pengaduan -->
                <div class="bg-white rounded-card shadow-lg p-6 border border-gray-100 flex flex-col justify-between hover:shadow-xl transition">
                    <div>
                        <span class="text-xs font-mono font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded">01</span>
                        <h3 class="text-lg font-bold text-navy-900 mt-3">Ajukan Pengaduan</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">
                            Buat laporan baru untuk memeriksa keaslian lowongan kerja serta mendeteksi potensi indikasi penipuan.
                        </p>
                    </div>
                    <a href="{{ route('periksa-lowongan.index') }}" class="mt-6 inline-flex items-center text-xs font-bold text-teal-700 hover:text-teal-800">
                        <span>Periksa Sekarang</span>
                        <span class="ml-1">→</span>
                    </a>
                </div>

                <!-- Kartu 02: Status Pengaduan -->
                <div class="bg-white rounded-card shadow-lg p-6 border border-gray-100 flex flex-col justify-between hover:shadow-xl transition">
                    <div>
                        <span class="text-xs font-mono font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded">02</span>
                        <h3 class="text-lg font-bold text-navy-900 mt-3">Status Pengaduan</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">
                            Memantau perkembangan laporan dan tindakan yang sedang diambil oleh analis dan dinas terkait.
                        </p>
                    </div>
                    <a href="#pengaduan-terbaru" class="mt-6 inline-flex items-center text-xs font-bold text-teal-700 hover:text-teal-800">
                        <span>Lihat Status</span>
                        <span class="ml-1">→</span>
                    </a>
                </div>

                <!-- Kartu 03: Riwayat Pengaduan -->
                <div class="bg-white rounded-card shadow-lg p-6 border border-gray-100 flex flex-col justify-between hover:shadow-xl transition">
                    <div>
                        <span class="text-xs font-mono font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded">03</span>
                        <h3 class="text-lg font-bold text-navy-900 mt-3">Riwayat Pengaduan</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">
                            Melihat kembali rekam jejak laporan yang pernah Anda ajukan dan hasil verifikasi dokumen resminya.
                        </p>
                    </div>
                    <a href="#pengaduan-terbaru" class="mt-6 inline-flex items-center text-xs font-bold text-teal-700 hover:text-teal-800">
                        <span>Buka Riwayat</span>
                        <span class="ml-1">→</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Pengaduan Terbaru Anda (Teks Wajib jika kosong: "Belum ada pengaduan yang diajukan") -->
        <div id="pengaduan-terbaru" class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">
                        Pengaduan Terbaru Anda
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pantau status penanganan kasus yang Anda ajukan.
                    </p>
                </div>
                <a href="{{ route('periksa-lowongan.index') }}" class="text-xs font-bold text-teal-700 hover:underline">
                    + Ajukan Pengaduan Baru
                </a>
            </div>

            @if($pengaduanTerbaru->isEmpty())
                <!-- Teks Wajib BDD & Panduan Bagian 7.1: "Belum ada pengaduan yang diajukan" -->
                <div class="py-12 text-center text-gray-500">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-base font-bold text-navy-900">
                        Belum ada pengaduan yang diajukan
                    </p>
                    <p class="text-xs text-gray-500 mt-1 mb-5">
                        Anda belum memiliki riwayat pemeriksaan atau laporan lowongan kerja mencurigakan.
                    </p>
                    <a href="{{ route('periksa-lowongan.index') }}"
                       class="inline-flex items-center px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold shadow transition">
                        Ajukan Pengaduan
                    </a>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($pengaduanTerbaru as $item)
                        @php
                            $low = $item->lowonganKerja;
                            $per = $low->perusahaan ?? null;
                            $klas = $item->hasilKlasifikasi;
                        @endphp
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/60 px-3 rounded-lg transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                        {{ $item->nomor_tiket }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ $item->tanggal_pengaduan->translatedFormat('d M Y, H:i') }} WIB
                                    </span>
                                </div>
                                <h4 class="text-sm font-bold text-navy-900">
                                    {{ $low->posisi_lowongan ?? '-' }} • <span class="font-normal text-gray-600">{{ $per->nama_perusahaan ?? '-' }}</span>
                                </h4>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $item->warna_badge ?? 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $item->status_pengaduan }}
                                </span>
                                <a href="{{ route('periksa-lowongan.hasil', $item->id_pengaduan) }}" class="text-xs font-bold text-teal-700 hover:underline">
                                    Hasil Analisis →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 4. Empat Tahap Penanganan (Bagian 7.1) -->
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-8 sm:p-10 text-white shadow-xl">
            <div class="mb-8">
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-1">
                    ALUR PROSES PENGADUAN
                </p>
                <h2 class="text-2xl font-bold tracking-tight">
                    Dari laporan masuk hingga hasil penanganan.
                </h2>
                <p class="text-xs text-gray-400 mt-1">Setiap laporan memiliki rekam jejak digital yang transparan dan dapat dipertanggungjawabkan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-navy-800/60 p-4 rounded-xl border border-navy-700">
                    <span class="text-xs font-mono font-bold text-teal-400 block mb-2">01</span>
                    <h4 class="text-sm font-bold text-white">Ajukan Pengaduan</h4>
                    <p class="text-xs text-gray-400 mt-1 leading-relaxed">Masyarakat mengisi informasi lowongan dan mengunggah bukti pendukung.</p>
                </div>
                <div class="bg-navy-800/60 p-4 rounded-xl border border-navy-700">
                    <span class="text-xs font-mono font-bold text-teal-400 block mb-2">02</span>
                    <h4 class="text-sm font-bold text-white">Pemeriksaan Laporan</h4>
                    <p class="text-xs text-gray-400 mt-1 leading-relaxed">Sistem AI memindai anomali dan analis melakukan audit investigasi manual.</p>
                </div>
                <div class="bg-navy-800/60 p-4 rounded-xl border border-navy-700">
                    <span class="text-xs font-mono font-bold text-teal-400 block mb-2">03</span>
                    <h4 class="text-sm font-bold text-white">Pemantauan Status</h4>
                    <p class="text-xs text-gray-400 mt-1 leading-relaxed">Pelapor dapat memantau posisi alur kasus secara real-time di sistem.</p>
                </div>
                <div class="bg-navy-800/60 p-4 rounded-xl border border-navy-700">
                    <span class="text-xs font-mono font-bold text-teal-400 block mb-2">04</span>
                    <h4 class="text-sm font-bold text-white">Hasil Penanganan</h4>
                    <p class="text-xs text-gray-400 mt-1 leading-relaxed">Kepala Bidang menetapkan disposisi atau menerbitkan laporan polisi resmi.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
