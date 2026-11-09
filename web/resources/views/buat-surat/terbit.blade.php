<x-app-layout>
    <div class="max-w-4xl mx-auto py-8">
        @php
            $lowongan = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan->perusahaan ?? null;
            $tindakLanjut = $pengaduan->tindakLanjutKasus;
        @endphp

        <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-10 text-navy-900 border border-gray-100 animate-fadeIn space-y-8">
            <!-- Icon Sukses Besar -->
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-risk-low flex items-center justify-center mx-auto mb-4 shadow">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <!-- Teks Wajib BDD: "Laporan Polisi Berhasil Diterbitkan" -->
                <h1 class="text-2xl sm:text-3xl font-extrabold text-navy-900 tracking-tight">
                    Laporan Polisi Berhasil Diterbitkan
                </h1>
                <p class="text-xs sm:text-sm text-gray-600 mt-2 max-w-xl mx-auto leading-relaxed">
                    Dokumen laporan resmi dan seluruh berkas bukti digital telah berhasil disahkan serta siap diteruskan ke instansi kepolisian tujuan.
                </p>

                <!-- Tiga Chip Resmi (BR-10 & FR-ST-08) -->
                <div class="flex flex-wrap items-center justify-center gap-2 mt-4">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-risk-low border border-emerald-200">
                        Kasus terdokumentasi otomatis
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-risk-low border border-emerald-200">
                        Kasus selesai
                    </span>
                </div>
            </div>

            <!-- Ringkasan Dokumen Laporan (Gambar 2.16) -->
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4 text-xs sm:text-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-teal-800 border-b border-gray-200 pb-2">
                    Ringkasan Dokumen Laporan
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-500 block text-xs">Nomor Dokumen Resmi:</span>
                        <strong class="font-mono text-base text-navy-900 font-extrabold">{{ $surat->nomor_surat }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Nomor Tiket Kasus:</span>
                        <span class="font-mono font-bold text-teal-700 text-sm">{{ $pengaduan->nomor_tiket }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Tanggal & Waktu Terbit:</span>
                        <span class="font-medium text-gray-800">{{ $surat->tanggal_terbit ? $surat->tanggal_terbit->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Nama Perusahaan Terlapor:</span>
                        <strong class="text-navy-900">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Instansi Kepolisian Tujuan:</span>
                        <span class="font-medium text-gray-800">{{ $surat->tujuan_instansi }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Pejabat Penanda Tangan:</span>
                        {{-- Kepala Bidang yang menetapkan keputusan Tindaklanjuti (tabel tindak_lanjut_kasus) --}}
                        <strong class="text-navy-900">{{ $tindakLanjut?->kepalaBidang?->name ? $tindakLanjut->kepalaBidang->name . ' (Kepala Bidang)' : '-' }}</strong>
                    </div>
                </div>

                <!-- Detail Pelanggaran: pasal yang dipilih saat membuat laporan (kolom pasal_dilanggar) -->
                <div class="pt-3 border-t border-gray-200">
                    <span class="text-xs font-semibold text-gray-600 block mb-2">Detail Pelanggaran:</span>
                    @php $daftarPasal = \App\Models\SuratTeguranLaporanPolisi::DAFTAR_PASAL; @endphp
                    @if(! empty($surat->pasal_dilanggar))
                        <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-gray-800">
                            @foreach($surat->pasal_dilanggar as $kode)
                                <li>{{ $daftarPasal[$kode]['judul'] ?? $kode }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-gray-500">-</p>
                    @endif
                </div>

                <!-- Berkas Bukti Lampiran -->
                <div class="pt-3 border-t border-gray-200">
                    <span class="text-xs font-semibold text-gray-600 block mb-2">Berkas Bukti Digital yang Terverifikasi:</span>
                                       <div class="space-y-1.5">
                        @forelse($pengaduan->buktiPendukung as $bukti)
                            <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200 text-xs">
                                <span class="font-mono text-gray-700">{{ $bukti->nama_file }}</span>
                                {{-- Jenis & ukuran berkas dari tabel bukti_pendukung --}}
                                <span class="text-[11px] text-gray-500 font-mono">{{ strtoupper($bukti->tipe_file) }} • {{ number_format($bukti->ukuran_file / 1048576, 1) }} MB</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">Belum ada berkas bukti.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-teal-50/70 p-3 rounded-lg border border-teal-200 text-xs text-teal-900 flex items-center justify-between">
                    <span>Kasus ini telah dipindahkan secara otomatis ke dalam <strong>Arsip Kasus</strong> Disnaker.</span>
                    <span class="text-teal-700 font-bold">Arsip Tersimpan</span>
                </div>
            </div>

            <!-- Tombol Aksi: Cetak Tanda Terima, Kembali ke Laporan, Lihat Arsip Kasus (Bagian 6.6 & 9) -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                <button type="button" onclick="window.print()"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 text-xs font-semibold shadow transition flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Tanda Terima</span>
                </button>

                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <a href="{{ route('tindak-lanjut.index') }}"
                       class="w-full sm:w-auto px-5 py-2.5 rounded-control border border-gray-300 text-gray-700 hover:bg-gray-100 text-xs font-semibold transition text-center">
                        Kembali ke Laporan
                    </a>

                    <a href="{{ route('tindak-lanjut.index') }}"
                       class="w-full sm:w-auto px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold shadow-lg transition flex items-center justify-center space-x-1.5">
                        <span>Lihat Arsip Kasus</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
