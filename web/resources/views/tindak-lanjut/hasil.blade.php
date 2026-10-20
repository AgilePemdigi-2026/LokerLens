<x-app-layout>
    <div class="max-w-3xl mx-auto py-8">
        @php
            $tindakLanjut = $pengaduan->tindakLanjutKasus;
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $isTindaklanjuti = $tindakLanjut && $tindakLanjut->keputusan === 'Tindaklanjuti';
        @endphp

        @if($isTindaklanjuti)
            <!-- A. Kasus Disetujui untuk Tindaklanjuti (Gambar 2.13) -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-10 text-navy-900 border border-gray-100 animate-fadeIn">
                <!-- Icon Sukses Hijau -->
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-ok flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <div class="text-center mb-6">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-900 tracking-tight">
                        Keputusan Berhasil Disimpan
                    </h1>
                    <p class="text-sm text-ok font-semibold mt-1">
                        Kasus berhasil ditandai untuk ditindaklanjuti.
                    </p>
                </div>

                <!-- Kartu Rincian Hasil Keputusan -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 text-xs sm:text-sm space-y-3 mb-6">
                    <div class="flex justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500">ID Laporan:</span>
                        <strong class="font-mono text-teal-800">{{ $pengaduan->nomor_tiket }}</strong>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500">Posisi Pekerjaan:</span>
                        <strong class="text-navy-900">{{ $lowongan->posisi_lowongan }} ({{ $perusahaan->nama_perusahaan ?? '-' }})</strong>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500">Keputusan yang Dipilih:</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800">
                            Tindaklanjuti
                        </span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500">Tanggal Keputusan:</span>
                        <span class="font-medium text-gray-800">{{ $tindakLanjut->tanggal_keputusan->translatedFormat('d F Y, H:i') }} WIB</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500">Status Kasus Terbaru:</span>
                        <strong class="text-ok font-bold">{{ $pengaduan->status_pengaduan }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Riwayat Kasus:</span>
                        <span class="text-gray-700 italic">Perubahan status berhasil dicatat</span>
                    </div>
                </div>

                <!-- Tombol Navigasi: Kembali ke Daftar Laporan & Lanjut ke Pembuatan Surat (Bagian 6.5.4 & 9) -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                    <a href="{{ route('tindak-lanjut.index') }}"
                       class="w-full sm:w-auto px-6 py-2.5 rounded-control border border-gray-300 text-gray-700 hover:bg-gray-100 text-xs font-semibold transition text-center">
                        Kembali ke Daftar Laporan
                    </a>

                    <a href="{{ route('buat-surat.index', $pengaduan->id_pengaduan) }}"
                       class="w-full sm:w-auto px-7 py-2.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold shadow-lg transition flex items-center justify-center space-x-2">
                        <span>Lanjut ke Pembuatan Surat</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        @else
            <!-- B. Kasus Tidak Ditindaklanjuti (FR-TL-05, BR-03) -->
            <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-12 text-navy-900 border border-gray-100 text-center animate-fadeIn">
                <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>

                <!-- Teks Wajib BR-03 & FR-TL-05: Kasus ditandai tidak dilanjuti dan diarsipkan -->
                <h1 class="text-xl sm:text-2xl font-bold text-navy-900 tracking-tight">
                    Kasus ditandai tidak dilanjuti dan diarsipkan
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-2 max-w-md mx-auto">
                    Laporan tiket <strong>{{ $pengaduan->nomor_tiket }}</strong> telah selesai diproses dan disimpan ke dalam arsip pengawasan ketenagakerjaan.
                </p>

                <div class="mt-8">
                    <a href="{{ route('tindak-lanjut.index') }}"
                       class="inline-flex items-center px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold shadow transition">
                        Kembali ke Daftar Laporan
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
